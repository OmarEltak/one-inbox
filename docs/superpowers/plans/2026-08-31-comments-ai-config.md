# Comments AI Config (Phase A) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a fifth "Comments" tab to `/settings/ai/config` that lets customers configure per-page AI behavior on Facebook and Instagram post comments (public replies + one-shot commenter DMs), stored as a single JSON column. Config only — ingestion and sending are Phase B.

**Architecture:** One nullable JSON column (`comment_settings`) on the existing `ai_configs` table. New Livewire props on `App\Livewire\Settings\AiConfig` hydrate from and pack into that column. A conditional tab button in the Blade view renders for Facebook/Instagram pages only. Zero new queries, jobs, or scheduler entries.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI, Pest (with `RefreshDatabase` on Feature tests), MySQL 8 in prod / SQLite in local dev.

**Spec:** `docs/superpowers/specs/2026-08-31-comments-ai-config-design.md`

**Non-negotiable pins that apply (from CLAUDE.md):**
- Do NOT touch `$metaVerified`, connections page, managed onboarding.
- Do NOT touch `Team::canDispatchAi()` — no new AI dispatch sites in this phase.
- Do NOT edit files on the VPS. All changes go through `git push origin main` → auto-deploy.
- Seeders are not run automatically on deploy (not relevant here — no seeder).

---

## File Map

Will create:
- `database/migrations/2026_08_31_120000_add_comment_settings_to_ai_configs.php` — schema change.
- `tests/Unit/Models/AiConfigCommentSettingsTest.php` — model-level tests.
- `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php` — Livewire component tests.

Will modify:
- `app/Models/AiConfig.php` — constants, `$fillable`, casts, `defaultCommentSettings()`.
- `app/Livewire/Settings/AiConfig.php` — 8 new props, hydration in `selectPage()` and `resetForm()`, 4 new keyword-add/remove methods, packing + validation in `saveConfig()`, `setTab()` guard.
- `resources/views/livewire/settings/ai-config.blade.php` — conditional tab, new tab block after Handoff, before Save.

---

## Task 1: Migration for `comment_settings` column

**Files:**
- Create: `database/migrations/2026_08_31_120000_add_comment_settings_to_ai_configs.php`

- [ ] **Step 1: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_configs', function (Blueprint $table): void {
            $table->json('comment_settings')->nullable()->after('escalation_topics');
        });
    }

    public function down(): void
    {
        Schema::table('ai_configs', function (Blueprint $table): void {
            $table->dropColumn('comment_settings');
        });
    }
};
```

- [ ] **Step 2: Run migration locally**

Run: `php artisan migrate`
Expected: `INFO  Running migrations.` then `2026_08_31_120000_add_comment_settings_to_ai_configs .............. DONE`

- [ ] **Step 3: Verify the column exists**

Run: `php artisan tinker --execute="var_export(\Illuminate\Support\Facades\Schema::hasColumn('ai_configs', 'comment_settings'));"`
Expected: `true`

- [ ] **Step 4: Test rollback works**

Run: `php artisan migrate:rollback --step=1` then `php artisan tinker --execute="var_export(\Illuminate\Support\Facades\Schema::hasColumn('ai_configs', 'comment_settings'));"`
Expected: `false`

Then re-apply: `php artisan migrate`
Expected: migration runs again with `DONE`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_31_120000_add_comment_settings_to_ai_configs.php
git commit -m "feat(ai): add comment_settings JSON column to ai_configs"
```

---

## Task 2: `AiConfig` model — constants, fillable, cast, defaults

**Files:**
- Modify: `app/Models/AiConfig.php`
- Test: `tests/Unit/Models/AiConfigCommentSettingsTest.php` (create)

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Models/AiConfigCommentSettingsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\AiConfig;

it('exposes safe default comment settings', function () {
    $defaults = AiConfig::defaultCommentSettings();

    expect($defaults)->toBe([
        'enabled'                          => false,
        'enabled_at'                       => null,
        'reply_mode'                       => AiConfig::COMMENT_REPLY_OFF,
        'reply_keywords'                   => [],
        'dm_mode'                          => AiConfig::COMMENT_DM_OFF,
        'dm_keywords'                      => [],
        'reply_instructions'               => '',
        'scope'                            => AiConfig::COMMENT_SCOPE_FUTURE_ONLY,
        'max_ai_replies_per_post_per_day'  => 20,
    ]);
});

it('casts comment_settings as array on round-trip', function () {
    $config = new AiConfig();
    $config->comment_settings = ['enabled' => true, 'reply_mode' => 'all'];

    // Simulate a save/reload cycle via JSON encode/decode (mirrors DB behavior).
    $encoded = $config->getAttributes()['comment_settings'];
    expect($encoded)->toBeString();

    $decoded = json_decode($encoded, true);
    expect($decoded)->toBe(['enabled' => true, 'reply_mode' => 'all']);
});

