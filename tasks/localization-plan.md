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
| `[ ]` | `layouts/app.blade.php` | App shell — check for hardcoded strings |
| `[ ]` | `layouts/app/header.blade.php` | Top bar, notifications, team switcher |
| `[ ]` | `layouts/app/sidebar.blade.php` | Nav labels (Inbox, Contacts, Campaigns, etc.) |
| `[ ]` | `layouts/auth.blade.php` | Auth shell |
| `[ ]` | `components/desktop-user-menu.blade.php` | Settings / Log Out labels |
| `[ ]` | `components/settings/layout.blade.php` | Settings nav tabs |
| `[ ]` | `components/auth-header.blade.php` | Auth page heading |
| `[ ]` | `partials/head.blade.php` | Meta tags, title |
| `[ ]` | `partials/ai-quota-banner.blade.php` | Banner copy |
| `[ ]` | `partials/settings-heading.blade.php` | Section heading |

---

## 2. Auth Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/auth/login.blade.php` | Email, password labels, "Sign in" button |
| `[ ]` | `livewire/auth/register.blade.php` | All form fields and CTA |
| `[ ]` | `livewire/auth/forgot-password.blade.php` | Instructions and button |
| `[ ]` | `livewire/auth/reset-password.blade.php` | Form labels |
| `[ ]` | `livewire/auth/confirm-password.blade.php` | Confirmation copy |
| `[ ]` | `livewire/auth/verify-email.blade.php` | Email verification copy |
| `[ ]` | `livewire/auth/two-factor-challenge.blade.php` | 2FA prompt copy |

---

## 3. Core App Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/dashboard.blade.php` | Stats labels, empty states, CTAs |
| `[ ]` | `livewire/inbox/index.blade.php` | Conversation list, status badges, filter labels |
| `[ ]` | `livewire/contacts/index.blade.php` | Table headers, actions, search placeholder |
| `[ ]` | `livewire/content/index.blade.php` | Content labels |
| `[ ]` | `livewire/analytics.blade.php` | Chart labels, metric names |
| `[ ]` | `livewire/ai-chat.blade.php` | Chat UI copy, placeholders |
| `[ ]` | `livewire/pay-wire.blade.php` | Payment / billing UI copy |

---

## 4. Campaigns

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/campaigns/index.blade.php` | List view, status badges |
| `[ ]` | `livewire/campaigns/show.blade.php` | Campaign detail, stats |
| `[ ]` | `livewire/campaigns/email-wizard.blade.php` | All wizard step labels |

---

## 5. Connections

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/connections/index.blade.php` | Platform cards, status indicators, action buttons |
| `[ ]` | `livewire/connections/whatsapp-qr-modal.blade.php` | Modal copy, instructions |

---

## 6. Settings Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/settings/profile.blade.php` | Field labels, save button |
| `[ ]` | `livewire/settings/password.blade.php` | Field labels |
| `[ ]` | `livewire/settings/appearance.blade.php` | Theme labels |
| `[ ]` | `livewire/settings/billing.blade.php` | Plan names, billing copy |
| `[ ]` | `livewire/settings/ai-config.blade.php` | AI config labels, descriptions |
| `[ ]` | `livewire/settings/ai-settings.blade.php` | AI settings copy |
| `[ ]` | `livewire/settings/quick-replies.blade.php` | Quick replies UI copy |
| `[ ]` | `livewire/settings/webhook-logs.blade.php` | Table headers, status labels |
| `[ ]` | `livewire/settings/admin-management.blade.php` | Admin panel copy |
| `[ ]` | `livewire/settings/two-factor.blade.php` | 2FA setup copy |
| `[ ]` | `livewire/settings/two-factor/recovery-codes.blade.php` | Recovery codes copy |
| `[ ]` | `livewire/settings/delete-user-form.blade.php` | Danger zone copy |

---

## 7. Teams

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/teams/create.blade.php` | Create team form labels |

---

## 8. Super Admin

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `livewire/super-admin/customers.blade.php` | Customer table, actions |
| `[ ]` | `livewire/super-admin/subscriptions.blade.php` | Subscription management copy |
| `[ ]` | `livewire/super-admin/page-assignments.blade.php` | Assignment UI copy |
| `[ ]` | `livewire/super-admin/onboarding-requests.blade.php` | Onboarding request copy |

---

## 9. Marketing — Core Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `welcome.blade.php` | Home page hero, features, testimonials, FAQs |
| `[ ]` | `pages/features.blade.php` | Features page copy |
| `[ ]` | `pages/pricing.blade.php` | Pricing tiers, feature lists |
| `[ ]` | `pages/about.blade.php` | About page copy |
| `[ ]` | `pages/contact.blade.php` | Contact form labels |
| `[ ]` | `pages/privacy.blade.php` | Privacy policy (consider whether Arabic version is needed) |
| `[ ]` | `pages/terms.blade.php` | Terms of service |
| `[ ]` | `pages/refund.blade.php` | Refund policy |
| `[ ]` | `pages/data-deletion-status.blade.php` | Data deletion page |

---

## 10. Marketing — Platform Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `pages/whatsapp-inbox.blade.php` | WhatsApp landing page copy |
| `[ ]` | `pages/instagram-dm.blade.php` | Instagram landing page copy |
| `[ ]` | `pages/facebook-messenger.blade.php` | Facebook landing page copy |
| `[ ]` | `pages/telegram-inbox.blade.php` | Telegram landing page copy |

---

## 11. Marketing — Industry Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `pages/industries/real-estate.blade.php` | Real estate industry page |
| `[ ]` | `pages/industries/ecommerce.blade.php` | E-commerce industry page |
| `[ ]` | `pages/industries/agencies.blade.php` | Agencies industry page |
| `[ ]` | `pages/industries/restaurants.blade.php` | Restaurants industry page |
| `[ ]` | `pages/industries/education.blade.php` | Education industry page |

---

## 12. Marketing — Comparison Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `pages/vs/trengo.blade.php` | vs Trengo comparison |
| `[ ]` | `pages/vs/manychat.blade.php` | vs ManyChat comparison |
| `[ ]` | `pages/vs/freshchat.blade.php` | vs Freshchat comparison |
| `[ ]` | `pages/vs/respond-io.blade.php` | vs Respond.io comparison |
| `[ ]` | `pages/vs/tidio.blade.php` | vs Tidio comparison |

---

## 13. Blog

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `blog/index.blade.php` | Blog listing UI chrome (not post content) |
| `[ ]` | `blog/show.blade.php` | Blog post chrome, meta labels |

---

## 14. Emails

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `emails/unsubscribe.blade.php` | Unsubscribe email template |
| `[ ]` | `emails/unsubscribed.blade.php` | Confirmation email template |

---

## 15. Error Pages

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `errors/419.blade.php` | CSRF / session expired error page |

---

## 16. Partials & Demos

| Status | File | Notes |
|--------|------|-------|
| `[ ]` | `partials/home-inbox-demo.blade.php` | Demo component copy |
| `[ ]` | `partials/home-agency-stack.blade.php` | Agency stack section copy |

---

## Notes

- **`lang/de.json` and `lang/es.json`** can be left on disk but are no longer active. Delete when confident they're not needed.
- **RTL check:** When testing Arabic, verify every page has `dir="rtl"` applied (set by `SetLocale` middleware) and that Tailwind's `start/end` utilities are used instead of `left/right` where needed.
- **New keys:** When adding keys to `lang/ar.json`, keep them alphabetically sorted to avoid merge conflicts.
- **Priority order:** Start with sections 1–3 (layouts + auth + core app) since those affect every logged-in user. Marketing pages (9–13) can follow.
