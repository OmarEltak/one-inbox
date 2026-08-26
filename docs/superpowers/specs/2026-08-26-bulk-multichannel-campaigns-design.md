# Bulk Multi-Channel Campaigns (WhatsApp via Wuzapi + Email)

**Date:** 2026-08-26
**Status:** Approved (three-round brainstorm + two independent architectural reviews)
**Extends:** `2026-05-16-bulk-email-import-design.md` (email wizard stays, gets promoted to a sibling of a new WhatsApp wizard)

---

## Problem

Users want to run bulk outbound campaigns to lists of contacts they upload (WhatsApp numbers or email addresses), not just to contacts already in existing conversations. The current `Campaigns\Index` modal only targets `platform_conversation_id` values from prior chats — it has no CSV import path, no cold-list support, and does not surface email as a channel option at all.

WhatsApp goes through **Wuzapi** (self-hosted WhatsApp Web bridge under `docker-compose.wuzapi.yml`, inbound via `WuzapiWebhookController`), not Meta Cloud API. That is materially riskier than Cloud API from a ban-perspective, so rate limits, jitter, warmup, quiet hours, and per-page circuit-breakers are load-bearing safety features, not nice-to-haves.

The system runs on a single VPS with a `database` queue driver and no Horizon. Any new feature must not degrade the p95 latency of the existing `urgent` queue (customer webhooks, inbound message processing, customer-facing AI replies).

## Goals (v1, phased a → b → c)

**Phase a (week 1):**
- New sibling wizard `/campaigns/whatsapp/new` for cold-list WhatsApp campaigns.
- CSV/XLSX upload with column mapping (reuses existing `SpreadsheetParser`).
- Phone normalization to E.164 via `giggsey/libphonenumber-for-php`, with a default-country picker at upload time.
- Optional columns: `name`, `opted_in_at` (encouraged, not required), plus arbitrary custom fields addressable as `{{column_name}}`.
- Test-send: send one message to any operator-specified number, does not consume daily cap, rate-limited to 5/hr/user.
- Jitter (`jitter_min_seconds` / `jitter_max_seconds` range) applied at schedule time as varied `scheduled_at`. Never `sleep()` in a worker.
- Feature flag `teams.features->>'bulk_whatsapp_campaigns'` (default off).
- Add `email` as a platform option in the existing `Campaigns\Index` modal; when a user picks `whatsapp` or `email`, they are routed to the respective sibling wizard.
- New `campaigns` queue with a dedicated systemd worker, isolated from `urgent`.
- Page-circuit-breaker in scheduler: do not dispatch if sender page is disconnected or banned.
- Backpressure gate in scheduler: skip tick if `campaigns` queue depth exceeds threshold.
- Hot-path index (`campaign_recipients (status, scheduled_at)`) verified by `EXPLAIN` against 100k seeded rows before migration is approved.

**Phase b (week 2):**
- Per-page warmup ramp (D1: 50, D2: 100, D3: 250, D4: 500, D5+: 1000) with per-team "aged number, skip warmup" override.
- Atomic daily cap enforcement via `page_send_counters` (`INSERT ... ON DUPLICATE KEY UPDATE sent_count = sent_count + 1`).
- Recipient-local quiet hours (default 9am–9pm) derived from `phone_country` via ISO2→timezone map for single-timezone countries; fall back to sender-page timezone for multi-timezone or unknown countries.
- Unified `contact_suppressions` table (channel-aware), migrated from existing `email_suppressions`.
- `ai-bulk` queue + `PersonalizeCampaignMessageJob` for opt-in AI personalization (uses existing `NaraRouter`). Hard concurrency cap.
- Batch-claim pattern replaces single-row claim in the scheduler.

**Phase c (week 3):**
- Spintax variable rendering (`{Hi|Hey|Hello} {{name}}`), seeded by recipient ID for retry stability.
- Auto-pause on signals: ≥3 blocks in 24h, >20% failure rate over last 100 sends, Wuzapi `session.disconnected|banned` webhook, <1% reply rate after 200 sends.
- `campaign_events` append-only audit table with 90-day retention.
- Nightly `AggregateCampaignEvents` rollup into `campaign_daily_stats`.
- Reply-inbox filter (one click from campaign → inbox filtered to conversations started by this campaign).

## Non-goals (v1)

