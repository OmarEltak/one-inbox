# OT1-Pro Production Limits & Capacity Playbook

**Last measured**: 2026-10-18 (k6 load tests against prod — webhook ingest + homepage breakpoint; see `docs/load-tests/`) · prior baseline 2026-09-28
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
| `php8.4-fpm` | PHP app | pool: **`pm=dynamic`, `max_children=20`** — see §3 |
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

## 3 · The tightest bottleneck: PHP-FPM `max_children = 20`

**2026-10-18 correction**: this doc previously claimed `max_children = 5` — the actual value in `/etc/php/8.4/fpm/pool.d/www.conf` is **20**. The §3 upgrade step was taken at some point and the doc wasn't updated. All ceiling math downstream has been revised against the measured 20-worker config.

```ini
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
```

**Meaning**: at most **20 concurrent HTTP requests can execute in PHP simultaneously**. Request #21 queues behind them in nginx. Under load this is still the single-most-visible failure mode — pages hang for 5-30 s, then either respond or 504.

**Measured behavior under saturation** (k6 run 2026-10-18, 300 VUs vs `/api/webhooks/meta` with invalid sig, 4 min):
- FPM scaled from 6 idle procs → 21 (20 workers + 1 master) within ~15 s of the ramp
- `/var/log/php8.4-fpm.log` fired `WARNING: server reached pm.max_children setting (20)` repeatedly
- Dynamic spawning adds first-burst latency — the FPM log also showed `spawning 16 children, there are 0 idle, and 19 total children` meaning requests arrived faster than `pm.start_servers=5` could warm up. Switching to `pm = static` with the same cap would eliminate that first-burst stall.
- CPU 1-min load avg peaked at **19.89 on 2 vCPU** — box still served 98 req/s with 0 % 5xx. (Load1 is NOT CPU utilization; it includes I/O-wait and D-state processes. Pure `%user` was not captured. The next load-test run must use `mpstat -P ALL 1` to decompose into `%user / %iowait / %idle`.) nginx + FPM queueing was clean.
- RAM peak 1.49 GB used of 7.8 GB — no memory pressure whatsoever.

**Measured ceilings** (replacing earlier napkin math):
- Signature-rejected webhook (`/api/webhooks/meta` with invalid HMAC) — **~98 req/s hard ceiling**, p95 1.0s @ 100 VUs → 3.4s @ 300 VUs (same throughput, more queueing). Measured 2026-10-18.
- Homepage render (`/`, full Blade + DB + Vite manifest) — **~30 req/s sustained**, p95 14 s @ 500 VUs with 0.51 % error rate. Measured 2026-10-18. First real break (non-200 responses) appears above ~30 req/s sustained.
- Any single slow request (>2 s DB query, hung external API) still burns a worker for its whole duration — the 20-worker budget is softened only by throughput, not by request duration.

**Alert threshold**: `active FPM children ≥ 18 for > 30 s` → send Omar an email (was `≥ 4` under the stale 5-child assumption).

**Next upgrade path candidates (NOT yet validated by CPU-utilization measurement)**: (a) switch `pm = dynamic` → `pm = static` with `pm.max_children = 20` to eliminate first-burst spawn latency — must verify no CPU-contention regression on 2 vCPU with `mpstat`; (b) raise `max_children` only after CPU-utilization data confirms worker threads are mostly I/O-waiting (DB/AI), not CPU-bound. RAM headroom alone (1.6 GB of 7.8 GB used) is insufficient justification — if workers are CPU-bound, more workers = more thrashing.

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
- `ScoreLeadJob` — no throttle. Dispatched from **11 call sites** in `ProcessIncomingMessage` — every inbound message-with-contact spawns one. Uses `default` queue with `$timeout = 300` (per-file comment references a real 2026-08-25 queue-cascade incident where the 60 s default caused SIGKILL cascading stalls). Each score = 1 NaraRouter call. **Measured 2026-10-18 (Run 6)**: scoring chain sustains 23 calls/sec with 10 concurrent workers, no cooldown. Not the bottleneck under realistic inbound.

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
| Webhook ingest rate (invalid-HMAC, reject path) | **98 req/s hard ceiling, measured 2026-10-18, CPU-bound** — see §8.5 Run 2 |
| Webhook ingest rate (valid HMAC + DB write + queue dispatch) | **Not yet measured end-to-end** — projection: lower than 98 req/s because the accept path adds signature verify, JSON decode, DB write, and Redis LPUSH |
| End-to-end message → AI reply latency | 3-15 s (dominated by NaraRouter call) |
| AI call rate — scoring model (direct benchmark) | **23 calls/sec sustained with 10 concurrent workers**, p50 0.5 s, no cooldown (measured 2026-10-18, Run 6) |
| AI call rate — reply generation (full `generateResponse`) | **~0.9 msgs/sec sustained** on 5-worker urgent queue (measured 2026-10-18, Run 8 — single message 32.5 s end-to-end). Reply model is a larger tier than the scoring model. |
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
- Still bounded by 20 FPM workers — under 25 concurrent uploads, excess users wait or 504 during the parse burst (projection; not re-measured since the 2026-09-29 mitigation)
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
| AI ceiling | ScoreMessage is the fast chain — **23 calls/sec sustained** with 10 concurrent workers (measured 2026-10-18, Run 6). Not the bottleneck. |
| Failure mode | Exception → logged as warning, no retry loop (returns silently) |

**Combined-load implication**: every inbound message = 1 AI reply call (urgent queue, ~28 s per Run 8) + 1 ScoreLead call (default queue, ~0.5 s per Run 6). Reply is 50× slower than scoring. The scoring chain is NOT the bottleneck — the reply chain is. Under any sustained inbound rate above ~0.9 msgs/s, the urgent queue grows at near-ingest rate because reply workers are blocked on `generateResponse`. ScoreLead on the `default` queue has 5 workers and ~23 calls/sec headroom — never hits NaraRouter cooldown at realistic inbound rates.

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

**TL;DR after 2026-10-18 load-test session** — measured hard ceilings on current infra (2 vCPU / 7.8 GB RAM / FPM max_children=20 / 5 urgent-queue workers):

