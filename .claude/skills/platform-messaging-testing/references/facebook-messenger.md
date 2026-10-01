# Facebook Messenger — reference

## Apps, IDs, endpoints

| Thing | Value | Source |
|---|---|---|
| Meta app | "One Inbox Business", id `1469090344742803` | diagnostic 2026-10-01 |
| Webhook callback | `https://ot1-pro.com/api/webhooks/meta` (GET verify / POST events) | `routes/api.php`, app-level subscription |
| Signature secret | `META_APP_SECRET` (fallback `META_APP_SECRET_LEGACY`) | `MetaWebhookController::verifySignature` |
| Graph version | `config('services.meta.graph_api_version')` (default v21.0; subscriptions show v25.0) | config |
| OAuth | `facebook.com/{v}/dialog/oauth`, scopes `pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement` | `FacebookPlatform::getConnectUrl` |
| Page token | from `/me/accounts` (long-lived user token → permanent page token) | `fetchPages()` |
| Routing key | `entry.id` == FB Page id == `pages.platform_page_id` | `ProcessIncomingMessage::handleMetaMessage` |
| Send | `POST graph.facebook.com/{v}/{page_id}/messages` | `SendPlatformMessage::sendViaMetaMessenger` |
| Sync/backfill | `graph.facebook.com/{v}/{page_id}/conversations` | `FacebookPlatform::fetchConversationsPage` |

## Subscriptions (verified 2026-10-01 via diagnostic)

- **App-level `page` object:** messages, messaging_postbacks, messaging_optins,
  message_deliveries, message_reads, message_echoes, feed.
- **Page-level (`FB_SUBSCRIBED_FIELDS`):** messages, message_echoes, message_deliveries,
  message_reads, messaging_postbacks, feed — set by `subscribePage()` on every connect.
  `feed` subscribes fine with `pages_manage_metadata` (pages 11/17 returned 200, 2026-09-06).
- If `subscribePage()` fails the page gets `metadata.subscription_error = twofa_required`
  and the Connections page shows a Retry. (2FA on the admin's FB account is the usual cause.)

## Verified behaviour

- Inbound Messenger webhooks with messages route and store for managed-onboarding pages
  (e.g. webhook rows 69077–69085 → team 2, 2026-10-01).
- Webhooks with `has_message:false` and `team_id:null` are deliveries/reads/feed — normal,
  not a routing failure.
- Echo handling: `is_echo` + `app_id` == our app → self-echo (AI keeps running);
  other `app_id` → native-app reply → AI paused (fix e2a2d9a, 2026-09-09).
- Missing echoes = app-level `message_echoes` gap (see `meta-webhook-two-layer-subscription`).

## Connect flows

- **Direct OAuth** ("Connect via Meta" on the Facebook card) — visible only when
  `META_APP_VERIFIED=true` or the user is a super-admin. Non-admins on an unapproved app get
  "Feature unavailable: Facebook Login is currently unavailable for this app".
- **Managed onboarding** (customers today): customer adds the OT admin FB account to their
  Page → super-admin connects via Meta → `/super-admin/onboarding-requests` (or
  `/super-admin/page-assignments`) moves the Page to the customer team; both paths cascade
  conversations + contacts and clear the active-pages cache.

## Quirks

- `/me/accounts` returns **every** page the super-admin account manages; connecting creates
  rows for all of them on the super-admin's team (see SKILL §6 re-OAuth risk).
- `error_code=100&error_message=Invalid Scopes…` comes back on the callback when a scope
  isn't in the FLfB config — surfaced since 6ae1c87.
- Facebook rotates/keeps two valid secrets after a reset — keep the `_LEGACY` env var until
  logs stop saying "verified with legacy secret".
