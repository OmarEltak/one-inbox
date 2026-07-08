# Localization Plan — Arabic & English

**Scope:** Arabic (ar) + English (en) only. Deutsch and Español have been removed.  
**Strategy:** Every hardcoded string visible to users must go through `__('key')`. Arabic uses RTL layout — verify `dir="rtl"` is applied correctly on every page.  
**Legend:** `[ ]` = todo · `[x]` = done

---

## How to mark done

Change `[ ]` to `[x]` when both conditions are met:
1. All visible strings on the page are wrapped in `__()`
2. Arabic translation keys exist in `lang/ar.json` and the page looks correct in RTL

---

## 1. Layouts & Shared Components

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `components/layouts/marketing.blade.php` | Language switcher already uses `__()` for labels; DE/ES removed |
| `[x]` | `layouts/marketing.blade.php` | Simple layout switcher — DE/ES removed |
| `[x]` | `layouts/app.blade.php` | No hardcoded user-visible strings |
| `[x]` | `layouts/app/header.blade.php` | Already uses `__()` throughout |
| `[x]` | `layouts/app/sidebar.blade.php` | All nav labels, onboarding modal localized |
| `[x]` | `layouts/auth.blade.php` | Wrapper only — no hardcoded strings |
| `[x]` | `components/desktop-user-menu.blade.php` | Settings / Log Out localized |
| `[x]` | `components/settings/layout.blade.php` | All nav tabs localized |
| `[x]` | `components/auth-header.blade.php` | Props-based, no hardcoded strings |
| `[x]` | `partials/head.blade.php` | No user-visible strings |
| `[x]` | `partials/ai-quota-banner.blade.php` | All copy localized |
| `[x]` | `partials/settings-heading.blade.php` | Already uses `__()` |

---

## 2. Auth Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/auth/login.blade.php` | Fully localized |
| `[x]` | `livewire/auth/register.blade.php` | Fully localized |
| `[x]` | `livewire/auth/forgot-password.blade.php` | Already used `__()` throughout |
| `[x]` | `livewire/auth/reset-password.blade.php` | Already used `__()` throughout |
| `[x]` | `livewire/auth/confirm-password.blade.php` | Already used `__()` throughout |
| `[x]` | `livewire/auth/verify-email.blade.php` | Already used `__()` throughout |
| `[x]` | `livewire/auth/two-factor-challenge.blade.php` | Already used `__()` throughout |

---

## 3. Core App Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/dashboard.blade.php` | Stats labels, empty states, CTAs, AI ON/OFF |
| `[x]` | `livewire/inbox/index.blade.php` | Conversation list, status badges, filter labels |
| `[x]` | `livewire/contacts/index.blade.php` | Table headers, actions, search placeholder |
| `[x]` | `livewire/content/index.blade.php` | Content labels |
| `[x]` | `livewire/analytics.blade.php` | Chart labels (via @js()), metric names |
| `[x]` | `livewire/ai-chat.blade.php` | Chat UI copy, placeholders |
| `[x]` | `livewire/pay-wire.blade.php` | Payment / billing UI copy |

---

## 4. Campaigns

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/campaigns/index.blade.php` | List view, status badges |
| `[x]` | `livewire/campaigns/show.blade.php` | Campaign detail, stats |
| `[x]` | `livewire/campaigns/email-wizard.blade.php` | All wizard step labels |

---

## 5. Connections

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/connections/index.blade.php` | Platform cards, status indicators, action buttons |
| `[x]` | `livewire/connections/whatsapp-qr-modal.blade.php` | Modal copy, instructions |

---

## 6. Settings Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/settings/profile.blade.php` | Fully localized |
| `[x]` | `livewire/settings/password.blade.php` | Fully localized |
| `[x]` | `livewire/settings/appearance.blade.php` | Fully localized |
| `[x]` | `livewire/settings/billing.blade.php` | Fully localized |
| `[x]` | `livewire/settings/ai-config.blade.php` | Fully localized |
| `[x]` | `livewire/settings/ai-settings.blade.php` | Fully localized |
| `[x]` | `livewire/settings/quick-replies.blade.php` | Fully localized |
| `[x]` | `livewire/settings/webhook-logs.blade.php` | Table headers, filters, status labels |
| `[x]` | `livewire/settings/admin-management.blade.php` | Admin panel, modal labels |
| `[x]` | `livewire/settings/two-factor.blade.php` | Fully localized |
| `[x]` | `livewire/settings/two-factor/recovery-codes.blade.php` | Fully localized |
| `[x]` | `livewire/settings/delete-user-form.blade.php` | Fully localized |