| Metric | Measured ceiling | Scenario |
|---|---|---|
| Marketing homepage throughput | **~30 req/s sustained** (0.5 % err at 500 VU burst) | Hacker News front-page spike |
| Mixed marketing pages (home/blog/pricing/vs) | **~44 req/s** with 1-3 s think time | Normal SEO traffic |
| Webhook ingest (sig-rejected) | **~98 req/s CPU-ceiling**, 0 % 5xx | Attacker flood on `/api/webhooks/meta` |
| Inbound-webhook transport (DB insert + Redis LPUSH + worker SELECT no-AI) | **141 jobs/s enqueue, 39 jobs/s drain** sustained | Platform-webhook burst |
| **Inbound full AI pipeline (reply + ScoreLead)** | **ONE-MESSAGE measurement: 32.5 s wall time (Run 8, outbound mocked).** The "~0.9 msgs/s sustained" number below is a PROJECTION (single-message latency × 5 workers), **NOT measured concurrent throughput**. A concurrent end-to-end test has not yet been run. | Real customer-message storm |
| NaraRouter scoring calls (direct, concurrent) | **~23 calls/sec at 10 parallel workers, 0 cooldown** | AI-only benchmark |
| Analytics (super-admin, heavy webhook_logs query) | **20 concurrent viewers in 2 s drain** | Dashboard refresh burst |
| MySQL concurrent connections during peak | **~15 processes** | All above combined |
| Peak RAM used across all tests | **1.6 GB of 7.8 GB** | Never a bottleneck |
| Peak CPU load1 during tests | **19.89 / 2 vCPU** (load1, not %CPU — see caveat below; 0 req dropped) | Webhook flood |

**The customer-facing ceiling is NOT yet measured as sustained throughput.** What Run 8 measured: **one message takes 32.5 s wall time end-to-end** on an idle box with outbound mocked. What Run 8 did NOT measure: concurrent AI replies/sec, queue depth growth under sustained inbound load, successful-reply latency p50/p95 under contention, failure/retry rate, or how worker contention changes per-message latency. The derived "~0.9 msgs/s" number is single-message-latency × 5 workers — a BACK-OF-ENVELOPE PROJECTION, not a measurement. Treat it as a working estimate until a concurrent end-to-end test confirms or refutes it.

**Also noted (2026-10-18 critique)**:
- "Load avg 19.89 on 2 vCPU = 10× saturation" overstates. Load1 includes I/O-wait and D-state processes, not pure CPU. Future tests capture `mpstat -P ALL 1` for `%user`, `%iowait`, `%idle`.
- "500 VUs" ≠ 500 real users — k6 virtual users simulate connection-pool concurrency, not authenticated sessions with cookies, CSRF, Livewire polling, or Reverb WebSockets. Marketing-mix ceilings should be read as "anonymous read throughput," not "concurrent-user capacity."
- Alert threshold in §7 is correctly updated to `≥ 18 of 20` (was stale `≥ 4 of 5` prior to the sweep).

---

Numbers below are derived from the per-feature data above. Anything marked **(theory)** hasn't been load-tested; **(measured)** is empirically verified.

**Per-feature simultaneous-user ceilings**:

| Feature | Comfortable | Hard ceiling | Root constraint |
|---|---|---|---|
| Browsing dashboards / settings / inbox UI | ~50-100 signed-in with normal think-time | **20 truly-simultaneous HTTP requests** | PHP-FPM `max_children = 20` (corrected 2026-10-18) |
| Landing on homepage (marketing) | ~25 fresh visits/sec | **~30/sec sustained; errors (0.51 %) appear above this, p95 → 14 s at 500 VUs** (measured 2026-10-18, Run 3) | 500 ms Blade+DB render × 20 FPM; 60 s nginx `fastcgi_read_timeout` |
| Sending a message from inbox | 5 users clicking Send at once | Same (needs auth test) | Each send holds a FPM worker |
| Inbound webhook ingest (sig-rejected) | ~90 req/sec sustained | **~98/sec hard ceiling** (measured 2026-10-18, Runs 1+2); 0 % 5xx up to 300 concurrent VUs | CPU 2 vCPU (not FPM — FPM queued cleanly at load1=19.89, but `%user` was not captured so true CPU saturation is unverified) |
| Inbound webhook — transport only (DB insert + queue dispatch + worker SELECT) | ~30 jobs/s | **39 jobs/s drain, 141 jobs/s enqueue** (measured 2026-10-18, Run 5) | 5 urgent workers × 155 ms/job no-op |
| Inbound webhook — full AI pipeline (NaraRouter reply + ScoreLead) | Not yet measured concurrent | **ONE-MESSAGE latency = 32.5 s** (Run 8, outbound mocked). Sustained concurrent throughput UNVERIFIED — the "~0.9 msgs/s" figure elsewhere is a single-latency × worker-count PROJECTION, not a measurement. | NaraRouter `generateResponse` latency |
| Analytics dashboards (super-admin, cold) | ~10 concurrent viewers | **20+ concurrent sustainable** (measured 2026-10-18, Run 7: 20 heavy queries drained in 2 s; MySQL sort buffer did NOT blow) | MySQL CPU; cache TTL hides repeat views |
| AI replies to inbound (per-worker, single message) | 1 msg every ~30 s per worker | **~28-32 s per message serial** (Run 8). Per-worker CONCURRENT throughput unverified. | NaraRouter chat latency, not provider quota |
| AI lead scoring (direct scoring-chain) | 10+ concurrent fine | **23 calls/s aggregate with 10 workers** (measured 2026-10-18, Run 6), 0 cooldown | NaraRouter scoring-chain latency — not a bottleneck |
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

**Where ceilings break first** (in order — revised 2026-10-18 after full load-test session: k6 Runs 1-4 + inbound-storm Run 5 + AI-latency Run 6 + MySQL Run 7):