- Meta Cloud API integration (irrelevant — we use Wuzapi).
- Adaptive warmup (fixed schedule + override covers 95% of cases).
- Redis queue migration (out of scope; database queue is adequate at current volume).
- Horizon (systemd + `queue:work` is enough at current volume).
- A/B split, cost estimator, per-country deliverability heatmap.
- Real bounce parsing from IMAP (only send-time failures recorded).
- Geocoding beyond country code.
- Microservices, separate worker machines, distributed locks.

## Data Model

### New tables

**`page_send_counters`** — atomic daily cap tracking per sender page.
```
page_id            bigint not null
day                date   not null
sent_count         int    not null default 0
PRIMARY KEY (page_id, day)
```
Increment via `INSERT INTO page_send_counters (page_id, day, sent_count) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE sent_count = sent_count + 1`. No SELECT-then-UPDATE.

**`contact_suppressions`** — unified suppression list across channels.
```
id             bigint pk
team_id        bigint not null
channel        string not null      -- 'email' | 'whatsapp'
identifier     string not null      -- normalized email or E.164 phone
reason         string not null      -- unsubscribed|bounced|complaint|manual|blocked
campaign_id    bigint nullable
created_at     timestamp
UNIQUE (team_id, channel, identifier)
INDEX (team_id, channel)
```
Migration path: copy every row from `email_suppressions` into `contact_suppressions` with `channel='email'`. Keep `email_suppressions` readable for one release, then drop.

**`campaign_events`** — append-only business-event audit (phase c).
```
id             bigint pk
campaign_id    bigint not null
recipient_id   bigint nullable
event_type     string not null      -- scheduled|sent|failed|bounced|opened|unsubscribed|paused|resumed
meta           json nullable
created_at     timestamp not null
INDEX (campaign_id, created_at)
INDEX (created_at)                  -- for retention scan
```
90-day retention. Nightly `AggregateCampaignEvents` command rolls day N-1 into `campaign_daily_stats`, deletes day N-90. Only business events are written here. Debug/framework noise goes to Laravel logs.

**`campaign_daily_stats`** — aggregated per-day per-campaign metrics (phase c).
```
campaign_id     bigint not null
day             date not null
sent            int default 0
failed          int default 0
opened          int default 0
replied         int default 0
unsubscribed    int default 0
PRIMARY KEY (campaign_id, day)
```
UI reads this instead of scanning `campaign_events`.

### Schema changes

**`campaigns`** — additive nullable columns:
- `warmup_bypass` bool default false
- `quiet_hours_start` tinyint default 9
- `quiet_hours_end` tinyint default 21
- `respect_recipient_tz` bool default true
- `paused_reason` string nullable
- `use_spintax` bool default true

**`campaign_recipients`** — additive nullable columns + new hot index:
- `phone` string nullable
- `phone_country` char(2) nullable
- `channel` string not null default 'email'
- `INDEX (status, scheduled_at)` — verified by `EXPLAIN` before merge.
- `UNIQUE (campaign_id, channel, COALESCE(email, phone))` — enforced at application layer if MySQL version doesn't support expression indexes cleanly.

**`contact_imports`** — additive:
- `channel` string default 'email'

**`teams`** — additive:
- `features` json (if not already present) — used for feature-flagging.

## Architecture

```
                    ┌─────────────────────────────────┐
                    │           USERS (HTTP)          │
                    └───────────────┬─────────────────┘
                                    │
                              Laravel App
                                    │
              ┌─────────────────────┼───────────────────────┐
              │                     │                       │
       HTTP/Livewire           Webhooks                   Cron
              │                     │                       │
              │                     ▼                       ▼
              │                urgent queue          CampaignScheduler
              │                (existing —           (page circuit-breaker,
              │                 do not touch)         backpressure gate,
              │                     │                 quiet hours check,
              │                     │                 daily cap check,
              │                     │                 batch claim)
              │                     │                       │
              │                     │                       ▼
              │                     │                campaigns queue
              │                     │                       │
              │                     │                       ▼
              │                     │              Send Jobs
              │                     │              SendCampaignEmailJob    (existing)
              │                     │              SendCampaignWhatsAppJob (new)
              │                     │                       │
              │                     │                       ▼
              │                     │              ChannelSender
              │                     │              ├── EmailSender (SMTP)
              │                     │              └── WhatsAppSender (Wuzapi — SOLE call site)
              │                     │
              │                     ▼
              │              conversational-ai
              │              (urgent queue)          ai-bulk queue
              │              SendAiResponse          PersonalizeCampaignMessageJob
              │              (customer-facing)       (campaign personalization only)
              │                                              │
              │                                              ▼
              │                                     rendered_message written
              │                                     back to campaign_recipients
              │
              └───────────────────────┬────────────────────
                                      │
                                      ▼
                                    MySQL
                                      │
                                    Redis (cache, locks, rate-limit counters)
```

