# Comments AI Configuration — Phase A (Config only)

**Date:** 2026-08-31
**Status:** Approved, ready to plan
**Scope:** Phase A of a multi-phase feature. Adds a fifth "Comments" tab to `/settings/ai/config` so customers can configure how the AI will behave on Facebook and Instagram post comments once ingestion is wired up in a later phase.

---

## 1. Why phased?

Full comment support has three big pieces:

1. **Config** (this spec) — UI + storage for per-page comment behavior.
2. **Ingestion** — subscribe to Meta `feed` (FB) and `comments` (IG) webhook fields; store incoming comments; dispatch AI job.
3. **Sending** — public reply via Graph API, one-shot Private Reply / IG `private_replies` DM, respecting Meta's 24h / 7-day windows.

Pieces 2 and 3 require Meta permissions we don't have yet — `pages_manage_engagement`, `instagram_manage_comments` — which are only unlocked after our current App Review passes. Building config first means:

- The moment those permissions activate, customers already have their preferences saved. No re-onboarding nag.
- Zero runtime cost added today. The feature is dormant JSON until Phase B reads it.
- The UI ships a truthful "coming soon once Meta approves" banner rather than pretending to work.

---

## 2. Non-goals (deferred to Phase B/C)

- Ingesting comment webhooks
- Storing a `Comment` model or a `comment_replies` table
- Any Graph API send code
- Meta App Review submission for the two new permissions (that is a product/ops task, not code)
- Comment moderation (hide/delete)
- Analytics on comment activity
- Bulk actions on historical comments

---

## 3. Storage — single JSON column

Add one column to `ai_configs`:

```php
Schema::table('ai_configs', function (Blueprint $t) {
    $t->json('comment_settings')->nullable()->after('escalation_topics');
});
```

- Nullable — existing rows stay valid, null is treated as "feature never touched" (master switch off).
- No backfill.
- Cast to `array` in `AiConfig::casts()`, matching the existing pattern for `working_hours`, `escalation_topics`, `product_catalog`, etc.
- Add `comment_settings` to `AiConfig::$fillable`.

### JSON shape

```
comment_settings = {
    "enabled":                              bool,        // master switch, default false
    "enabled_at":                           ISO 8601 | null, // stamped when enabled flips false→true
    "reply_mode":                           "off" | "all" | "questions_and_complaints" | "custom_keywords",
    "reply_keywords":                       string[],    // used only when reply_mode = custom_keywords
    "dm_mode":                              "off" | "always" | "on_purchase_intent",
    "dm_keywords":                          string[],    // used only when dm_mode = on_purchase_intent
    "reply_instructions":                   string,      // max 500 chars, freeform persona nudge
    "scope":                                "future_posts_only" | "all_posts",
    "max_ai_replies_per_post_per_day":      int,         // 1..100, default 20
}
```

All keys are always present after save (the Livewire component packs them fully); this avoids null-checking noise downstream.

### Constants (add to `AiConfig`)

```php
public const COMMENT_REPLY_OFF                     = 'off';
public const COMMENT_REPLY_ALL                     = 'all';
public const COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS = 'questions_and_complaints';
public const COMMENT_REPLY_CUSTOM_KEYWORDS         = 'custom_keywords';

public const COMMENT_DM_OFF                = 'off';
public const COMMENT_DM_ALWAYS             = 'always';
public const COMMENT_DM_ON_PURCHASE_INTENT = 'on_purchase_intent';

public const COMMENT_SCOPE_FUTURE_ONLY = 'future_posts_only';
public const COMMENT_SCOPE_ALL_POSTS   = 'all_posts';

public const COMMENT_MAX_REPLIES_PER_POST_MIN =   1;
public const COMMENT_MAX_REPLIES_PER_POST_MAX = 100;
```

Add a `public static function defaultCommentSettings(): array` that returns the "everything off, safe defaults" payload. Used both by the Livewire component's `mount()` when `comment_settings` is null and by future Phase B code that needs a shape guarantee.

---

## 4. UI changes — `resources/views/livewire/settings/ai-config.blade.php`

### 4.1 Tab strip (line 91-97)

Make the `$tabs` array conditional on platform:

```php
$tabs = [
    'sales_goal' => ['label' => __('Sales Goal'), 'icon' => 'flag'],
    'knowledge'  => ['label' => __('Knowledge'),  'icon' => 'book-open'],
    'behavior'   => ['label' => __('Behavior'),   'icon' => 'sparkles'],
    'handoff'    => ['label' => __('Handoff'),    'icon' => 'user-group'],
];

if (in_array($selectedPage->platform, ['facebook', 'instagram'], true)) {
    $tabs['comments'] = ['label' => __('Comments'), 'icon' => 'chat-bubble-oval-left-ellipsis'];
}
```