1. **NaraRouter / AI provider latency** — the TRUE customer-facing wall, but **less tight than previously projected**. Run 6 measured p50 **0.5 s** per scoring call and **23 calls/sec concurrent** with no cooldown. Full inbound pipeline (reply + ScoreLead = 2 AI calls per msg at ~0.5-2 s each) sustains **~8-10 messages/s** across 5 urgent workers before NaraRouter chain exhaustion. During an ad-driven burst the urgent queue still grows (ingest 141 jobs/s vs AI-drain ~10/s = ~130 jobs/s accumulation) but drains ~2× faster than the pre-measurement projection.
2. **CPU on 2 vCPU** — the TRUE wall for stateless endpoints. Load1 hit 19.89 on the 300-VU webhook burst; FPM queued cleanly up to 300 concurrent connections without a single 5xx. Doubling to 4 vCPU is still the single highest-ROI upgrade (as §1 already said).
3. **PHP-FPM = 20** (was documented as 5 — corrected 2026-10-18). ~20 heavy concurrent requests → request #21 queues behind, up to the 60 s nginx `fastcgi_read_timeout` ceiling.
4. **Nginx `fastcgi_read_timeout = 60 s`** — the first user-visible failure. Homepage test (500 VUs) produced 30 of 5 819 requests that either 5xx'd or returned empty bodies after hitting this wall.
5. **Single-worker queues** (campaigns, comments-ingest, comments-send, transcription) — no per-team fairness, first user monopolizes.
6. **MySQL sort-memory** on `webhook_logs` — already shipped once; can re-blow on concurrent analytics.
7. **RAM** — NOT a bottleneck at current scale. Peak 1.6 GB of 7.8 GB during the hardest test. RAM headroom allows more workers, but RAM alone is not sufficient justification — raising `max_children` requires first measuring whether workers are CPU-bound via `mpstat %user` under load.

### Security finding (2026-10-18) — webhook throttle appears bypassed; mechanism UNVERIFIED

**Observation**: Runs 1 + 2 sustained 94-98 req/sec against `/api/webhooks/meta` for 7 minutes from a single k6 process with no throttle response (no 429s, no slow-downs attributable to rate-limiting).

**Hypothesis (NOT yet verified)**: Prod sits behind Cloudflare. If `$request->ip()` returns the direct connection IP (= Cloudflare edge IP, which rotates across thousands of edges), the throttle's `by($request->ip())` key would effectively rotate per-request and never aggregate — defeating the limit. This is the most likely explanation but has NOT been confirmed by inspecting the actual resolved request IP during a test.

**What must be verified before accepting the hypothesis or shipping a "fix"**:
1. Inspect `app/Http/Middleware/TrustProxies.php` (or `bootstrap/app.php` in Laravel 11) — what IPs/CIDRs are trusted?
2. Log `$request->ip()` and the full `X-Forwarded-For` + `CF-Connecting-IP` headers DURING a k6 run — confirm which IP the rate-limiter actually sees.
3. If TrustProxies currently trusts `*`, verify a spoofed `CF-Connecting-IP` from a non-Cloudflare source cannot bypass the limit by setting that header to a varying value per request. Trust forwarded IPs ONLY from the known Cloudflare IP ranges (`https://www.cloudflare.com/ips/`).
4. After fixing TrustProxies, re-run the k6 test and confirm the throttle actually fires at 120 req/min.

**Severity assessment is also conditional**: the pre-middleware HMAC verification still rejects invalid payloads before any DB write, so even an unthrottled flood can't poison `webhook_logs` or `urgent` queue — it can only briefly saturate FPM. But a throttle is cheap defense-in-depth and should actually work; whether it currently does is unproven.

**Growth triggers** (when to act):

| Signal | Action |
|---|---|
| 5+ signups/day OR active campaigns from 3+ teams | Evaluate switching `pm = dynamic` → `pm = static` with `max_children = 20` (eliminates first-burst spawn latency; verify no CPU-contention regression on 2 vCPU before committing) |
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
| C | 1 team runs a 100k-recipient campaign while other teams send inbound | Isolated `campaigns` queue means inbound survives ✓; own team's inbound replies throttled by the ~0.9 msgs/s AI pipeline ceiling (Run 8), not by AI cooldown |
| D | 50 concurrent analytics page loads | PHP-FPM max_children=20 → 30 requests queue in nginx; also risk of MySQL sort-memory blow-up (mitigated 2026-10-18 by `webhook_logs_created_at_index`) |
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
| FPM active children | 18+ out of 20 for > 30 s | `[OT1] PHP-FPM saturated` |
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

## 8.5 · Measured load-test results (2026-10-18)

Raw CSV telemetry + k6 summaries live in `docs/load-tests/`. Each run was done against **live prod** (`https://ot1-pro.com`) while no customer traffic was present. Server-side telemetry was captured every 1 s via `/tmp/loadmon.sh` on the VPS (load1, RAM, FPM proc count, queue depths, MySQL process count, established HTTP conn count).

### Run 1 — webhook ingest, invalid HMAC, 10→100 VUs, 3m30s
- Script: `scripts/load/webhook-ingest.js`
- Telemetry: `docs/load-tests/2026-10-18-webhook-ingest-telemetry.csv`
- Result: **94.4 req/s sustained**, 19 827 requests, 0 % 5xx, 0 errors, p50 520 ms, p95 **1.01 s**, max 2.82 s
- Server peak: load1 **18.91 / 2 vCPU**, FPM procs **21** (max_children hit), RAM 1.49 GB, 102 active HTTP conns
- Interpretation: HMAC rejection is cheap enough that 20 FPM workers × ~5 req/s/worker ≈ measured 94.

### Run 2 — webhook ingest breakpoint, invalid HMAC, 50→300 VUs, 4m00s
- Script: `scripts/load/webhook-ingest-breakpoint.js` (new 2026-10-18)
- Telemetry: `docs/load-tests/2026-10-18-webhook-ingest-breakpoint-telemetry.csv`
- Result: **98.0 req/s sustained** (same throughput as Run 1 — ceiling hit), 23 526 requests, 0 % 5xx, 0 errors, p50 1.76 s, p95 **3.42 s**, max 5.14 s
- Server peak: load1 **19.89**, FPM procs **21**, 303 active HTTP conns, RAM 1.36 GB
- Interpretation: Throughput is **CPU-bound at ~98 req/s** for signature-rejected traffic on this hardware. Pushing from 100 → 300 VUs just adds queue latency, not throughput. Nginx + FPM socket queue absorbed 300 concurrent connections without any 5xx. **Even at 10× CPU saturation the box did not drop a single request** — it just got slower.