### Queue topology

| Queue           | Purpose                                                    | Worker |
|-----------------|------------------------------------------------------------|--------|
| `urgent`        | Inbound webhooks, customer AI replies (existing)           | 1      |
| `default`       | Everything unclassified (existing)                         | 1      |
| `campaigns`     | `SendCampaignEmailJob`, `SendCampaignWhatsAppJob`          | 1 (later 2) |
| `ai-bulk`       | `PersonalizeCampaignMessageJob`                            | 1 (hard cap) |

systemd units (production VPS):
```
one-inbox-queue-urgent      → --queue=urgent    --tries=3 --timeout=90
one-inbox-queue-default     → --queue=default   --tries=3 --timeout=180
one-inbox-queue-campaigns   → --queue=campaigns --tries=3 --timeout=60 --sleep=5
one-inbox-queue-ai-bulk     → --queue=ai-bulk   --tries=2 --timeout=45 --sleep=10
```

### Components

- `App\Services\Campaigns\SpreadsheetParser` — existing; extended to expose row schema for phone columns.
- `App\Services\Campaigns\PhoneNormalizer` — wraps `libphonenumber-for-php`. `normalize(string $raw, string $defaultCountry): {e164, country_iso2}` or throws `InvalidPhoneException`.
- `App\Services\Campaigns\ContactImporter` — interface.
  - `EmailContactImporter` (existing).
  - `PhoneContactImporter` (new) — validates via `PhoneNormalizer`, upserts contacts by `(team_id, phone)`, tags `imported:{filename}`, returns counts.
- `App\Services\Campaigns\CampaignScheduler` — channel-agnostic. Given a campaign, produces `campaign_recipients` rows with staggered `scheduled_at` respecting: daily cap, warmup ramp, quiet hours, jitter range.
- `App\Console\Commands\DispatchScheduledCampaignMessages` — cron every minute. Chain of gates (documented below). Dispatches per-channel send jobs.
- `App\Jobs\SendCampaignWhatsAppJob` — one recipient. Re-checks status, suppression, page state. Renders. Calls `WhatsAppSender::send()`. Updates recipient + counter atomically.
- `App\Services\Wuzapi\WhatsAppSender` — **THE ONLY** Wuzapi call site for campaigns. Enforces "no third parallel send path" invariant (see project memory: `whatsapp_parallel_send_paths`).
- `App\Services\Campaigns\SpintaxRenderer` — `render(string $template, int $seed): string`. Seeded by recipient ID for retry stability.
- `App\Services\Campaigns\RecipientTimezoneResolver` — `resolve(string $countryIso2, string $senderTz): string` — returns primary timezone for known single-tz countries, otherwise sender timezone.
- `App\Services\Campaigns\CampaignHealthMonitor` — auto-pause signal aggregator (phase c). Reads recent `campaign_events`, decides pause.

### Livewire

- `App\Livewire\Campaigns\WhatsAppWizard` — steps: `upload → map → compose → test → review → launched`. Mirrors existing `EmailWizard` shape.
- `App\Livewire\Campaigns\Index` — modal gets:
  - `email` added to platform options.
  - CSV upload region gated by `@if(in_array($this->platform, ['whatsapp', 'email']))`.
  - "Next" for whatsapp/email routes to the respective sibling wizard with prefilled name + page.
  - Existing conversation-audience flow preserved for fb/ig/telegram.

### Routes

```
GET  /campaigns/whatsapp/new          → WhatsAppWizard
POST /campaigns/{campaign}/test-send  → CampaignController@testSend (throttle: 5/hr/user)
GET  /health/metrics                  → HealthMetricsController (internal; drives backpressure)
```
Existing email routes unchanged.

## Data Flow (WhatsApp campaign)

