# OT1-Pro Production Limits & Capacity Playbook

**Last measured**: 2026-09-28
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
| `one-inbox-queue.service` | Main queue worker | Consumes `urgent, default, comments-ingest, comments-send` in that priority order (verified 2026-09-29) |
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

The app uses **6 named queues** on Redis. Workers are dedicated per role so a bulk-send storm can't starve an inbound message.

| Queue | Purpose | Workers | Failure impact if backed up |
|---|---|---|---|
| `urgent` | Inbound messages, AI responses, platform sends | **5 workers** (`one-inbox-queue` + `@1..4`) | **Customers see silence** — real messages don't get replied to |
| `transcription` | Voice-note transcription (Groq primary, whisper.cpp fallback) | **1 worker** (`one-inbox-whisper.service`) | Voice notes fail to become text; AI can't respond to them |
| `campaigns` | Bulk WhatsApp/email per-recipient sends | **1 worker** (`one-inbox-queue-campaigns.service`) | Campaigns run slowly; inbound unaffected |
| `comments-ingest` | Meta comment webhooks classification | **1 worker** (main service only — `@1..4` do NOT process) | AI comment replies delayed |
| `comments-send` | Publishing AI comment replies | **1 worker** (same) | Same |
| `default` | `ConvertAudioToOgg`, `DescribeImage`, `ScoreLeadJob`, misc | **5 workers** (`one-inbox-queue` + `@1..4`) | Lead scoring + voice/image previews delayed |

**Per-team throttles**:

- `TranscribeAudio` — `transcribe:inflight:{team_id}` capped at **5** in-flight per team. 6th job releases back with a 3-second delay. **This prevents one user's 100-voice-note burst from monopolizing transcription.** ✓
- `SendCampaignWhatsAppJob` — **NO per-team throttle** (verified 2026-09-29 by reading `handle()` and `CampaignScheduler`). Rate control is upstream only — `campaigns:dispatch-recipients` scheduled command dispatches in per-minute batches. With ONE `campaigns` worker + `--max-jobs=1000`, a single team's 100k-row campaign monopolizes the queue and delays every other team's campaigns (inbound `urgent` still unaffected). **Future work**: mirror the transcription pattern with `Cache::add("campaign:inflight:{team_id}", ...)`.
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

⚠️ **CONFIRMED LANDMINE — 2026-09-28.** The Excel/CSV parse happens **synchronously inside the Livewire HTTP request**, not in a queued job.
🩹 **Mitigation shipped 2026-09-29.** See "Mitigations shipped" below.

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

I'll close these as we run the load tests.

---

## Change log

| Date | Change |
|---|---|
| 2026-09-28 | Initial doc — hardware + services snapshot, PHP-FPM bottleneck identified, queue topology mapped, alert plan proposed, load-test scope defined |
| 2026-09-28 | Corrected transcription driver order (Groq primary, whisper fallback). Confirmed and documented the sync-Excel-parse landmine in §5.3 with the fix path. |
| 2026-09-29 | Shipped Excel-parse mitigation — capped uploads to 2 MB, added memory + time-limit guards, added contrast-safe amber notice to both wizards (EN + AR) with support@ot1-pro.com fallback. Still need the async import job to lift the cap. |
| 2026-09-29 | Closed 4 open verifications: (1) documented per-service queue assignments (main worker also handles comment queues; @1..4 do not; comment queues have only 1 worker each — potential bottleneck); (2) confirmed NO per-team throttle on `SendCampaignWhatsAppJob` — one team can monopolize the 1 campaigns worker; (3) mapped `ScoreLeadJob` — dispatched from every inbound message, runs on `default` with 5-min timeout, doubles NaraRouter call rate under inbound bursts; (4) clarified the `one-inbox-whisper.service` naming (it's a Laravel queue worker, not the whisper.cpp daemon). |