### Run 3 — homepage render, 50→500 VUs, 3m00s
- Script: `scripts/load/homepage.js`
- Telemetry: `docs/load-tests/2026-10-18-homepage-telemetry.csv`
- Result: **29.6 req/s sustained**, 5 819 requests, **0.51 % error rate (30 failures / 5 819)**, p50 3.63 s, p95 **14.03 s**, max **60 s** (nginx timeout)
- Server peak: load1 **12.95**, FPM procs **21**, RAM 1.43 GB, 301 active conns
- Interpretation: Homepage render is **3× heavier per request than webhook rejection** (~500 ms vs ~200 ms per worker-second). Load avg is *lower* than Run 2 because workers spend most of their time waiting on MySQL, not burning CPU. The first break (non-200 responses) appeared when sustained req rate exceeded ~30 req/s. **Max response time hit the 60-second nginx `fastcgi_read_timeout` ceiling** — some requests were killed outright.

### What breaks first (observed, in order)
1. **FPM `max_children=20` saturates almost instantly** on any burst > 20 concurrent — subsequent requests queue in the nginx/FPM socket.
2. **CPU 1-min load exceeds 10× capacity** on sig-reject bursts, 6× capacity on homepage bursts. The 2 vCPU is the true wall.
3. **Nginx `fastcgi_read_timeout` (60 s)** kills requests that waited > 60 s for a worker. This is the first user-visible failure mode (5xx or empty body).
4. **RAM was never the bottleneck** in any test — 1.5 GB peak of 7.8 GB. RAM allows more workers; CPU-utilization measurement (not captured — only load1) must confirm workers are I/O-waiting before raising `max_children`.

### Run 9 — Deep Analysis storm (uncovered a dead-worker incident)
- Command: `php artisan load-test:deep-analysis-storm --concurrent=3 --cohort=50`
- Fixture: isolated test team, 50 fake conversations with 3 messages each, Business plan.
- **Finding 1 — production incident uncovered**: initial run showed 3 DeepAnalysis rows sitting in `queues:heavy-analysis` for 400 s with no state change (`queued=3, running=0, completed=0, failed=0`). Investigation via `systemctl status one-inbox-queue` revealed the main queue worker had been **STOPPED since 2026-10-17 22:59 UTC — dead for ~18 hours**. Only `one-inbox-queue@1..4` were running, and those are configured for `--queue=urgent,default` ONLY (confirmed via `grep ExecStart /etc/systemd/system/one-inbox-queue@.service`). **No worker was consuming `heavy-analysis`, `comments-ingest`, or `comments-send`.** Any Deep Analysis a real customer triggered in that window would have silently hung — exactly the same failure mode as the 2026-10-08 incident recorded in CLAUDE.md pin #7. The service was likely stopped manually and never re-enabled.
- **Finding 2 — remediation**: `systemctl start one-inbox-queue && systemctl enable one-inbox-queue` restored processing. The service is now active AND enabled for auto-start on reboot (previously `disabled`, so would not survive a reboot even after a manual start).
- **Finding 3 — queue drains work after remediation**: re-ran the storm, 3 analyses completed in 2-6 s each, serial drain. Confirms the single-worker heavy-analysis queue serializes concurrent analyses.
- **Caveat — the per-analysis latency is NOT a trustworthy real-world number**: NaraRouter returned `"Empty response from AI."` for all 3 analyses (`result_json: {"raw": "", "summary": "Empty response from AI."}`). The 50-conversation fixture used thin repetitive messages ("Hi, do you offer bulk pricing for 50 users?") that didn't give the aggregation model enough signal. Each analysis finished in ~2 s instead of the real-world 60-120 s projected in §13. A proper measurement needs a richer fixture (varied, longer transcripts) — flagged as follow-up.
- Interpretation:
  - Load testing SURFACED a 18-hour-stuck production issue that no monitoring had alerted on. §7's capacity:health-check doesn't currently check "is the main queue worker alive?" — gap flagged.
  - The 2026-10-08 fix (adding `heavy-analysis` to the queue list) was correct, but the systemd unit remained `disabled` (not auto-starting on boot) AND someone later `systemctl stop`'d it without a corresponding start. Documenting the units in Git (§9 open verification item) is now higher priority.
  - Heavy-analysis queue depth 0 at run end + 0 lingering test rows confirms clean recovery.