1. **Upload**: user uploads CSV/XLSX + picks default country → `SpreadsheetParser::preview(20)` shows headers + first 20 rows.
2. **Map**: user maps columns → `phone` (required), `name` (optional), `opted_in_at` (optional), rest as custom fields.
3. **Import**: `PhoneContactImporter::import()` streams rows, normalizes via `libphonenumber`, dedupes, upserts contacts by `(team_id, phone)`, records counts in `contact_imports`. Invalid rows offered as downloadable CSV.
4. **Compose**: user picks sender page (WhatsApp), writes template, sets jitter min/max, chooses quiet hours (default 9–21), toggles AI personalization + spintax (phase b/c).
5. **Test**: operator sends one message to a specified number using either a chosen sample row or manually filled variables. Result shown inline.
6. **Review**: shows N recipients, estimated days (`ceil(N / effective_daily_cap)`), warmup warnings, cost preview if AI enabled.
7. **Launch**: creates campaign (status `active`). `CampaignScheduler::schedule($campaign)` inserts `campaign_recipients` with staggered `scheduled_at` respecting quiet hours + jitter + daily cap for future days.
8. **Cron tick** (every minute): `DispatchScheduledCampaignMessages` runs the gate chain:

   ```
   Gate 0 — Backpressure:      campaigns_queue_depth < 500  else skip tick
   Gate 1 — Campaign state:    status = 'active'            else skip
   Gate 2 — Page state:        page NOT in {disconnected, banned}  else pause campaign
   Gate 3 — Quiet hours:       recipient local time within window  else pushed forward
   Gate 4 — Daily cap:         page_send_counters[today] < cap     else defer to tomorrow
   Gate 5 — Claim work:        phase a → LIMIT 1 with row lock (simple, correct at current volume)
                               phase b → LIMIT 50 batch claim (UPDATE ... SET status='queued' ... LIMIT 50)
   Gate 6 — Dispatch:          per-row → SendCampaignWhatsAppJob on `campaigns` queue
                               phase a hard ceiling: max 50 dispatches per tick regardless
   ```

9. **Per-recipient job** (`SendCampaignWhatsAppJob`):
   - Re-fetch recipient with row lock; bail if status ≠ `queued`.
   - Re-check campaign + page state + suppression (they may have changed since scheduling).
   - Render template (spintax → variables → optional AI).
   - Call `WhatsAppSender::send()`.
   - On success: `status='sent', sent_at=now`, atomic increment `page_send_counters`, append `campaign_events` row.
   - On transient failure: `attempts++`, release with `pow(2, attempts) * 60s` backoff.
   - On permanent failure: `status='failed'`, log to `campaign_events`.
   - On Wuzapi cap-exceeded: not a failure, push `scheduled_at += 1 day`, back to `pending`.

10. **Auto-pause** (phase c): `CampaignHealthMonitor` runs on a separate cron, sets `campaigns.status='paused'` + `paused_reason` if any signal trips.

## Resource Control (single-VPS constraints)

**Architectural invariant:**
> No background workload may cause `urgent` queue p95 latency to exceed 2 seconds.

**SLO panel** (measured, exported to `/health/metrics`):
| Metric                    | Target      | Enforcement                        |
|---------------------------|-------------|------------------------------------|
| `urgent_queue_p95`        | < 2000 ms   | HARD — auto-pauses new dispatch    |
| `webhook_response_p95`    | < 500 ms    | HARD — auto-pauses new dispatch    |
| `campaigns_queue_depth`   | < 500       | HARD — drives scheduler backpressure |
| `ai_bulk_queue_depth`     | < 200       | HARD — drives ai-bulk backpressure |
| `http_p95`                | < 1000 ms   | SOFT — logged                      |
| `db_query_p95`            | < 100 ms    | SOFT — logged                      |

**Banned patterns:**
- `sleep()` inside a worker. Jitter = varied `scheduled_at`, never runtime sleep.
- Dispatching to `urgent` from any campaign code path.
- Writing to `campaign_events` for framework/debug noise. Only business state transitions.
- Direct Wuzapi calls from campaign code. Every campaign send routes through `WhatsAppSender`.
- SELECT-then-UPDATE on `page_send_counters`. Must be atomic upsert.

## Validation & Safety

- Phone normalization enforced at upload (invalid rows quarantined, downloadable).
- File size cap 10 MB. Row cap 50,000 per upload.
- Signed URLs for any public endpoints.
- Per-team scoping in every query (never trust request-supplied `team_id`).
- Test-send throttle: 5 requests / hour / user.
- Wuzapi credentials decrypted only inside `WhatsAppSender::send()`, never logged.
- `opted_in_at` timestamp stored when provided (defensive record for ban-appeal).
- Feature flag gate default OFF; enabled per-team via super-admin.

