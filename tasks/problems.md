# Instagram & Connection Problems — 2026-04-26

## Status Legend
- 🔴 CRITICAL — blocking all messages
- 🟡 BROKEN — feature doesn't work
- 🟢 FIXED — resolved this session
- ⏳ WAITING — blocked on external factor (Meta review, user action)

---

## 🟢 FIXED: All IG Webhooks Crashing (CRITICAL — was blocking everything)

**Symptom:** No Instagram messages arriving since 2026-04-23. 30 failed jobs in queue.

**Root cause:** The self-heal logic in `ProcessIncomingMessage::handleMetaMessage()` was finding
Mishkah IG (page 36) as the first active IG page and trying to update its `platform_page_id`
to Omar's IG legacy ID (`17841429680280453`). But page 39 already had that exact ID → unique
constraint crash → job retried 3 times → failed → all IG webhooks silently dropped.

**Fix applied (this session):**
1. `app/Jobs/ProcessIncomingMessage.php` — Rewrote self-heal logic:
   - Step 1: Look for any page (including inactive) with the incoming `entry.id`
   - If found inactive → reactivate and use it (no update needed)
   - Step 2: Only patch the first active IG page if NO page at all has the target ID AND no conflict exists
   - Previously: blindly updated first active page without checking for conflicts