The tab is hidden entirely for WhatsApp, Telegram, and email — no dead UI.

### 4.2 New tab block

Insert after the Handoff tab block (~line 527) and before `Save`.

Sections, in order:

1. **Coming-soon banner** — violet info card explaining the feature activates once Meta App Review approves comment permissions. Non-dismissible; it's the truth, not an ad.
2. **Master switch** — `comment_enabled` toggle in a big card, same visual language as the `is_active` card at the top of the form. When off, sections 3–8 are hidden.
3. **Public reply mode** — 4-card grid using the same button+card style as Sales Goal presets (line 130). Cards: Off / All / Questions & complaints only / Custom keywords. When "Custom keywords" is selected, show a keyword-chip block underneath (reuse the exact chip UI from Handoff tab lines 411-424, just bound to `comment_reply_keywords`).
4. **DM behavior** — 3-card grid: Off / Always DM after replying / DM only on purchase-intent. When "purchase-intent" is selected, show a keyword-chip block bound to `comment_dm_keywords`. Prefill defaults (English + Arabic): `price, cost, order, buy, how much, ثمن, سعر, أوردر, بكم`. Under the section, a small helper line explaining the 24h/7-day one-shot DM windows.
5. **Reply persona nudge** — one `flux:textarea` bound to `comment_reply_instructions`, 500-char maxlength with counter (mirror the pattern used for `business_description` on line 188). Placeholder text as noted in the design conversation.
6. **Scope** — two radio-style cards: "Only comments on posts made after this setting was enabled" (default) vs "All comments on any post."
7. **Rate limit** — one `flux:input` type=number bound to `comment_max_replies_per_post_per_day`, min 1, max 100, default 20. Same visual pattern as `contact_ai_reply_cap` (line 517).

All labels and text zinc-900-forced to match the rest of the form's dark-mode overrides.

---

## 5. Livewire component changes

File: `app/Livewire/Settings/AiConfig.php` (verify path during implementation; if it lives elsewhere, adapt).

### 5.1 New public properties

```php
public bool   $comment_enabled = false;
public string $comment_reply_mode = 'off';
public array  $comment_reply_keywords = [];
public string $comment_dm_mode = 'off';
public array  $comment_dm_keywords = [];
public string $comment_reply_instructions = '';
public string $comment_scope = 'future_posts_only';
public int    $comment_max_replies_per_post_per_day = 20;
```

### 5.2 `mount()` / `loadConfig($config)` hydration

If `$config->comment_settings` is null, fall back to `AiConfig::defaultCommentSettings()`. Then assign each field. Never leave a prop unset — the form binds directly.

### 5.3 New methods (thin, copy-paste from existing keyword handlers)

```php
public function addCommentReplyKeyword(): void
public function removeCommentReplyKeyword(int $index): void
public function addCommentDmKeyword(): void
public function removeCommentDmKeyword(int $index): void
```

Each is 1–3 lines, matching `addEscalationKeyword` / `removeEscalationKeyword`.

### 5.4 `saveConfig()` changes

Before the existing `$config->save()`, pack the props back into an array and set `comment_settings`:

```php
$wasEnabled = (bool) data_get($config->comment_settings, 'enabled', false);
$nowEnabled = $this->comment_enabled;

$config->comment_settings = [
    'enabled'                          => $nowEnabled,
    'enabled_at'                       => ($nowEnabled && ! $wasEnabled)
        ? now()->toIso8601String()
        : data_get($config->comment_settings, 'enabled_at'),
    'reply_mode'                       => in_array($this->comment_reply_mode, [
        AiConfig::COMMENT_REPLY_OFF,
        AiConfig::COMMENT_REPLY_ALL,
        AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS,
        AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS,
    ], true) ? $this->comment_reply_mode : AiConfig::COMMENT_REPLY_OFF,
    'reply_keywords'                   => array_values(array_filter(array_map('trim', $this->comment_reply_keywords))),
    'dm_mode'                          => in_array($this->comment_dm_mode, [
        AiConfig::COMMENT_DM_OFF,
        AiConfig::COMMENT_DM_ALWAYS,
        AiConfig::COMMENT_DM_ON_PURCHASE_INTENT,
    ], true) ? $this->comment_dm_mode : AiConfig::COMMENT_DM_OFF,
    'dm_keywords'                      => array_values(array_filter(array_map('trim', $this->comment_dm_keywords))),
    'reply_instructions'               => mb_substr((string) $this->comment_reply_instructions, 0, 500),
    'scope'                            => in_array($this->comment_scope, [
        AiConfig::COMMENT_SCOPE_FUTURE_ONLY,
        AiConfig::COMMENT_SCOPE_ALL_POSTS,
    ], true) ? $this->comment_scope : AiConfig::COMMENT_SCOPE_FUTURE_ONLY,
    'max_ai_replies_per_post_per_day'  => max(
        AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN,
        min(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX, (int) $this->comment_max_replies_per_post_per_day)
    ),
];
```