### Run 8 — First measured end-to-end baseline, single message (2026-10-18)
- Command: `php artisan load-test:e2e-trace` (new; `app/Console/Commands/LoadTestE2eTrace.php`)
- Fixture: isolated test team (ai_enabled=true, 1 000 wallet credits granted), test Page with 24/7 active AiConfig, Bus::fake for SendPlatformMessage so outbound HTTP never fires.
- EXERCISED: `webhook_logs` INSERT → ProcessIncomingMessage → SendAiResponse (REAL NaraRouter `generateResponse` call) → ScoreLeadJob (REAL NaraRouter `scoreMessage` call)
- BYPASSED (documented explicitly): nginx+FPM HTTP entry, signature verify, `throttle:webhooks`, SendPlatformMessage::handle (outbound mocked), retry loops
- **Result: 32.53 s wall time for ONE message**, 68 SQL queries, inbound + outbound (AI reply) rows both created, outbound dispatch captured but not sent.
- Verified AI reply content was real + coherent: *"Absolutely — we do offer volume pricing for teams of 50. The..."*
- Interpretation:
  - This is the first **measured** end-to-end number on this doc. All prior AI-pipeline capacity numbers were projections.
  - The 32.5 s breakdown (sync execution, so stages serialize instead of overlap):
    - WebhookLog INSERT: 5 ms
    - ProcessIncomingMessage: ~2 s (contact create, conversation create, message create, dispatch chains)
    - SendAiResponse debounce (`usleep(2_000_000)`): 2 s
    - SendAiResponse NaraRouter `generateResponse`: estimated 20-25 s (large text-primary model, full conversation context)
    - ScoreLeadJob NaraRouter `scoreMessage`: ~0.5-1 s
    - Response-delay simulation + remaining DB writes: ~2-3 s
  - **In production** (async), the 2 s debounce and 0-1 s response-delay-min overlap with the AI call rather than serialize, trimming maybe 3 s. True async per-message wall time is estimated **~25-30 s**.
  - Per-worker PROJECTION (NOT measured): 5 urgent workers × (1 msg / 28 s) = **~0.18 msgs/sec/worker, ~0.9 msgs/sec aggregate**. This is single-message latency extrapolated by worker count — it assumes perfect worker utilization and no contention, DB-connection, or NaraRouter-concurrency effects between workers. The real concurrent sustained throughput could be meaningfully different (likely lower).
  - **What a concurrent end-to-end test must measure before this becomes a trustworthy number**: (a) successful AI replies/sec at a defined sustained inbound rate; (b) p50/p95 reply latency measured as `message.platform_sent_at - message.created_at`; (c) urgent-queue depth over time during the burst and the drain-to-10 time after burst-end; (d) failure/retry rate; (e) `mpstat %user / %iowait / %idle` throughout.
  - **What Run 8 does prove**: the full pipeline WORKS (all 7 gates pass with the right fixture), an AI reply IS generated, SendPlatformMessage IS dispatched, and the per-message serial budget is 32.5 s. Everything beyond that is a working estimate pending the concurrent test.

### Run 6 — AI-call latency, direct `scoreMessage()` benchmark
- Command: `php artisan load-test:ai-latency --count=N` (new 2026-10-18; `app/Console/Commands/LoadTestAiLatency.php`)
- Pattern: calls `AiProviderInterface::scoreMessage()` with unpersisted Message + Contact instances in a tight loop. No DB writes, no queue, no outbound. Pure NaraRouter → provider → response.
- Sequential (1 worker × 20 calls): p50 **0.50 s**, p95 2.62 s, 1.27 calls/sec, 0 errors
- Concurrent (5 parallel × 10 calls = 50 total): per-worker p50 ~0.37 s, aggregate **13.1 calls/sec**, 0 errors, 0 cooldown
- Concurrent (10 parallel × 15 calls = 150 total): per-worker p50 ~0.40 s, aggregate **~23 calls/sec**, 0 errors, 0 cooldown
- Interpretation:
  - NaraRouter's **scoring chain** sustains **23 calls/sec with 10 concurrent workers, 0 cooldown**. Scoring is NOT the AI bottleneck.
  - Reply generation (`generateResponse` → text-primary chain) is a different story. The subsequent Run 8 full-pipeline measurement showed each inbound message takes **~28 s** of worker time, dominated by the reply call. Reply generation is **~50× slower** than scoring.
  - Each scoring-call test burns ~\$0.01 of NaraRouter credits per 20 calls — negligible. Reply-call cost is higher per call but was only measured once in Run 8.

### Run 7 — MySQL analytics-query cost (direct `EXPLAIN` + concurrent-query test)
- No command — ran `EXPLAIN` + `SET profiling=1` directly on prod MySQL.
- Baseline table sizes: `messages` 33 713 rows / 10.5 MB, `webhook_logs` 65 431 rows / 260.7 MB (260 MB because payload is a JSON blob).
- Representative heavy query: `SELECT platform, COUNT(*), SUM(processed) FROM webhook_logs WHERE created_at >= NOW() - INTERVAL 7 DAY GROUP BY platform`
  - EXPLAIN: index scan on `webhook_logs_platform_processed_index`, 56 412 rows, `Using where`
  - Single-query latency: **295 ms cold, 143-164 ms warm**
- Concurrent-query test (10 identical queries in parallel SSH): **drained in 1 s**. 20 concurrent: **2 s**.
- Interpretation:
  - **MySQL sort buffer did NOT blow up** at any load tested. Prior incident (`feedback_prod_out_of_sort_memory`) may have been a one-off from a different query or a since-dropped `ORDER BY ... LIMIT` pattern.
  - ~~Missing index: `webhook_logs` has **no index on `created_at`**~~ **Fixed 2026-10-18** — migration `2026_10_18_000001_add_created_at_index_to_webhook_logs.php` adds `webhook_logs_created_at_index`. Measured post-index: EXPLAIN now range-scans **153 rows** (vs 56 412 pre-index) using the new index, query time dropped **143-295 ms → ~1 ms** (150-300× faster). Analytics load now effectively free from this query.
  - Each analytics page load fires ~10 computed queries (see `app/Livewire/SuperAdmin/Analytics.php` — messagesDaily, convByPlatform, retentionCohort, etc.). Cache TTL (`self::CACHE_TTL_WINDOWED`) hides this cost from repeat views. Cold uncached load ≈ **~500 ms total per page**.
  - 10 concurrent super-admin analytics viewers (worst case: all cold) ≈ 100 queries ≈ 10-20 s combined MySQL time. Still absorbs cleanly on 2 vCPU. Not a near-term bottleneck.

### Run 5 — inbound-message storm (urgent-queue drain), 500 / 2 000 / 5 000 synthetic webhooks
- Command: `php artisan load-test:inbound-storm --count=N` (new 2026-10-18; `app/Console/Commands/LoadTestInboundStorm.php`)
- Pattern: insert N `webhook_log` rows + `ProcessIncomingMessage::dispatch(...)` with fake `entry.id='LOADTEST_SINK_*'` so page lookup fails and the job no-ops after the SELECT (no AI calls, no outbound sends). Measures pure transport layer: DB insert + Redis LPUSH + worker dequeue + page SELECT + DB UPDATE.
- Results (converged — three runs confirm the same drain ceiling):

  | Count | Enqueue rate | Peak queue depth | Post-enqueue drain | Sustained drain |
  |---|---|---|---|---|
  | 500 | 148.9 jobs/s | 394 | 12.0 s | 32.8 jobs/s |
  | 2 000 | 141.5 jobs/s | 1 596 | 42.0 s | 38.0 jobs/s |
  | 5 000 | ~141 jobs/s | ~3 000 (est) | 104.0 s | **39.0 jobs/s** |

