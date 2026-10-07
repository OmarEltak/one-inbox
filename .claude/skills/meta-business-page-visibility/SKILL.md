---
name: meta-business-page-visibility
description: Use BEFORE diagnosing any "no Pages returned by Meta", "Facebook connection succeeded but 0 pages", "customer can't connect their Facebook Page", or "I'm a Page admin but it says I'm not" bug. Also use before ANY change to `app/Services/Platforms/FacebookPlatform.php` scope strings, `fetchPages()`, or the `/me/accounts` call. Codifies the Business-Portfolio-mediated Page role trap that burned ~4 hours across multiple sessions in 2026-10 — Meta's `/me/accounts` endpoint ONLY returns Pages where the user has a direct Page admin role, NOT Pages where the role was assigned via a Business Portfolio, even though Meta's own Business Suite UI shows both identically as "People with Facebook access, Full access". The fix is `business_management` scope + `/me/businesses/{id}/owned_pages` enumeration, implemented 2026-10-07 in `FacebookPlatform::fetchBusinessMediatedPages()`. NEVER diagnose "no Pages returned" by guessing (2FA, scope stripping, wrong account, pending invite) without first querying BOTH endpoints with the user's actual stored token. The guessing cycle is the exact failure mode this skill prevents.
---

# Meta Business-Portfolio Page Visibility Trap

## The trap in one sentence

Meta's `/me/accounts` only returns Pages where the user is a **direct Page admin** on their personal Facebook account. Pages where the admin role was assigned via a **Business Portfolio** never appear there, even when Business Suite's UI shows the user as "People with Facebook access, Full access" — Business Suite deliberately conflates the two paths visually.

## Why it burns hours when you don't know

The failure mode of every past session that hit this bug:

1. User reports: "I'm an admin, OAuth succeeded, but it says no Pages returned."
2. Session guesses: "it's 2FA," "scopes got stripped," "wrong FB account," "pending invite," "Standard vs Advanced Access."
3. User screenshots Business Suite showing them as full admin on the Page.
4. Session doubles down on wrong theory, proposes architectural rewrites (FLfB migration), argues with the user.
5. Eventually someone queries `/me/businesses` with the token and gets `(#100) Missing Permission — business_management` — the actual answer.

Steps 2-4 take hours. Step 5 takes 30 seconds. **Do step 5 first.**

## The one diagnostic that disambiguates every "no Pages returned" case

Run this against prod with the user's stored token BEFORE proposing anything:

```bash
ssh root@187.77.67.94 'cat > /var/www/ot1-pro.com/probe.php <<EOF
<?php
require __DIR__."/vendor/autoload.php";
\$app = require_once __DIR__."/bootstrap/app.php";
\$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\$a = App\Models\ConnectedAccount::find(ACCOUNT_ID_HERE);
\$t = \$a->access_token;

foreach ([
  "/me/accounts" => ["fields" => "id,name", "limit" => 100, "summary" => "total_count"],
  "/me/businesses" => ["fields" => "id,name"],
  "/me/permissions" => [],
] as \$endpoint => \$params) {
  \$r = Illuminate\Support\Facades\Http::withToken(\$t)->get("https://graph.facebook.com/v23.0{\$endpoint}", \$params);
  echo "=== {\$endpoint} status={\$r->status()} ===\n{\$r->body()}\n\n";
}

\$dbg = Illuminate\Support\Facades\Http::get("https://graph.facebook.com/v23.0/debug_token", [
  "input_token" => \$t,
  "access_token" => config("services.meta.app_id")."|".config("services.meta.app_secret"),
]);
echo "=== debug_token (granular_scopes = Pages they actually picked) ===\n{\$dbg->body()}\n";
EOF
cd /var/www/ot1-pro.com && php probe.php 2>&1; rm probe.php'
```

Read the four responses together — they fully disambiguate the cause.

## Diagnosis matrix

| `/me/accounts` | `/me/permissions` | Likely cause | Fix |
|---|---|---|---|
| Returns pages ✓ | — | Working — nothing to diagnose. | — |
| `data: []`, `total_count: 0` | `pages_messaging` NOT granted | **2FA strip** — user is in a Business Portfolio that requires 2FA for all admins, user hasn't enabled 2FA on personal FB → Meta refuses to grant Page scopes. Message is literally "I apologize, I'm having a moment" in reverse — Meta silently gives you `pages_show_list` + `public_profile` and nothing else. | User enables 2FA at `facebook.com/security/2fac/settings`, redoes OAuth. No code fix. |
| `data: []`, `total_count: 0` | All Page scopes granted ✓, `business_management` NOT granted | **Business-mediated role, token predates our business_management scope.** User's Page role is assigned at Business layer, old token doesn't have the right scope to traverse it. | User re-OAuths (fresh token picks up new scope). Our code auto-enumerates via `/me/businesses` → `owned_pages`. |
| `data: []`, `total_count: 0` | All scopes granted ✓ INCLUDING `business_management` | **Business-mediated role, our code should have found it.** `fetchBusinessMediatedPages()` should have returned something. If it returned `[]`, user genuinely has no Page admin role on any Page in any Business. | Check Facebook notifications for pending Page invite. If none, user needs the Page owner to assign them a role via Business Suite → Page → Page Access → "Add with Facebook access, Full control". |
| `data: []`, OAuth error present | — | OAuth itself failed — read `storage/logs/laravel.log` for the actual Meta error body. | Depends on error. |