## Failure Modes

| Failure                          | Handling                                            |
|----------------------------------|-----------------------------------------------------|
| Wuzapi HTTP 5xx                  | Transient. Backoff retry (max 3).                   |
| Wuzapi session disconnected      | Auto-pause all campaigns on that page.              |
| Wuzapi session banned            | Auto-pause + mark page banned + notify team owner.  |
| Phone invalid at send time       | Mark recipient failed, do not retry.                |
| Campaign paused mid-send         | In-flight job bails on status re-check.             |
| Queue worker dies                | `pending` rows remain, next tick picks them up.     |
| `campaigns` queue depth > 500    | Scheduler skips tick, logs, alerts at 3 in a row.   |
| Suppression added mid-campaign   | Send job re-checks, marks `unsubscribed`, bails.    |
| Daily cap hit mid-day            | Remaining eligible rows deferred to next day.       |
| `page_send_counters` write race  | Atomic upsert prevents.                             |

## Testing (Pest, 80%+ coverage)

**Unit:**
- `SpintaxRendererTest`, `PhoneNormalizerTest`, `RecipientTimezoneResolverTest`.

**Feature:**
- `PhoneContactImporterTest` — normalize, dedupe, tag, invalid row quarantine.
- `CampaignSchedulerWarmupTest` — respects D1/D2/D3 caps, honors override.
- `CampaignSchedulerQuietHoursTest` — pushes `scheduled_at` past quiet windows, handles multi-tz fallback.
- `CampaignSchedulerBackpressureTest` — skips tick when queue depth > threshold.
- `CampaignSchedulerPageCircuitBreakerTest` — does not dispatch when page disconnected.
- `SendCampaignWhatsAppJobTest` — gate stack: paused campaign bails, suppression hits, cap exceeded requeues to tomorrow, transient failure retries, permanent failure marks failed.
- `WhatsAppSenderTest` — mocked Wuzapi; verifies single call site; auth failure → transient.
- `WhatsAppWizardTest` — full Livewire flow: upload → map → import → test → launch.
- `TestSendThrottleTest` — 5/hr enforcement.
- `ContactSuppressionsMigrationTest` — email_suppressions rows preserved.

**Performance gate (blocks phase-a merge):**
- Seed 100k `campaign_recipients` rows, run dispatcher query, `EXPLAIN` must show:
  - Uses `(status, scheduled_at)` index.
  - `rows` estimate < 200.
  - No `Using filesort`.

## Rollout

**Phase a (week 1):** feature flag on for OT1 team only. Dogfood.
**Phase b (week 2):** flag on for 3–5 volunteer teams.
**Phase c (week 3):** flag default on for all teams.

## Migration / Rollback

All new tables additive. All column additions nullable with defaults. `email_suppressions` is copied (not moved) into `contact_suppressions` in phase b; the old table stays readable for one release, dropped in a follow-up.

Rollback per phase:
- Drop new tables (`page_send_counters`, `contact_suppressions`, `campaign_events`, `campaign_daily_stats`).
- Drop added columns.
- Remove systemd units for `campaigns` and `ai-bulk` queues.
- Feature flag OFF disables all new UI without a code deploy.

## Open questions (for phase b/c refinement, not gating phase a)

- What Wuzapi webhook events actually surface `blocked` / `reported` signals? (`WuzapiWebhookController` inspection required before phase-c auto-pause code.)
- Do we need per-country warmup schedules? (Some countries are more aggressive with bans — Egypt appears more forgiving than KSA anecdotally.)
- Do we ever want to send WhatsApp campaigns via Meta Cloud API to teams that have both a Wuzapi and a Cloud API connection? (Currently: no. Wuzapi only.)

## References

- Prior spec: `docs/superpowers/specs/2026-05-16-bulk-email-import-design.md`
- Project memory: "WhatsApp has two parallel send paths" — new WhatsApp bulk send MUST go through `WhatsAppSender`, never become a third path.
- Architecture pin #11 (`CLAUDE.md`): blog-post quality bar (unrelated but shows the "one place for a discipline" pattern this spec follows for `WhatsAppSender`).
- Two independent architectural reviews (GPT-5 + Claude Opus 4.7) — see this session's transcript for the full back-and-forth that produced the resource-control section.