it('exposes the expected constants', function () {
    expect(AiConfig::COMMENT_REPLY_OFF)->toBe('off');
    expect(AiConfig::COMMENT_REPLY_ALL)->toBe('all');
    expect(AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS)->toBe('questions_and_complaints');
    expect(AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS)->toBe('custom_keywords');

    expect(AiConfig::COMMENT_DM_OFF)->toBe('off');
    expect(AiConfig::COMMENT_DM_ALWAYS)->toBe('always');
    expect(AiConfig::COMMENT_DM_ON_PURCHASE_INTENT)->toBe('on_purchase_intent');

    expect(AiConfig::COMMENT_SCOPE_FUTURE_ONLY)->toBe('future_posts_only');
    expect(AiConfig::COMMENT_SCOPE_ALL_POSTS)->toBe('all_posts');

    expect(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN)->toBe(1);
    expect(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX)->toBe(100);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Unit/Models/AiConfigCommentSettingsTest.php`
Expected: FAIL — `Undefined constant App\Models\AiConfig::COMMENT_REPLY_OFF` (or similar) and `Method App\Models\AiConfig::defaultCommentSettings() does not exist`.

- [ ] **Step 3: Add constants**

In `app/Models/AiConfig.php`, add after the existing `CONTACT_CAP_MAX` constant (~line 34):

```php
    // Comments — Phase A (config only; ingestion + sending unlock in Phase B
    // once Meta App Review approves pages_manage_engagement + instagram_manage_comments).
    public const COMMENT_REPLY_OFF                      = 'off';
    public const COMMENT_REPLY_ALL                      = 'all';
    public const COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS = 'questions_and_complaints';
    public const COMMENT_REPLY_CUSTOM_KEYWORDS          = 'custom_keywords';

    public const COMMENT_DM_OFF                = 'off';
    public const COMMENT_DM_ALWAYS             = 'always';
    public const COMMENT_DM_ON_PURCHASE_INTENT = 'on_purchase_intent';

    public const COMMENT_SCOPE_FUTURE_ONLY = 'future_posts_only';
    public const COMMENT_SCOPE_ALL_POSTS   = 'all_posts';

    public const COMMENT_MAX_REPLIES_PER_POST_MIN =   1;
    public const COMMENT_MAX_REPLIES_PER_POST_MAX = 100;
```

- [ ] **Step 4: Add `comment_settings` to `$fillable`**

In the `$fillable` array (existing lines ~36-60), add `'comment_settings'` as the last entry before `'is_active'`:

```php
        'sales_methodology',
        'comment_settings',
        'is_active',
```

- [ ] **Step 5: Add `comment_settings` cast**

In `casts()` (existing method ~62-81), add before the closing array:

```php
            'comment_settings' => 'array',
```

- [ ] **Step 6: Add `defaultCommentSettings()` static method**

Add after `defaultEscalationKeywordsFor()` (~line 135):

```php
    /**
     * Safe defaults for the Comments tab. Every documented key is always present
     * so downstream Phase B code can rely on shape without null-checking.
     *
     * @return array{
     *     enabled: bool,
     *     enabled_at: string|null,
     *     reply_mode: string,
     *     reply_keywords: array<int, string>,
     *     dm_mode: string,
     *     dm_keywords: array<int, string>,
     *     reply_instructions: string,
     *     scope: string,
     *     max_ai_replies_per_post_per_day: int,
     * }
     */
    public static function defaultCommentSettings(): array
    {
        return [
            'enabled'                          => false,
            'enabled_at'                       => null,
            'reply_mode'                       => self::COMMENT_REPLY_OFF,
            'reply_keywords'                   => [],
            'dm_mode'                          => self::COMMENT_DM_OFF,
            'dm_keywords'                      => [],
            'reply_instructions'               => '',
            'scope'                            => self::COMMENT_SCOPE_FUTURE_ONLY,
            'max_ai_replies_per_post_per_day'  => 20,
        ];
    }
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Unit/Models/AiConfigCommentSettingsTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 8: Commit**

```bash
git add app/Models/AiConfig.php tests/Unit/Models/AiConfigCommentSettingsTest.php
git commit -m "feat(ai): add comment_settings constants + defaults on AiConfig model"
```

---

## Task 3: Livewire component — props + hydration + reset

**Files:**
- Modify: `app/Livewire/Settings/AiConfig.php`
- Test: `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php` (create)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`. This test file will grow across the next few tasks — start with the hydration test.

```php
<?php

declare(strict_types=1);

use App\Livewire\Settings\AiConfig as AiConfigComponent;
use App\Models\AiConfig;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Livewire\Livewire;

function makeUserWithFacebookPage(): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->update(['current_team_id' => $team->id]);
    $page = Page::factory()->create([
        'team_id'  => $team->id,
        'platform' => 'facebook',
        'is_active' => true,
    ]);

    return [$user, $team, $page];
}

it('hydrates comment settings defaults when no config exists', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSet('comment_enabled', false)
        ->assertSet('comment_reply_mode', AiConfig::COMMENT_REPLY_OFF)
        ->assertSet('comment_dm_mode', AiConfig::COMMENT_DM_OFF)
        ->assertSet('comment_scope', AiConfig::COMMENT_SCOPE_FUTURE_ONLY)
        ->assertSet('comment_max_replies_per_post_per_day', 20)
        ->assertSet('comment_reply_instructions', '');
});

