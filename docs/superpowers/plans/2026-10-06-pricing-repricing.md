# AI Credit Economy — Re-pricing to 4-tier ladder

**Date**: 2026-10-06
**Depends on**: all 8 phases of `2026-10-05-ai-credit-economy.md` (shipped locally, uncommitted)
**Why**: pricing pivot — "everything free except costly ops" (bulk campaigns, Deep Analysis, WhatsApp Meta API, AI usage). Collapse 6-tier ladder → 4 clear tiers with concrete limits.

---

## Final ladder (confirmed with Omar)

| Feature | **Free** | **Starter** | **Pro** | **Business** |
|---|:---:|:---:|:---:|:---:|
| **Price / month (USD)** | 0 | 8 | 29 | 99 |
| **Monthly AI credits included** | 100 | 500 | 3,000 | 12,000 |
| **Facebook / Instagram pages** | 1 | 3 | 8 | 20 |
| **WhatsApp Business API numbers** | 0 | 1 | 3 | 10 |
| **Bulk campaigns / month** | 0 | 3 | 15 | **100** |
| **Contacts stored** | 1,000 | 5,000 | 25,000 | 500,000 |
| Priority support | — | — | ✅ | ✅ |
| White-label | — | — | — | ✅ |

**Credit packs** (never expire, work on any plan):

| Pack | USD | Credits |
|---|---:|---:|
| Small | 10 | 1,000 |
| Medium | 25 | 3,000 |
| Large | 100 | 15,000 |

**Per-action costs** (in `config/ai_costs.php`):

| Action | Credits |
|---|---:|
| AI reply to customer | 1 |
| AI chat message | 1 |
| AI comment reply | 1 |
| Lead scoring | 0 (free, we eat it) |
| Deep Analysis | 5 per 100 contacts (min 10) |
| Agent audit | max(3, deep-analysis scaling) |
| **Bulk campaign recipient (NEW)** | 1 each |
| **WhatsApp Meta API conversation (NEW)** | 10 per 24h window |

---

## Checklist — V1 scope (committed in this phase)

### A — Config changes

- [ ] `config/plans.php` — collapse 6 plans → 4. Add `limits` sub-array with `pages`, `whatsapp_numbers`, `bulk_campaigns_monthly`, `contacts_stored`. Keep `price_id` nullable (no Stripe wired).
- [ ] `config/plans.php` — update packs section to the new $10/$25/$100 numbers (same as before; verify).
- [ ] `config/ai_costs.php` — add `bulk_campaign_recipient => 1` and `whatsapp_meta_conversation => 10` keys.
- [ ] `.env.example` — no new vars needed (manual payment already there).

### B — Business logic enforcement

- [ ] `app/Models/Team.php` — `canCreateCampaign()` already exists. Update its reader to pull from `config('plans.plans.{plan}.limits.bulk_campaigns_monthly')` instead of the current `monthly_limits` key. Preserve rolling-30-days window from Phase 2.
- [ ] `app/Jobs/SendCampaignWhatsAppJob.php` — after successful send, call `AiCredits::charge($team, 'bulk_campaign_recipient', [idempotency_key: "campaign:{campaign_id}:recipient:{recipient_id}"])`. Wrap in try/catch — ledger failure must not undo a message the customer already received.
- [ ] `app/Jobs/SendCampaignEmailJob.php` — same pattern.
- [ ] Verify no other campaign-send job exists. Grep for `SendCampaign` to confirm.

### C — User-facing UI updates

- [ ] `resources/views/livewire/settings/top-up.blade.php` — update the plans table to show 4 tiers with the new limit columns (pages, WA numbers, campaigns/mo, contacts). Add visual indicator for current plan.
- [ ] `app/Livewire/Settings/TopUp.php` — update the plans property to render the new ladder (loop over `config('plans.plans')`, exclude Free, exclude any plan without a price_id).
- [ ] `resources/views/pages/pricing-faq.blade.php` — update the plan comparison table at top to the 4 tiers. Keep the dynamic cost table from `config('ai_costs')`.
- [ ] `resources/views/livewire/campaigns/index.blade.php` (and WhatsApp/Email wizards) — the quota chip currently shows "X/Y campaigns this month" from Phase 2. Change the data source to the new config path. Verify the amber/red/emerald thresholds still work.

### D — Arabic strings

- [ ] `lang/ar.json` — add new strings: "Starter", "Business", limit labels ("Facebook / Instagram pages", "WhatsApp Business API numbers", "Bulk campaigns / month", "Contacts stored", "Priority support", "White-label"), per-recipient charging error messages.
- [ ] Verify Arabic coverage test is still 100 % after the update.

