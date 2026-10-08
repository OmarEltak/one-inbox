# OT1-Pro Production Limits & Capacity Playbook

**Last measured**: 2026-09-28 (hardware / FPM / queue baselines) · last reviewed 2026-10-08
**Prod host**: `187.77.67.94` (`https://ot1-pro.com`)
**Purpose**: Single source of truth for what this box can handle, where the breaking points are, and when to alert / scale.

Anytime you change the hardware, add a service, tune PHP-FPM/MySQL/nginx, or run a load test, **update this file in the same commit**. Numbers that go stale are worse than no numbers.

---

## 1 · Hardware & OS

| | Value |
|---|---|
| CPU | AMD EPYC 9354P — **2 vCPU** @ 2.0 GHz (1 thread/core) |
| RAM | **7.8 GB** total, ~1.6 GB in use at idle, no swap |
| Disk | 96 GB SSD, ~9% used |
| OS | Ubuntu 24.04.4 LTS |
| Provider | Hetzner CX32 (verify) |

**Ceiling insight**: 2 vCPU is the tightest constraint. Anything CPU-bound (Whisper transcription, image resize, PHP compilation) competes with everything else. Doubling to 4 vCPU is the single most impactful upgrade.

---

## 2 · Installed services (running, per systemctl)

| Service | Role | Notes |
|---|---|---|
| `nginx` | HTTP front | `worker_processes auto` (2), `worker_connections 768` |
| `php8.4-fpm` | PHP app | pool: **`pm=dynamic`, `max_children=5`** — see §3 |
| `mysql` (8.0) | Primary DB | `one_inbox` schema |
| `redis-server` | Queues + cache + session | Backing store for horizon-style queue processing |
| `docker` + `containerd` | Wuzapi WhatsApp gateway container | Bound to `127.0.0.1:8082` (see prod-ops runbook) |
| `one-inbox-queue.service` | Main queue worker | Consumes `urgent, default, heavy-analysis, comments-ingest, comments-send` in that priority order (verified 2026-10-08). `heavy-analysis` was added 2026-10-08 after a user-reported 20-hour stuck Deep Analysis — the queue was created with the AI credit economy spec but the worker config was never updated. **The systemd unit file is NOT in git** — see §9 open verification. |
| `one-inbox-queue@1..4` | 4× worker instances | Consume `urgent, default` ONLY — do NOT process comment queues (verified 2026-09-29) |
| `one-inbox-queue-campaigns.service` | Dedicated campaigns worker | Consumes `campaigns` only. `--max-jobs=1000 --timeout=60` (verified 2026-09-29) |
| `one-inbox-whisper.service` | Transcription queue worker | Consumes `transcription` only. `--tries=1 --timeout=90`. This is a Laravel queue worker (misnamed "whisper" — the actual whisper.cpp HTTP daemon is separate). Verified 2026-09-29 |
| `one-inbox-reverb.service` | Reverb WebSocket | Port 8080 — live inbox updates |
| `ffmpeg` (7.x) | Audio conversion | Used by `ConvertAudioToOgg` job |

---

## 3 · The tightest bottleneck: PHP-FPM `max_children = 5`

```ini
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
```

**Meaning**: at most **5 concurrent HTTP requests can execute in PHP simultaneously**. Request #6 queues behind them in nginx. Under load this is the single-most-visible failure mode — pages hang for 5-30 s, then either respond or 504.

**Napkin math for load**:
- Each dashboard page ≈ 200 ms → 5 workers × 5 req/sec/worker = **25 req/sec sustained** ceiling
- Each webhook ingest ≈ 50 ms → theoretical **100 req/sec** ceiling
- Any single slow request (>2 s DB query, hung external API) burns a worker for its whole duration

**Alert threshold**: `active FPM children ≥ 4 for > 30 s` → send Omar an email.

**Upgrade path**: bump to `max_children = 20` once we go to 4 vCPU + 16 GB — each PHP worker ~40 MB, so 20 workers ≈ 800 MB.

---

## 4 · Queue topology

The app uses **7 named queues** on Redis. Workers are dedicated per role so a bulk-send storm can't starve an inbound message.

| Queue | Purpose | Workers | Failure impact if backed up |
|---|---|---|---|
| `urgent` | Inbound messages, AI responses, platform sends | **5 workers** (`one-inbox-queue` + `@1..4`) | **Customers see silence** — real messages don't get replied to |
| `transcription` | Voice-note transcription (Groq primary, whisper.cpp fallback) | **1 worker** (`one-inbox-whisper.service`) | Voice notes fail to become text; AI can't respond to them |
| `campaigns` | Bulk WhatsApp/email per-recipient sends | **1 worker** (`one-inbox-queue-campaigns.service`) | Campaigns run slowly; inbound unaffected |
| `comments-ingest` | Meta comment webhooks classification | **1 worker** (main service only — `@1..4` do NOT process) | AI comment replies delayed |
| `comments-send` | Publishing AI comment replies | **1 worker** (same) | Same |
| `heavy-analysis` | Deep Analysis jobs (`DispatchDeepAnalysisJob`) + any async paid-cohort work | **1 worker** (main service only — added 2026-10-08) | Operator Deep Analyses stall; `/ai-chat` idempotency lock keeps the user on the "Analysis in progress" banner until the job runs or fails. |
| `default` | `ConvertAudioToOgg`, `DescribeImage`, `ScoreLeadJob`, `ProcessAiChatTurn` (admin chat async turn — added 2026-10-08), misc | **5 workers** (`one-inbox-queue` + `@1..4`) | Lead scoring + voice/image previews delayed; admin chat replies stuck in "typing dots" placeholder. |

**Per-team throttles**:

- `TranscribeAudio` — `transcribe:inflight:{team_id}` capped at **5** in-flight per team. 6th job releases back with a 3-second delay. **This prevents one user's 100-voice-note burst from monopolizing transcription.** ✓
- `SendCampaignWhatsAppJob` — ~~NO per-team throttle~~ **SHIPPED 2026-09-29 (Phase 4, PR #64)** — capped at 3 concurrent sends per team via `Cache::add("campaign:send:inflight:{team_id}", 1, 60)` + `try/finally` symmetric decrement. Between-team fairness restored; within-team throughput unchanged (3 concurrent × ~1-5 msg/sec WhatsApp cap ≈ same effective rate as pre-throttle).
- `ScoreLeadJob` — no throttle. Dispatched from **11 call sites** in `ProcessIncomingMessage` — every inbound message-with-contact spawns one. Uses `default` queue with `$timeout = 300` (per-file comment references a real 2026-08-25 queue-cascade incident where the 60 s default caused SIGKILL cascading stalls). Each score = 1 NaraRouter call → an inbound burst = ScoreLead burst = NaraRouter cooldown risk. Under a big inbound spike, `default` queue backs up.

**Alert threshold**:
- `queues:urgent` depth ≥ 50 for > 60 s → real customer messages piling up
- `queues:transcription` depth ≥ 20 for > 5 min → Whisper capacity saturated
- `queues:campaigns` depth ≥ 500 → normal (bulk sends), > 5,000 = worth flagging

---

## 5 · Feature-by-feature capacity

The number in **bold** is what we *think* is the ceiling; unknown = we haven't measured. Load tests will fill these in.

### 5.1 · Inbound message handling (Meta webhook → AI reply)

| Metric | Value |
|---|---|
| Webhook ingest rate (HMAC + DB write) | **≥ 30 req/s** (theoretical, based on 50 ms/req × 5 FPM workers) |
| End-to-end message → AI reply latency | 3-15 s (dominated by NaraRouter call) |
| AI call rate (via NaraRouter, all providers combined) | **~10 calls/sec** before hitting a provider's per-minute quota → cooldown kicks in (see ARCHITECTURE §4) |
| Concurrent AI dispatches per team | Unlimited at code level — bounded only by `Team::canDispatchAi()` gates |

**Combined pain point (Omar's scenario A — "10 users × 10 voice notes each")**:
- 100 voice notes hit `TranscribeAudio` at once
- Per-team cap allows 5 per team concurrent → **max 50 in-flight** across 10 teams
- Whisper on 2 vCPU: realistically ~1 note/sec (verify with load test)
- 100 notes / 1 = **100 seconds to drain the queue** — acceptable, but the queue depth alert would fire

### 5.2 · Voice notes (audio → text → AI reply)

**Driver order** (verified in `AppServiceProvider@register`): **Groq is PRIMARY, whisper.cpp is FALLBACK.** `TranscriptionRouter` tries drivers in order until one succeeds. `CircuitBreaker` cools each driver individually on `RateLimitedException` — so if Groq hits its rate limit, the router transparently switches to local whisper.cpp for that call, and Groq is re-tried after its cool-down.

| Metric | Value |
|---|---|
| ffmpeg conversion (any → OGG) | ~500 ms per 60 s audio |
| **Groq (primary)** transcription | **~1 s per minute of audio** (network round-trip dominates) |
| **whisper.cpp (fallback)** on 2 vCPU | ~30-60 s per minute of audio (needs empirical verify) |
| Concurrent transcription (Groq) | Bounded by Groq rate limit + our per-team cap |
| Per-team throttle | 5 in-flight (enforced by `TranscribeAudio` job) ✓ |
| Fallback trigger | Groq `RateLimitedException` → circuit breaker cools Groq → next job uses whisper.cpp |

**Practical implication**: Whisper's CPU cost only bites during Groq outage or quota exhaustion. In steady state you're paying pennies to Groq for ~1 s transcriptions. Local Whisper is insurance so voice notes never fail hard. **CPU contention on this box is a fallback-only concern, not a normal-day concern.**

### 5.3 · Bulk WhatsApp campaigns

⚠️ ~~**CONFIRMED LANDMINE — 2026-09-28.**~~ **RESOLVED for WhatsApp wizard 2026-09-29 (PR #63)** — parse now runs in the async `ImportCampaignRecipients` job. Email wizard (`EmailWizard@confirmMapAndImport`) still sync as of 2026-09-29 — deferred as Phase 3b.

`app/Livewire/Campaigns/WhatsAppWizard.php:155-170` — `advanceToCompose()`:
```php
$rows = iterator_to_array($parser->stream());   // loads entire file into memory
$importer->import(rows: $rows, ...);            // inserts every row synchronously
```

| Metric | Value |
|---|---|
| Max upload size (Livewire validation) | `max:2048` = **2 MB** (was 10 MB pre-mitigation) |
| 2 MB Excel row count | ~20k rows |
| Parse + insert time (single user, 2 MB) | ~2-10 s (unverified precisely — needs load test) |
| PHP-FPM worker held during parse | YES — bounded by `set_time_limit(90)` guard |
| Memory during parse | Capped at 256 MB by `ini_set('memory_limit', '256M')` guard |
| Per-recipient send rate (after parse) | 1-5 msg/sec per WA number (WhatsApp policy) |

**Combined pain point (Omar's scenario B — "10 users upload 2 MB Excel + run campaigns simultaneously")**:
- Each parse now takes 2-10 s instead of 10-60 s → **6-30× less worker-hold time**
- Still bounded by 5 FPM workers — under 10 concurrent uploads, 5 users get 504 during the parse burst
- Real full fix: async `ImportCampaignRecipients` job (still TODO)
- Sends themselves (post-parse) run on isolated `campaigns` queue → those DO NOT starve inbound `urgent` ✓

**Fix path (must-do before scaling)**:
1. Convert `advanceToCompose()` to enqueue an `ImportCampaignRecipients` job
2. Return immediately with an `import_id` and poll for progress via Livewire
3. Show a progress bar ("Imported 12,384 / 98,201 contacts")
4. Only unlock the "Compose message" step once import is complete

**Mitigations shipped 2026-09-29** (`fix/campaigns-import-mitigation`):
- `max:10240` → `max:2048` in both `WhatsAppWizard@advanceToMap` and `EmailWizard@uploadAndPreview`
- `ini_set('memory_limit', '256M')` + `set_time_limit(90)` guards inside both parse methods
- Amber contrast-safe notice on both wizards' upload step (EN + AR translations)
- Per-form custom validation message tells users the limit and the `support@ot1-pro.com` fallback in one line
- Bottleneck: 10 users × 100k = **1M messages queued**. Even at 5 msg/sec per WA number × 10 numbers = 50 msg/sec = **20,000 seconds ≈ 5.5 hours to drain**
- Real limit is not our server — it's WhatsApp's per-number rate

### 5.4 · AI lead scoring

| Metric | Value |
|---|---|
| Trigger | **Every inbound message with a linked contact** — dispatched at 11 sites in `ProcessIncomingMessage` (verified 2026-09-29) |
| Queue | `default` (no `onQueue()` call in `ScoreLeadJob`) |
| Workers | 5 (shares with `urgent` on the main + `@1..4` instances) |
| `$tries` / `$timeout` | 2 / **300 s** (comment cites 2026-08-25 queue-cascade incident — the 60 s default caused SIGKILL and cascading stalls) |
| Cost per score | 1 NaraRouter call (`scoreMessage()` — text chain) |
| AI ceiling | Same as AI reply rate — ~10 calls/sec before NaraRouter provider cooldown |
| Failure mode | Exception → logged as warning, no retry loop (returns silently) |

**Combined-load implication**: every inbound message = 1 AI reply call (urgent queue) + 1 ScoreLead call (default queue). A burst of N inbound messages produces **2N NaraRouter calls**. Under a 50/sec inbound burst that's 100 calls/sec → NaraRouter cooldown almost certain. Both queues will drain slowly (5-min per-job timeout on ScoreLead means each stuck score holds a `default` worker for 5 min).

### 5.5 · Analytics dashboards (`/analytics`, `/super-admin/analytics`)

| Metric | Value |
|---|---|
| Query cost | Ranges from cheap (COUNT on indexed cols) to expensive (aggregates on `webhook_logs` — that table is HUGE) |
| Concurrent viewers we've tested | Unknown |
| Cache TTL | Unknown |

**Known risk**: `webhook_logs` "Out of sort memory" error already shipped once (memory `feedback_prod_out_of_sort_memory`). If analytics queries hit that table without proper LIMIT + indexed WHERE, they can crash MySQL sort buffer under any concurrent access.

### 5.6 · Reverb WebSocket (live inbox)

| Metric | Value |
|---|---|
| Concurrent connections | Bounded by port 8080 fd limits (default 1024 per Unix worker) |
| Load test needed | Yes — spin up 500 fake connections, measure memory |

### 5.7 · Concurrent-user ceilings (best current estimates)

Numbers derived from the per-feature data above. Anything marked **(theory)** hasn't been load-tested yet; **(measured)** is empirically verified.

**Per-feature simultaneous-user ceilings**:

| Feature | Comfortable | Hard ceiling | Root constraint |
|---|---|---|---|
| Browsing dashboards / settings / inbox UI | ~50-100 signed-in with normal think-time | **5 truly-simultaneous HTTP requests** | PHP-FPM `max_children = 5` |
| Landing on homepage (marketing) | ~25 fresh visits/sec | ~100/sec burst before 504 (theory) | 200ms/req × 5 FPM |
| Sending a message from inbox | 5 users clicking Send at once | Same 5 | Each send holds a FPM worker |
| Inbound webhook ingest | ~30 msg/sec sustained | ~100/sec burst (theory) | 50ms HMAC-verify + insert × 5 FPM |
| AI replies to inbound | 5-10 simultaneous | **10 calls/sec** before NaraRouter cooldown | 5 `urgent` workers + provider quota |
| AI lead scoring | Shares AI reply budget | 10 calls/sec — but doubles the AI rate (every inbound = 1 reply + 1 score) | 5 `default` workers, 1 NaraRouter call each |
| Voice notes (Groq path) | ~50 in-flight globally (5/team × 10 teams) | Groq API rate limit | Per-team cap + Groq quota |
| Voice notes (Whisper fallback) | 1-2 concurrent (theory) | 2 | 2 vCPU, Whisper CPU-bound |
| Bulk WhatsApp campaigns | 1 team fast, others queue behind | **1-5 msg/sec per WA number** (WhatsApp policy) | 1 `campaigns` worker + WA gateway |
| Excel upload (contacts import) | 5 users uploading 2 MB simultaneously | Same 5 (FPM cap, held ≤90s by guards) | FPM + sync parse |
| Comment automation (FB/IG) | ~1 comment/sec end-to-end | Same | Only 1 worker on each comment queue |
| Analytics dashboards | ~5-10 concurrent viewers (theory) | Blocked first by MySQL sort-memory on `webhook_logs` aggregates | FPM + MySQL |
| Live inbox updates (Reverb WS) | ~500-1000 idle connected users (theory) | 1024 fd default | Reverb port 8080 |

**Total-user rollups** (what "N users at the same time" really means):

| Interpretation | Users |
|---|---|
| Actively hammering the site, no think-time | ~5-25 |
| Signed-in with normal usage (click every 5-10s) | **~50-100** |
| Passively logged in with dashboard open, receiving Reverb pushes | ~500-1000 (Reverb ceiling, untested) |
| Actively receiving inbound messages that trigger AI replies | ~30 msg/sec across all customers → **~5-10 customers actively conversing at once** |
| Running bulk campaigns at the same time | 1 gets fast throughput; others wait |

**Where ceilings break first** (in order):

1. **PHP-FPM = 5** — loudest failure mode. >5 heavy concurrent requests → others see 504/lag.
2. **NaraRouter provider cooldown** — 10 AI calls/sec practical ceiling. Every inbound = 2 AI calls → cooldown at ~5 inbound/sec sustained across all customers.
3. **Single-worker queues** (campaigns, comments-ingest, comments-send, transcription) — no per-team fairness, first user monopolizes.
4. **MySQL sort-memory** on `webhook_logs` — already shipped once; can re-blow on concurrent analytics.

**Growth triggers** (when to act):

| Signal | Action |
|---|---|
| 5+ signups/day OR active campaigns from 3+ teams | Bump FPM `max_children` 5 → 20 (10-min prod change) |
| >10 messages/sec sustained inbound | Add throttle on `ScoreLeadJob` OR make scoring conditional (every 3rd msg) |
| >2 teams running large campaigns concurrently | Add per-team throttle on `SendCampaignWhatsAppJob` mirroring `TranscribeAudio` |
| Regular comment volume from 2+ FB/IG pages | Add `comments-ingest`+`comments-send` to `one-inbox-queue@1..4` |
| >100 concurrent signed-in users | Upgrade to 4 vCPU / 16 GB |

---

## 6 · Combined-load stress scenarios to test

The load-testing suite (§8) must exercise these compound scenarios, not just isolated endpoints:

| # | Scenario | What breaks first? (hypothesis) |
|---|---|---|
| A | 10 teams × 10 voice notes simultaneously | Whisper queue backs up 100+ s; inbound `urgent` unaffected |
| B | 10 teams upload 10 MB Excel + start campaigns concurrently | Excel parse thrashes 2 vCPU; **web requests time out during parse** if parse is sync |
| C | 1 team runs a 100k-recipient campaign while other teams send inbound | Isolated `campaigns` queue means inbound survives ✓; own team's inbound may hit AI cooldown |
| D | 50 concurrent analytics page loads | PHP-FPM max_children=5 → 45 requests queue; also risk of MySQL sort-memory blow-up |
| E | Marketing campaign lands 500 visitors/sec on `/` | **FPM saturates instantly** — need CDN caching or FPM bump |
| F | AI provider outage (all NaraRouter models cool down) | Global 30-min cooldown ✓; jobs release with jitter (bounded by `$tries=2`) — verify no cascading failures |

---

## 7 · Alert plan (proposed)

A new artisan command `capacity:health-check` runs every 5 minutes via the scheduler. Emails Omar when any threshold trips (with a 60-min per-alert cool-down so we don't get spammed).

| Signal | Threshold | Email subject |
|---|---|---|
| System CPU 1-min load avg | > 1.8 for > 5 min | `[OT1] CPU saturated (X.X load)` |
| Free RAM | < 500 MB for > 2 min | `[OT1] RAM low (Xmb free)` |
| Disk free on `/` | < 5 GB | `[OT1] Disk filling up (Xg free)` |
| FPM active children | 5 out of 5 for > 30 s | `[OT1] PHP-FPM saturated` |
| `queues:urgent` depth | ≥ 50 for > 60 s | `[OT1] Inbound queue backing up` |
| `queues:transcription` depth | ≥ 20 for > 5 min | `[OT1] Whisper queue backing up` |
| `queues:campaigns` depth | ≥ 5,000 | `[OT1] Campaigns queue very deep` |
| NaraRouter cooldown active | Any (rare, so always alert) | `[OT1] AI providers all cooling down` |
| MySQL long-running query | Any > 30 s | `[OT1] Long MySQL query (X.Xs)` |

**Delivery**: Same `omareltak7@gmail.com` from-address as nudges. Send via the existing Mail infrastructure — no new secrets or services needed.

---

## 8 · Load-testing plan

Tool: **k6** (single-binary, JS scripts). Run against a **staging box** (recommend spinning up `staging.ot1-pro.com` on a Hetzner CX22 = ~$5/mo mirror) — do NOT run destructive load against prod during business hours.

Scripts to write, in priority order:

1. `scripts/load/webhook-ingest.js` — hammer `/api/webhooks/meta` with signed fake payloads, ramp 1 → 100 req/s, measure p95 latency + error rate
2. `scripts/load/voice-note-burst.js` — enqueue N `TranscribeAudio` jobs via API, watch queue drain rate + Whisper CPU
3. `scripts/load/campaign-parse-storm.js` — upload N Excel files simultaneously, measure parse latency + FPM saturation
4. `scripts/load/homepage-flood.js` — GET `/` at 500 req/s, measure FPM saturation + cache hit rate
5. `scripts/load/analytics-concurrent.js` — 50 concurrent dashboard loads
6. `scripts/load/compound-realistic.js` — combined scenario mixing all of the above at realistic ratios

Each script emits a JSON report → `docs/load-tests/YYYY-MM-DD-<scenario>.json` and a markdown summary that appends the "current known ceiling" table in §5.

---

## 9 · Open verifications (things this doc claims that need proof)

- [x] ~~Which queues do `one-inbox-queue.service` and `@1..4` actually consume?~~ **main = urgent+default+comments-ingest+comments-send; @1..4 = urgent+default only (comment queues have only 1 worker)** — documented in §2 + §4 (2026-09-29)
- [x] ~~Is Excel campaign parsing sync or queued?~~ **CONFIRMED SYNC — landmine documented in §5.3** (2026-09-28); mitigation shipped 2026-09-29
- [x] ~~Per-team throttle on `SendCampaignWhatsAppJob`?~~ **NO throttle exists — documented in §4 with future-work note** (2026-09-29)
- [x] ~~`ScoreLeadJob` trigger + queue name~~ **dispatched from every inbound-message-with-contact (11 sites in `ProcessIncomingMessage`); runs on `default`; timeout 300s; 2 tries** — documented in §5.4 (2026-09-29)
- [ ] Actual whisper.cpp transcription latency on this hardware — measure with a 30-second audio file
- [ ] `client_max_body_size` — what's the actual nginx-side limit for Excel uploads?
- [ ] Reverb concurrent connection ceiling under real message load
- [x] ~~Transcription driver order~~ **Groq primary, whisper.cpp fallback — verified in `AppServiceProvider@register`** (2026-09-28)
- [x] ~~`heavy-analysis` queue listed in `one-inbox-queue.service` ExecStart?~~ **Fixed 2026-10-08 — added to the `--queue=` list after a 20h stuck Deep Analysis.** The systemd unit file lives only on prod — captured below as a durable follow-up.
- [ ] **Systemd unit files are not version-controlled.** Both `one-inbox-queue.service` and the related `@1..4` + `one-inbox-queue-campaigns` + `one-inbox-whisper` + `one-inbox-reverb` units live only at `/etc/systemd/system/` on prod. Any `--queue=X,Y,Z` change (like the 2026-10-08 `heavy-analysis` fix) is invisible to future greps + will be lost on a server rebuild. **Follow-up**: move canonical versions under `deploy/systemd/` in the repo, add a deploy step to `scp` them to prod when they change.

I'll close these as we run the load tests.

---

## Change log

| Date | Change |
|---|---|
| 2026-09-28 | Initial doc — hardware + services snapshot, PHP-FPM bottleneck identified, queue topology mapped, alert plan proposed, load-test scope defined |
| 2026-09-28 | Corrected transcription driver order (Groq primary, whisper fallback). Confirmed and documented the sync-Excel-parse landmine in §5.3 with the fix path. |
| 2026-09-29 | Shipped Excel-parse mitigation — capped uploads to 2 MB, added memory + time-limit guards, added contrast-safe amber notice to both wizards (EN + AR) with support@ot1-pro.com fallback. Still need the async import job to lift the cap. |
| 2026-09-29 | Closed 4 open verifications: (1) documented per-service queue assignments (main worker also handles comment queues; @1..4 do not; comment queues have only 1 worker each — potential bottleneck); (2) confirmed NO per-team throttle on `SendCampaignWhatsAppJob` — one team can monopolize the 1 campaigns worker; (3) mapped `ScoreLeadJob` — dispatched from every inbound message, runs on `default` with 5-min timeout, doubles NaraRouter call rate under inbound bursts; (4) clarified the `one-inbox-whisper.service` naming (it's a Laravel queue worker, not the whisper.cpp daemon). |
| 2026-09-29 | Added §5.7 concurrent-user ceilings — per-feature comfortable/hard-cap table, total-user rollups, breaking-point order, growth-signal triggers. Answers the "how many users can this box hold at once" question. |
| 2026-09-29 | Phase 1 of the 5-phase load-management plan (§11 below) shipped — permanent "Message the founder" WhatsApp link in the sidebar + inline WhatsApp support link in both campaign wizards' amber notice. Contrast-safe emerald pair. EN + AR. |
| 2026-09-29 | Phase 2 shipped (PR #62) — plan-tier monthly campaign limits (Free=1, Starter=5, Pro=25, Enterprise=∞). Rolling 30-day window. Gated at all 3 create sites. Emerald/amber/red quota chip on `/campaigns` index. 5 tests. |
| 2026-09-29 | Phase 3 shipped for WhatsApp wizard (PR #63) — async `ImportCampaignRecipients` job on `default` queue with per-team throttle. Wizard has new `importing` step with progress bar polled every 2s. Upload cap lifted 2 MB → 10 MB. Sync-parse guards removed. Email wizard deferred as Phase 3b. 4 tests. |
| 2026-09-29 | Phase 4 shipped (PR #64) — per-team throttle on `SendCampaignWhatsAppJob` (3 concurrent per team) using the `Cache::add + increment/decrement` pattern with `try/finally` for symmetric release. Info line on `/campaigns/{id}` sets user expectations. 2 tests. Full queue-position UI deferred as Phase 4b (needs Reverb push infra). Row-count JS preview (Phase 5) effectively subsumed by Phase 3's progress bar + copy — deferred as low-ROI. |

---

## 11 · Load-management architecture — 5-phase plan (Omar 2026-09-29)

Omar's proposal (better than the earlier one I had): treat the two biggest bottlenecks (Excel parse + campaign send) as **queue-position UX + plan-tier limits + async work**. Solves the technical bottleneck AND creates a revenue lever.

The 5 phases, ranked by ROI:

### Phase 1 — Support visibility ✅ SHIPPED 2026-09-29
- Emerald "Message the founder" chip permanently in the app sidebar (all authenticated pages)
- Inline WhatsApp link in both campaign wizards' amber upload-limit notice
- Both WhatsApp links open `wa.me/201026361218` with prefilled context (team + email in sidebar version)
- EN + AR translations
- Contrast-safe: `bg-emerald-50` + `text-emerald-900` + `border-emerald-200` (contrast-guardrails skill safe pair)

### Phase 2 — Plan-tier campaign limits ✅ SHIPPED 2026-09-29 (PR #62)
> **2026-10-06 Phase RP update**: the tier structure was collapsed from 6 → 4 and prices were cut. Below are the values ORIGINALLY shipped in PR #62. The current authoritative source is `config/plans.php` (`plans.plans.{slug}.limits.bulk_campaigns_monthly`): **Free = 0, Starter ($8) = 3, Pro ($29) = 15, Business ($99) = 100**. Legacy `enterprise` / `agency` slugs resolve to `business` via `Team::resolvePlanSlug()`.

- Free tier: **1 campaign/month** _(now 0 per Phase RP)_
- Starter ($29): **5 campaigns/month** _(now $8, 3 campaigns)_
- Pro ($79): **25 campaigns/month** _(now $29, 15 campaigns)_
- Enterprise: `PHP_INT_MAX` (effectively unlimited) _(removed; use `business` for the top tier at $99 with 100 campaigns)_
- Rolling 30-day window (not calendar month) — fairer for new signups
- Config: `config/campaigns.php` → `monthly_limits` array, env-overridable
- Enforcement: `Team::canCreateCampaign()` + `campaignsCreatedThisMonth()` + `monthlyCampaignLimit()` + `campaignsRemainingThisMonth()`
- Gated at all 3 create sites: `Campaigns\Index@save`, `WhatsAppWizard@launch`, `EmailWizard@launch` (defence-in-depth against concurrent tabs)
- UI chip on `/campaigns` index — emerald under limit, amber at ≥80%, red at 0. Upgrade CTA → `/pricing`.
- Fallback for unknown plan slugs → free-tier cap
- 5 Pest tests: `tests/Feature/Campaigns/PlanMonthlyLimitTest.php`

### Phase 3 — Async Excel import w/ progress bar ✅ SHIPPED 2026-09-29 (PR #63, WhatsApp wizard)
- `App\Jobs\ImportCampaignRecipients` — runs on `default` queue, `timeout=600`, `tries=1`
- Per-team throttle: `Cache::add("campaign:import:inflight:{team_id}", 1, 900)` — 1 in-flight per team; concurrent releases with 10s delay
- `WhatsAppWizard@advanceToCompose` creates `ContactImport` row + dispatches job + transitions to new `'importing'` step (added to `STEPS` const)
- `checkImportProgress()` polled by `wire:poll.2s` reads row status, auto-advances to `compose` on completion
- `retryImport()` re-dispatches on failure
- Progress bar UI: "12,384 of 98,201 processed" with animated fill (emerald-100 track + emerald-600 fill)
- Reassurance panel: "You can close this tab — the import will keep running on our server"
- Failed state shows retry button + last error (red-50/red-900 per contrast-guardrails)
- Upload cap lifted 2 MB → **10 MB (~100k rows)**. Removed `ini_set/set_time_limit` guards.
- Upload-step notice re-cast from amber "temporary limit" to emerald "feature announcement"
- 4 Pest tests: `tests/Feature/Campaigns/ImportCampaignRecipientsJobTest.php`
- **Still open** (Phase 3b): Email wizard (`EmailWizard@confirmMapAndImport`) still parses sync. Same landmine class; deferred because the WhatsApp path is the primary campaign use case in MENA.

### Phase 4 — Per-team send throttle ✅ SHIPPED 2026-09-29 (PR #64)
- `SendCampaignWhatsAppJob@handle` now enforces `Cache::add("campaign:send:inflight:{team_id}", 1, 60)` + `Cache::increment/decrement` capping concurrent sends at **3 per team**
- Mirrors `TranscribeAudio` pattern that already protects the transcription queue
- `try/finally` around `WhatsAppSender::send` so no code path leaks the lock (success, transient, permanent-error all pass through finally)
- 4th+ concurrent recipient for a team releases with 5s delay so other teams keep flowing
- Emerald info line under progress bar on `/campaigns/{id}` for active campaigns with pending recipients: "Sending up to 3 messages at a time per team so other users' campaigns keep flowing"
- 2 Pest tests: `tests/Feature/Campaigns/PerTeamSendThrottleTest.php`
- **Deferred** (originally scoped): full queue-position UI ("You are #7 in queue, ~4 min wait"). The per-team throttle is the load-critical piece; the position UI is UX polish that needs a Redis LLEN + Reverb push infrastructure not built here. Note as future work.

### Phase 5 — Row-count guidance (nice-to-have, deferred)
- Original plan: JS reads file first-N-bytes to preview row count before upload
- **Effectively addressed** by Phase 3's new upload-step notice + explicit "~100,000 contacts" copy + async progress bar
- Users no longer risk uploading a large file only to hit a low server cap (cap is 10 MB, and progress is visible immediately). Formal JS-side preview deferred as low-ROI.

### Cross-cutting: usage-quota banner (still open)
When a team hits 80% of their monthly campaign quota, show a top-bar warning that links to `/billing`. When they hit 100%, hard-block the launch step with an "Upgrade to launch another campaign" CTA. Reuses the pattern from `partials/ai-quota-banner.blade.php`. **Status**: 80%/100% signalling already covered by the amber/red chip on `/campaigns` index (Phase 2); top-bar version is UX polish, not shipped.

### Progress tracker
- [x] Phase 1 — Support visibility (2026-09-29, PR #61)
- [x] Phase 2 — Plan-tier campaign limits (2026-09-29, PR #62)
- [x] Phase 3 — Async Excel import w/ progress bar — WhatsApp wizard (2026-09-29, PR #63)
- [ ] Phase 3b — Async Excel import for Email wizard (deferred; same pattern as WA)
- [x] Phase 4 — Per-team send throttle (2026-09-29, PR #64)
- [ ] Phase 4b — Queue-position UI ("you are #N") — deferred (needs Reverb push infra)
- [x] Phase 5 — Row-count guidance — addressed by Phase 3's copy + progress bar; JS preview deferred as low-ROI

---

## 13 · AI credit economy (approved 2026-10-05)

**Full spec**: `docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md`

The AI chat was silently capping customer-conversation context at ~14 of thousands (`AdminChatContext.php:89`, 6 KB char budget) and treating every AI action as "1 unit" regardless of real cost. Users couldn't see their usage, couldn't pay for more, couldn't trigger expensive-but-valuable actions like "analyze last 1 000 contacts." This spec fixes all of that without wiring a payment provider (OT1 Pro isn't a registered entity yet — manual top-up only).

### Core architecture
- **Append-only `ai_credit_ledger`** — every charge/grant/refund is a signed row with team, delta, balance_type, reason, cost source. Balance = `SUM(delta)` cached in Redis (60 s TTL).
- **Two balances per team**: `monthly` (resets on billing-cycle anniversary, use-it-or-lose-it) + `wallet` (prepaid, never expires, drained after monthly).
- **Cost table** in `config/ai_costs.php` — tunable per action without code changes. `AiCredits::charge($team, $action, $meta)` is called from every dispatch site after a successful AI call.
- **Confirmation gate**: any action > 5 credits throws `ExpensiveActionRequiresConfirmationException` unless the team has opted into auto-deduct. UI shows a modal "This will use 50 credits · you'll have 290 left · [Confirm] [Always auto-deduct]".
- **Header meter chip** — green ≤ 60 %, amber 60-85 %, red > 85 %. Replaces the banner-only pattern (banner still shows at 100 % / upstream pause).
- **Manual payment flow**: `/settings/billing/top-up` shows pricing + PayPal + bank + WhatsApp; `/super-admin/billing` lets Omar grant credits with a payment-reference note that goes into the ledger.
- **Deep Analysis** — the first premium action. Async job on new `heavy-analysis` queue chunks the cohort into 10 batches, hits NaraRouter once per batch + one aggregation call, stores structured result in `deep_analyses` so re-asks don't re-charge.

### Phase order (6 days total)
- **A** — ledger + `AiCredits` service + rename `config/stripe.php` → `plans.php` + wire dispatch sites (1 day)
- **B** — header meter chip + `/settings/billing` ledger view (0.5 day)
- **C** — live-chat context fix (targeted page = ~100 convos, both directions) + retry/idempotency on AiChat (0.5 day)
- **D** — cost table + confirmation modal + Deep Analysis job + 1 000-contact premium analysis live (1.5 days)
- **E** — super-admin `/super-admin/billing` grant screen (0.5 day)
- **F** — `/settings/billing/top-up` user page with PayPal/bank/WhatsApp (0.5 day)
- **G** — privacy/terms updates + `/pricing-faq` page + Arabic translations (0.5 day)
- **H** — agent-audit sub-feature on Deep Analysis path (needs `messages.handled_by_user_id`, 1 day)

### Capacity impact on this box
Minimal. The `heavy-analysis` queue was originally spec'd to need **one dedicated worker**; in practice it's folded into `one-inbox-queue.service`'s queue list alongside `urgent, default, comments-*` (2026-10-08 fix after a 20h stuck Deep Analysis — the queue was created with the spec but the worker config was never updated to listen on it). Each Deep Analysis job hits NaraRouter 11 times (10 chunks + 1 aggregation) over ~60-120 s. At the napkin-math ceiling of 10 NaraRouter calls/sec (§5.1), each concurrent Deep Analysis consumes ~1 sec/sec of our AI budget — so **max 2 concurrent Deep Analyses team-wide** before AI-reply latency starts to climb. Cost table caps per-user frequency (expensive queries need credits) which naturally throttles this.

If Deep Analysis volume grows (3+ running at once observed), split it off into its own dedicated service so `urgent` isn't fighting for the same worker during an analysis burst.

### Progress tracker (shipped 2026-10-05 → 2026-10-08)
- [x] Phase A — ledger + AiCredits service + `config/plans.php` rename (2026-10-05)
- [x] Phase B — header meter chip + `/settings/billing` ledger view (2026-10-05 → 2026-10-08 chip bug fixes for legacy enterprise slug)
- [x] Phase C — live-chat context fix + retry/idempotency (shipped; §5.1 digest expansion)
- [x] Phase D — cost table + confirmation modal + Deep Analysis (2026-10-05); detection regex expanded 2026-10-08 to catch natural phrasings like "read 3000 contacts and analyze them"
- [x] Phase E — super-admin grant screen at `/super-admin/billing`
- [x] Phase F — user top-up page at `/settings/billing/top-up` with 3 pack sizes ($10/$25/$100)
- [x] Phase G — privacy/terms/pricing-faq updates + Arabic; founder-voice copy sweep 2026-10-08 replaced `support@ot1-pro.com` with `omareltak7@gmail.com` across 12 files
- [x] Phase H — agent-audit sub-feature on Deep Analysis path

### Phase RP (plan ladder collapse — 2026-10-06, bolt-on to the spec)
- Collapsed 6 tiers → 4: Free ($0, 100 credits/mo), Starter ($8, 500), Pro ($29, 3,000), Business ($99, 12,000)
- Legacy `enterprise` / `agency` subscription_plan values are aliased to `business` in `config/plans.php:legacy_aliases` and resolved via `Team::resolvePlanSlug()`
- Catch-up 2026-10-08: five code sites (EnforcePlanLimits, ResetMonthlyAiCredits, BackfillAiCreditLedgerSeeder, SuperAdmin/Customers display, Settings/Billing display, Settings components/ai-credit-meter) were reading `$team->subscription_plan` raw and silently falling back to Free for legacy-slug teams. All now go through `Team::resolvePlanSlug()`. See commit `ec9019b`.
| 2026-09-29 → 2026-09-30 | Campaigns index mobile-responsive fix (PR #66) — header stacks, campaign card layout stacks, button labels shorten so both CTAs visible on ~490px viewports. |
| 2026-09-29 → 2026-09-30 | Email-wizard `/campaigns/email/new` 500 rabbit hole — 4 fragile Blade escapes (`{{ '{{...}}' }}`) at lines 117, 198, 208, 210 all rewritten to canonical `@{{...}}` verbatim escape or `@php $var = '{{'.$c.'}}'; @endphp` pattern. PRs #67, #68, #70. Full contrast rewrite of email wizard from dark-shell (invisible white text on light shell) to light zinc palette in PR #69. |
| 2026-09-30 | **Deploy workflow hardened** (`.github/workflows/deploy.yml` in PR #69) — added `export HOME=/tmp XDG_CONFIG_HOME=/tmp` (deploy user's real `$HOME` not writable by psysh subprocess) + explicit `config:clear`/`route:clear`/`view:clear` BEFORE the `:cache` commands so stale compiled artifacts from a prior deploy or wrong-user manual run can't linger and re-explode. Root cause of a `MissingAppKeyException` storm on 2026-09-29 that stacked on top of the email-wizard 500. |

---

## 12 · Deploy pipeline (updated 2026-09-30)

`.github/workflows/deploy.yml` runs on push to `main`. Auto-deploys in ~24s via SSH as the `deploy` user.

**Sequence** (post-2026-10-08 Path A hardening — maintenance-mode wrap):

1. `set -e` + `trap 'php artisan up || true' EXIT` — safety net: a half-failed deploy still exits maintenance mode instead of leaving prod stuck at 503.
2. **`export XDG_CONFIG_HOME=/tmp HOME=/tmp`** — required for psysh subprocess; deploy user's real `$HOME` (`/var/www`) is not writable
3. **`php artisan down --render="errors::503" --retry=30 [--secret=…]`** — enter maintenance mode BEFORE any disk mutation. Users during the window see the branded 503 "I'm giving the site a quick tune-up" page with `Retry-After: 30`. Optional `DEPLOY_MAINT_SECRET` GitHub Actions secret provides a bypass URL (`https://ot1-pro.com/<secret>`) to verify the deploy before letting users back in.
4. `git pull origin main`
5. `.env` guards — idempotent writes for `APP_DEBUG=false`, `FLARE_KEY`, `LOG_STACK`
6. `composer install --no-dev --optimize-autoloader`
7. `npm ci && npm run build`
8. `php artisan migrate --force`
9. `config:cache`, `route:cache`, `view:cache` — rebuild fresh, all as `deploy` user. **`*:clear` steps removed** 2026-10-08 — they physically delete the compiled files for ~2-5s each until `*:cache` rewrites them, which was the single biggest cause of mid-deploy 500s (`MissingAppKeyException`). `*:cache` already writes atomically (tempfile + rename), so pre-clearing bought nothing.
10. `queue:restart` — pick up new job classes
11. `sudo systemctl reload php8.4-fpm` — clear opcache so FPM workers see the new bootstrap
12. `php artisan up` — exit maintenance mode. The `trap` from step 1 also runs this on error exit so prod never gets stuck down.

**What broke before the Path A hardening** (2026-09-29 and intermittently thereafter):
- A blade parse error was fixed on `main` and deployed
- Old `config:clear` step physically deleted `bootstrap/cache/config.php`; any request hitting FPM in the next 2-5s before `config:cache` rewrote it returned `MissingAppKeyException` as a 500
- The `npm run build` step rewrites `public/build/manifest.json` mid-request; any `@vite(...)` call reading a half-written file 500'd
- Combined 5-20s window of random 500s every deploy — users saw it, Omar watched it in Flare
- Result: `MissingAppKeyException` on random requests for ~10 minutes until manual recovery on the worst occurrence

**Prevention** (2026-10-08 Path A): maintenance-mode wrap + removed `*:clear` steps. Users during the window see a branded 503 instead of 500s; the window itself is also shorter because the clear/cache dance is gone.

**Still open as Path B**: atomic releases via symlink swap. Required if we ship a destructive migration (dropped/renamed column) where the old release serving traffic during the swap window could crash on the new schema. Not blocking for normal feature deploys. Captured as a follow-up — would move to Capistrano-style `releases/` + `current` symlink, likely using deployerphp/deployer or Laravel Envoy.

**Runbook** (if MissingAppKey ever fires again despite the guards):
```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com \
  && rm -f bootstrap/cache/config.php \
  && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache \
  && systemctl reload php8.4-fpm'
```
And confirm APP_KEY visibility:
```bash
sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="echo strlen(config('app.key'));"
```
Expect a number > 30. Zero = APP_KEY missing from `.env`. See `ot1-pro-prod-ops` skill rule #1 for the full runbook.
| 2026-09-30 | Task A shipped (PR #72) — fixed 2 real prod-log TypeErrors in `app/Livewire/Analytics.php` (round() rejecting MySQL AVG() string result under PHP 8.4 — cast to float) + 1 SQL error (`SELECT * FROM (SELECT ?,?,?) x` generating duplicate `?` columns — rewrote to plain `IN (?,?,?)`). 3 Pest tests. |
| 2026-09-30 | Task B shipped (PR #73) — `capacity:health-check` artisan command per §7 alert plan. Scheduled every 5 min via routes/console.php. Monitors CPU load, free RAM, disk free, queue depths (urgent/transcription/campaigns/default), NaraRouter global cooldown. Per-signal 60-min cool-down. Env-overridable thresholds. 4 Pest tests. Verified live on prod: "All capacity signals within thresholds." |
| 2026-09-30 | Task C shipped (PR #74) — Phase 3b async Excel import for Email wizard. Mirrors Phase 3 WA wizard: new `App\Jobs\ImportEmailRecipients` on `default` queue with per-team throttle (`email:import:inflight:{team}`), wizard has new 'importing' step (STEPS: upload→map→**importing**→compose→review→launched) with `wire:poll.2s="checkImportProgress"`, retry on failure, 10 MB cap. 4 Pest tests. Verified live: 6-step indicator renders, notice shows "Upload up to 10 MB". |
| 2026-09-30 | Task D shipped (PR #75) — k6 load-testing scaffold. `scripts/load/homepage.js` (ramp 50→500 VUs, fails on p95>5s or err>5%), `scripts/load/webhook-ingest.js` (fake signed payloads 1→100 req/s), plus README with install + priority table + prod-safety warning. Next step (needs staging box): point at `staging.ot1-pro.com` mirror, capture measurements, replace `(theory)` marks in §5.7 with `(measured)`. |
| 2026-10-05 | AI credit economy spec drafted and approved — `docs/superpowers/specs/2026-10-04-ai-credit-economy-design.md`. Introduces append-only `ai_credit_ledger`, two-balance model (monthly plan allowance + prepaid wallet), header meter chip, cost table per action, confirmation modal for actions > 5 credits, Deep Analysis premium feature (5 credits per 100 contacts analysed). **No payment provider wired** — OT1 isn't a registered entity yet; manual top-up via PayPal/bank/WhatsApp handled by a super-admin grant screen. Live-chat context fix bundled: targeted-page expansion lifts the per-page sample from ~14 convos to ~100 and includes outbound messages so moderator-audit queries work. See §13 below for scope and phase list. |
| 2026-10-06 | **Phase RP shipped (plan ladder collapse)** — 6 tiers → 4. Current: Free ($0, 100 credits/mo), Starter ($8, 500), Pro ($29, 3,000), Business ($99, 12,000). Legacy `enterprise` / `agency` subscription_plan values aliased to `business` via `Team::resolvePlanSlug()`. §11 Phase 2 numbers above are the pre-RP shipped values; actual authoritative config lives in `config/plans.php`. |
| 2026-10-07 | **AI chat async turn processing** (commit `0cd84ce`) — `app/Livewire/AiChat.php:sendMessage()` previously blocked 5-15 s on the inline NaraRouter call. A user who clicked Send and navigated away lost the request because `wire:submit` was cancelled mid-flight before AiCommand was ever persisted. Fixed by persisting the AiCommand with `status=pending` synchronously (~50 ms) and dispatching `App\Jobs\ProcessAiChatTurn` (on `default` queue) which does the slow part. New Reverb event `AiChatTurnCompleted` swaps the "typing dots" placeholder for the real response. If the user navigated away, `mount()` loads the pending row on next visit and shows the dots until the Reverb event fires (or until the completed row is already there, race-safe). Idempotency lock added for Deep Analysis — `AiChat::hasRunningDeepAnalysis()` disables the composer while a queued/running analysis exists for this (team, user); a browser Notification fires on completion. |
| 2026-10-08 | **Fixed `/ai-chat` 500 on page load** (commit `809ff07`) — `hasRunningDeepAnalysis()` queried `where('user_id', ...)` but the `deep_analyses` schema uses `triggered_by_user_id`. Every page load of `/ai-chat` 500'd with `PDOException 42S22` until a navigation away. Lesson captured in `tasks/lessons.md` under "verify schema with DESCRIBE before writing a query against an unfamiliar table". |
| 2026-10-08 | **Fixed stuck Deep Analysis / `heavy-analysis` queue not listened** — User reported "Analysis in progress" banner stuck for over 1 hour on `/ai-chat`. DeepAnalysis id=1 was `status=queued` since 2026-10-07 22:49 with `started_at=NULL`, 20+ hours untouched. Root cause: `DispatchDeepAnalysisJob` and `DeepAnalysisService` call `->onQueue('heavy-analysis')`, but the systemd unit for `one-inbox-queue.service` had `--queue=urgent,default,comments-ingest,comments-send` — no `heavy-analysis`. Jobs sat on a queue nobody listened to. **Fix on prod**: added `heavy-analysis` to the queue list, `systemctl daemon-reload && restart`. Marked the stuck analysis as `failed` so the AI chat lock released. §4 queue topology updated. Lesson in `tasks/lessons.md` with the 30-second diagnostic for future similar cases. New open verification: version-control the systemd unit files under `deploy/systemd/`. |
| 2026-10-08 | **Deploy pipeline Path A** (commit `02a05b9`) — every `git push origin main` was producing ~5-20s of 500s for live users during the ~24s auto-deploy. §12 fully rewritten. Core changes: wrap the whole dance in `php artisan down --render="errors::503" --retry=30` / `php artisan up`, remove the `*:clear` steps (they physically deleted the compiled files and bought nothing — `*:cache` already writes atomically), add `set -e` + `trap … EXIT` safety so a half-failed deploy never leaves prod stuck in maintenance mode. Optional `DEPLOY_MAINT_SECRET` GitHub Actions secret unlocks a bypass URL for verifying the new release before letting users back in. Path B (atomic releases with symlink swap) kept as a follow-up for destructive migrations. |
| 2026-10-08 | **Legacy-plan-slug sweep** (commit `ec9019b`) — 5 code sites were reading `$team->subscription_plan` raw and silently falling back to Free's 100 AI credits / 1 page / 0 campaigns for teams on legacy `enterprise`/`agency` slugs: EnforcePlanLimits middleware (all 4 checks), ResetMonthlyAiCredits scheduled job (so Omar's enterprise team had received 100/month instead of 12,000), BackfillAiCreditLedgerSeeder, SuperAdmin/Customers display, Settings/Billing display, components/ai-credit-meter chip. All now route through `Team::resolvePlanSlug()`. Catch-up grant of 12,000 monthly credits dispatched to team 2 on prod via `AiCredits::grant`. |
| 2026-10-08 | **UX fixes bundle** — (a) sidebar perf: `User::hasPermission()` was 8 DB queries per sidebar render for non-owner team members, now memoized to 1 (commit `c3cedf3`); (b) /settings/billing ledger + AI chat markdown tables both wrap in `overflow-x-auto` for mobile scroll; AI chat ruleset rewritten to fix "20 → 2\n0" column-crush bug (commit `6181f04`); (c) connections syncing banner converted from persistent inline bar to dismissible toast; (d) error page copy sweep to founder voice ("I'm giving the site a quick tune-up" instead of "We're..."), replaced `support@ot1-pro.com` with `omareltak7@gmail.com` across 12 files (commit `06678eb`); (e) ai-quota banner `grid-column: 2/-1` to stop crushing every page's main column to ~222px (commit `b5aeebd`). |