it('hydrates comment settings from an existing config row', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    AiConfig::create([
        'page_id' => $page->id,
        'team_id' => $team->id,
        'business_description' => 'test business description over ten chars',
        'comment_settings' => [
            'enabled'                          => true,
            'enabled_at'                       => '2026-08-31T10:00:00+00:00',
            'reply_mode'                       => AiConfig::COMMENT_REPLY_ALL,
            'reply_keywords'                   => ['price'],
            'dm_mode'                          => AiConfig::COMMENT_DM_ALWAYS,
            'dm_keywords'                      => [],
            'reply_instructions'               => 'be brief',
            'scope'                            => AiConfig::COMMENT_SCOPE_ALL_POSTS,
            'max_ai_replies_per_post_per_day'  => 50,
        ],
    ]);

    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSet('comment_enabled', true)
        ->assertSet('comment_reply_mode', AiConfig::COMMENT_REPLY_ALL)
        ->assertSet('comment_reply_keywords', ['price'])
        ->assertSet('comment_dm_mode', AiConfig::COMMENT_DM_ALWAYS)
        ->assertSet('comment_reply_instructions', 'be brief')
        ->assertSet('comment_scope', AiConfig::COMMENT_SCOPE_ALL_POSTS)
        ->assertSet('comment_max_replies_per_post_per_day', 50);
});
```

**Note on factories:** if `Page::factory()` or `Team::factory()` don't exist, check `database/factories/` for the exact model factory contract in this repo and adapt the fixture setup. The `Team` model uses a `user_id` foreign key per the existing codebase (see `app/Models/Team.php`).

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`
Expected: FAIL — `Property [comment_enabled] not found on component`.

- [ ] **Step 3: Add public properties**

In `app/Livewire/Settings/AiConfig.php`, add after `public int $contact_ai_reply_cap = 20;` (~line 40):

```php

    // Comments — Phase A
    public bool   $comment_enabled = false;
    public ?string $comment_enabled_at = null;
    public string $comment_reply_mode = AiConfigModel::COMMENT_REPLY_OFF;
    public array  $comment_reply_keywords = [];
    public string $comment_dm_mode = AiConfigModel::COMMENT_DM_OFF;
    public array  $comment_dm_keywords = [];
    public string $comment_reply_instructions = '';
    public string $comment_scope = AiConfigModel::COMMENT_SCOPE_FUTURE_ONLY;
    public int    $comment_max_replies_per_post_per_day = 20;
```

- [ ] **Step 4: Hydrate from config in `selectPage()`**

In `selectPage()`, inside the `if ($config)` branch, after `$this->is_active = $config->is_active ?? true;` (~line 98), append:

```php
            $commentDefaults = AiConfigModel::defaultCommentSettings();
            $comment = is_array($config->comment_settings) ? $config->comment_settings : $commentDefaults;

            $this->comment_enabled                       = (bool) ($comment['enabled']                          ?? $commentDefaults['enabled']);
            $this->comment_enabled_at                    = $comment['enabled_at']                               ?? $commentDefaults['enabled_at'];
            $this->comment_reply_mode                    = (string) ($comment['reply_mode']                     ?? $commentDefaults['reply_mode']);
            $this->comment_reply_keywords                = array_values((array) ($comment['reply_keywords']     ?? $commentDefaults['reply_keywords']));
            $this->comment_dm_mode                       = (string) ($comment['dm_mode']                        ?? $commentDefaults['dm_mode']);
            $this->comment_dm_keywords                   = array_values((array) ($comment['dm_keywords']        ?? $commentDefaults['dm_keywords']));
            $this->comment_reply_instructions            = (string) ($comment['reply_instructions']             ?? $commentDefaults['reply_instructions']);
            $this->comment_scope                         = (string) ($comment['scope']                          ?? $commentDefaults['scope']);
            $this->comment_max_replies_per_post_per_day  = (int) ($comment['max_ai_replies_per_post_per_day']   ?? $commentDefaults['max_ai_replies_per_post_per_day']);
```

- [ ] **Step 5: Reset in `resetForm()`**

In `resetForm()`, after `$this->is_active = true;` (~line 341), append:

```php

        $commentDefaults = AiConfigModel::defaultCommentSettings();
        $this->comment_enabled                       = $commentDefaults['enabled'];
        $this->comment_enabled_at                    = $commentDefaults['enabled_at'];
        $this->comment_reply_mode                    = $commentDefaults['reply_mode'];
        $this->comment_reply_keywords                = $commentDefaults['reply_keywords'];
        $this->comment_dm_mode                       = $commentDefaults['dm_mode'];
        $this->comment_dm_keywords                   = $commentDefaults['dm_keywords'];
        $this->comment_reply_instructions            = $commentDefaults['reply_instructions'];
        $this->comment_scope                         = $commentDefaults['scope'];
        $this->comment_max_replies_per_post_per_day  = $commentDefaults['max_ai_replies_per_post_per_day'];
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`
Expected: PASS — 2 tests.

- [ ] **Step 7: Commit**

```bash
git add app/Livewire/Settings/AiConfig.php tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php
git commit -m "feat(ai): hydrate Comments tab props from AiConfig.comment_settings"
```

---

## Task 4: Livewire component — keyword add/remove methods

**Files:**
- Modify: `app/Livewire/Settings/AiConfig.php`
- Test: `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`:

