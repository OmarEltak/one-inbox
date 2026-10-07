# Self-Healing System for Production Errors

**Status:** Backlog — captured 2026-10-08. Build after current UX work settles.

## The Idea

When a user hits a 5xx / specific-status error on ot1-pro.com, instead of
Omar manually tailing logs → diagnosing → coding → deploying, a lightweight
automated loop handles the first pass:

1. **Observe.** Every 500 / 404 (configurable list of status codes) gets
   logged with full context: user id, team id, URL, previous route, user
   agent, trace, request body (sanitized), timestamp, build commit.
2. **Diagnose.** A Claude Code or similar AI agent is woken up (via GitHub
   webhook or scheduled poll) with the error context. It can read the
   codebase, grep for the stack trace, read recent commits, and propose
   a fix.
3. **Fix.** The agent opens a draft PR with the proposed change + a
   WHY-this-fixes-it explanation + the error ID it addresses. Omar
   reviews. Nothing lands on main without human approval.
4. **Test.** Not unit tests — real tests. Candidate options:
   - **Playwright E2E** for UI flows (login, connect FB, send chat message).
   - **HTTP smoke tests** that hit each critical route and assert 200 +
     expected string.
   - **Replay the exact URL** that hit the 500 and confirm it now returns
     the expected code.
5. **Document.** Each incident gets its own file in a repo somewhere
   (e.g. `docs/incidents/2026-10-08-<slug>.md`) with: symptom, root cause,
   fix link, tests added, decisions made. Future incidents reference past
   ones so the agent builds an institutional memory.
6. **Notify.** Discord/WhatsApp/email to Omar at key moments:
   - A 500 fired → "status: diagnosing, incident #42"
   - PR opened → "status: proposed fix, review here <link>"
   - If the agent can't diagnose after N attempts → "status: struggling,
     need human — here's what I tried"
   - Resolved → "status: resolved, PR #<id> merged, incident doc at <link>"

## Scope / tooling notes to think about

- **Error ingestion.** Options: Laravel Telescope (private, DB-backed) ·
  Sentry (free tier ~5k events/month) · a tiny home-grown table that
  our ExceptionHandler writes to. The home-grown table avoids a vendor
  but we lose their query UI. Sentry probably wins for MVP.
- **AI agent runtime.** GitHub Actions can trigger Claude Code via `cc`
  CLI on an issue event. Or a tiny Laravel command that fires a Claude
  API call with the stack trace + repo context.
- **Repo-vs-prod access.** The agent should get READ access to the code
  (public clone) and the error payload. It should NOT get write access
  to prod — all changes go via PR.
- **Rate-limiting.** One PR per unique error signature per 24h, max.
  Otherwise a flood of the same error = flood of PRs = Omar burns out.
- **Signature dedup.** `hash(route_name + exception_class + top_3_trace_frames)`.
- **"Specific status codes".** Not every 404 is interesting (random bots
  hit /wp-admin etc). Need a filter: only authenticated-user 4xx, or
  only 5xx, or only routes defined in the app.
- **Who faced it.** Already have `user_id` on session + `team_id` on
  `currentTeam`. Add to the ingestion payload. Also log the referrer
  so we see what the user clicked to get there.
- **"Struggling" signal.** Agent self-reports after N retries without a
  high-confidence fix. Honesty > fake fixes.

## What this is NOT

- Not auto-deploy. Human review always.
- Not unit tests. User asked for real tests (E2E / smoke).
- Not infrastructure monitoring (CPU/disk). That's a separate concern.

## Decision log (empty — fill as we build)

- [ ] Error ingestion vendor (Sentry vs home-grown)
- [ ] Agent runtime (GitHub Actions vs server-side command)
- [ ] Notification channel (WhatsApp vs Discord vs email)
- [ ] Test runner (Playwright vs Pest HTTP vs curl smoke)
- [ ] Repo for incident docs (same repo under docs/incidents/ or separate)

## Related existing pieces in the codebase

- `App\Http\Middleware\RequireConnection` — surfaces 403 with context
- Laravel's exception handler — need to wire payload capture
- `resources/views/errors/*` — already have branded error pages
- `App\Livewire\SuperAdmin\Errors` — super-admin error-viewer exists
  (`super-admin.errors` route) — repurpose or inform this