### E — Tests

- [ ] `tests/Feature/Campaigns/PlanMonthlyLimitTest.php` — existing test file. Update numeric expectations: Free=0 (changed from 1), Starter=3 (changed from 5), Pro=15 (changed from 25), Business=100 (new row).
- [ ] `tests/Feature/Billing/BulkCampaignCreditChargeTest.php` (NEW) — assert:
    1. A successful WhatsApp campaign send writes a `bulk_campaign_recipient` ledger row.
    2. The idempotency key prevents double-charge if the job retries.
    3. Email campaign send does the same.
    4. If `AiCredits::charge` throws (ledger failure), the message is still marked sent (we don't undo real sends).
- [ ] `tests/Feature/Settings/TopUpPageTest.php` — update to assert the 4 tiers render and the new limit columns are visible.
- [ ] `tests/Feature/Pages/PricingFaqPageTest.php` — assert new ladder renders.
- [ ] All existing tests must still pass. Baseline is 6 pre-existing failures (PasswordReset×3, TestSendThrottle, ConciergeFraming, Dashboard). Target: still 6 pre-existing, 0 new regressions.

### F — Deferred (NOT in this commit — note in journal + create follow-up tasks)

- [ ] `whatsapp_numbers` enforcement — config exists but no `Team::canConnectWhatsAppNumber()` check yet (WhatsApp Cloud API not wired to a connect flow).
- [ ] `contacts_stored` enforcement — needs separate design to avoid dropping real customer webhooks when a team hits the cap. Document the "upgrade to receive new contacts" UX before enforcing.
- [ ] WhatsApp Meta API per-conversation 10-credit charge — the cost is in `ai_costs.php`, but there's no call site yet (we don't dispatch outbound WhatsApp via Meta Cloud API).

---

## Verification checklist (after code lands, before commit)

- [ ] `php -d memory_limit=1G vendor/bin/pest` — 6 baseline fails only, no new regressions
- [ ] `php -d memory_limit=1G vendor/bin/pest tests/Unit/ArabicTranslationCoverageTest.php` — 16/16 green
- [ ] Spot-grep: `config('stripe` returns 0 matches
- [ ] Spot-grep: references to old plans (`starter`, `agency`, `enterprise` as config keys) — audit each to confirm it still resolves
- [ ] `git diff --stat` — report expected ~5-8 files changed

## Browser test (after commit + migrate)

### Pre-flight
1. `php artisan migrate` (no new migrations needed from re-pricing; this is just a safety net)
2. `php artisan config:clear`
3. `php artisan queue:work --queue=heavy-analysis,urgent,default` in another terminal

### Scenarios to drive via Claude-in-Chrome
1. Register fresh account → verify Free plan + 100 credit starting balance
2. `/settings/billing` → meter chip visible top-right, balance card renders
3. `/settings/billing/top-up` → **4 plans** render, **3 packs** render, payment methods panel visible
4. Click "chat with us about Starter" → WhatsApp opens with prefilled message naming Starter
5. `/pricing-faq` → 4-tier comparison table renders with correct limits, cost table from config
6. `/ai-chat` → ask "hello" → 1 credit decrements, meter updates
7. Ask "analyze last 1000 contacts" → **confirmation modal fires with 50 credits**, "you'll have 49 left"
8. Confirm → queue worker runs → chat gets "✅ complete" message
9. Try creating a bulk campaign on Free → blocked with "upgrade to Starter" message
10. Super-admin: `/super-admin/billing` → grant 500 wallet credits to test team → verify ledger
11. Locale toggle → Arabic → every page's new strings translate
12. **Any 500 → fix inline before continuing**

### Then prod
After local browser verification is clean:
1. `git push origin main`
2. Monitor GitHub Actions deploy (~24s)
3. SSH to prod → run both backfill seeders: `sudo -u deploy php artisan db:seed --class='Database\\Seeders\\BackfillAiCreditLedgerSeeder' --force && sudo -u deploy php artisan db:seed --class='Database\\Seeders\\BackfillHandledByUserIdSeeder' --force`
4. Browser-smoke prod on https://ot1-pro.com — hit the same screens from steps 2-11 above
5. Record findings in `tasks/journal.md`

---

## Rollback plan

- Config-only re-pricing is pure code change — no new migrations. `git revert` of the single commit reverts to the shipped 6-tier state.
- If prod needs a quick fix without a revert, set `LEGACY_MESSAGE_COUNTER=true` in `.env` + `config:cache` to pause the ledger-based banner behavior while diagnosing.