- Server peak during 5 000-count run: load1 **3.05**, MySQL concurrent processes **15**, RAM 1.6 GB. Queue workers (not FPM) do all the work — this is a worker-pool test, not an FPM test.
- Interpretation:
  - **Enqueue ceiling ≈ 141 jobs/s** — bounded by Eloquent `WebhookLog::create()` DB insert speed + Redis LPUSH (JSON serialization + dispatch pipeline). This is the ingest layer.
  - **Drain ceiling ≈ 39 jobs/s sustained** — bounded by the 5 `urgent` workers at ~155 ms/job each (DB SELECT + Eloquent event firing + DB UPDATE markProcessed). Converges across 500 / 2 000 / 5 000 counts.
  - This is the **no-AI floor**. Real customer inbound (NaraRouter reply + ScoreLead) was later measured at **~0.9 msgs/s sustained** full-pipeline (Run 8). The transport layer absorbs ~140× more than the AI pipeline can process.
  - **Scaling implication**: during a real inbound burst, the urgent queue depth builds up fast (ingest 141/s vs AI-drain 0.9/s → ~140 jobs/s accumulation). A 60-second ad-driven burst would park ~8 400 messages waiting for AI replies, with the last customer waiting ~2.5 hours for their reply at current infra.
  - **The reply-generation NaraRouter call is the real bottleneck**, not the queue infra, not provider rate-limit, not CPU. Each inbound message blocks a worker for ~28 s on `generateResponse`.

### Run 4 — marketing-pages mix (50 % /, 25 % blog, 15 % /pricing, 10 % /vs/wati), 50→300 VUs, 4m30s
- Script: `scripts/load/marketing-pages-mix.js` (new 2026-10-18)
- Telemetry: `docs/load-tests/2026-10-18-marketing-mix-telemetry.csv`
- Result: **43.5 req/s mixed**, 11 831 requests, 0 % 5xx, **1 of 11 831 empty-body**, p50 1.14 s, p95 **5.73 s**, max 45.5 s
- Per-page latency: home p95 6.18 s, blog p95 5.12 s, pricing/vs p95 5.03 s
- Server peak: load1 **11.65**, FPM procs **21**, 180 active conns
- Interpretation: Blog/pricing render ~15 % faster than homepage (fewer DB queries on simpler layouts). Mixed-traffic sustain is **~44 req/s** — higher than Run 3 (29 req/s) because of the 1-3 s think time between requests. Marketing stack is production-ready for ~500 unique visitors in a 30 s burst (a Hacker News front-page spike feels like this).

### What was NOT measured yet (status after 2026-10-18 session)

- [x] **Inbound message storm — transport layer** (Run 5): 141 jobs/s enqueue, 39 jobs/s drain, 5 000-job scale clean. ✅
- [ ] **Inbound message storm — full AI pipeline**: needs a real test Page row + real team + outbound mock (so SendPlatformMessage fails fast without hitting Meta). Each test burns ~$0.08 of NaraRouter + ScoreLead calls per 100 messages. Blocked on test fixture setup.
- [ ] **Voice-note storm**: TranscribeAudio burst — Groq primary + whisper.cpp fallback. Per-team throttle = 5 in-flight. Needs a hosted test audio URL + test team.
- [ ] **Campaign send storm**: `SendCampaignWhatsAppJob` flood from multiple teams simultaneously — tests the 3-per-team throttle vs the 1 campaigns-queue worker. Needs test teams with mock-outbound WhatsApp sender.
- [ ] **Deep Analysis concurrent**: multiple heavy-analysis jobs across teams — each hits NaraRouter 11×. Needs test teams with contacts.
- [ ] **Analytics viewers concurrent**: authenticated `/analytics` loads hitting `webhook_logs` aggregates — this is where MySQL sort buffer blew up historically (§5.5). Needs session fixture or signed URL path.
- [ ] **Reverb concurrent connections**: Spin up 500 fake WS connections, measure memory + fd use.
- [ ] **Compound ("real Monday morning")**: inbound storm + 2 campaigns + 3 Deep Analyses + 5 dashboard viewers all at once.

---

## 10 · Proposed SLOs (draft — not yet signed off, not yet proven)

**These are TARGETS, not achievements.** Every row below needs a dedicated test that proves the number holds under the stated load. The current state of each test is tracked in the "Verified by" column — "none yet" means the SLO is aspirational.

**Why this section exists**: previous versions of this doc conflated "biggest number k6 produced" with "capacity we can commit to." That is false. An SLO is a promise under defined conditions. Everything else is a point measurement.

### Measurement boundaries (apply to every row)
- **Target load** = the stated sustained input rate held for ≥ 60 s.
- **Measurement window** = the 60 s hold phase only. Ramp-up excluded.
- **Pipeline bounds** = named explicitly in the SLO; any stage not named is NOT exercised and NOT guaranteed.
- **Abort thresholds** = if load1 > 25, free RAM < 500 MB, urgent-queue depth > 10 000, or any 5xx > 1 % for > 10 s during a test — kill the test and investigate before continuing.
- **Mocks vs real** = each test MUST declare whether outbound (Meta Graph, WhatsApp gateway, Groq, Reverb) is live or mocked. A mocked SLO is weaker than a live one.

### Proposed SLOs