```php
it('adds and removes comment reply keywords', function () {
    [$user] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('addCommentReplyKeyword')
        ->call('addCommentReplyKeyword')
        ->assertSet('comment_reply_keywords', ['', ''])
        ->set('comment_reply_keywords.0', 'price')
        ->set('comment_reply_keywords.1', 'cost')
        ->call('removeCommentReplyKeyword', 0)
        ->assertSet('comment_reply_keywords', ['cost']);
});

it('adds and removes comment DM keywords', function () {
    [$user] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('addCommentDmKeyword')
        ->set('comment_dm_keywords.0', 'buy')
        ->call('removeCommentDmKeyword', 0)
        ->assertSet('comment_dm_keywords', []);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="adds and removes"`
Expected: FAIL — `Method [addCommentReplyKeyword] not found on component`.

- [ ] **Step 3: Add the four methods**

In `app/Livewire/Settings/AiConfig.php`, add after `removeTopicKeyword()` (~line 312) and before `setTab()`:

```php

    public function addCommentReplyKeyword(): void
    {
        $this->comment_reply_keywords[] = '';
    }

    public function removeCommentReplyKeyword(int $index): void
    {
        unset($this->comment_reply_keywords[$index]);
        $this->comment_reply_keywords = array_values($this->comment_reply_keywords);
    }

    public function addCommentDmKeyword(): void
    {
        $this->comment_dm_keywords[] = '';
    }

    public function removeCommentDmKeyword(int $index): void
    {
        unset($this->comment_dm_keywords[$index]);
        $this->comment_dm_keywords = array_values($this->comment_dm_keywords);
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="adds and removes"`
Expected: PASS — 2 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Settings/AiConfig.php tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php
git commit -m "feat(ai): add comment keyword add/remove handlers to Livewire component"
```

---

## Task 5: `saveConfig()` — pack, validate, clamp, stamp `enabled_at`

**Files:**
- Modify: `app/Livewire/Settings/AiConfig.php`
- Test: `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`:

```php
it('persists comment settings and stamps enabled_at on first enable', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_enabled', true)
        ->set('comment_reply_mode', AiConfig::COMMENT_REPLY_ALL)
        ->set('comment_dm_mode', AiConfig::COMMENT_DM_ALWAYS)
        ->set('comment_reply_instructions', 'keep it short')
        ->set('comment_max_replies_per_post_per_day', 30)
        ->call('saveConfig')
        ->assertHasNoErrors();

    $config = AiConfig::where('page_id', $page->id)->firstOrFail();
    $settings = $config->comment_settings;

    expect($settings['enabled'])->toBeTrue();
    expect($settings['enabled_at'])->not->toBeNull();
    expect($settings['reply_mode'])->toBe(AiConfig::COMMENT_REPLY_ALL);
    expect($settings['dm_mode'])->toBe(AiConfig::COMMENT_DM_ALWAYS);
    expect($settings['reply_instructions'])->toBe('keep it short');
    expect($settings['max_ai_replies_per_post_per_day'])->toBe(30);
});

it('preserves enabled_at across subsequent saves', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    $component = Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_enabled', true)
        ->call('saveConfig');

    $firstStamp = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['enabled_at'];
    expect($firstStamp)->not->toBeNull();

    // Save again — enabled_at must not change.
    sleep(1); // ensure any accidental re-stamp would produce a different value
    $component->call('saveConfig');

    $secondStamp = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['enabled_at'];
    expect($secondStamp)->toBe($firstStamp);
});

it('clamps the per-post reply cap to the allowed range', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_max_replies_per_post_per_day', 999)
        ->call('saveConfig');

    expect(AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['max_ai_replies_per_post_per_day'])
        ->toBe(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_max_replies_per_post_per_day', 0)
        ->call('saveConfig');

    expect(AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings['max_ai_replies_per_post_per_day'])
        ->toBe(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN);
});

it('coerces unknown enum values to safe defaults', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_mode', 'yolo')
        ->set('comment_dm_mode', 'nope')
        ->set('comment_scope', 'huh')
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect($settings['reply_mode'])->toBe(AiConfig::COMMENT_REPLY_OFF);
    expect($settings['dm_mode'])->toBe(AiConfig::COMMENT_DM_OFF);
    expect($settings['scope'])->toBe(AiConfig::COMMENT_SCOPE_FUTURE_ONLY);
});

it('trims and drops empty keywords', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_keywords', ['  price  ', '', 'cost'])
        ->set('comment_dm_keywords', [''])
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect($settings['reply_keywords'])->toBe(['price', 'cost']);
    expect($settings['dm_keywords'])->toBe([]);
});

