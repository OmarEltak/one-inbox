# Meta App Permissions — Snapshot

**Captured:** 2026-10-27 by browser-navigating developers.facebook.com and reading each use case's Permissions tab for both apps.

**How to re-run this audit:** open each use case → Permissions tab → scrape rows with usage + status. The "Status" column uses Arabic strings:
- `جاهز للاختبار` = **Standard Access** (works for app admins / developers / testers / role users only — _enough for Omar's current testing_)
- `جاهز للنشر` = **Advanced Access** (live — works for the public without being a tester)
- `تم رفض مراجعة التطبيقات` = **App Review Rejected** (still works for testers; blocks public users until a new submission is approved)
- Empty / "إضافة" button = **Not Added** to the use case (OAuth with this scope → "Invalid Scopes" error for every user)

**Important:** _"Rejected" is only about public users._ If Omar is testing via tester accounts, rejected scopes still work. A current bug is only caused by:
1. Scope **not added** to the use case → OAuth rejection
2. Scope **not requested** in our OAuth URL → granted token lacks it → endpoint 400s
3. Code using the **wrong scope** for the endpoint it hits

---

## App 1 — OT1 Direct Connect (Direct IG Login)

| | |
|---|---|
| App ID | `2908423109505861` |
| Mode | Development |
| Env var | `META_INSTAGRAM_APP_ID` |
| OAuth host | `graph.instagram.com` |
| Webhook route | `/api/webhooks/meta-ig` |
| OAuth URL built by | `FacebookPlatform::getInstagramConnectUrl` |
| Current requested scopes (as of 2026-10-27) | `instagram_business_basic`, `instagram_business_manage_messages`, `instagram_business_manage_comments` |

### Instagram use case — Permissions tab

| Permission | Status | Usage | Notes |
|---|---|---|---|
| `instagram_business_basic` | Standard | 156 | ✅ Requested in OAuth. Covers profile + media reads. |
| `instagram_business_manage_messages` | Standard | 147 | ✅ Requested in OAuth. DM read/write. |
| `instagram_business_manage_comments` | Standard | 0 | ✅ **Added in this session (2026-10-27).** Covers IG comment reply + webhook. New connects after today pick it up. |
| `instagram_basic` | Standard | 0 | Legacy. Not requested; present but unused. |
| `instagram_manage_comments` | Standard | 0 | Legacy. Not requested; present but unused. |
| `instagram_manage_messages` | Standard | 0 | Legacy. Not requested; present but unused. |
| `public_profile` | Standard | 0 | Legacy. Not requested; present but unused. |

**Not added to this use case (would break OAuth if we requested them):**
`instagram_business_content_publish`, `instagram_business_manage_insights`, `ads_management`, `ads_read`, `business_management`, `catalog_management`, `email`, `instagram_branded_content_*`, `instagram_content_publish`, `instagram_creator_marketplace_*`, `instagram_manage_contents`, `instagram_manage_engagement`, `instagram_manage_events`, `instagram_manage_insights`, `instagram_manage_upcoming_events`, `instagram_shopping_tag_products`, `pages_read_engagement`, `pages_show_list`.

---

## App 2 — One Inbox Business (Main app)

| | |
|---|---|
| App ID | `1469090344742803` |
| Mode | Published |
| Env var | `META_APP_ID` |
| OAuth host | `graph.facebook.com` |
| Webhook route | `/api/webhooks/meta` |
| OAuth URLs built by | `FacebookPlatform::getConnectUrl` (FB), `getInstagramViaFacebookConnectUrl` (via-FB IG) |
| Current requested FB scopes | `pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_posts,business_management` |
| Current requested via-FB IG scopes | FB scopes above + `instagram_basic,instagram_manage_messages,instagram_manage_comments` |

### Facebook Messaging use case — Permissions tab

| Permission | Status | Usage | Notes |
|---|---|---|---|
| `pages_show_list` | **Advanced Access** | 8 k | ✅ Live for all users. |
| `pages_messaging` | **App Review REJECTED** | 12.8 k | ✅ Requested. Works for testers. **Public DM delivery is BLOCKED until re-approved.** |
| `pages_manage_metadata` | **App Review REJECTED** | 8.5 k | ✅ Requested. Testers OK. |
| `pages_read_engagement` | **App Review REJECTED** | 8.8 k | ✅ Requested. Testers OK. |
| `pages_utility_messaging` | **App Review REJECTED** | 3 k | Not requested. 24h+ messaging. |
| `business_management` | **Advanced Access** | 4.5 k | ✅ Requested. Live. |
| `instagram_basic` | **App Review REJECTED** | 3.3 k | Not requested from this use case (via-FB IG uses it). |
| `instagram_manage_messages` | **App Review REJECTED** | 3 k | Not requested from this use case. |
| `email` | **Advanced Access** | 0 | Live. |
| `public_profile` | **Advanced Access** | 307 | Live. |
| `ads_management` | Standard | 248 | Not requested. |
| `marketing_messages_messenger` | Standard | 0 | Not requested. |
| `paid_marketing_messages` | Standard | 0 | Not requested. |
| `Business Asset User Profile Access` | Standard | 0 | Feature flag. |
| `Human Agent` | Standard | 0 | Feature flag (7-day human reply window). |

### Instagram use case (via-FB IG) — Permissions tab

| Permission | Status | Usage | Notes |
|---|---|---|---|
| `instagram_business_basic` | **Advanced Access** | 1.5 k | Live. (Direct IG Login app also uses the same scope name.) |
| `instagram_business_manage_messages` | **Advanced Access** | 1.5 k | Live. |
| `instagram_basic` | **App Review REJECTED** | 3.3 k | ✅ Requested in via-FB IG OAuth. Testers OK. |
| `instagram_manage_messages` | **App Review REJECTED** | 3 k | ✅ Requested. Testers OK. |
| `instagram_manage_comments` | **App Review REJECTED** | 3 k | ✅ Requested. **Via-FB IG comment reply scope.** Testers OK. |
| `pages_show_list` | **Advanced Access** | 8 k | Live. |
| `pages_read_engagement` | **App Review REJECTED** | 8.8 k | Via-FB IG also needs this. |
| `business_management` | **Advanced Access** | 4.5 k | Live. |
| `email` | **Advanced Access** | 0 | Live. |
| `public_profile` | **Advanced Access** | 307 | Live. |
| `ads_management`, `catalog_management`, `instagram_branded_content_*`, `instagram_content_publish`, `instagram_manage_contents`, `instagram_manage_insights`, `instagram_shopping_tag_products` | Standard | 0 | Not requested. |

**Not added here (would break OAuth):** `instagram_business_manage_comments`, `instagram_manage_engagement`, `instagram_business_content_publish`, `instagram_business_manage_insights`.

### Pages API use case — Permissions tab

| Permission | Status | Usage | Notes |
|---|---|---|---|
| `pages_show_list` | **Advanced Access** | 8 k | Live. |
| `pages_manage_metadata` | **App Review REJECTED** | 8.5 k | Testers OK. |
| `pages_read_engagement` | **App Review REJECTED** | 8.8 k | Testers OK. |
| `pages_manage_posts` | **Standard** | 94 | ✅ Requested. Testers only. Covers Page's own feed publishing. |
| `pages_manage_engagement` | **Standard** | 0 | ✅ Requested (re-added 2026-10-28 after Meta ticket 2162436254672944 explained the dependency). Needed for `POST /{comment-id}/comments` (public reply to user comments). Testers only. |
| `pages_read_user_content` | **Standard** | 0 | ✅ Added to use case 2026-10-28 (NOT requested in OAuth URL). **Hidden invariant:** this scope must stay in this use case or OAuth with `pages_manage_engagement` breaks with "Invalid Scopes: pages_read_user_content". It's a Meta OAuth-validator dependency — do not remove from the Dev Console. |
| `business_management` | **Advanced Access** | 4.5 k | Live. |
| `email`, `public_profile` | **Advanced Access** | — | Live. |
| `read_insights`, `facebook_branded_content_ads_brand` | Standard | 0 | Not requested. |

### WhatsApp use case

Not audited in this session — the WhatsApp URL enum didn't resolve on first attempt. Add on next audit pass. Known requested scopes from code: `whatsapp_business_management`, `whatsapp_business_messaging`.

---

## Known-actionable gaps (updated 2026-10-28)

1. **✅ FIXED 2026-10-27:** `instagram_business_manage_comments` added to the OT1 Direct Connect Instagram use case. Reconnecting @ammlmsdtt carries the scope. IG Direct Login comment webhooks + replies verified working on prod (commits `d021271` + `c3637fe`).

2. **✅ FIXED 2026-10-28:** `pages_manage_engagement` is now requested in both FB OAuth URLs (commit `a7a6439`). The 2-month block was a Meta OAuth-validator dependency: `pages_manage_engagement` requires `pages_read_user_content` to be in the Pages API use case even though we never request it in OAuth. Fix was to add `pages_read_user_content` to that use case (Standard Access auto-granted). Verified: Brandk reconnected, test FB comment landed in `/comments` with "REPLIED" + "DM sent". See `.claude/skills/comments-architecture/SKILL.md` §6 and Meta ticket 2162436254672944 for the full diagnosis.

3. **🟡 Submission-blocking (for public users, not for Omar's testing):** The following permissions must be re-submitted and approved before non-tester customers can use the app:
   - FB Messaging: `pages_messaging`, `pages_manage_metadata`, `pages_read_engagement`
   - via-FB IG: `instagram_basic`, `instagram_manage_messages`, `instagram_manage_comments`
   - Pages: `pages_manage_metadata`, `pages_read_engagement`
   - All of the above show `تم رفض مراجعة التطبيقات` (App Review Rejected). Omar's hired reviewer needs to address the rejection reasons (visible in each permission's details) and resubmit.

4. **🟡 Submission needed:** `pages_manage_engagement`, `pages_manage_posts`, and `pages_read_user_content` are currently Standard Access. For public (non-tester) customers to post AI comment replies, all three need App Review Advanced Access. Submit alongside the main resubmission. Submission text draft in `docs/meta-app-review/comments-permissions-submission.md`.

5. **🟡 Direct IG Login:** `instagram_business_manage_comments` is Standard. For public (non-tester) customers to receive comment webhooks + replies, submit it for Advanced Access via the App Review tab.

---

## Appendix — URLs for quick re-check

- All apps: https://developers.facebook.com/apps/
- Main app use cases: https://developers.facebook.com/apps/1469090344742803/use_cases/
- Direct IG app use cases: https://developers.facebook.com/apps/2908423109505861/use_cases/
- Main app FB Messaging permissions: https://developers.facebook.com/apps/1469090344742803/use_cases/customize/permissions/?use_case_enum=FACEBOOK_MESSAGING&selected_tab=permissions&product_route=use_cases
- Main app Instagram permissions: https://developers.facebook.com/apps/1469090344742803/use_cases/customize/permissions/?use_case_enum=INSTAGRAM_BUSINESS&selected_tab=permissions&product_route=use_cases
- Main app Pages permissions: https://developers.facebook.com/apps/1469090344742803/use_cases/customize/?use_case_enum=PAGES_API&selected_tab=permissions&product_route=use_cases
- Direct IG app Instagram permissions: https://developers.facebook.com/apps/2908423109505861/use_cases/customize/permissions/?use_case_enum=INSTAGRAM_BUSINESS&selected_tab=permissions&product_route=use_cases
