# Agent Lanes

Two Claude Code sessions are working on this repo concurrently. To avoid
collisions and confusing "reverts" from switching branches inside one shared
working tree, we run them in separate git worktrees.

## Layout

| Session | Working directory | Branch | Focus |
|---|---|---|---|
| Campaigns session | `C:\Users\NanoChip\Herd\one-inbox`      | `feat/bulk-whatsapp-campaigns-phase-a` | Bulk WhatsApp Campaigns — phone normalization, contact importer, dispatcher, tests |
| Ops / AI session   | `C:\Users\NanoChip\Herd\one-inbox-main` | `main`                                 | AI provider chain, queue infra, WhatsApp send/receive fixes, prod ops, roadmap items from open list |

## Rules

1. **Do not `git checkout <other-lane's branch>` inside your worktree.** Your
   worktree is pinned. Switch branches only if you have a clear reason and
   coordinate here first.
2. **Files owned by the other lane are off-limits.** Working list:
   - Campaigns lane owns: `app/Models/Campaign*.php`, `app/Models/CampaignRecipient.php`, `app/Jobs/*Campaign*.php`, `app/Services/Campaigns/*`, `database/migrations/*campaign*`, `tests/Feature/Campaigns/*`, `resources/views/livewire/campaigns/*`, docs marked "phase-a".
   - AI/Ops lane owns: `app/Services/Ai/*`, `app/Jobs/SendAiResponse.php`, `app/Jobs/ScoreLeadJob.php`, `app/Jobs/SendPlatformMessage.php`, `app/Jobs/ProcessIncomingMessage.php`, `config/services.php` (NARAROUTER block), prod `.env` NARAROUTER + QUEUE keys, systemd `one-inbox-queue@.service`.
3. **Shared files require a note here before editing.** Currently shared:
   - `app/Models/Page.php` — Campaigns lane is adding channel-related columns; AI/Ops touches gateway metadata. Ping in this doc if editing.
   - `config/queue.php` — AI/Ops set `retry_after` to 600 (see incident doc 2026-08-25); Campaigns lane should not lower this without discussion.
4. **Both lanes push to `main` via merge/rebase when ready.** Feature branch merges to main; main pushes go direct.
5. If you see a file diff you didn't create, someone else in this doc is
   probably working on it — do not `git checkout HEAD --` to revert
   without checking here first.

## Current lane state (updated by each session as they go)

### Ops / AI session — 2026-08-27

**In-flight:** Redis queue driver migration (item #5 from the open roadmap). Verifying prod queue is drained, installing redis-server, flipping `QUEUE_CONNECTION=database → redis`, restarting worker fleet, monitoring.

**Recently shipped to main and deployed to prod:**
- Model chain switched to `agnes-2.5-flash → agnes-2.0-flash → nemotron-3-ultra → qwen-3.8-max-free → deepseek-v4-flash → mistral-large`.
- Scoring model `ox-alpha-bynara` (0.05x weight).
- Multi-key NaraRouter failover (primary + secondary), 5h reset window, exhaustion email to `omareltak7@gmail.com`.
- History window 20 → 40 messages.
- Fail-fast HTTP timeouts on NaraRouter (60s → 25s + connectTimeout 5).
- 4 parallel queue workers via `one-inbox-queue@.service` template, `--sleep=1`.
- AI-delay overlap (AI call runs during typing budget instead of stacked after it).

**Deferred / auto-reverted:**
- Vision (multimodal image_url through `coalesceRoles`): reverted twice by
  linter/user because it broke the string-invariant pinned by CLAUDE.md §4.
  Needs proper coalesce refactor with new tests.
- Burst-debounce sleep in `SendAiResponse::handle`: reverted once. Reason
  unclear; existing `newerInboundExists` check at line 131 is doing partial
  debounce already.

### Campaigns session

*(To be filled in by the other session — please list current WIP files and target for phase A.)*