---

## 7. Teams

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/teams/create.blade.php` | Create team form labels |

---

## 8. Super Admin

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `livewire/super-admin/customers.blade.php` | Customer table, actions |
| `[x]` | `livewire/super-admin/subscriptions.blade.php` | Subscription management copy |
| `[x]` | `livewire/super-admin/page-assignments.blade.php` | Assignment UI copy |
| `[x]` | `livewire/super-admin/onboarding-requests.blade.php` | Onboarding request copy |

---

## 9. Marketing — Core Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `welcome.blade.php` | Home page hero, features, testimonials, FAQs |
| `[x]` | `pages/features.blade.php` | Features page copy |
| `[x]` | `pages/pricing.blade.php` | Pricing tiers, feature lists |
| `[x]` | `pages/about.blade.php` | About page copy |
| `[x]` | `pages/contact.blade.php` | Contact form labels |
| `[x]` | `pages/privacy.blade.php` | Privacy policy |
| `[x]` | `pages/terms.blade.php` | Terms of service |
| `[x]` | `pages/refund.blade.php` | Refund policy |
| `[x]` | `pages/data-deletion-status.blade.php` | Data deletion page |

---

## 10. Marketing — Platform Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `pages/whatsapp-inbox.blade.php` | WhatsApp landing page copy |
| `[x]` | `pages/instagram-dm.blade.php` | Instagram landing page copy |
| `[x]` | `pages/facebook-messenger.blade.php` | Facebook landing page copy |
| `[x]` | `pages/telegram-inbox.blade.php` | Telegram landing page copy |

---

## 11. Marketing — Industry Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `pages/industries/real-estate.blade.php` | Localized |
| `[x]` | `pages/industries/ecommerce.blade.php` | Localized |
| `[x]` | `pages/industries/agencies.blade.php` | Localized |
| `[x]` | `pages/industries/restaurants.blade.php` | Localized |
| `[x]` | `pages/industries/education.blade.php` | Localized |

---

## 12. Marketing — Comparison Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `pages/vs/trengo.blade.php` | Localized |
| `[x]` | `pages/vs/manychat.blade.php` | Localized |
| `[x]` | `pages/vs/freshchat.blade.php` | Localized |
| `[x]` | `pages/vs/respond-io.blade.php` | Localized |
| `[x]` | `pages/vs/tidio.blade.php` | Localized |

---

## 13. Blog

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `blog/index.blade.php` | Blog listing UI chrome |
| `[x]` | `blog/show.blade.php` | Blog post chrome, meta labels |

---

## 14. Emails

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `emails/unsubscribe.blade.php` | Unsubscribe email template |
| `[x]` | `emails/unsubscribed.blade.php` | Confirmation email template |

---

## 15. Error Pages

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `errors/419.blade.php` | CSRF / session expired error page |

---

## 16. Partials & Demos

| Status | File | Notes |
|--------|------|-------|
| `[x]` | `partials/home-inbox-demo.blade.php` | Demo component copy |
| `[x]` | `partials/home-agency-stack.blade.php` | Agency stack section copy |

---

## Notes

- **`lang/de.json` and `lang/es.json`** can be left on disk but are no longer active. Delete when confident they're not needed.
- **RTL check:** When testing Arabic, verify every page has `dir="rtl"` applied (set by `SetLocale` middleware) and that Tailwind's `start/end` utilities are used instead of `left/right` where needed.
- **New keys:** When adding keys to `lang/ar.json`, keep them alphabetically sorted to avoid merge conflicts.
- **Priority order:** Start with sections 1–3 (layouts + auth + core app) since those affect every logged-in user. Marketing pages (9–13) can follow.
