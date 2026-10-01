---
name: platform-messaging-testing
description: Use FIRST whenever a connected channel on ot1-pro "receives no messages", "messages don't show in the inbox", "connected but nothing arrives", replies don't reach the customer, a connect/OAuth button fails or connects the wrong account, or before changing ANY messaging/connection code (FacebookPlatform, ConnectionController, MetaWebhookController, ProcessIncomingMessage, SendPlatformMessage, subscribed_fields, OAuth scopes, page assignment). Also use when someone asks "how do I test Instagram/Messenger messaging", "does the Meta path work without App Review", or "what can I change in the Meta integration". Covers Facebook Messenger and both Instagram paths (Connect via Meta / Direct IG Login): the inbound pipeline layer by layer, the browser diagnostic at /super-admin/pages/{id}/diagnose, a live test protocol, what is verified to work vs not, and the change rules that previous sessions broke. Built from the 2026-10-01 incident; extend it when other platforms (WhatsApp, Telegram, email) are debugged.
---

# Platform messaging — test & debug playbook

The inbound path has ~8 independent layers. Any one failing looks identical to the user:
"connected, but no messages". Sessions on this repo have repeatedly guessed the layer
(Meta approval, IDs, secrets) and been wrong. This skill exists so you **measure which
layer broke** before changing anything.

Per-platform details live in references — read the one you need:
- `references/facebook-messenger.md` — Messenger pages (main Meta app).
- `references/instagram.md` — Instagram: **two different apps/paths**, IDs, scopes, quirks.
- `references/local-harness.md` — running tests + the real app locally in a cloud container.

## 1. The inbound pipeline (where messages can die)

```
customer DM
  → Meta decides to send a webhook          (app access level, app roles, IG "Allow access to messages")
  → app-level subscription has the field    (POST /{app-id}/subscriptions)          ← layer A
  → page-level subscription has the field   (POST /{page-id}/subscribed_apps)       ← layer B
  → POST /api/webhooks/meta | /meta-ig      (throttle:webhooks 120/min/IP)
  → HMAC signature check                    (MetaWebhookController::verifySignature, tries
                                             IG secret, IG legacy, app secret, app legacy)
  → webhook_logs row + ProcessIncomingMessage on queue `urgent`
  → page lookup by entry.id                 (platform_page_id or metadata igbid/igsid/legacy_id/oauth_user_id;
                                             must be is_active; prefers active connected account)
  → Conversation::firstOrCreate(team_id = page.team_id, page_id, platform, sender id)
  → inbox lists it only for THAT team, page in team's active_pages (5-min cache),
    status != archived, sales_stage != spam (unless Spam filter)
```

A message can be lost at Meta (no `webhook_logs` row), at routing (row with `team_id`
null), or be stored but invisible (conversation exists, wrong viewer/filter). Those three
need completely different fixes, so identify which one first.

## 2. Triage in 5 minutes — use the diagnostic, not guesses

The cloud container usually **cannot reach ot1-pro.com, graph.facebook.com or SSH** (egress
proxy). Don't stall on SSH commands — ask the user to open, as a super-admin:

```
https://ot1-pro.com/super-admin/pages/{pageId}/diagnose
```

(`pageId` is the `?pageId=` in the inbox URL.) Read-only JSON, no tokens/secrets
(`app/Http/Controllers/SuperAdmin/PageDiagnosticController.php`). Ask them to send a fresh
DM ~30 s before opening it. Then read it in this order:

| Field | What it tells you | Next step |
|---|---|---|
| `webhooks.for_this_page_latest[0].created_at` ≈ time of the test DM | Meta delivered it | go down the table |
| no new row for the DM, `unrouted_instagram_entry_ids` empty | **Meta never sent it** | §4 Meta-side checklist |
| entry id appears in `unrouted_instagram_entry_ids` | arrived, **our routing missed** (ID mismatch / inactive page) | compare that id with `page.platform_page_id` + `page.metadata`; fix lookup or page row |
| row has `error` / `processed:false` | job threw | read `error`; reproduce locally (§5) |
| `conversations.latest[*].hidden_from_owner_inbox_because` non-empty | stored but filtered | the listed reason (spam / archived / team mismatch / stale cache) |
| `viewer.can_see_page_in_own_inbox: false` | **you're looking from the wrong team** | log in as the page's team (common after super-admin reassignment) |
| `meta.token_debug.is_valid:false` or scope missing (`instagram_manage_messages`, `pages_messaging`) | token / permission | reconnect the page |
| `meta.page_subscribed_apps` missing our app or a field | layer B gap | reconnect, or `subscribePage()` / `subscribeInstagramPage()` |
| `meta.app_subscriptions_main` object lacks the field | layer A gap | tinker snippet in `meta-webhook-two-layer-subscription` skill |
| `siblings` has another **active** row with same id | single-active invariant broken | ARCHITECTURE §2 — never delete; deactivate the wrong one |

Real example (2026-10-01, page 22): user said "no messages arrive". Report showed the DM
row at 17:56:02 routed to team 41, conversation stored, `viewer.current_team_id: 2` →
`can_see_page_in_own_inbox: false`. Nothing was broken — the page had just been reassigned
to a test customer. 30 seconds with the report vs. an hour of theorising.

## 3. Live test protocol (do this before declaring a channel "works")

1. **Record the sender.** Note whether the sending account has a role on the Meta app
   (admin/developer/tester). On an unapproved app Meta's docs say Standard Access only
   delivers notifications *from* role users — so a DM from your own (admin) account proves
   nothing about real customers. Test with a role account AND a no-role account and
   write down which one you used.