2. Production DB: Reactivated page 39 (Omar's personal IG, `platform_page_id=17841429680280453`) and account 9
3. Deactivated page 40 (duplicate of page 39 with different ID format)
4. Deployed to `one-inbox-prod`, queue restarted

**Verify fix:**
```bash
cd C:/Users/NanoChip/Herd/one-inbox-prod
php artisan tinker --execute="\App\Models\WebhookLog::where('platform','instagram')->orderBy('id','desc')->limit(3)->get(['id','team_id','status'])->each(fn(\$w)=>print_r(\$w->toArray()));"
# team_id should now be populated (not null) on new incoming webhooks
```

---

## 🟢 FIXED: Personal IG (omar_eltak88) Send Fails

**Symptom:** Messages received but outbound sends fail for Direct IG Login (method 2).

**Root cause:** `sendViaMetaMessenger()` built the send URL using `platform_page_id` which
(after self-heal) becomes the legacy IG User ID. The Messages API requires the IGBID
(stored in `metadata['igbid']`).

**Fix applied:**
- `app/Jobs/SendPlatformMessage.php` — Uses `metadata['igbid'] ?? platform_page_id` for
  the send URL when `auth_type === 'instagram_business'`

---

## 🟢 FIXED: Button Label "Connect via Facebook" → "Connect via Meta"

- `resources/views/livewire/connections/index.blade.php` — renamed both button labels

---

## ⏳ WAITING: Mishkah University IG — No Inbound Messages

**Symptom:** Mishkah IG (page 36) has 0 conversations. No webhooks for their IG arrive.

**Root cause:** `instagram_manage_messages` via Facebook Login is still in Meta App Review
(Standard Access). Meta only delivers IG-via-Facebook webhooks for accounts connected by the
app developer. Mishkah's IG account customers' DMs are not delivered until Meta grants
Advanced Access (app review approval).

**Evidence:** All 245 IG webhook_logs have `entry.id=17841429680280453` (Omar's personal IG),
zero have Mishkah's IGBID (`17841406970888724`).

**What's needed:** Meta App Review approval for `instagram_manage_messages` (submitted 2026-04-09).
No code fix possible — purely a Meta platform restriction.

**Workaround while waiting:** Ask Mishkah's IG account owner to DM your IG test account
directly so you can verify the pipeline works. Cannot test Mishkah IG DMs until approved.

---

## ⏳ WAITING: Friend's Direct IG Login — Two Errors

### Error 1: "The profile you're looking for doesn't exist" (Instagram screen)
**Cause:** Friend's Instagram account is a **personal account**, not Business or Creator.
Instagram Business Login (`instagram.com/oauth/authorize`) only works for Business/Creator accounts.

**Fix:** Friend must convert their IG to a Business/Creator account:
Instagram app → Profile → Edit Profile → Switch to Professional Account → Creator or Business

### Error 2: "Feature unavailable: Facebook Login is currently unavailable for this app"
**Cause:** This appeared when friend tried "Connect via Meta" (method 1). App is in review.
Friend IS a General Tester (Ahmed Mamdouh, accepted — confirmed on roles page), but this
error can appear if:
- The device/browser wasn't logged into Facebook as Ahmed
- OR Meta temporarily restricted the app during review
- OR friend was not logged into the right Facebook account

**Fix:** Friend must try again while logged into Facebook (as Ahmed Mamdouh) in the same browser.
If error persists after app review approval, it will automatically resolve.

---

## ⏳ WAITING: amdo7a Instagram Tester Invitation Pending

**Symptom:** Ahmed Mamdouh (amdo7a on Instagram) shows **معلق (Pending)** for Instagram Tester
role in the Meta developer console. He HAS accepted the General Tester role but NOT the
Instagram Tester role (for sub-app 1408745007038040).

**Impact:** Direct IG Login (method 2) via the Instagram sub-app won't work for amdo7a's IG
until he accepts the Instagram tester invitation.

**Fix:** Ahmed must accept the Instagram tester invitation at:
developers.facebook.com → Apps → 1408745007038040 → roles → look for invitation email

---

## 🟡 BROKEN: Facebook Messenger (تعلم المسيقة) — 2FA Required

**Symptom:** Page 9 (تعلم المسيقة) has `subscription_error: twofa_required` in metadata.
The webhook subscription fails because Omar's personal Facebook account doesn't have 2FA enabled.

**Fix:** Omar enables 2FA at facebook.com → Settings → Security and Login → Two-Factor Authentication,
then clicks "retry here" on ot1-pro.com/connections page next to تعلم المسيقة.

---

## DB State Summary (production, 2026-04-26)

### Active Instagram Pages (team 3)
| Page | Name | platform_page_id | Method | Status |
|------|------|-----------------|--------|--------|
| 36 | Mishkah University IG | 17841406970888724 | Via Meta | ✅ Active, ⏳ no webhooks yet |
| 39 | Omar Mohamed Eltak (omar_eltak88) | 17841429680280453 | Direct IG Login | ✅ Reactivated this session |

### Inactive Instagram Pages (can be ignored)
| Page | Name | Issue |
|------|------|-------|
| 13 | Omar (team 4 duplicate) | Wrong team, inactive |
| 28 | Id'z | Was connected by Ahmed via Meta, inactive |
| 37 | Almotaheda wood | Was connected by Ahmed via Meta, inactive |
| 40 | Omar (duplicate of 39, different ID) | Deactivated this session |
| 42 | Instagram (unknown) | Failed IG Business Login, inactive |

### Facebook Messenger Pages (active, team 3)
| Page | Name | Status |
|------|------|--------|
| 9 | تعلم المسيقة | 🟡 2FA error — not subscribed |
| 18 | الدار ELDAR | ✅ Working |
| 23 | Arabicwithmishkah | ✅ Working (Ahmed's account) |
| 24 | إجازة أونلاين | ✅ Working (Ahmed's account) |
| 25 | Mishkah University FB | ✅ Working (Omar's account) |
| 41 | Brandk | ✅ Working |

---

## Action Items

| Priority | Item | Owner | Status |
|----------|------|-------|--------|
| ✅ Done | Fix self-heal crash (all IG webhooks dropping) | Claude | Fixed + deployed |
| ✅ Done | Reactivate page 39 (Omar IG) | Claude | Fixed in prod DB |
| ✅ Done | Fix IG send URL (wrong page ID) | Claude | Fixed + deployed |
| ✅ Done | Rename "Connect via Facebook" → "Connect via Meta" | Claude | Fixed + deployed |
| 👤 Omar | Enable 2FA on Facebook account → retry تعلم المسيقة subscription | Omar | Pending |
| 👤 Ahmed | Accept Instagram Tester invitation for sub-app 1408745007038040 | Ahmed | Pending |
| 👤 Friend | Convert IG to Business/Creator account to use Direct IG Login | Friend | Pending |
| ⏳ Meta | App Review approval for `instagram_manage_messages` | Meta | Pending review |