| # | SLO | Target | Conditions | Verified by | Status |
|---|---|---|---|---|---|
| S1 | `webhook_accept_rate` — fraction of valid-HMAC inbound webhooks accepted + logged in < 1 s | ≥ 99.5 % | Sustained 20 req/s valid-HMAC inbound for 60 s; measured at nginx access log; webhook_log row count must match 1:1 | none yet | **unverified** |
| S2 | `ai_reply_p95` — 95 % of inbound customer messages receive an AI reply within 15 s | p95 reply latency ≤ 15 s under 2 msgs/s sustained inbound | Full pipeline: webhook → DB → ProcessIncomingMessage → NaraRouter reply → ScoreLead → SendPlatformMessage (OUTBOUND MOCKED to a sink team only, documented in test report); measured via `message.platform_sent_at - message.created_at`; test team isolated from real traffic | none yet | **UNVERIFIED — likely NOT currently achievable.** Run 8 single-message latency was 32.5 s on an idle box with no contention. 15 s p95 at 2 msgs/s sustained would require more than halving the current single-message cost. Keep as aspirational. |
| S3 | `urgent_queue_recovery` — after any burst, urgent queue returns to depth ≤ 10 within 60 s of burst-end | 60 s to drain to ≤ 10 after a 1 000-msg burst stops arriving | Measured via `LLEN queues:urgent` sampled every 2 s; burst injected via `load-test:inbound-storm --count=1000` with a real test page so the full worker path runs | partially (Run 5 drained 5 000 jobs in 104 s, but no-AI path only) | **UNVERIFIED for full-AI path — likely NOT currently achievable.** Run 5's 39 jobs/s drain was the no-AI transport path. A full-AI burst of 1 000 messages at a projected ~0.9 msgs/s drain would need ~1 100 s to clear, not 60 s. The transport-only partial verification should NOT be read as supporting S3 for real customer traffic. Full-AI queue drain must be tested separately. |
| S4 | `no_message_loss` — zero duplicate sends OR dropped inbounds across worker restart, pod replace, or AI provider outage | 0 duplicates, 0 drops, measured over a 10-minute test with 3 forced `systemctl restart one-inbox-queue*` events + 1 simulated NaraRouter 5xx storm | Measured via (a) count of `platform_message_id` duplicates on outbound rows, (b) count of webhook_log rows with `processed=false` after test wind-down, (c) idempotency key duplicates on `ai_credit_ledger`. External delivery semantics of Meta Graph are OUT OF SCOPE — this SLO covers only our own at-least-once/at-most-once behavior. | none yet | **unverified** |
| S5 | `analytics_cold_load` — super-admin analytics page cold-load returns first byte in ≤ 3 s at 10 concurrent viewers | p95 ≤ 3 s FCP (first contentful paint) for 10 concurrent k6 VUs loading `/super-admin/analytics` from a cold cache | Requires authenticated session fixture (not yet built); each VU gets a unique session so no shared cache warm-up. Current full-page load not measured — only the heaviest single query (143 ms warm, 1 ms after Run 7 index). | partially (query cost only, not full page) | **unverified (page-level)** |
| S6 | `inbound_webhook_accept_under_attack` — valid-HMAC webhooks continue to accept at ≥ S1 rate even during an invalid-HMAC flood from the same IP range | S1 holds while 300-VU invalid-HMAC flood runs against the same endpoint | Measured by running Run 2's breakpoint test concurrently with S1's valid-HMAC baseline. Depends on fixing the Cloudflare `TrustProxies` throttle bypass first (noted in §8.5). | none yet | **unverified** |
| S7 | `deep_analysis_concurrent` — N concurrent Deep Analyses on the heavy-analysis queue complete without blocking urgent-queue AI replies | 3 concurrent Deep Analyses complete within 3 × single-analysis latency; urgent-queue p95 reply latency does NOT regress by > 20 % during the storm | Measured via `load-test:deep-analysis-storm --concurrent=3` + S2's reply latency measured concurrently | none yet | **unverified** |

### What this section is NOT

- Not a commitment Omar has made to any customer.
- Not a guarantee for the current infra. These targets presume the pending fixes (full TrustProxies verify, systemd units in Git, measured end-to-end pipeline test).
- Not a replacement for incident response — a blown SLO is a signal to investigate, not an auto-rollback.

### Next action

Build and run `load-test:e2e-trace` (single-message end-to-end trace, outbound mocked, sync execution, per-stage timing) **before** running the Deep Analysis storm. That test closes the biggest honesty gap (unmeasured full pipeline) and establishes the baseline that S2 and S3 ramp from.

---

## 9 · Open verifications (things this doc claims that need proof)

- [x] ~~`capacity:health-check` does NOT alert on dead queue workers.~~ **Fixed 2026-10-18** — `workerLivenessSignals()` added to `CapacityHealthCheck`. Checks `systemctl is-active` for the 8 required units (main queue, @1..@4, campaigns, whisper, reverb). Any non-active unit trips an alert (60-min per-signal cool-down). Caveat: an active service doesn't fully prove its queue is making progress — a queue-depth-not-decreasing probe is a stronger check and remains open as a follow-up.
- [x] ~~Main queue worker was dead for 18h~~ **Fixed 2026-10-18** — restarted + enabled for auto-start.
- [x] ~~Systemd unit files not in Git~~ **Fixed 2026-10-18** — all 5 unit files captured in `deploy/systemd/` with a README describing the deploy flow. The `one-inbox-queue.service` + `one-inbox-queue@.service` had never been version-controlled; they are now.
- [ ] **Queue-progress probe (vs active-service probe).** The current `workerLivenessSignals` catches dead services but not stuck ones (worker running but not progressing — e.g. hung on a slow query). A better check: sample `queues:urgent` depth every 60 s and alert if depth did not decrease and jobs were enqueued. Follow-up.