2. Send the DM; open the diagnostic; confirm the webhook row + conversation (§2).
3. Open the inbox **as the page's team** and check the conversation renders.
4. Reply from the inbox → customer receives it → an echo row arrives (`is_echo:true`) and
   AI is **not** paused (self-echo detected by `app_id`).
5. Reply from the native app (Business Suite / Instagram app) → echo row, AI **is** paused.
6. If AI is enabled, confirm `SendAiResponse` replied (or why not — `Team::canDispatchAi()`).

Record results in `tasks/journal.md` with the sender's role status. Unrecorded tests are how
the April "Meta never delivers IG via Meta" conclusion survived for 6 months — wrong.

## 4. Meta-side checklist (no webhook row at all)

- Both subscription layers include the field (§2 rows above).
- Instagram account is **Professional** and has *Settings → Messages and story replies →
  Message controls → Connected tools → Allow access to messages* **ON**.
- Sender role vs app access level (§3 step 1). App is **not approved** as of 2026-10-01
  (CLAUDE.md pin #1 — two milestones; don't flip `META_APP_VERIFIED`).
- Direct IG Login accounts: the connected IG account must be an **accepted Instagram
  Tester** of "OT1 Direct Connect" (accept at instagram.com/accounts/manage_access →
  Tester Invites).
- Webhook signature: secret rotations have silently 403'd every webhook before
  (2026-05-05). Look for `Meta webhook signature mismatch` in `laravel.log`.

## 5. Reproducing locally

Pipeline bugs (routing, rendering, 500s) reproduce locally with SQLite + Playwright; Meta
behaviour does not. See `references/local-harness.md` for the exact setup (PHP 8.3 vs
lockfile 8.4 workaround, seed script, browser login, contrast audit). Relevant tests:
`tests/Feature/Connections/InstagramDirectLoginTest.php`,
`tests/Feature/Connections/ConciergeFramingTest.php`,
`tests/Feature/SuperAdmin/PageDiagnosticTest.php`,
`tests/Feature/Onboarding/OptionalStepDoesNotBlockTest.php`.
Use `Http::fake()` for every Graph/Instagram call — tests must never hit Meta.

## 6. What you may change, and what you must not

Each rule below exists because a session broke it in production.

**Don't**
- Add a webhook field to `FB_SUBSCRIBED_FIELDS` / `IG_SUBSCRIBED_FIELDS` without (a) the
  OAuth scope that grants it and (b) the app-level subscription. Meta rejects the **whole**
  `subscribed_apps` call if one field lacks permission — `messages` then never subscribes
  and the UI still says "Connected" (2026-09-06 → 10-01).
- Add an OAuth scope that isn't checked in the Facebook Login for Business config —
  Meta returns "Invalid Scopes: pages_read_user_content" for *every* user (2026-09-08).
  Admin accounts see a consent screen anyway, so admin testing gives a false positive.
- Remove the "Connect Direct (IG Login)" button or "Connect via Meta" — they are different
  Meta apps with different access; removing one silently changed which app new IG
  connections use (8c46719, 2026-09-27).
- Treat a missing `code` on an OAuth callback as impossible — Meta returns
  `?error_code=&error_message=` or nothing at all. Both callbacks must handle it.
- Remove/bypass the `Page::booted()` single-active observer (ARCHITECTURE §2).
- Re-run "Connect via Meta" from the super-admin account while customers' pages are
  assigned: `fetchPages()`/`detectInstagramAccount()` recreate rows on the holding team and
  the observer deactivates the customer's copy (code-reading finding, not yet fixed).

**Safe / encouraged**
- Extend `PageDiagnosticController` when a new failure mode appears — make the next
  incident a one-reload diagnosis.
- Surface failures in the UI (flash with Meta's own message; `metadata.subscription_error`).
- Add regression tests that reproduce the prod symptom on the old code first.

## 7. Verifying a fix in production from a cloud session

`git push origin HEAD:main` → GitHub Actions "Deploy to Production" (~30 s). The SSH step
can report success while a command failed, so read the job log (GitHub MCP
`get_job_logs`): `git pull` must show your SHA, then `Blade templates cached`,
`Broadcasting queue restart signal`. Then ask the user to retry and paste the diagnostic.
Journal every prod change (`ot1-pro-prod-ops` skill).

## 8. Open issues (update as they close)

| Issue | Status (2026-10-01) |
|---|---|
| Are via-Meta IG DMs from **no-role** senders delivered? | Delivery proven for page 22, sender's role not recorded — verify per §3.1, then fix the IG card hint text accordingly |
| Direct IG Login for `ot1.pro` returns to callback with no `code`, no error | Unexplained; flash now lists received params — get them |
| "Mark as Lost/Converted" → 500 | Not reproducible locally; need `production.ERROR` log line |
| Super-admin re-OAuth steals assigned pages | Code-reading only; needs a test + fix |
| `app_subscriptions` for the Instagram app | Not queryable with our creds (OAuthException 190) — check in App Dashboard |

## Adding another platform to this skill

When WhatsApp/Telegram/email messaging gets debugged, add `references/<platform>.md` with
the same sections as the Meta ones (apps & IDs, connect flow, webhook route + verification,
routing key, verified behaviour with dates, quirks, don'ts) and a row per platform in §2's
table if the diagnostic learns it.
