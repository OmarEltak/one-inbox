# Bulk Email Campaigns from CSV/XLSX Upload

**Date:** 2026-05-16
**Status:** Approved (decisions Q1=a, Q2=c, Q3=c, Q4=a + tracking/compliance picks confirmed by user)

## Problem

Users want to upload a CSV or Excel sheet of email addresses and run a bulk email campaign against that list — using their own connected email account as the sender.

## Goals (v1)

- Upload `.csv` or `.xlsx`, parse it streaming (low memory).
- Auto-detect header row, let user map columns → `email`, `name`, custom fields.
- Validate emails, dedupe within file, upsert into existing `contacts` table per `(team_id, email)`. Tag with `imported:{filename}`.
- Create an email campaign targeting that tag (or any existing contact filter).
- Compose subject + body with `{{name}}`, `{{email}}`, `{{<custom_field>}}` substitution. Optional AI personalization toggle (reuses existing `ai_personalize` flag + Gemini service stub — generation hook only in v1).
- Pick sender from user's connected email accounts (`pages.platform = 'email'`).
- Throttled send: daily cap (default 200) + 30–60s jitter between sends per campaign, per sender.
- Per-recipient send tracking with retry (3 tries, exponential backoff) and status (`pending`, `sending`, `sent`, `failed`, `bounced`, `opened`, `unsubscribed`).
- Mandatory unsubscribe link → public route → per-team `email_suppressions`. Suppression checked before every send.
- Open tracking via 1x1 pixel.
- Campaign show page with live progress and per-recipient table.

## Non-goals (v1)

- Click tracking / URL rewriting.
- HTML editor (use plain text + variables; HTML can come later).
- Real bounce parsing from IMAP (only SMTP send-time failures are recorded as bounced).
- Send-time windows / timezone scheduling.
- Provider integrations (Resend/Postmark/SES).

## Data Model

### New tables

**`contact_imports`** — header row per upload
```
id, team_id, user_id, filename, original_name, total_rows,
imported_rows, skipped_rows, invalid_rows, tag, status, created_at, updated_at
```

**`campaign_recipients`** — per-send row
```
id, campaign_id, contact_id (nullable), email, name, custom_fields json,
status (pending|sending|sent|failed|bounced|opened|unsubscribed),
attempts, last_error, scheduled_at, sent_at, opened_at, failed_at,
created_at, updated_at
unique (campaign_id, email)
index (campaign_id, status, scheduled_at)
```

**`email_suppressions`** — per-team suppression list
```
id, team_id, email, reason (unsubscribed|bounced|complaint|manual),
campaign_id (nullable), created_at
unique (team_id, email)
```

### Schema changes

**`campaigns`** — add columns (additive, nullable for back-compat):
- `subject` string nullable — email subject
- `sender_page_id` foreign key to `pages` nullable — which connected email account sends
- `daily_cap` unsigned int nullable, default 200
- `jitter_min_seconds` unsigned int, default 30
- `jitter_max_seconds` unsigned int, default 60
- Existing `target_criteria` JSON gets an optional `contact_tag` filter key for tag-based audience.

### Existing usage preserved

- The existing WhatsApp/FB/IG/Telegram campaign flow keeps working (it does not use the new columns).
- The new email type uses `platform = 'email'` and `type = 'broadcast'`.

## Architecture

```
+-------------------+      +------------------+      +-----------------------+
| Livewire Wizard   | ---> | ContactImporter  | ---> | Contact upsert (team) |
| (Upload+Map+Send) |      | + SpreadsheetParser|     | + tagged "imported:.."|
+-------------------+      +------------------+      +-----------------------+
         |
         v
+-------------------+
| Campaign + recip. |  recipient rows pre-created with scheduled_at = now + jitter*n
+-------------------+
         |
         v
+-------------------+      +--------------------+      +--------------------+
| Scheduler (every  | ---> | SendCampaignEmail  | ---> | Mail via per-page  |
| minute via cron)  |      | Job (per recipient)|      | SMTP transport     |
+-------------------+      +--------------------+      +--------------------+
         |                              |
         v                              v
respects daily cap          opens => pixel route updates row
                            unsub => public route inserts suppression
```

### Components

- `App\Services\Email\SpreadsheetParser` — streams `.csv`/`.xlsx` via OpenSpout, yields rows as arrays. Auto-detects header row (first non-empty row). Exposes `preview(int $rows)` and `eachRow(callable)`.
- `App\Services\Email\EmailValidator` — RFC validation + domain check (uses PHP `filter_var` + `egulias/email-validator` already present via Laravel).
- `App\Services\Email\ContactImporter` — given parsed rows, column map, team, filename → upserts Contacts by `(team_id, email)`, merges tags, merges metadata, returns import counts.
- `App\Services\Email\CampaignDispatcher` — given campaign id → builds `campaign_recipients` rows with staggered `scheduled_at` honoring daily cap.
- `App\Services\Email\TemplateRenderer` — renders subject + body with `{{var}}` substitution; appends unsub link + tracking pixel automatically.
- `App\Services\Email\SmtpMailerFactory` — given a `Page` (platform=email), builds a Symfony Mailer transport using its decrypted credentials.
- `App\Jobs\SendCampaignEmailJob(int $recipientId)` — handles one recipient. Re-checks campaign status, suppression, sender activity, then renders + sends. On `Throwable`, increments `attempts`, persists `last_error`, schedules retry with backoff.
- `App\Console\Commands\DispatchScheduledCampaignEmails` — pulled by scheduler every minute; dispatches jobs for recipients where `status='pending' AND scheduled_at <= now()` AND under daily cap.
- `App\Http\Controllers\EmailTrackingController` — `GET /e/o/{recipient}/{sig}` (1x1 pixel), `GET /e/u/{recipient}/{sig}` (unsub confirmation page), `POST /e/u/{recipient}/{sig}` (confirm).