it('truncates reply instructions to 500 characters', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->set('business_description', 'valid business description over ten chars')
        ->set('comment_reply_instructions', str_repeat('a', 600))
        ->call('saveConfig');

    $settings = AiConfig::where('page_id', $page->id)->firstOrFail()->comment_settings;
    expect(mb_strlen($settings['reply_instructions']))->toBe(500);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`
Expected: FAIL — new tests fail because `saveConfig()` isn't writing `comment_settings` yet.

- [ ] **Step 3: Extend `saveConfig()` to pack comment settings**

In `app/Livewire/Settings/AiConfig.php`, inside `saveConfig()`, add validation right after the existing `$this->validate([...])` block (~line 127):

```php
        // Comment settings validation (soft — invalid enums get coerced below, but
        // instructions length and rate limits are enforced here for a clean error surface).
        $this->validate([
            'comment_reply_instructions'           => 'nullable|string|max:500',
            'comment_max_replies_per_post_per_day' => 'required|integer|min:1|max:100',
        ]);
```

Then, just before building the `$data` array (~line 165), compute the packed comment settings:

```php
        // Pack comment settings. Invalid enum values silently coerce to safe defaults
        // (defence in depth: even if a client bypasses the UI, the row stays valid).
        $existingComment = optional(AiConfigModel::where('page_id', $this->selectedPageId)->first())->comment_settings;
        $wasEnabled = (bool) data_get($existingComment, 'enabled', false);
        $priorStamp = data_get($existingComment, 'enabled_at');

        $replyModeAllowed = [
            AiConfigModel::COMMENT_REPLY_OFF,
            AiConfigModel::COMMENT_REPLY_ALL,
            AiConfigModel::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS,
            AiConfigModel::COMMENT_REPLY_CUSTOM_KEYWORDS,
        ];
        $dmModeAllowed = [
            AiConfigModel::COMMENT_DM_OFF,
            AiConfigModel::COMMENT_DM_ALWAYS,
            AiConfigModel::COMMENT_DM_ON_PURCHASE_INTENT,
        ];
        $scopeAllowed = [
            AiConfigModel::COMMENT_SCOPE_FUTURE_ONLY,
            AiConfigModel::COMMENT_SCOPE_ALL_POSTS,
        ];

        $commentReplyKeywords = array_values(array_filter(
            array_map(fn ($k) => trim((string) $k), $this->comment_reply_keywords),
            fn ($k) => $k !== '',
        ));
        $commentDmKeywords = array_values(array_filter(
            array_map(fn ($k) => trim((string) $k), $this->comment_dm_keywords),
            fn ($k) => $k !== '',
        ));

        $commentSettings = [
            'enabled'                          => $this->comment_enabled,
            'enabled_at'                       => ($this->comment_enabled && ! $wasEnabled)
                ? now()->toIso8601String()
                : $priorStamp,
            'reply_mode'                       => in_array($this->comment_reply_mode, $replyModeAllowed, true)
                ? $this->comment_reply_mode
                : AiConfigModel::COMMENT_REPLY_OFF,
            'reply_keywords'                   => $commentReplyKeywords,
            'dm_mode'                          => in_array($this->comment_dm_mode, $dmModeAllowed, true)
                ? $this->comment_dm_mode
                : AiConfigModel::COMMENT_DM_OFF,
            'dm_keywords'                      => $commentDmKeywords,
            'reply_instructions'               => mb_substr((string) $this->comment_reply_instructions, 0, 500),
            'scope'                            => in_array($this->comment_scope, $scopeAllowed, true)
                ? $this->comment_scope
                : AiConfigModel::COMMENT_SCOPE_FUTURE_ONLY,
            'max_ai_replies_per_post_per_day'  => max(
                AiConfigModel::COMMENT_MAX_REPLIES_PER_POST_MIN,
                min(
                    AiConfigModel::COMMENT_MAX_REPLIES_PER_POST_MAX,
                    (int) $this->comment_max_replies_per_post_per_day,
                ),
            ),
        ];
```

Then, inside the `$data` array (~line 165), add before `'is_active' => $this->is_active,`:

```php
            'comment_settings' => $commentSettings,
```

- [ ] **Step 4: Run all component tests to verify pass**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`
Expected: PASS — all Comments-tab tests green (2 hydration + 2 keyword mgmt + 6 save).

- [ ] **Step 5: Run the full test suite to check for regressions**

Run: `vendor/bin/pest`
Expected: PASS — no regressions in existing tests.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Settings/AiConfig.php tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php
git commit -m "feat(ai): persist comment settings in saveConfig with enum coercion + clamps"
```

---

## Task 6: Blade view — conditional tab + tab block

**Files:**
- Modify: `resources/views/livewire/settings/ai-config.blade.php`
- Test: `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`:

```php
it('renders the Comments tab button for Facebook pages', function () {
    [$user, $team, $page] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSeeHtml("wire:click=\"setTab('comments')\"");
});

it('renders the Comments tab button for Instagram pages', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->update(['current_team_id' => $team->id]);
    Page::factory()->create([
        'team_id' => $team->id,
        'platform' => 'instagram',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->assertSeeHtml("wire:click=\"setTab('comments')\"");
});

it('hides the Comments tab button for WhatsApp and Telegram pages', function () {
    foreach (['whatsapp', 'telegram'] as $platform) {
        $user = User::factory()->create();
        $team = Team::factory()->create(['user_id' => $user->id]);
        $user->update(['current_team_id' => $team->id]);
        Page::factory()->create([
            'team_id'  => $team->id,
            'platform' => $platform,
            'is_active' => true,
        ]);

        $this->actingAs($user);

        Livewire::test(AiConfigComponent::class)
            ->assertDontSeeHtml("wire:click=\"setTab('comments')\"");
    }
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="Comments tab button"`
Expected: FAIL — tab HTML not present.

- [ ] **Step 3: Make the `$tabs` array conditional**

In `resources/views/livewire/settings/ai-config.blade.php`, replace the existing `@php $tabs = [...] @endphp` block (lines ~90-97) with:

```blade
                        @php
                            $selectedPage = $pages->firstWhere('id', $selectedPageId);
                            $tabs = [
                                'sales_goal' => ['label' => __('Sales Goal'),  'icon' => 'flag'],
                                'knowledge'  => ['label' => __('Knowledge'),   'icon' => 'book-open'],
                                'behavior'   => ['label' => __('Behavior'),    'icon' => 'sparkles'],
                                'handoff'    => ['label' => __('Handoff'),     'icon' => 'user-group'],
                            ];
                            if ($selectedPage && in_array($selectedPage->platform, ['facebook', 'instagram'], true)) {
                                $tabs['comments'] = ['label' => __('Comments'), 'icon' => 'chat-bubble-oval-left-ellipsis'];
                            }
                        @endphp
```

- [ ] **Step 4: Run tab-visibility tests to verify pass**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="Comments tab button"`
Expected: PASS — 3 tests.

- [ ] **Step 5: Add the Comments tab block**

In `resources/views/livewire/settings/ai-config.blade.php`, insert after the Handoff tab's closing `@endif {{-- /Handoff tab --}}` (~line 527) and before `{{-- Save --}}`:

```blade

                        {{-- Tab: Comments (Facebook & Instagram only) --}}
                        @if($activeTab === 'comments')
                            {{-- Coming-soon banner: the feature only activates once Meta App Review
                                 approves pages_manage_engagement + instagram_manage_comments. --}}
                            <section class="rounded-lg border border-violet-200 bg-violet-50 p-4">
                                <flux:heading size="sm" class="mb-1 text-violet-900">{{ __('Coming soon — save your settings now') }}</flux:heading>
                                <flux:text size="sm" class="text-violet-900">{{ __('Comment features activate once Meta approves our Instagram & Facebook comment permissions (in App Review). Save your config here — it applies automatically the moment approval lands. Nothing you configure runs until then.') }}</flux:text>
                            </section>

                            {{-- Master switch --}}
                            <div class="rounded-xl border-2 {{ $comment_enabled ? 'border-green-500 bg-green-50 dark:bg-green-900/10' : 'border-zinc-300 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800/50' }} p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:heading size="sm" class="text-zinc-900">
                                            {{ __('Comment AI for this page:') }}
                                            <span class="{{ $comment_enabled ? 'text-green-600' : 'text-zinc-500' }}">
                                                {{ $comment_enabled ? __('Enabled') : __('Off') }}
                                            </span>
                                        </flux:heading>
                                        <flux:text size="sm" class="mt-0.5 text-zinc-900">{{ __('Turn this on to let the AI reply to comments on your posts, and optionally DM the commenter.') }}</flux:text>
                                    </div>
                                    <flux:switch wire:model.live="comment_enabled" />
                                </div>
                            </div>

                            @if($comment_enabled)
                                {{-- Public reply mode --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('When should the AI reply publicly?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Choose which comments the AI should reply to in public, right under the post.') }}</flux:text>

                                    @php
                                        $replyModes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_OFF,                      'title' => __('Off'),                          'desc' => __('AI never replies to comments publicly.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_ALL,                      'title' => __('All comments'),                 'desc' => __('AI replies to every comment on your posts.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS, 'title' => __('Questions & complaints only'), 'desc' => __('Recommended: AI only replies when the comment asks something or expresses a problem.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS,          'title' => __('Custom keywords'),              'desc' => __('AI only replies to comments containing keywords you list.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($replyModes as $mode)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_reply_mode', '{{ $mode['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_reply_mode === $mode['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $mode['title'] }}</span>
                                                    @if($comment_reply_mode === $mode['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $mode['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>

                                    @if($comment_reply_mode === \App\Models\AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS)
                                        <div class="mt-4">
                                            <flux:text size="sm" class="mb-2 text-zinc-900">{{ __('Reply only when the comment contains any of these keywords:') }}</flux:text>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach($comment_reply_keywords as $index => $kw)
                                                    <div wire:key="crk-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1">
                                                        <flux:input wire:model.blur="comment_reply_keywords.{{ $index }}" size="xs" class="!w-32 !bg-transparent !p-0 !text-sm" />
                                                        <button type="button" wire:click="removeCommentReplyKeyword({{ $index }})" class="w-5 h-5 flex items-center justify-center rounded-full text-white hover:bg-white/20">
                                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <flux:button wire:click="addCommentReplyKeyword" type="button" variant="outline" size="sm" icon="plus">
                                                {{ __('Add keyword') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </section>

                                {{-- DM behavior --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Should the AI DM the commenter?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('The AI can open a private message with the person who commented. Meta allows one AI-sent DM per comment: 24 hours on Facebook, 7 days on Instagram. After that the customer must reply for the conversation to continue.') }}</flux:text>

                                    @php
                                        $dmModes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_OFF,                'title' => __('Off'),                       'desc' => __('AI never DMs commenters. Public reply only.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_ALWAYS,             'title' => __('Always DM after replying'),  'desc' => __('Every time the AI replies publicly, it also opens a DM with the commenter.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_DM_ON_PURCHASE_INTENT, 'title' => __('DM only on purchase intent'),'desc' => __('DM only when the comment contains a buying-intent keyword you list below.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-3">
                                        @foreach($dmModes as $mode)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_dm_mode', '{{ $mode['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_dm_mode === $mode['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $mode['title'] }}</span>
                                                    @if($comment_dm_mode === $mode['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $mode['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>

                                    @if($comment_dm_mode === \App\Models\AiConfig::COMMENT_DM_ON_PURCHASE_INTENT)
                                        <div class="mt-4">
                                            <flux:text size="sm" class="mb-2 text-zinc-900">{{ __('Purchase-intent keywords (comment must contain any of these to trigger a DM):') }}</flux:text>
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                @foreach($comment_dm_keywords as $index => $kw)
                                                    <div wire:key="cdk-{{ $index }}" class="kw-chip flex items-center gap-1 rounded-full bg-zinc-100 dark:bg-zinc-800 border border-white pl-3 pr-1 py-1">
                                                        <flux:input wire:model.blur="comment_dm_keywords.{{ $index }}" size="xs" class="!w-32 !bg-transparent !p-0 !text-sm" />
                                                        <button type="button" wire:click="removeCommentDmKeyword({{ $index }})" class="w-5 h-5 flex items-center justify-center rounded-full text-white hover:bg-white/20">
                                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                            <flux:button wire:click="addCommentDmKeyword" type="button" variant="outline" size="sm" icon="plus">
                                                {{ __('Add keyword') }}
                                            </flux:button>
                                        </div>
                                    @endif
                                </section>

                                {{-- Reply persona nudge --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Reply style rules') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Optional. Custom rules for how the AI writes replies specifically on comments — shorter and more careful than DMs, since comments are public.') }}</flux:text>
                                    <div x-data="{ count: $wire.entangle('comment_reply_instructions').length ?? 0 }" x-init="$watch('$wire.comment_reply_instructions', v => count = (v ?? '').length)">
                                        <flux:textarea
                                            wire:model="comment_reply_instructions"
                                            placeholder="{{ __('e.g. Keep replies to 1–2 sentences. Always thank the commenter first. Never quote prices — invite them to DM.') }}"
                                            rows="3"
                                            maxlength="500"
                                            class="text-zinc-900"
                                        />
                                        <div class="flex justify-end mt-1">
                                            <p class="text-xs text-zinc-900" :class="count > 450 ? 'text-amber-500' : ''" x-text="count + ' / 500'"></p>
                                        </div>
                                    </div>
                                </section>

                                {{-- Scope --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Which posts does this apply to?') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('Choose whether the AI acts on comments from every post, or only from posts made after you enable this feature.') }}</flux:text>

                                    @php
                                        $scopes = [
                                            ['key' => \App\Models\AiConfig::COMMENT_SCOPE_FUTURE_ONLY, 'title' => __('Future posts only'), 'desc' => __('Recommended: safer for existing viral posts with lots of comments.')],
                                            ['key' => \App\Models\AiConfig::COMMENT_SCOPE_ALL_POSTS,   'title' => __('All posts'),          'desc' => __('Includes historical posts. Use with care on accounts with a big backlog.')],
                                        ];
                                    @endphp
                                    <div class="grid gap-3 md:grid-cols-2">
                                        @foreach($scopes as $scope)
                                            <button
                                                type="button"
                                                wire:click="$set('comment_scope', '{{ $scope['key'] }}')"
                                                class="text-left p-4 rounded-xl border-2 transition-colors
                                                    {{ $comment_scope === $scope['key']
                                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-950/30'
                                                        : 'border-violet-300 hover:border-violet-400' }}"
                                            >
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <span class="font-medium !text-zinc-900 dark:!text-zinc-900">{{ $scope['title'] }}</span>
                                                    @if($comment_scope === $scope['key'])
                                                        <flux:icon name="check-circle" class="w-5 h-5 text-blue-500 shrink-0" />
                                                    @endif
                                                </div>
                                                <p class="text-xs text-zinc-900 leading-relaxed">{{ $scope['desc'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>
                                </section>

                                {{-- Rate limit --}}
                                <section>
                                    <flux:heading size="lg" class="mb-1 text-zinc-900">{{ __('Per-post daily reply cap') }}</flux:heading>
                                    <flux:text size="sm" class="mb-4 text-zinc-900">{{ __('The most AI replies allowed on a single post in any 24-hour window. Prevents a viral post from draining your AI budget. Recommended: 20.') }}</flux:text>
                                    <div class="max-w-xs">
                                        <flux:input
                                            wire:model="comment_max_replies_per_post_per_day"
                                            type="number"
                                            label="{{ __('Cap (1-100 replies/post/day)') }}"
                                            min="{{ \App\Models\AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN }}"
                                            max="{{ \App\Models\AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX }}"
                                            class="text-zinc-900"
                                        />
                                        @error('comment_max_replies_per_post_per_day') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </section>
                            @endif
                        @endif {{-- /Comments tab --}}

```

- [ ] **Step 6: Manually verify the tab renders locally**

Run: `php artisan serve` (or just visit `https://one-inbox.test/settings/ai/config` if Herd is running).
Expected: For a Facebook or Instagram page, a "Comments" tab appears in the tab strip. Click it — the coming-soon banner shows, plus the master switch. Toggle the switch → the four subsections appear. Save → refresh → all fields persist.

For a WhatsApp/Telegram/email page, the "Comments" tab does NOT appear.

- [ ] **Step 7: Run the full test suite**

Run: `vendor/bin/pest`
Expected: PASS — everything green.

- [ ] **Step 8: Commit**

```bash
git add resources/views/livewire/settings/ai-config.blade.php tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php
git commit -m "feat(ai): Comments tab UI on ai-config page for FB/IG pages"
```

---

## Task 7: `setTab()` guard — reset when leaving the tab's supported platforms

**Files:**
- Modify: `app/Livewire/Settings/AiConfig.php`
- Test: `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php`:

```php
it('resets activeTab to sales_goal if comments tab is somehow active for an unsupported platform', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->update(['current_team_id' => $team->id]);
    Page::factory()->create([
        'team_id' => $team->id,
        'platform' => 'whatsapp',
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('setTab', 'comments')
        ->assertSet('activeTab', 'sales_goal');
});

it('accepts comments tab for supported platforms', function () {
    [$user] = makeUserWithFacebookPage();
    $this->actingAs($user);

    Livewire::test(AiConfigComponent::class)
        ->call('setTab', 'comments')
        ->assertSet('activeTab', 'comments');
});
```

- [ ] **Step 2: Run tests to verify the first one fails**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="resets activeTab"`
Expected: FAIL — `activeTab` is `'comments'`, expected `'sales_goal'`.

- [ ] **Step 3: Add the guard**

In `app/Livewire/Settings/AiConfig.php`, replace `setTab()` (~line 314-317) with:

```php
    public function setTab(string $tab): void
    {
        if ($tab === 'comments' && $this->selectedPageId !== null) {
            $team = Auth::user()?->currentTeam;
            $page = $team?->pages()->where('id', $this->selectedPageId)->first();
            if (! $page || ! in_array($page->platform, ['facebook', 'instagram'], true)) {
                $this->activeTab = 'sales_goal';

                return;
            }
        }

        $this->activeTab = $tab;
    }
```

- [ ] **Step 4: Run both guard tests to verify pass**

Run: `vendor/bin/pest tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php --filter="tab"`
Expected: PASS.

- [ ] **Step 5: Run full test suite**

Run: `vendor/bin/pest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Settings/AiConfig.php tests/Feature/Livewire/Settings/AiConfigCommentTabTest.php
git commit -m "feat(ai): guard setTab against comments tab on unsupported platforms"
```

---

## Task 8: Deploy verification

- [ ] **Step 1: Push to main**

```bash
git push origin main
```

- [ ] **Step 2: Wait for GitHub Actions auto-deploy (~24s per CLAUDE.md production topology)**

Watch the Actions tab or SSH to the VPS and check for the migration.

- [ ] **Step 3: Verify the migration ran on prod**

Run: `ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && sudo -u deploy php artisan migrate:status | grep comment_settings"`
Expected: line with `Ran` next to `2026_08_31_120000_add_comment_settings_to_ai_configs`.

- [ ] **Step 4: Visit prod page as super-admin**

Open `https://ot1-pro.com/settings/ai/config`, pick a Facebook or Instagram page, verify the Comments tab appears with the coming-soon banner and works as designed.

- [ ] **Step 5: Verify config:cache is fine on prod**

Run: `ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && sudo -u deploy php artisan config:cache && sudo systemctl reload php8.4-fpm"`
Expected: no output, HTTP 200 on the page.

(Per CLAUDE.md pin: never run `config:cache` as root — always via `deploy` user.)

---

## Self-Review

**Spec coverage:**
- §3 (storage: one JSON column + fillable + cast + constants + defaults): Tasks 1 and 2.
- §4 (UI: conditional tab + tab block sections a-g): Task 6.
- §5 (Livewire props, hydration, save packing, methods): Tasks 3, 4, 5, 7.
- §6 (migration): Task 1.
- §7 (tests: unit + feature): Tasks 2, 3, 4, 5, 6, 7.
- §8 (performance: no new runtime cost): satisfied structurally — no queue jobs, no scheduler, one existing eager-loaded column.
- §9 (anti-scope pins): stated at plan header, respected throughout (no changes to `Team::canDispatchAi`, no `$metaVerified` touches, no connections page, no provider strings).
- §10 (rollout via `git push origin main`, no seeder, no env change): Task 8.

**Placeholder scan:** No TBDs, no "add appropriate error handling," no "similar to Task N." All code blocks are complete.

**Type/name consistency:**
- Property names match across Livewire component, tests, and Blade view (`comment_enabled`, `comment_reply_mode`, `comment_reply_keywords`, `comment_dm_mode`, `comment_dm_keywords`, `comment_reply_instructions`, `comment_scope`, `comment_max_replies_per_post_per_day`, plus internal `comment_enabled_at` for stamp preservation).
- Constants (`COMMENT_REPLY_*`, `COMMENT_DM_*`, `COMMENT_SCOPE_*`, `COMMENT_MAX_REPLIES_PER_POST_MIN/MAX`) match between Task 2 (definition), Task 5 (validation in save), Task 6 (Blade), and the tests.
- Method names (`addCommentReplyKeyword`, `removeCommentReplyKeyword`, `addCommentDmKeyword`, `removeCommentDmKeyword`) match between Task 4 (definition), Task 6 (Blade `wire:click`), and tests.
- Storage key names in JSON (`enabled`, `enabled_at`, `reply_mode`, `reply_keywords`, `dm_mode`, `dm_keywords`, `reply_instructions`, `scope`, `max_ai_replies_per_post_per_day`) match between the model default, hydration in Task 3, packing in Task 5, and test assertions.

All checks pass. Plan is ready to execute.