Notes:
- The `enabled_at` transition rule: stamp on false→true, preserve on true→true, preserve on true→false (so re-enabling later can reuse the last enabled window if the customer prefers). If we want re-stamp on every off→on cycle instead, change the condition — but per design conversation, we re-stamp only on false→true, which is what this code does.
- Enum values are validated against the `AiConfig` constants — invalid inputs silently coerce to safe defaults. Defense in depth: even if the browser tampers, the row can never contain an unknown enum value.
- Rate-limit clamped both sides at save time (browser can bypass min/max attributes).

### 5.5 `setTab()` guard

If a WhatsApp/Telegram page is selected and `activeTab === 'comments'` for any reason (URL param, stale state), reset to `'sales_goal'`. Prevents the "invisible tab renders a hidden form" edge case.

---

## 6. Migration

`database/migrations/YYYY_MM_DD_HHMMSS_add_comment_settings_to_ai_configs.php`

```php
return new class extends Migration {
    public function up(): void {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->json('comment_settings')->nullable()->after('escalation_topics');
        });
    }

    public function down(): void {
        Schema::table('ai_configs', function (Blueprint $table) {
            $table->dropColumn('comment_settings');
        });
    }
};
```

No data change. Reversible. Runs on the auto-deploy pipeline like any other migration.

---

## 7. Tests

Phase A test scope is small on purpose — there is no runtime behavior to exercise, only persistence and validation.

### 7.1 Unit — `tests/Unit/Models/AiConfigCommentSettingsTest.php`

- `comment_settings` cast round-trips as an array.
- `defaultCommentSettings()` returns every documented key with safe defaults.
- Model still hydrates when `comment_settings` is null.

### 7.2 Feature — `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`

- Tab is present in rendered HTML when the selected page is Facebook or Instagram.
- Tab is absent when the selected page is WhatsApp, Telegram, or email.
- Saving with `comment_enabled = true` stamps `enabled_at` once and preserves it on subsequent saves.
- Rate-limit clamp: submitting 999 lands at 100, submitting 0 lands at 1.
- Invalid enum values (e.g., `reply_mode = "yolo"`) coerce to their safe default.
- Reload after save preserves every field.

No AI/pipeline tests — those belong to Phase B.

---

## 8. Performance

- Runtime cost added today: **zero**. The column is loaded as part of the existing `AiConfig` eager-load; no new queries, no new jobs, no scheduler entries, no cache lookups.
- Storage cost: one nullable JSON column per page. On typical config payload, ~300 bytes serialized.
- When Phase B ships and this JSON gets read on every incoming comment, the shape is already flat — no joins needed to decide whether to reply, in what mode, or with what keywords. That matters for a small server processing bursty webhook traffic.

---

## 9. Explicit anti-scope reminders (per CLAUDE.md pins)

- **Do not touch** `$metaVerified`, the connections page, or the managed-onboarding flow. This feature is entirely inside the AI config page.
- **Do not touch** `Team::canDispatchAi()` — comment dispatch is Phase B and will compose its condition inside `canDispatchAi()` at that time.
- **Do not touch** provider fallback strings, `NaraRouterProvider::coalesceRoles()`, or the reactivation guard — Phase A adds no AI calls.
- **Do not touch** `Page::booted()` — no page-level changes.

---

## 10. Rollout

1. Ship migration + code together via the normal `git push origin main` → GitHub Actions auto-deploy.
2. No data seeding needed.
3. No prod `.env` change needed.
4. No systemd reload needed (no new jobs / no queue consumers).
5. Announce nothing to customers yet — the coming-soon banner in the tab itself is the communication. When Meta App Review lands the two permissions, Phase B ships and the banner comes down.

---

## 11. Open questions (none blocking)

- Whether "Always DM after replying" should also fire when the reply mode is "Questions & complaints only" — assume yes for now; revisit if it feels aggressive in Phase B testing.
- Whether Instagram Reels comments follow the same webhook shape as feed post comments (Meta docs are ambiguous). Not a Phase A concern.