### Livewire components

- `App\Livewire\Campaigns\EmailWizard` — single multi-step component (`step` property): `upload → map → compose → review → launched`.
- `App\Livewire\Campaigns\Show` — campaign detail with progress + per-recipient table.
- Existing `App\Livewire\Campaigns\Index` extended to include an "Email" button alongside the existing "New Campaign" → routes to wizard.

### Routes

- `GET /campaigns/email/new` → `EmailWizard`
- `GET /campaigns/{campaign}` → `Campaigns\Show`
- `GET /e/o/{recipient}/{sig}` → tracking pixel
- `GET /e/u/{recipient}/{sig}` → unsubscribe page (public, no auth)
- `POST /e/u/{recipient}/{sig}` → confirm unsubscribe

## Data Flow

1. **Upload**: user uploads file → Livewire temp storage → `SpreadsheetParser::preview(20)` shows first 20 rows + detected headers.
2. **Map**: user maps detected columns → `email` (required), `name` (optional), any other as `custom_fields[*]`.
3. **Import (commit step)**: `ContactImporter::import()` streams every row, validates email, dedupes, upserts Contact, increments counters. Creates `contact_imports` row.
4. **Compose**: user picks sender email account, writes subject + body, optional AI toggle, sets daily cap.
5. **Review**: shows N recipients, estimated days to complete (`ceil(N / daily_cap)`), warns if list size > sender's known daily limit.
6. **Launch**: creates `campaign` (status=`active`), calls `CampaignDispatcher::schedule($campaign)` which inserts `campaign_recipients` rows with staggered `scheduled_at`, respecting daily cap. Sets `total_contacts`.
7. **Cron tick** (every minute): `DispatchScheduledCampaignEmails` picks due `pending` rows and dispatches `SendCampaignEmailJob` for each (batched, 50 max per tick).
8. **Per-recipient job**:
   - Re-fetch campaign + recipient. If campaign is not `active`, skip.
   - Check `email_suppressions` — if listed, mark `unsubscribed`.
   - Render subject + body with template vars.
   - Append unsubscribe link + tracking pixel.
   - Build per-Page SMTP transport, send.
   - On success: `status='sent', sent_at=now`. Bump campaign `sent_count`.
   - On failure: `attempts++`, `last_error=msg`. If `attempts >= 3`, `status='failed'`. Else release back with backoff (`pow(2, attempts) * 60s`).
9. **Open**: recipient opens email → pixel hit → `opened_at=now`, `status='opened'` if currently `sent`.
10. **Unsubscribe**: recipient clicks link → public page asks confirm → POST inserts suppression and marks recipient `unsubscribed`.

## Validation & Safety

- Email regex via `filter_var(..., FILTER_VALIDATE_EMAIL)`.
- File size cap 10 MB. Row cap 50,000 per upload.
- Signed URLs for pixel + unsub (uses Laravel's `URL::signedRoute`).
- Per-team scoping enforced in every query.
- Mass-assignment: explicit `$fillable` on every new model.
- Decrypt SMTP credentials only inside the send job, never log them.

## Failure Modes

- **SMTP auth fails**: mark recipient `failed`, bubble exception → user sees error count on Show page, can edit sender on the campaign and retry.
- **Sheet has no header / no email column**: wizard blocks at map step with inline error.
- **Duplicate uploads**: same email gets re-tagged + metadata merged; no duplicate Contact.
- **Campaign paused mid-send**: in-flight job sees `status != active` and bails before sending.
- **Queue worker dies**: `pending` rows remain; next cron tick picks them up.

## Testing

Pest feature tests:
- `tests/Unit/SpreadsheetParserTest.php` — parses CSV and XLSX fixtures, detects header.
- `tests/Unit/EmailValidatorTest.php` — accepts valid, rejects invalid.
- `tests/Feature/ContactImporterTest.php` — upserts, tags, counts.
- `tests/Feature/CampaignDispatcherTest.php` — schedules with stagger, respects cap.
- `tests/Feature/SendCampaignEmailJobTest.php` — sends via fake `Mail`, marks sent, retries on failure, suppression check.
- `tests/Feature/EmailTrackingTest.php` — pixel marks opened, unsub inserts suppression and marks recipient.
- `tests/Feature/EmailWizardTest.php` — Livewire flow: upload → map → import → launch.

## Sidebar / Navigation

Email wizard is reached from the existing Campaigns page via a new "Email Campaign" button. No new top-level nav entry.

## Migration / Rollback

All new tables are additive. Campaign schema changes are nullable. Rollback = drop new tables + drop new columns.