## The code path (as of 2026-10-07)

`FacebookPlatform::fetchPages()` queries BOTH sources and merges:

1. `/me/accounts` → direct Page admin roles (traditional path)
2. `/me/businesses` → `/{biz}/owned_pages` + `/{biz}/client_pages` → Business-mediated roles

The second source requires `business_management` scope. Both OAuth URL builders include it:
- `getConnectUrl()` → `scope=pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,business_management`
- `getInstagramViaFacebookConnectUrl()` → same + IG scopes

Business-sourced Pages do NOT include a page access token in `/{biz}/owned_pages`. We resolve it per-page via `GET /{page_id}?fields=access_token` with the user token (which `business_management` unlocks). See `FacebookPlatform::resolvePageAccessToken()`.

## Diagnostic metadata stored on each failed OAuth

When BOTH sources return 0 pages, `fetchPages()` writes to `connected_accounts.metadata.last_fetch_pages_zero`:

```json
{
  "at": "ISO8601 timestamp",
  "granted_scopes": ["pages_show_list", ...],
  "missing_scopes": ["pages_messaging", ...],
  "has_business_management": true|false,
  "likely_cause": "twofa_or_scope_strip" | "needs_business_management_reconsent" | "no_direct_or_business_page_role"
}
```

`ConnectionController::connectResultFlash()` branches on `likely_cause` to pick the specific user-facing error message. Three distinct messages, one per cause.

## Things that LOOK the same but AREN'T

Meta's Business Suite UI deliberately presents these two paths identically:

| What UI shows | What it actually means | Appears in `/me/accounts`? |
|---|---|---|
| Business Suite → Page → "People with Facebook access" → "Full access" | Added via **Business Portfolio access** (Business admin → Page assignment) | **NO** — needs `business_management` + Business endpoints |
| Business Suite → Page → "People with Facebook access" → "Full access" (same label!) | Added via `facebook.com/pages/manage/people_and_other_pages` (direct Page admin) | **YES** |

There is no way to tell which path was used from Business Suite's UI alone. The only authority is `/me/accounts` for the user's own token.

## Red flags in your own reasoning

If you catch yourself thinking any of these when diagnosing a "no Pages returned" case, STOP and run the diagnostic probe above:

| Thought | Why it's wrong |
|---|---|
| "They probably didn't tick the page on the Meta picker" | `granular_scopes` from `debug_token` tells you objectively if they did or didn't — don't guess. |
| "The customer has no Facebook Page" | Business-mediated admins have Pages they can see in Business Suite but not in `/me/accounts`. Query `/me/businesses`. |
| "They need to be added as a direct Page admin" | True for `/me/accounts`-only users; false now that we support Business endpoints. First check if OUR code queried the Business path. |
| "This must be a 2FA thing" | Only true when `missing_scopes` is non-empty. If all scopes granted, 2FA is NOT the cause. |
| "Let me propose a Facebook Login for Business (FLfB) migration" | DON'T. We already fixed the real issue (2026-10-07). FLfB is a much larger change and was proposed by a session that misdiagnosed this case as unfixable. |
| "Let's make them switch to their own Business Portfolio" | Doesn't help — Business-owned Pages have this issue regardless of who owns the Business. Omar's own account also hit this before the fix (`/me/accounts` returned OT1-Pro only after the fix was deployed). |

## Prior incidents this skill prevents

- **2026-10-06, ~3 hours burned**: Khaled Kandeel (test tester) OAuthed with full admin role on OT1-Pro Page via Business Portfolio. Session confidently diagnosed 2FA, then Standard Access gating, then "needs direct Page role", then proposed FLfB migration. Actual cause: our code didn't query `/me/businesses`. The user had to insist multiple times that Khaled WAS an admin before the session queried `/me/businesses` and got the "Missing Permission" that would have revealed the real issue in 30 seconds.
- **2026-10-07**: Same session partially fell back into the same hole when a second test account (`omarmohamedeltak@gmail.com`) showed a different pattern. Was close to proposing another large rewrite before the diagnostic matrix above forced recognition that it was a different cause (2FA strip) with a different fix (user enables 2FA).

## When adding new scopes

If you touch the `scope=...` string in `getConnectUrl()` or `getInstagramViaFacebookConnectUrl()`:

1. **Pre-validate with curl** before deploying — Meta's "Invalid Scopes" rejection is silent at the OAuth URL level but kills the real redirect:
   ```bash
   curl -s -o /dev/null -w "%{http_code} %{redirect_url}\n" "https://www.facebook.com/v21.0/dialog/oauth?client_id=1469090344742803&redirect_uri=https%3A%2F%2Fot1-pro.com%2Fconnections%2Ffacebook%2Fcallback&scope=YOUR_NEW_SCOPES_HERE&response_type=code&state=test"
   ```
   `302 → login.php?...` = Meta accepts. Any other response = Meta rejected the scope combination.

2. **Update the stored scopes array** in `handleCallback()` to match (around line 442). Mismatched arrays cause diagnostic metadata to lie about what was granted.

3. **Check the 2026-09-07 comment** at `FacebookPlatform.php:83-87` — some scope additions (notably anything that activates a Facebook Login for Business config match) will trip `Invalid Scopes: pages_read_user_content`. If that happens, don't ship — fix the Dev Console config first.
