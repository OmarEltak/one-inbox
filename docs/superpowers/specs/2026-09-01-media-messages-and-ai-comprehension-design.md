# Media Messages & AI Comprehension — Design Spec

**Date:** 2026-09-01
**Branch:** `feat/media-messages-and-ai-comprehension`
**Status:** Draft — pending user review

## 1. Problem

Today, when a customer sends an image or voice note on WhatsApp (and other platforms), the inbox at `/inbox` renders it as the literal string `[audio]` or `[img]`. The human agent can't see or listen to the media, and the AI responder is blind to it. Competitors like respond.io show media inline and let AI understand it — table-stakes for a customer-service inbox.

## 2. Goals

- Display images and audio inline in `/inbox` for both directions (inbound + outbound).
- Let human agents send images and voice notes from the composer.
- AI understands images (via NaraRouter's vision-capable models).
- AI understands audio (via Groq Whisper primary → self-hosted whisper.cpp fallback).
- WhatsApp first (Cloud API + Evolution API QR), then Meta/IG/Telegram/Email use the same pipeline.
- Zero incremental cost at current scale ($0/mo Groq free tier + self-hosted whisper.cpp).

## 3. Non-goals (deferred)

- Video messages (rendering placeholder only, no AI comprehension).
- Documents/PDFs, location messages, contact cards, stickers.
- AI *generating* images or audio replies.
- Real-time transcription streaming (async is fine).

## 4. Architecture

Three isolated subsystems with clear boundaries:

### 4.1 Subsystem A — Media Storage

Pure storage layer. Knows nothing about platforms or AI.

**Files:**
- `app/Services/Media/MediaStorage.php` — `store()`, `streamUrl()`, `delete()`
- `app/Models/MediaAsset.php` — Eloquent model
- `database/migrations/xxxx_create_media_assets_table.php`
- `routes/web.php` — `GET /media/{ulid}?sig=...` (signed URL)

**Schema:**
```
media_assets:
  id                    ulid PK
  team_id               foreign key
  disk                  string        (e.g. 'local', 's3' — future)
  path                  string        (relative path on disk)
  original_filename     string nullable
  mime_type             string
  size_bytes            unsigned int
  kind                  enum('image','audio','video','document')
  duration_seconds      unsigned int nullable  (audio/video)
  checksum_sha256       string(64)             (unique per team_id — dedup within tenant only, never cross-team)
  metadata              json nullable          (dimensions, codec, etc.)
  created_at            timestamp
```

**Path layout:** `storage/app/media/{team_id}/{yyyy}/{mm}/{ulid}.{ext}`

**Signed URLs:** 7-day expiry, HMAC-signed via Laravel's `URL::temporarySignedRoute`. UI regenerates on hover/click if expired.

**Storage disk:** local (`storage/app/media` symlinked via `storage:link`). Abstracted via Laravel `Storage` facade so swap to S3 later requires only config change. At current 91 GB free + ~3 MB/min WhatsApp audio + ~200 KB/image, ~45 months of runway before object storage becomes necessary.

**Dedup:** if incoming media checksum already exists **for the same `team_id`**, reuse existing `MediaAsset` row rather than duplicate on disk. Dedup is intentionally scoped to a single team to avoid leaking media (or cached AI descriptions) across tenant boundaries.

Composite unique index: `(team_id, checksum_sha256)`.

### 4.2 Subsystem B — Platform Media Ingest/Egress

Platform-specific HTTP integrations. Extends existing `app/Services/Platforms/*Platform.php` files.

**Interface additions (each platform implements):**
```
downloadInboundMedia(webhookPayload): MediaAsset
uploadOutboundMedia(mediaAsset): platformMediaId
sendMediaMessage(chat, mediaAsset, caption): platformMessageId
```

**Platform-specific notes:**
- **WhatsApp Cloud API:** two-step (GET media URL by ID → GET media bytes with auth header).
- **WhatsApp Evolution (QR):** base64 in webhook payload → decode → store.
- **Meta Messenger:** `attachments[].payload.url` in webhook, direct download.
- **Instagram:** same as Meta.
- **Telegram:** `getFile` API → download from `https://api.telegram.org/file/bot<token>/<path>`.
- **Email:** MIME attachments already parsed by mail package, adapt to `MediaAsset`.

**Outbound size limits (client-side enforced before upload):**
- Image: 5 MB max, JPEG/PNG pass-through.
- Audio: 16 MB max, converted to `.ogg` Opus server-side via ffmpeg (WhatsApp native format).

### 4.3 Subsystem C — AI Media Comprehension

Turns media into text the AI can reason about.

**Files:**
- `app/Services/Ai/VisionRouter.php` — capability-aware NaraRouter sub-select
- `app/Services/Ai/TranscriptionRouter.php` — Groq primary + whisper.cpp fallback
- `app/Services/Ai/Transcription/GroqDriver.php`
- `app/Services/Ai/Transcription/WhisperCppDriver.php`
- `app/Jobs/TranscribeAudio.php` — dispatched to `transcription` queue
- `app/Jobs/DescribeImage.php` — dispatched to `default` queue (I/O-bound)

**Vision routing:**
`VisionRouter::describe($asset)` iterates the NaraRouter fallback chain, skips text-only models (hardcoded capability map, updated when chain changes), and sends the first vision-capable model a multipart request with the signed media URL + a system prompt: "Describe this image for a customer-service AI. Note any product, defect, receipt, screen, or text visible."

Result cached in `media_assets.metadata->'ai_description'` — vision call happens once per image regardless of how many times AI needs to reference it.

**Transcription routing (state machine per driver, stored in Redis):**

```
State: healthy → try driver
State: cooling (rate-limited)  → skip driver for N seconds
State: circuit-open (failing)  → skip driver for N minutes, then half-open

Circuit breaker rules (per driver):
- 3 failures in 60s → open for 5 minutes
- After 5 min → half-open (1 request through)
- Half-open success → close (healthy)
- Half-open fail → open again for 5 min
```

**TranscriptionRouter flow:**
```
1. Check GroqDriver circuit state — skip if open/cooling
2. Call GroqDriver.transcribe(asset) with 5s timeout
   - success → cache result in media_assets.metadata, return text
   - 429    → mark cooling 60s, fall through
   - other  → increment failure counter, fall through
3. Call WhisperCppDriver.transcribe(asset) with 60s timeout
   - success → cache result, return text
   - fail   → return null, message body stays as [voice note], alert email
```

**Whisper.cpp setup:**
- Binary: `/usr/local/bin/whisper.cpp` (built from source during install)
- Model: `medium` (~2 GB RAM, ~40s per 30s Egyptian Arabic clip)
- Dedicated systemd service: `one-inbox-whisper.service`
- Consumes `transcription` queue only, `--queue=transcription`, concurrency=1
- `CPUQuota=80%`, `Nice=10` — kernel-enforced isolation from PHP-FPM and default workers

**Per-team rate limit:** max 5 in-flight `TranscribeAudio` jobs per team (Redis counter). Extra jobs wait in queue.

## 5. Queue topology

| Queue | Workers | Purpose | CPU character |
|---|---|---|---|
| `default` | 4 (existing, unchanged) | text messages, AI replies, media downloads, outbound sends, image AI description | I/O-bound |
| `transcription` | 1 (new) | whisper.cpp jobs only | CPU-bound |

**Rationale:** whisper.cpp pins a core for ~40s. If it shared the `default` queue, one job would starve the main workers. Isolation guarantees text/image traffic is never blocked by audio backlog. Under 100 simultaneous voice notes, text/image customers see zero latency impact; audio users are served serially at ~1-2s per note when Groq is healthy, ~40s per note during Groq outage.

## 6. Data flow diagrams

### 6.1 Inbound WhatsApp voice note

```
WA webhook
  → MetaWebhookController@handle
    → ProcessIncomingMessage job (queue: default)
      → WhatsappPlatform::downloadInboundMedia(payload)
        → MediaStorage::store(binary, team, kind='audio')
          → returns MediaAsset row
      → Message row saved (media_asset_id set, body='[voice note]')
      → Reverb broadcast → inbox UI renders <audio controls>
      → if Team::canDispatchAi():
          dispatch TranscribeAudio(message) → transcription queue
            → TranscriptionRouter::transcribe(asset)
               (Groq 1-2s OR whisper.cpp 40s)
            → Message::update(body = transcribed text)
            → Reverb broadcast → agent sees transcription
            → dispatch SendAiResponse(message) → default queue
              (normal AI flow, treats it as text-with-audio-origin)
```

### 6.2 Outbound voice note from agent

```
Agent hold-to-record in composer
  → browser MediaRecorder API captures .webm/opus
  → POST /api/media/upload (multipart)
    → MediaStorage::store(binary, team, kind='audio')
    → ffmpeg convert .webm → .ogg opus (async job on default queue)
    → returns MediaAsset ULID
  → Livewire component calls SendPlatformMessage(chat, mediaAsset, caption)
    → default queue
      → WhatsappPlatform::uploadOutboundMedia(asset) → WA media_id
      → WhatsappPlatform::sendMediaMessage(chat, mediaAsset)
      → Message row saved
      → Reverb broadcast → agent's bubble updates 'sending' → 'sent'
```

### 6.3 Inbound image

```
WA/Meta/IG webhook
  → ProcessIncomingMessage
    → downloadInboundMedia → MediaStorage::store
    → Message saved (body='[image]' or caption if present)
    → Reverb → UI renders thumbnail with lightbox
    → if canDispatchAi():
        dispatch DescribeImage(message) → default queue
          → VisionRouter::describe(asset)
             (iterates chain, first vision-capable model wins)
          → cache description on media_asset.metadata.ai_description
          → dispatch SendAiResponse(message) with description injected
            into system prompt as: "Customer sent an image showing: {desc}"
```

## 7. Error handling

| Failure | Response |
|---|---|
| Media download from platform fails | Retry 3x with exponential backoff. On final failure: store message with body `[media unavailable]`, alert email. Do NOT block AI dispatch chain — AI simply lacks the media context. |
| MediaStorage disk write fails | Retry 1x. On failure: job → `failed_jobs`, alert email. |
| Groq 429 rate limit | Mark `groq` cooling 60s in Redis, immediately fall through to whisper.cpp. |
| Groq 5xx or timeout > 5s | Increment failure counter. 3 in 60s → circuit open 5 min. Fall through to whisper.cpp. |
| whisper.cpp hangs > 60s | Laravel job `$timeout=60` fires (SIGTERM to child process), then SIGKILL if unresponsive. Job → `failed_jobs`. Store `[voice note — transcription unavailable]`. Alert email. Audio player still visible in UI. |
| whisper.cpp OOM / crash | systemd auto-restart (Restart=on-failure, RestartSec=5s). Current job → `failed_jobs`. Alert email. |
| Vision model returns 400 | Try next vision-capable in chain. If chain exhausted: AI reply generated without image context (caption still used if present). |
| Signed URL expired | UI regenerates on hover/click via AJAX (`GET /media/{ulid}/refresh-url`). No user-visible break. |
| Per-team rate limit exceeded (>5 in-flight whisper jobs) | Job stays in queue, delayed. Not a failure, just backpressure. |

**Alert email destination:** `NARAROUTER_ALERT_EMAIL` env var (currently `omareltak7@gmail.com`), reused — same channel Omar already monitors.

## 8. Feature flags (kill switches, all default ON)

Stored in `.env`, read by config, cached with `config:cache`:

| Flag | Effect when OFF |
|---|---|
| `MEDIA_INGEST_ENABLED` | Media rendered as `[audio]`/`[image]` placeholders (current behavior). Nothing else affected. |
| `VISION_ENABLED` | `DescribeImage` job no-ops. AI reply generated without image context. |
| `TRANSCRIPTION_ENABLED` | `TranscribeAudio` job no-ops. AI reply generated without audio context. |
| `TRANSCRIPTION_GROQ_ENABLED` | Skip Groq entirely, go straight to whisper.cpp. |

These flags let us disable any subsystem in prod without a code deploy — critical for a solo-dev safety net.

## 9. Testing strategy

| Layer | Coverage |
|---|---|
| **Unit** | `MediaStorage` (mocked filesystem, dedup logic). `TranscriptionRouter` (mocked HTTP + mocked whisper binary, all circuit-breaker state transitions). `VisionRouter` (mocked NaraRouter chain iteration, verifies text-only models are skipped). `GroqDriver` and `WhisperCppDriver` (mocked I/O). |
| **Feature** | `POST /api/media/upload` returns 200 + MediaAsset ULID. Inbound WA webhook with audio payload → end-to-end job chain resolves, message row has `media_asset_id`. Composer voice recording upload → outbound send job dispatched with correct WA media_id. |
| **Integration** | Real `whisper.cpp` against fixtures: `tests/fixtures/audio/arabic-10s.ogg` and `english-10s.ogg`, asserts transcription contains expected keywords. Marked `@group=slow`, skipped in CI, run manually before prod deploy. |
| **Manual** | One-time sanity: real Groq call against fixtures, eyeball transcription quality. Recorded in ADR, not a repeated test. |

**Coverage target:** 80%+ on Subsystem A + C. Subsystem B is thin platform-adapter code, tested at the feature layer.

## 10. Rollout plan

Phased rollout, each phase deployable independently:

| Phase | Scope | Duration |
|---|---|---|
| **Phase 1** | Subsystem A + Subsystem B for WhatsApp only. Inbound + outbound rendering. No AI yet. Ship. Verify agents can see and send media on 1-2 real conversations. | ~1 day |
| **Phase 2** | Subsystem C VisionRouter. Ship. Verify AI describes an image correctly on a test conversation. | ~2 hrs |
| **Phase 3a** | Install whisper.cpp + ffmpeg on VPS. Deploy `one-inbox-whisper.service`. Deploy `TranscribeAudio` job + `TranscriptionRouter` (registered with `WhisperCppDriver` only in this phase). AI transcription live via local only. Verify on Arabic + English clips. | ~1 day |
| **Phase 3b** | Register `GroqDriver` on the existing `TranscriptionRouter` (Groq becomes the first driver tried, whisper.cpp remains as the fallback registered in 3a). Circuit breaker logic activates. Flip `TRANSCRIPTION_GROQ_ENABLED=true`. Verify latency drops from ~40s to ~2s on happy path. Kill-switch: flip the env flag back to `false` and only whisper.cpp is tried. | ~3 hrs |
| **Phase 4** | Extend Subsystem B to Meta/IG/Telegram/Email. Same platform interface, same media pipeline. | ~0.5 day |

Total: ~3 days of solo-dev work spread across the phases, safely committable at each phase boundary.

## 11. Privacy & compliance

**New third-party data flow:** customer audio → Groq (US-based). Text data has already been flowing to NaraRouter for months, so this is category-consistent but audio-specific and worth calling out.

Actions:
- Update `/privacy` page: add "Audio messages may be processed by Groq for transcription. Content is not stored by Groq per their terms."
- Update `docs/ARCHITECTURE.md` §12 (provider list) with Groq + link to their privacy terms.
- Add opt-out at the `team` level (`teams.audio_transcription_enabled` boolean, default `true`). If a team disables it, audio comes through as `[voice note]` and AI ignores it — human handles manually.

## 12. Rollback strategy

Each phase has a specific rollback:

| Phase | Rollback |
|---|---|
| 1 | `MEDIA_INGEST_ENABLED=false` → placeholder rendering resumes. Media assets stay on disk (harmless). |
| 2 | `VISION_ENABLED=false` → AI ignores images. |
| 3a/3b | `TRANSCRIPTION_ENABLED=false` → AI ignores audio. Stop `one-inbox-whisper.service` if RAM pressure. |
| 4 | Per-platform flags (`MEDIA_INGEST_META_ENABLED`, etc.) if needed — probably not necessary since pipeline is proven by then. |

Schema changes are additive-only (new `media_assets` table + new nullable column `messages.media_asset_id`). No destructive migrations.

## 13. VPS impact

- Install: `whisper.cpp` binary + `medium` model (~2 GB disk), ffmpeg via apt.
- New systemd unit: `one-inbox-whisper.service` (1 worker, `CPUQuota=80%`, `Nice=10`).
- Steady-state RAM growth: ~500 MB (whisper binary loaded on demand, model page-cached).
- Disk growth: ~2 GB (whisper model) + ~2 GB/mo user media at current scale.
- New outbound network: Groq API (`api.groq.com` HTTPS).
- No new inbound ports.
- Redis usage: minor (circuit breaker keys + per-team rate limit counters).

## 14. Open items (nothing blocking implementation)

- Confirm Groq's exact free-tier rate limit at time of implementation (their docs are the source of truth; my earlier "20-40 rpm" was from memory).
- Decide voice-note UI polish: waveform vs plain HTML5 audio bar. Default: plain HTML5 for Phase 1, waveform (WaveSurfer.js) as post-launch polish if it feels needed.
- Decide image lightbox library: use existing project-included one if any (grep during implementation), else vanilla CSS modal to avoid a dependency.
