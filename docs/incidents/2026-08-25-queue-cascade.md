# Incident: Queue worker SIGKILL cascade silently dropped user sends & inbound messages

**Date:** 2026-08-25
**Severity:** Critical (would have been P0 with real user load)
**Duration:** ~48 hours of degraded / dropped WhatsApp traffic before detection
**Detected by:** User visual inspection ("my messages don't appear")
**Should have been detected by:** monitoring on `failed_jobs` count or worker restart count. Neither existed.

## Symptom

1. Messages composed in the OT1 inbox UI (`/inbox?pageId=18`) appeared in the local thread but never reached WhatsApp.
2. Inbound WhatsApp messages from `ahmed.mamdouh120456@gmail.com` and others never appeared in OT1.
3. Symptom was intermittent — AI-generated replies still went out, but human sends and webhook processing were silently dropped.

## Root cause

`App\Jobs\ScoreLeadJob` was written with no `$timeout` override, so it inherited Laravel's 60 s default. Its handler calls `AiProviderInterface::scoreMessage()` which routes through NaraRouter and can chain 2-3 provider fallbacks — regularly exceeding 60 s wall time.

Laravel's timeout mechanism sends `SIGKILL` to the entire PHP worker process — not just the offending job. Systemd (`one-inbox-queue.service`) then restarts the worker. Any job that was concurrently in the pipeline (`SendPlatformMessage`, `ProcessIncomingMessage`) was terminated mid-flight and never marked failed, never retried, never surfaced.

Because all jobs share the single `default` queue, a slow non-critical job (lead scoring) sat in front of user-critical jobs (sends, webhooks). Head-of-line blocking + SIGKILL cascade = silent data loss.

## Evidence gathered

- `failed_jobs`: 10+ `ScoreLeadJob` entries in a single hour, all `Illuminate\Queue\TimeoutExceededException`.
- `journalctl -u one-inbox-queue`: worker restart counter at **38** in ~3 hours. Exit reason: `Main process exited, code=killed, status=9/KILL`.
- `jobs` table: 253 pending backlog. Job 9986 (`SendPlatformMessage` for message id 1593, Omar's "test" at 08:09:17) had `attempts=0` three hours later. Its target message row: `platform_message_id = NULL`, `metadata = NULL` — proving the dispatch never reached the Wuzapi gateway.
- Server memory: 6.6 GiB free. **Not** OOM. Purely a job-level timeout cascade.

## Fix shipped

- **Commit `e64c1f2`** — `public int $timeout = 300;` on `ScoreLeadJob`. AI call can now complete without triggering SIGKILL.
- `queue:restart` broadcast + `systemctl restart one-inbox-queue` picked up the new setting.
- `queue:retry all` requeued the 30 failed `ScoreLeadJob` entries so they succeed on the new timeout.

## Why it wasn't caught earlier

1. **No alert on `failed_jobs` count.** Would have paged at failure #5.
2. **No alert on systemd `RestartCount`.** Would have paged when the worker restarted 6+ times in an hour.
3. **`SendPlatformMessage` has no UI-visible failure contract.** The Livewire component optimistically writes the `messages` row and returns success to the UI regardless of dispatch outcome. There is no polling that flips the message to "failed" if the job never runs.
4. **All jobs share the `default` queue.** No isolation between "must-run-fast user-facing" and "background AI scoring."
5. **Sessions have been shipping features on a queue that has been silently degrading.** Nobody was watching the queue depth or failure rate.

## The rule going forward (candidate CLAUDE.md pin)

> **Every job that calls an external network service (AI provider, HTTP API, WhatsApp gateway, SMTP) MUST declare `public int $timeout` explicitly.** Default = 300 for AI/gateway calls, 60 for HTTP APIs with strict SLAs. Jobs without an explicit timeout will silently take down the worker under load. This has already shipped one silent-drop incident (2026-08-25).

## Follow-ups (not shipped this session)

Ordered by leverage:

1. **Flare alert on `failed_jobs >= 5 in 1h`** and **worker `RestartCount >= 5 in 1h`.** 15 minutes of work. Would have caught this at failure #5, not #38.
2. **Dedicated queues.** `sends` and `webhooks` on their own worker; `ai_background` (score, classify, summarize) on a separate worker. `->onQueue('sends')` on `SendPlatformMessage` and `ProcessIncomingMessage`; `->onQueue('ai_background')` on `ScoreLeadJob`. Add second systemd unit `one-inbox-queue-background` scoped to that queue only. Head-of-line blocking becomes impossible.
3. **Audit every job in `app/Jobs/` for an explicit `$timeout`.** Add the ones missing it. Add a PHPStan/Pest rule that fails CI if a `ShouldQueue` class lacks `$timeout`.
4. **UI feedback on send failure.** After N seconds unprocessed, flip the message row to `failed` and render red in the inbox with a retry button. Prevents "sent to /dev/null" as a class.

## What a real user would have seen

Every human-typed WhatsApp reply from every team on the platform: silently discarded. Every inbound WhatsApp message: silently discarded. The customer thinks the operator is ignoring them. The operator thinks the customer never wrote back. Support tickets, refund requests, brand damage — all downstream. **Zero customers today made this a rehearsal instead of a launch-day disaster.**