- [x] ~~Which queues do `one-inbox-queue.service` and `@1..4` actually consume?~~ **main = urgent+default+comments-ingest+comments-send; @1..4 = urgent+default only (comment queues have only 1 worker)** — documented in §2 + §4 (2026-09-29)
- [x] ~~Is Excel campaign parsing sync or queued?~~ **CONFIRMED SYNC — landmine documented in §5.3** (2026-09-28); mitigation shipped 2026-09-29
- [x] ~~Per-team throttle on `SendCampaignWhatsAppJob`?~~ **NO throttle exists — documented in §4 with future-work note** (2026-09-29)
- [x] ~~`ScoreLeadJob` trigger + queue name~~ **dispatched from every inbound-message-with-contact (11 sites in `ProcessIncomingMessage`); runs on `default`; timeout 300s; 2 tries** — documented in §5.4 (2026-09-29)
- [ ] Actual whisper.cpp transcription latency on this hardware — measure with a 30-second audio file
- [ ] `client_max_body_size` — what's the actual nginx-side limit for Excel uploads?
- [ ] Reverb concurrent connection ceiling under real message load
- [x] ~~Transcription driver order~~ **Groq primary, whisper.cpp fallback — verified in `AppServiceProvider@register`** (2026-09-28)
- [x] ~~`heavy-analysis` queue listed in `one-inbox-queue.service` ExecStart?~~ **Fixed 2026-10-08 — added to the `--queue=` list after a 20h stuck Deep Analysis.** The systemd unit file lives only on prod — captured below as a durable follow-up.
- [x] ~~**Systemd unit files are not version-controlled.**~~ **Fixed 2026-10-18** — captured all 5 units in `deploy/systemd/` with README. See §9 above.
- [ ] (historical note, kept for context) **Systemd unit files are not version-controlled.** Both `one-inbox-queue.service` and the related `@1..4` + `one-inbox-queue-campaigns` + `one-inbox-whisper` + `one-inbox-reverb` units live only at `/etc/systemd/system/` on prod. Any `--queue=X,Y,Z` change (like the 2026-10-08 `heavy-analysis` fix) is invisible to future greps + will be lost on a server rebuild. **Follow-up**: move canonical versions under `deploy/systemd/` in the repo, add a deploy step to `scp` them to prod when they change.

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
| 2026-10-18 | **Run 9 — Deep Analysis storm uncovered an 18h-dead queue worker.** `one-inbox-queue.service` had been stopped since 2026-10-17 22:59 UTC and never re-enabled. It is the ONLY worker consuming `heavy-analysis`, `comments-ingest`, `comments-send` — @1..4 instances only handle `urgent, default`. Any Deep Analysis a real customer triggered in that window would have silently hung. **Remediation**: `systemctl start one-inbox-queue && systemctl enable one-inbox-queue`. Service now active + enabled for auto-start. Also added to the Run 9 writeup: §7's `capacity:health-check` command doesn't check for dead queue workers — that gap is now the highest-priority follow-up along with the §9 "systemd units in Git" item. |
| 2026-10-18 | **Run 8 — first measured single-message end-to-end latency for AI pipeline.** Single-message sync trace with outbound mocked via Bus::fake. **32.5 s wall time for one message.** The derived "~0.9 msgs/s aggregate" (5 workers × 1/28 s) is a projection, NOT measured concurrent throughput. Open question: whether 5 concurrent reply calls run in parallel at ~28 s each, or whether DB/NaraRouter contention stretches that under real load. A concurrent end-to-end test is the next priority. |
| 2026-10-18 | **Added `webhook_logs_created_at_index`** (migration `2026_10_18_000001`) — fixes the missing index flagged in Run 7. Post-index EXPLAIN: range scan on 153 rows (was 56 412 on the platform-index); timing dropped **143-295 ms → ~1 ms** per query (150-300× faster). Analytics "sort buffer" risk for this access pattern now effectively eliminated. |
| 2026-10-18 | **Extended load-test session (part 2)** — added `load-test:ai-latency` benchmark + direct MySQL analytics-query measurement. AI scoring p50 **0.5 s**, aggregate **23 calls/sec** with 10 concurrent workers, 0 cooldown. MySQL analytics drains **20 concurrent heavy queries in 2 s** — sort buffer did not blow. ~~Revised ceiling in §5.7 to ~8-10 msgs/s~~ **SUPERSEDED later same day by Run 8 — the 8-10 msgs/s number was itself a projection from scoring-call latency × worker count and was wrong by ~10×. Actual measured ceiling is 0.9 msgs/s.** Found missing `created_at` index on `webhook_logs`. All load-test artifacts cleaned up. Total AI spend well under \$1. |
| 2026-10-18 | **First measured load-test session against prod** — ran k6 webhook-ingest (100 VU + 300 VU), homepage (500 VU), marketing-pages-mix (300 VU), and new `load-test:inbound-storm` artisan command (500 / 2 000 / 5 000 synthetic webhooks). Raw results and server-side telemetry in `docs/load-tests/`. Measured ceilings now live in §3 and §8.5. Key findings: (1) `pm.max_children` is actually **20**, not the documented 5 — doc corrected. (2) Signature-rejected webhook ingest ceiling is **98 req/s** CPU-bound; 0 % 5xx even at 300 VUs. (3) Homepage ceiling is **30 req/s** before 0.5 % error rate at 500 VUs. (4) Inbound-storm transport layer drains at **39 jobs/s sustained** with 141 jobs/s enqueue rate. (5) Full AI pipeline — original rough projection was 1.5-5 msgs/s; **actual measured ceiling (Run 8, later same day): 0.9 msgs/s**, 40-150× below the transport ingest layer. (6) Security finding: `throttle:webhooks` is bypassed because Cloudflare's edge IP pool defeats `$request->ip()` — fix via `TrustProxies` + `CF-Connecting-IP`. |
| 2026-10-08 | **UX fixes bundle** — (a) sidebar perf: `User::hasPermission()` was 8 DB queries per sidebar render for non-owner team members, now memoized to 1 (commit `c3cedf3`); (b) /settings/billing ledger + AI chat markdown tables both wrap in `overflow-x-auto` for mobile scroll; AI chat ruleset rewritten to fix "20 → 2\n0" column-crush bug (commit `6181f04`); (c) connections syncing banner converted from persistent inline bar to dismissible toast; (d) error page copy sweep to founder voice ("I'm giving the site a quick tune-up" instead of "We're..."), replaced `support@ot1-pro.com` with `omareltak7@gmail.com` across 12 files (commit `06678eb`); (e) ai-quota banner `grid-column: 2/-1` to stop crushing every page's main column to ~222px (commit `b5aeebd`). |
