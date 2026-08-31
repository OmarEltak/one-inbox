# Comments AI — Phase B Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn Phase A's dormant `comment_settings` JSON into a live system: ingest Meta comment webhooks → classify + filter + rate-limit → post AI reply publicly and optionally DM the commenter. Ships live without waiting for Meta App Review (Standard Access is sufficient inside our verified Business Portfolio).

**Architecture:** Extend the existing `ProcessIncomingMessage` job to route webhook `change.field=feed` (FB) and `field=comments` (IG) events into a two-stage pipeline: `IngestCommentJob` (fast, filters + dedupe + rate-limit) → optional `ClassifyCommentJob` (cheap Nara call for q&c mode) → `SendAiCommentReplyJob` (AI reply + Graph API). Two new tables: `pages_posts` (post creation-time cache) and `comments` (decision log). Redis for dedupe + rate-limit (bypasses prod's `CACHE_STORE=database`).

**Tech Stack:** Laravel 12, Livewire 4, Meta Graph API v21.0, Redis (via `Illuminate\Support\Facades\Redis`), Pest with `RefreshDatabase`, existing `AiProviderInterface` (NaraRouter provider).

**Spec:** `docs/superpowers/specs/2026-08-31-comments-ai-phase-b-design.md`

**CLAUDE.md pins enforced:** #1 (managed OAuth only, no `$metaVerified` touches), #2 (managed onboarding untouched), #3 (Page observer untouched), #4 (`Team::canDispatchAi()` gates every AI dispatch in `SendAiCommentReplyJob`), #5 (empty string on Nara failure — never fallback text), #9 (route through `AiProviderInterface` so `coalesceRoles` runs).

---

## File Map

**Create:**
- `database/migrations/2026_09_01_100000_create_pages_posts_table.php`
- `database/migrations/2026_09_01_100100_create_comments_table.php`
- `app/Models/PagesPost.php`
- `app/Models/Comment.php`
- `database/factories/PagesPostFactory.php`
- `database/factories/CommentFactory.php`
- `app/Services/Comments/MetaCommentPayloadParser.php`
- `app/Services/Comments/PostCreationTimeCache.php`
- `app/Services/Comments/CommentFilterService.php`
- `app/Jobs/IngestCommentJob.php`
- `app/Jobs/ClassifyCommentJob.php`
- `app/Jobs/SendAiCommentReplyJob.php`
- `tests/Unit/Models/PagesPostTest.php`
- `tests/Unit/Models/CommentTest.php`
- `tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php`
- `tests/Feature/Jobs/IngestCommentJobTest.php`
- `tests/Feature/Jobs/ClassifyCommentJobTest.php`
- `tests/Feature/Jobs/SendAiCommentReplyJobTest.php`
- `docs/meta-app-review/comments-permissions-submission.md`

**Modify:**
- `app/Services/Platforms/FacebookPlatform.php` — add `pages_manage_engagement` to 3 scope lists; add `feed` + `comments` to webhook subscribe fields.
- `app/Jobs/ProcessIncomingMessage.php` — extend `processMetaMessenger()` to also iterate `entry.changes[]` and dispatch `IngestCommentJob` when `field IN ('feed','comments')` with `item='comment'`.

---

## Task 1: OAuth scope extension — add `pages_manage_engagement`

**Files:**
- Modify: `app/Services/Platforms/FacebookPlatform.php` (3 lines: 48, 70, 339)
- Test: `tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php` (create)

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

it('requests pages_manage_engagement in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
});

it('requests pages_manage_engagement in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
});
```

- [ ] **Step 2: Run test to verify RED**

Run: `vendor/bin/pest tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php`
Expected: FAIL — string not found.

- [ ] **Step 3: Implement**

In `app/Services/Platforms/FacebookPlatform.php`:

Line 48 replace:
```
'scope' => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement',
```
with:
```
'scope' => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_engagement',
```

Line 70 replace:
```
'scope'         => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,instagram_basic,instagram_manage_messages,instagram_manage_comments',
```
with:
```
'scope'         => 'pages_show_list,pages_messaging,pages_manage_metadata,pages_read_engagement,pages_manage_engagement,instagram_basic,instagram_manage_messages,instagram_manage_comments',
```

Line 339 replace:
```
'scopes' => ['pages_messaging', 'pages_manage_metadata', 'pages_show_list', 'pages_read_engagement'],
```
with:
```
'scopes' => ['pages_messaging', 'pages_manage_metadata', 'pages_show_list', 'pages_read_engagement', 'pages_manage_engagement'],
```

- [ ] **Step 4: Verify GREEN**

Run: `vendor/bin/pest tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Platforms/FacebookPlatform.php tests/Unit/Services/Platforms/FacebookPlatformScopesTest.php
git commit -m "feat(comments): request pages_manage_engagement in managed OAuth scopes"
```

---

## Task 2: Migrations + models — `pages_posts` and `comments`

**Files:**
- Create: `database/migrations/2026_09_01_100000_create_pages_posts_table.php`
- Create: `database/migrations/2026_09_01_100100_create_comments_table.php`
- Create: `app/Models/PagesPost.php`
- Create: `app/Models/Comment.php`
- Create: `database/factories/PagesPostFactory.php`
- Create: `database/factories/CommentFactory.php`
- Create: `tests/Unit/Models/PagesPostTest.php`
- Create: `tests/Unit/Models/CommentTest.php`

- [ ] **Step 1: Write `pages_posts` migration**

Create `database/migrations/2026_09_01_100000_create_pages_posts_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pages_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('platform_post_id');
            $table->timestamp('created_at_platform');
            $table->timestamp('first_seen_at')->useCurrent();
            $table->unique(['page_id', 'platform_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages_posts');
    }
};
```

- [ ] **Step 2: Write `comments` migration**

Create `database/migrations/2026_09_01_100100_create_comments_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pages_post_id')->constrained('pages_posts')->cascadeOnDelete();
            $table->string('platform_comment_id')->unique();
            $table->string('parent_comment_id')->nullable();
            $table->string('commenter_platform_id');
            $table->string('commenter_name');
            $table->text('text');
            $table->timestamp('received_at');
            $table->string('decision', 40)->nullable();
            $table->string('decision_reason')->nullable();
            $table->text('reply_text')->nullable();
            $table->string('graph_reply_id')->nullable();
            $table->timestamp('dm_sent_at')->nullable();
            $table->string('dm_graph_message_id')->nullable();
            $table->json('graph_error')->nullable();
            $table->timestamps();
            $table->index(['page_id', 'created_at']);
            $table->index(['page_id', 'decision', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
```

- [ ] **Step 3: Run migrations**

Run: `php artisan migrate`
Expected: both migrations `DONE`.

- [ ] **Step 4: Write `PagesPost` model**

Create `app/Models/PagesPost.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagesPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'platform_post_id',
        'created_at_platform',
        'first_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at_platform' => 'datetime',
            'first_seen_at'       => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
```

- [ ] **Step 5: Write `Comment` model**

Create `app/Models/Comment.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    // Decision enum — kept as constants (not native enum) to keep the migration column loose.
    public const DECISION_REPLIED                = 'replied';
    public const DECISION_DM_ONLY                = 'dm_only';
    public const DECISION_RATE_LIMITED           = 'rate_limited';
    public const DECISION_FILTERED_OFF           = 'filtered_off';
    public const DECISION_FILTERED_MODE          = 'filtered_mode';
    public const DECISION_FILTERED_SCOPE         = 'filtered_scope';
    public const DECISION_FILTERED_KEYWORD       = 'filtered_keyword';
    public const DECISION_FILTERED_WORKING_HOURS = 'filtered_working_hours';
    public const DECISION_FILTERED_SELF          = 'filtered_self';
    public const DECISION_FILTERED_REPLY         = 'filtered_reply';
    public const DECISION_ERROR_GRAPH_API        = 'error_graph_api';
    public const DECISION_ERROR_AI               = 'error_ai';

    protected $fillable = [
        'page_id',
        'pages_post_id',
        'platform_comment_id',
        'parent_comment_id',
        'commenter_platform_id',
        'commenter_name',
        'text',
        'received_at',
        'decision',
        'decision_reason',
        'reply_text',
        'graph_reply_id',
        'dm_sent_at',
        'dm_graph_message_id',
        'graph_error',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'dm_sent_at'  => 'datetime',
            'graph_error' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function pagesPost(): BelongsTo
    {
        return $this->belongsTo(PagesPost::class);
    }
}
```

- [ ] **Step 6: Write factories**

Create `database/factories/PagesPostFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagesPost>
 */
class PagesPostFactory extends Factory
{
    protected $model = PagesPost::class;

    public function definition(): array
    {
        return [
            'page_id'             => Page::factory(),
            'platform_post_id'    => (string) $this->faker->unique()->numberBetween(100000, 999999),
            'created_at_platform' => now()->subDays(7),
            'first_seen_at'       => now(),
        ];
    }
}
```

Create `database/factories/CommentFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $page = Page::factory()->create();
        $post = PagesPost::factory()->create(['page_id' => $page->id]);

        return [
            'page_id'               => $page->id,
            'pages_post_id'         => $post->id,
            'platform_comment_id'   => (string) $this->faker->unique()->numberBetween(1000000, 9999999),
            'parent_comment_id'     => null,
            'commenter_platform_id' => (string) $this->faker->unique()->numberBetween(100, 999999),
            'commenter_name'        => $this->faker->name(),
            'text'                  => $this->faker->sentence(),
            'received_at'           => now(),
        ];
    }
}
```

- [ ] **Step 7: Write model tests**

Create `tests/Unit/Models/PagesPostTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\PagesPost;

it('belongs to a page', function () {
    $page = Page::factory()->create();
    $post = PagesPost::factory()->create(['page_id' => $page->id]);

    expect($post->page->id)->toBe($page->id);
});

it('casts datetime fields', function () {
    $post = PagesPost::factory()->create();

    expect($post->created_at_platform)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($post->first_seen_at)->toBeInstanceOf(\Carbon\Carbon::class);
});
```

Create `tests/Unit/Models/CommentTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;

it('belongs to a page and a pages_post', function () {
    $comment = Comment::factory()->create();

    expect($comment->page)->toBeInstanceOf(Page::class);
    expect($comment->pagesPost)->toBeInstanceOf(PagesPost::class);
});

it('exposes decision constants', function () {
    expect(Comment::DECISION_REPLIED)->toBe('replied');
    expect(Comment::DECISION_RATE_LIMITED)->toBe('rate_limited');
    expect(Comment::DECISION_FILTERED_MODE)->toBe('filtered_mode');
    expect(Comment::DECISION_ERROR_GRAPH_API)->toBe('error_graph_api');
});

it('casts graph_error as array', function () {
    $comment = Comment::factory()->create([
        'graph_error' => ['code' => 100, 'message' => 'test'],
    ]);
    $comment->refresh();

    expect($comment->graph_error)->toBe(['code' => 100, 'message' => 'test']);
});
```

- [ ] **Step 8: Run tests to verify GREEN**

Run: `vendor/bin/pest tests/Unit/Models/PagesPostTest.php tests/Unit/Models/CommentTest.php`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add database/migrations/2026_09_01_100000_create_pages_posts_table.php database/migrations/2026_09_01_100100_create_comments_table.php app/Models/PagesPost.php app/Models/Comment.php database/factories/PagesPostFactory.php database/factories/CommentFactory.php tests/Unit/Models/PagesPostTest.php tests/Unit/Models/CommentTest.php
git commit -m "feat(comments): pages_posts + comments tables, models, factories"
```

---

## Task 3: Meta comment payload parser

**Files:**
- Create: `app/Services/Comments/MetaCommentPayloadParser.php`
- Test: `tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php`:

```php
<?php

declare(strict_types=1);

use App\Services\Comments\MetaCommentPayloadParser;

it('parses a Facebook feed comment change', function () {
    $change = [
        'field' => 'feed',
        'value' => [
            'item'       => 'comment',
            'comment_id' => '123_456',
            'post_id'    => '123',
            'from'       => ['id' => 'user_1', 'name' => 'Ada Lovelace'],
            'message'    => 'How much does this cost?',
            'created_time' => 1756636800,
            'verb'       => 'add',
        ],
    ];

    $parsed = (new MetaCommentPayloadParser())->parse($change);

    expect($parsed)->not->toBeNull();
    expect($parsed['platform_comment_id'])->toBe('123_456');
    expect($parsed['platform_post_id'])->toBe('123');
    expect($parsed['commenter_platform_id'])->toBe('user_1');
    expect($parsed['commenter_name'])->toBe('Ada Lovelace');
    expect($parsed['text'])->toBe('How much does this cost?');
    expect($parsed['parent_comment_id'])->toBeNull();
});

it('parses an Instagram comment change', function () {
    $change = [
        'field' => 'comments',
        'value' => [
            'id'   => 'ig_comment_1',
            'text' => 'Love this!',
            'from' => ['id' => 'ig_user_1', 'username' => 'ada'],
            'media' => ['id' => 'ig_media_1'],
        ],
    ];

    $parsed = (new MetaCommentPayloadParser())->parse($change);

    expect($parsed)->not->toBeNull();
    expect($parsed['platform_comment_id'])->toBe('ig_comment_1');
    expect($parsed['platform_post_id'])->toBe('ig_media_1');
    expect($parsed['commenter_platform_id'])->toBe('ig_user_1');
    expect($parsed['commenter_name'])->toBe('ada');
    expect($parsed['text'])->toBe('Love this!');
});

it('returns null when field is not a comment field', function () {
    $parsed = (new MetaCommentPayloadParser())->parse(['field' => 'messages', 'value' => []]);
    expect($parsed)->toBeNull();
});

it('returns null when FB feed change is not a comment item', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => ['item' => 'like', 'post_id' => '123'],
    ]);
    expect($parsed)->toBeNull();
});

it('returns null when FB verb is not add (edit, remove, hide ignored)', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => [
            'item' => 'comment', 'verb' => 'remove',
            'comment_id' => 'c1', 'post_id' => 'p1',
            'from' => ['id' => 'u1', 'name' => 'x'], 'message' => 'x',
        ],
    ]);
    expect($parsed)->toBeNull();
});

it('extracts parent_id when FB comment is a reply to another comment', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => [
            'item' => 'comment', 'verb' => 'add',
            'comment_id' => 'c2', 'post_id' => 'p1',
            'parent_id' => 'c1',
            'from' => ['id' => 'u1', 'name' => 'x'], 'message' => 'reply',
        ],
    ]);
    expect($parsed['parent_comment_id'])->toBe('c1');
});
```

- [ ] **Step 2: Run to verify RED**

Run: `vendor/bin/pest tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php`
Expected: FAIL — class does not exist.

- [ ] **Step 3: Implement**

Create `app/Services/Comments/MetaCommentPayloadParser.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Comments;

/**
 * Normalize Meta webhook `entry.changes[]` payloads into a shape the ingestion
 * pipeline can consume without caring about FB vs IG differences.
 *
 * Returns null when the change is not an addable comment (wrong field, wrong item,
 * verb!=add, etc.) — the ingest job treats null as "silently drop".
 */
class MetaCommentPayloadParser
{
    /**
     * @param  array<string, mixed>  $change  One item from entry.changes[]
     * @return array{
     *     platform: 'facebook'|'instagram',
     *     platform_comment_id: string,
     *     platform_post_id: string,
     *     parent_comment_id: string|null,
     *     commenter_platform_id: string,
     *     commenter_name: string,
     *     text: string,
     *     received_at: \Carbon\Carbon,
     * }|null
     */
    public function parse(array $change): ?array
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        return match ($field) {
            'feed'     => $this->parseFacebookFeed($value),
            'comments' => $this->parseInstagramComments($value),
            default    => null,
        };
    }

    /** @param array<string, mixed> $value */
    protected function parseFacebookFeed(array $value): ?array
    {
        if (($value['item'] ?? null) !== 'comment') {
            return null;
        }
        if (($value['verb'] ?? 'add') !== 'add') {
            return null;
        }
        $commentId = $value['comment_id'] ?? null;
        $postId    = $value['post_id'] ?? null;
        $from      = $value['from'] ?? [];
        $message   = $value['message'] ?? '';

        if (! $commentId || ! $postId || empty($from['id'])) {
            return null;
        }

        return [
            'platform'              => 'facebook',
            'platform_comment_id'   => (string) $commentId,
            'platform_post_id'      => (string) $postId,
            'parent_comment_id'     => isset($value['parent_id']) ? (string) $value['parent_id'] : null,
            'commenter_platform_id' => (string) $from['id'],
            'commenter_name'        => (string) ($from['name'] ?? 'Unknown'),
            'text'                  => (string) $message,
            'received_at'           => isset($value['created_time'])
                ? \Carbon\Carbon::createFromTimestamp((int) $value['created_time'])
                : now(),
        ];
    }

    /** @param array<string, mixed> $value */
    protected function parseInstagramComments(array $value): ?array
    {
        $commentId = $value['id'] ?? null;
        $mediaId   = $value['media']['id'] ?? null;
        $from      = $value['from'] ?? [];
        $text      = $value['text'] ?? '';

        if (! $commentId || ! $mediaId || empty($from['id'])) {
            return null;
        }

        return [
            'platform'              => 'instagram',
            'platform_comment_id'   => (string) $commentId,
            'platform_post_id'      => (string) $mediaId,
            'parent_comment_id'     => isset($value['parent_id']) ? (string) $value['parent_id'] : null,
            'commenter_platform_id' => (string) $from['id'],
            'commenter_name'        => (string) ($from['username'] ?? 'Unknown'),
            'text'                  => (string) $text,
            'received_at'           => now(),
        ];
    }
}
```

- [ ] **Step 4: Run tests GREEN**

Run: `vendor/bin/pest tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php`
Expected: PASS — 6 tests.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Comments/MetaCommentPayloadParser.php tests/Unit/Services/Comments/MetaCommentPayloadParserTest.php
git commit -m "feat(comments): MetaCommentPayloadParser normalizes FB feed + IG comment webhooks"
```

---

## Task 4: `IngestCommentJob` — dedupe, filter, rate-limit, decide

**Files:**
- Create: `app/Jobs/IngestCommentJob.php`
- Create: `app/Services/Comments/PostCreationTimeCache.php`
- Create: `app/Services/Comments/CommentFilterService.php`
- Test: `tests/Feature/Jobs/IngestCommentJobTest.php`

Complexity note: This is the biggest task in the plan. The `CommentFilterService` encapsulates the decision tree from spec §6 IngestCommentJob step 5-11, keeping the job class thin.

- [ ] **Step 1: Write `PostCreationTimeCache`**

Create `app/Services/Comments/PostCreationTimeCache.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Comments;

use App\Models\Page;
use App\Models\PagesPost;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * On cache miss, calls Graph API once for the post's created_time and stores it.
 * Every subsequent comment on the same post is a DB hit only.
 */
class PostCreationTimeCache
{
    public function resolve(Page $page, string $platformPostId): ?PagesPost
    {
        $existing = PagesPost::where('page_id', $page->id)
            ->where('platform_post_id', $platformPostId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $createdAt = $this->fetchCreatedTime($page, $platformPostId);
        if (! $createdAt) {
            return null;
        }

        return PagesPost::create([
            'page_id'             => $page->id,
            'platform_post_id'    => $platformPostId,
            'created_at_platform' => $createdAt,
            'first_seen_at'       => now(),
        ]);
    }

    protected function fetchCreatedTime(Page $page, string $platformPostId): ?Carbon
    {
        try {
            $response = Http::timeout(10)->get("https://graph.facebook.com/v21.0/{$platformPostId}", [
                'fields'       => 'created_time',
                'access_token' => decrypt($page->page_access_token),
            ]);

            if (! $response->successful()) {
                Log::warning('PostCreationTimeCache fetch failed', [
                    'page_id'          => $page->id,
                    'platform_post_id' => $platformPostId,
                    'status'           => $response->status(),
                    'body'             => $response->body(),
                ]);

                return null;
            }

            $createdTime = $response->json('created_time');

            return $createdTime ? Carbon::parse($createdTime) : null;
        } catch (\Throwable $e) {
            Log::warning('PostCreationTimeCache exception', [
                'page_id'          => $page->id,
                'platform_post_id' => $platformPostId,
                'error'            => $e->getMessage(),
            ]);

            return null;
        }
    }
}
```

- [ ] **Step 2: Write `CommentFilterService`**

Create `app/Services/Comments/CommentFilterService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services\Comments;

use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;
use Illuminate\Support\Facades\Redis;

/**
 * Decision tree for whether an incoming (parsed) comment should be replied to.
 * Returns a decision string (or null meaning "proceed"), plus optional reason.
 * Rate-limit and mode checks are here so the ingest job stays thin.
 */
class CommentFilterService
{
    /**
     * @param  array{platform_comment_id: string, platform_post_id: string, parent_comment_id: ?string, commenter_platform_id: string, text: string}  $parsed
     * @return array{decision: string|null, reason: string|null}
     */
    public function decide(Page $page, AiConfig $config, PagesPost $post, array $parsed): array
    {
        $settings = $config->comment_settings ?? AiConfig::defaultCommentSettings();

        if (empty($settings['enabled'])) {
            return ['decision' => Comment::DECISION_FILTERED_OFF, 'reason' => 'feature disabled on this page'];
        }

        if ($parsed['parent_comment_id'] !== null) {
            return ['decision' => Comment::DECISION_FILTERED_REPLY, 'reason' => 'reply-to-comment; MVP only handles top-level'];
        }

        if ($this->commenterIsPage($page, $parsed['commenter_platform_id'])) {
            return ['decision' => Comment::DECISION_FILTERED_SELF, 'reason' => 'page commented on its own post'];
        }

        if (! $config->isWithinWorkingHours()) {
            return ['decision' => Comment::DECISION_FILTERED_WORKING_HOURS, 'reason' => 'outside working hours'];
        }

        $scope    = $settings['scope']       ?? AiConfig::COMMENT_SCOPE_FUTURE_ONLY;
        $enabledAt = $settings['enabled_at'] ?? null;
        if ($scope === AiConfig::COMMENT_SCOPE_FUTURE_ONLY && $enabledAt) {
            if ($post->created_at_platform->lt(\Carbon\Carbon::parse($enabledAt))) {
                return ['decision' => Comment::DECISION_FILTERED_SCOPE, 'reason' => "post predates enabled_at={$enabledAt}"];
            }
        }

        $mode = $settings['reply_mode'] ?? AiConfig::COMMENT_REPLY_OFF;
        if ($mode === AiConfig::COMMENT_REPLY_OFF) {
            return ['decision' => Comment::DECISION_FILTERED_OFF, 'reason' => 'reply_mode=off'];
        }

        if ($mode === AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS) {
            $keywords = $settings['reply_keywords'] ?? [];
            if (! $this->anyKeywordMatches($parsed['text'], $keywords)) {
                return ['decision' => Comment::DECISION_FILTERED_KEYWORD, 'reason' => 'no reply_keyword matched'];
            }
        }

        // Rate limit — INCR here so we count skips-by-cap correctly.
        $rlKey = "comments:rl:{$page->id}:{$post->id}:" . now()->format('Y-m-d');
        $count = (int) Redis::incr($rlKey);
        if ($count === 1) {
            Redis::expire($rlKey, 86400);
        }
        $cap = (int) ($settings['max_ai_replies_per_post_per_day'] ?? 20);
        if ($count > $cap) {
            return ['decision' => Comment::DECISION_RATE_LIMITED, 'reason' => "cap {$cap}/day reached"];
        }

        return ['decision' => null, 'reason' => null];
    }

    protected function commenterIsPage(Page $page, string $commenterId): bool
    {
        $selfIds = array_filter([
            $page->platform_page_id,
            $page->metadata['igsid'] ?? null,
            $page->metadata['igbid'] ?? null,
        ]);

        return in_array($commenterId, $selfIds, true);
    }

    /** @param array<int, string> $keywords */
    protected function anyKeywordMatches(string $text, array $keywords): bool
    {
        $needle = mb_strtolower($text);
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($needle, mb_strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }
}
```

- [ ] **Step 3: Write `IngestCommentJob`**

Create `app/Jobs/IngestCommentJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\Page;
use App\Services\Comments\CommentFilterService;
use App\Services\Comments\PostCreationTimeCache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class IngestCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    /**
     * @param  array{platform: 'facebook'|'instagram', platform_comment_id: string, platform_post_id: string, parent_comment_id: ?string, commenter_platform_id: string, commenter_name: string, text: string, received_at: \Carbon\Carbon}  $parsed
     */
    public function __construct(public array $parsed, public string $platformPageId)
    {
        $this->onQueue('comments-ingest');
    }

    public function handle(PostCreationTimeCache $postCache, CommentFilterService $filter): void
    {
        // 1. Dedupe via Redis SET NX.
        $dedupeKey = "comments:seen:{$this->parsed['platform_comment_id']}";
        if (! Redis::set($dedupeKey, '1', 'EX', 86400, 'NX')) {
            return;
        }

        // 2. Resolve page. Reuse the matcher shape from ProcessIncomingMessage for consistency.
        $page = Page::where('platform', $this->parsed['platform'])
            ->where(fn ($q) => $q->where('platform_page_id', $this->platformPageId)
                ->orWhereJsonContains('metadata->igsid', $this->platformPageId)
                ->orWhereJsonContains('metadata->igbid', $this->platformPageId))
            ->where('is_active', true)
            ->latest()
            ->first();

        if (! $page || ! $page->aiConfig) {
            return; // silent drop — customer never opted in
        }

        // 3. Resolve/cache post.
        $post = $postCache->resolve($page, $this->parsed['platform_post_id']);
        if (! $post) {
            Log::warning('IngestCommentJob: could not resolve post creation time', [
                'page_id' => $page->id,
                'post_id' => $this->parsed['platform_post_id'],
            ]);
            return;
        }

        // 4. Run the filter decision tree.
        $decision = $filter->decide($page, $page->aiConfig, $post, $this->parsed);

        // 5. Store the row. If decision is null, leave decision unset and dispatch classifier/sender.
        $comment = Comment::create([
            'page_id'               => $page->id,
            'pages_post_id'         => $post->id,
            'platform_comment_id'   => $this->parsed['platform_comment_id'],
            'parent_comment_id'     => $this->parsed['parent_comment_id'],
            'commenter_platform_id' => $this->parsed['commenter_platform_id'],
            'commenter_name'        => $this->parsed['commenter_name'],
            'text'                  => $this->parsed['text'],
            'received_at'           => $this->parsed['received_at'],
            'decision'              => $decision['decision'],
            'decision_reason'       => $decision['reason'],
        ]);

        if ($decision['decision'] !== null) {
            return; // filtered out — no further work
        }

        // 6. Dispatch classifier (q&c mode) or straight to sender.
        $settings = $page->aiConfig->comment_settings ?? AiConfig::defaultCommentSettings();
        $mode = $settings['reply_mode'] ?? AiConfig::COMMENT_REPLY_OFF;
        if ($mode === AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS) {
            ClassifyCommentJob::dispatch($comment->id);
        } else {
            SendAiCommentReplyJob::dispatch($comment->id);
        }
    }
}
```

- [ ] **Step 4: Write feature tests**

Create `tests/Feature/Jobs/IngestCommentJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Jobs\ClassifyCommentJob;
use App\Jobs\IngestCommentJob;
use App\Jobs\SendAiCommentReplyJob;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
});

/**
 * @return array{Page, AiConfig}
 */
function makePageWithCommentConfig(string $platform, array $overrides = []): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id]);
    $account = ConnectedAccount::factory()->create(['team_id' => $team->id, 'platform' => $platform]);
    $page = Page::factory()->create([
        'team_id'              => $team->id,
        'connected_account_id' => $account->id,
        'platform'             => $platform,
        'platform_page_id'     => 'PAGE_' . $platform,
        'is_active'            => true,
    ]);
    $config = AiConfig::create(array_merge([
        'page_id'              => $page->id,
        'team_id'              => $team->id,
        'business_description' => 'valid business description over ten chars',
        'is_active'            => true,
        'is_24_7'              => true,
        'comment_settings'     => array_merge(AiConfig::defaultCommentSettings(), [
            'enabled'    => true,
            'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_ALL,
        ]),
    ], $overrides));

    return [$page->refresh(), $config];
}

function fakePostCreatedTimeResponse(): void
{
    Http::fake([
        'graph.facebook.com/v21.0/*' => Http::response(['created_time' => now()->subHour()->toIso8601String()], 200),
    ]);
}

function parsedComment(string $platform, string $commentId = 'c1', array $overrides = []): array
{
    return array_merge([
        'platform'              => $platform,
        'platform_comment_id'   => $commentId,
        'platform_post_id'      => 'POST_1',
        'parent_comment_id'     => null,
        'commenter_platform_id' => 'USER_1',
        'commenter_name'        => 'Ada',
        'text'                  => 'How much?',
        'received_at'           => now(),
    ], $overrides);
}

it('stores a comment and dispatches SendAiCommentReplyJob when mode=all', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    (new IngestCommentJob(parsedComment('facebook'), $page->platform_page_id))
        ->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));

    expect(Comment::count())->toBe(1);
    expect(Comment::first()->decision)->toBeNull();
    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('is idempotent on redelivery (dedupe)', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    $job = new IngestCommentJob(parsedComment('facebook'), $page->platform_page_id);
    $job->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));
    $job->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));

    expect(Comment::count())->toBe(1);
});

it('filters reply-to-comment as filtered_reply', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook');

    (new IngestCommentJob(parsedComment('facebook', overrides: ['parent_comment_id' => 'c0']), $page->platform_page_id))
        ->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));

    expect(Comment::first()->decision)->toBe(Comment::DECISION_FILTERED_REPLY);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('rate-limits after cap and stores rate_limited row', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page, $config] = makePageWithCommentConfig('facebook', [
        'comment_settings' => array_merge(AiConfig::defaultCommentSettings(), [
            'enabled' => true, 'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_ALL,
            'max_ai_replies_per_post_per_day' => 2,
        ]),
    ]);

    foreach (['c1', 'c2', 'c3'] as $cid) {
        (new IngestCommentJob(parsedComment('facebook', $cid), $page->platform_page_id))
            ->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));
    }

    expect(Comment::where('decision', Comment::DECISION_RATE_LIMITED)->count())->toBe(1);
    Queue::assertPushed(SendAiCommentReplyJob::class, 2);
});

it('dispatches classifier when reply_mode is questions_and_complaints', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook', [
        'comment_settings' => array_merge(AiConfig::defaultCommentSettings(), [
            'enabled' => true, 'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS,
        ]),
    ]);

    (new IngestCommentJob(parsedComment('facebook'), $page->platform_page_id))
        ->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));

    Queue::assertPushed(ClassifyCommentJob::class);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('filters custom_keywords miss', function () {
    Queue::fake();
    fakePostCreatedTimeResponse();
    [$page] = makePageWithCommentConfig('facebook', [
        'comment_settings' => array_merge(AiConfig::defaultCommentSettings(), [
            'enabled' => true, 'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS,
            'reply_keywords' => ['price'],
        ]),
    ]);

    (new IngestCommentJob(parsedComment('facebook', overrides: ['text' => 'lovely photo']), $page->platform_page_id))
        ->handle(app(\App\Services\Comments\PostCreationTimeCache::class), app(\App\Services\Comments\CommentFilterService::class));

    expect(Comment::first()->decision)->toBe(Comment::DECISION_FILTERED_KEYWORD);
});
```

- [ ] **Step 5: Run RED**

Run: `vendor/bin/pest tests/Feature/Jobs/IngestCommentJobTest.php`
Expected: FAIL — classes missing.

- [ ] **Step 6: Verify GREEN after implementations from Steps 1-3**

Run: `vendor/bin/pest tests/Feature/Jobs/IngestCommentJobTest.php`
Expected: PASS — 6 tests.

Note: `ClassifyCommentJob` and `SendAiCommentReplyJob` don't exist yet but the test uses `Queue::fake()` which stubs the dispatch — the referenced classes only need to be class-loadable stubs. Create them as empty class files in Task 4 to satisfy the autoloader:

```php
<?php declare(strict_types=1); namespace App\Jobs;
use Illuminate\Bus\Queueable; use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class ClassifyCommentJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public int $commentId) {}
    public function handle(): void {}
}
```
(Same shape for `SendAiCommentReplyJob`.) Full implementations come in Tasks 5 and 6.

- [ ] **Step 7: Commit**

```bash
git add app/Services/Comments/ app/Jobs/IngestCommentJob.php app/Jobs/ClassifyCommentJob.php app/Jobs/SendAiCommentReplyJob.php tests/Feature/Jobs/IngestCommentJobTest.php
git commit -m "feat(comments): IngestCommentJob with dedupe, filter, rate-limit"
```

---

## Task 5: `ClassifyCommentJob` — cheap Q/C/N classification

**Files:**
- Modify: `app/Jobs/ClassifyCommentJob.php` (replace stub with real implementation)
- Test: `tests/Feature/Jobs/ClassifyCommentJobTest.php`

- [ ] **Step 1: Write tests**

Create `tests/Feature/Jobs/ClassifyCommentJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Jobs\ClassifyCommentJob;
use App\Jobs\SendAiCommentReplyJob;
use App\Models\Comment;
use App\Services\Ai\AiProviderInterface;
use Illuminate\Support\Facades\Queue;

it('dispatches SendAiCommentReplyJob when classifier says Q', function () {
    Queue::fake();
    $comment = Comment::factory()->create();

    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('Q');
    app()->instance(AiProviderInterface::class, $ai);

    (new ClassifyCommentJob($comment->id))->handle($ai);

    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('dispatches SendAiCommentReplyJob when classifier says C', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('C');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    Queue::assertPushed(SendAiCommentReplyJob::class);
});

it('marks decision=filtered_mode when classifier says N and does not dispatch send', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('N');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_FILTERED_MODE);
    Queue::assertNotPushed(SendAiCommentReplyJob::class);
});

it('marks decision=error_ai when classifier returns empty string (Nara failure)', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
});

it('treats unexpected letter as N (safety default)', function () {
    Queue::fake();
    $comment = Comment::factory()->create();
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('yolo');

    (new ClassifyCommentJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_FILTERED_MODE);
});
```

- [ ] **Step 2: Replace stub with real `ClassifyCommentJob`**

Overwrite `app/Jobs/ClassifyCommentJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Comment;
use App\Services\Ai\AiProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClassifyCommentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public array $backoff = [30, 120];

    public function __construct(public int $commentId)
    {
        $this->onQueue('comments-ingest');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $comment = Comment::find($this->commentId);
        if (! $comment || $comment->decision !== null) {
            return;
        }

        $system = 'You classify a single public comment into one of three categories. '
                . 'Respond with EXACTLY ONE letter: Q, C, or N. '
                . 'Q: the comment asks a question (explicit or implied). '
                . 'C: the comment expresses a complaint, problem, or negative sentiment. '
                . 'N: neither — praise, greeting, spam, off-topic.';

        $response = trim($ai->callChat([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $comment->text],
        ]));

        // Per CLAUDE.md pin #5: empty string means provider failure. Do NOT send.
        if ($response === '') {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'classifier returned empty (Nara failure)',
            ]);
            return;
        }

        $letter = strtoupper($response[0] ?? 'N');

        if ($letter === 'Q' || $letter === 'C') {
            SendAiCommentReplyJob::dispatch($comment->id);
            return;
        }

        // 'N' or anything unexpected — safety-default to N.
        $comment->update([
            'decision'        => Comment::DECISION_FILTERED_MODE,
            'decision_reason' => "classifier returned '{$letter}'",
        ]);
    }
}
```

- [ ] **Step 3: Verify GREEN**

Run: `vendor/bin/pest tests/Feature/Jobs/ClassifyCommentJobTest.php`
Expected: PASS — 5 tests.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/ClassifyCommentJob.php tests/Feature/Jobs/ClassifyCommentJobTest.php
git commit -m "feat(comments): ClassifyCommentJob — 1-shot Q/C/N classifier on cheapest Nara model"
```

---

## Task 6: `SendAiCommentReplyJob` — Nara reply + Graph API public reply + optional DM

**Files:**
- Modify: `app/Jobs/SendAiCommentReplyJob.php` (replace stub with real implementation)
- Test: `tests/Feature/Jobs/SendAiCommentReplyJobTest.php`

- [ ] **Step 1: Write tests**

Create `tests/Feature/Jobs/SendAiCommentReplyJobTest.php`:

```php
<?php

declare(strict_types=1);

use App\Jobs\SendAiCommentReplyJob;
use App\Models\AiConfig;
use App\Models\Comment;
use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\PagesPost;
use App\Models\Team;
use App\Models\User;
use App\Services\Ai\AiProviderInterface;
use Illuminate\Support\Facades\Http;

function makeCommentForSend(array $settingsOverrides = []): Comment
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['owner_id' => $user->id, 'ai_enabled' => true, 'ai_credits_used' => 0, 'ai_credits_limit' => 1000]);
    $account = ConnectedAccount::factory()->create(['team_id' => $team->id, 'platform' => 'facebook']);
    $page = Page::factory()->create([
        'team_id' => $team->id, 'connected_account_id' => $account->id,
        'platform' => 'facebook', 'platform_page_id' => 'PAGE_1', 'is_active' => true,
    ]);
    AiConfig::create([
        'page_id' => $page->id, 'team_id' => $team->id,
        'business_description' => 'valid business description over ten chars',
        'is_active' => true, 'is_24_7' => true,
        'comment_settings' => array_merge(AiConfig::defaultCommentSettings(), array_merge([
            'enabled' => true, 'enabled_at' => now()->subDay()->toIso8601String(),
            'reply_mode' => AiConfig::COMMENT_REPLY_ALL,
        ], $settingsOverrides)),
    ]);
    $post = PagesPost::factory()->create(['page_id' => $page->id]);

    return Comment::factory()->create([
        'page_id' => $page->id, 'pages_post_id' => $post->id,
        'text' => 'How much does this cost?',
    ]);
}

it('posts the AI reply publicly and stores decision=replied', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('Great question — DM us for pricing!');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_REPLIED);
    expect($comment->reply_text)->toBe('Great question — DM us for pricing!');
    expect($comment->graph_reply_id)->toBe('REPLY_1');
});

it('stores decision=error_ai when Nara returns empty string', function () {
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
    Http::assertNothingSent();
});

it('stores decision=error_graph_api on 4xx and does not retry', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['error' => ['message' => 'deleted', 'code' => 100]], 400),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('hi!');
    $comment = makeCommentForSend();

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->decision)->toBe(Comment::DECISION_ERROR_GRAPH_API);
    expect($comment->graph_error)->not->toBeNull();
});

it('sends a DM when dm_mode=always', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
        'graph.facebook.com/v21.0/*/messages' => Http::response(['message_id' => 'M_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('reply');
    $comment = makeCommentForSend([
        'dm_mode' => AiConfig::COMMENT_DM_ALWAYS,
    ]);

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    $comment->refresh();
    expect($comment->dm_sent_at)->not->toBeNull();
    expect($comment->dm_graph_message_id)->toBe('M_1');
});

it('skips DM when dm_mode=on_purchase_intent and no keyword match', function () {
    Http::fake([
        'graph.facebook.com/v21.0/*/comments' => Http::response(['id' => 'REPLY_1'], 200),
    ]);
    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldReceive('callChat')->once()->andReturn('reply');
    $comment = makeCommentForSend([
        'dm_mode'      => AiConfig::COMMENT_DM_ON_PURCHASE_INTENT,
        'dm_keywords'  => ['price'],
    ]);
    $comment->update(['text' => 'nice photo']);

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->dm_sent_at)->toBeNull();
});

it('respects canDispatchAi and stores error_ai when team cannot dispatch', function () {
    $comment = makeCommentForSend();
    $comment->page->team->update(['ai_enabled' => false]);

    $ai = Mockery::mock(AiProviderInterface::class);
    $ai->shouldNotReceive('callChat');

    (new SendAiCommentReplyJob($comment->id))->handle($ai);

    expect($comment->fresh()->decision)->toBe(Comment::DECISION_ERROR_AI);
});
```

- [ ] **Step 2: Replace stub with real `SendAiCommentReplyJob`**

Overwrite `app/Jobs/SendAiCommentReplyJob.php`:

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiConfig;
use App\Models\Comment;
use App\Services\Ai\AiProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendAiCommentReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(public int $commentId)
    {
        $this->onQueue('comments-send');
    }

    public function handle(AiProviderInterface $ai): void
    {
        $comment = Comment::with('page.team', 'page.aiConfig')->find($this->commentId);
        if (! $comment || $comment->decision !== null) {
            return;
        }

        $team   = $comment->page->team;
        $config = $comment->page->aiConfig;

        if (! $team || ! $config || ! $team->canDispatchAi()) {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'team cannot dispatch AI',
            ]);
            return;
        }

        $settings = $config->comment_settings ?? AiConfig::defaultCommentSettings();
        $reply = trim($ai->callChat($this->buildPromptMessages($config, $settings, $comment)));

        if ($reply === '') {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_AI,
                'decision_reason' => 'Nara returned empty string',
            ]);
            return;
        }

        // Public reply via Graph API.
        $graphResp = $this->postPublicReply($comment, $reply);
        if (! $graphResp['ok']) {
            $comment->update([
                'decision'        => Comment::DECISION_ERROR_GRAPH_API,
                'decision_reason' => 'public reply Graph API returned non-2xx',
                'reply_text'      => $reply,
                'graph_error'     => $graphResp['error'],
            ]);
            if ($graphResp['status'] >= 500 || $graphResp['status'] === 429) {
                throw new \RuntimeException("Retryable Graph failure: {$graphResp['status']}");
            }
            return; // permanent 4xx — no retry
        }

        $comment->fill([
            'decision'       => Comment::DECISION_REPLIED,
            'reply_text'     => $reply,
            'graph_reply_id' => $graphResp['id'],
        ]);

        // Optional DM.
        $dmMode = $settings['dm_mode'] ?? AiConfig::COMMENT_DM_OFF;
        if ($this->shouldDm($dmMode, $settings, $comment->text)) {
            $dmResp = $this->sendDm($comment, $reply);
            if ($dmResp['ok']) {
                $comment->dm_sent_at = now();
                $comment->dm_graph_message_id = $dmResp['message_id'];
            } else {
                // Log but don't fail — public reply already went through.
                $comment->graph_error = $dmResp['error'];
                Log::warning('SendAiCommentReplyJob: DM failed but public reply succeeded', [
                    'comment_id' => $comment->id,
                    'status'     => $dmResp['status'],
                ]);
            }
        }

        $comment->save();
    }

    /** @return array<int, array{role: string, content: string}> */
    protected function buildPromptMessages(AiConfig $config, array $settings, Comment $comment): array
    {
        $persona = "You reply to public comments on the business's " . $comment->page->platform . " page. "
                 . "Keep replies to 1-3 sentences, natural tone, no hard sell. "
                 . "Business: " . mb_substr((string) $config->business_description, 0, 400) . ". "
                 . "Tone: " . ($config->tone ?? 'friendly') . ". "
                 . "Language: " . ($config->language ?? 'en') . ".";
        if (! empty($settings['reply_instructions'])) {
            $persona .= " Extra rules: " . $settings['reply_instructions'];
        }

        return [
            ['role' => 'system', 'content' => $persona],
            ['role' => 'user',   'content' => "Public comment from {$comment->commenter_name}: \"{$comment->text}\""],
        ];
    }

    /** @return array{ok: bool, id?: string, status: int, error?: array} */
    protected function postPublicReply(Comment $comment, string $reply): array
    {
        $response = Http::timeout(15)->asJson()->post(
            "https://graph.facebook.com/v21.0/{$comment->platform_comment_id}/comments",
            [
                'message'      => $reply,
                'access_token' => decrypt($comment->page->page_access_token),
            ]
        );

        if ($response->successful()) {
            return ['ok' => true, 'id' => (string) $response->json('id'), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    /** @return array{ok: bool, message_id?: string, status: int, error?: array} */
    protected function sendDm(Comment $comment, string $reply): array
    {
        $recipientContainerId = $comment->page->platform === 'instagram'
            ? ($comment->page->metadata['ig_user_id'] ?? $comment->page->platform_page_id)
            : $comment->page->platform_page_id;

        $body = [
            'recipient' => ['comment_id' => $comment->platform_comment_id],
            'message'   => ['text' => $reply],
        ];
        if ($comment->page->platform === 'facebook') {
            $body['messaging_type'] = 'RESPONSE';
        }

        $response = Http::timeout(15)->withOptions(['query' => [
            'access_token' => decrypt($comment->page->page_access_token),
        ]])->asJson()->post(
            "https://graph.facebook.com/v21.0/{$recipientContainerId}/messages",
            $body
        );

        if ($response->successful()) {
            return ['ok' => true, 'message_id' => (string) ($response->json('message_id') ?? ''), 'status' => $response->status()];
        }

        return ['ok' => false, 'status' => $response->status(), 'error' => (array) $response->json()];
    }

    protected function shouldDm(string $mode, array $settings, string $text): bool
    {
        return match ($mode) {
            AiConfig::COMMENT_DM_OFF               => false,
            AiConfig::COMMENT_DM_ALWAYS            => true,
            AiConfig::COMMENT_DM_ON_PURCHASE_INTENT => $this->matchesAny($text, $settings['dm_keywords'] ?? []),
            default                                => false,
        };
    }

    /** @param array<int, string> $keywords */
    protected function matchesAny(string $text, array $keywords): bool
    {
        $needle = mb_strtolower($text);
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($needle, mb_strtolower($kw)) !== false) {
                return true;
            }
        }
        return false;
    }
}
```

- [ ] **Step 3: Run tests GREEN**

Run: `vendor/bin/pest tests/Feature/Jobs/SendAiCommentReplyJobTest.php`
Expected: PASS — 6 tests.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/SendAiCommentReplyJob.php tests/Feature/Jobs/SendAiCommentReplyJobTest.php
git commit -m "feat(comments): SendAiCommentReplyJob — Nara reply + Graph public reply + optional DM"
```

---

## Task 7: Wire webhook routing — extend `ProcessIncomingMessage`

**Files:**
- Modify: `app/Jobs/ProcessIncomingMessage.php` (extend `processMetaMessenger`)
- Modify: `app/Services/Platforms/FacebookPlatform.php` (add `feed`, `comments` to subscribe fields)
- Test: `tests/Feature/Jobs/ProcessIncomingMessageCommentsTest.php`

- [ ] **Step 1: Locate the webhook subscribe method**

Grep for the current `subscribed_fields` list:
```bash
grep -n "subscribed_fields\|messages" app/Services/Platforms/FacebookPlatform.php | head -10
```

- [ ] **Step 2: Add `feed` and `comments` to the subscribe fields**

Find the line in `FacebookPlatform.php` that calls `POST /{page-id}/subscribed_apps` with `subscribed_fields=messages,messaging_postbacks,...`. Extend it to include `feed` (for FB) and `comments` (for IG). Concrete diff depends on exact current implementation — inspect and change in-place, keeping the same code style.

- [ ] **Step 3: Write feature test for the router extension**

Create `tests/Feature/Jobs/ProcessIncomingMessageCommentsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Jobs\IngestCommentJob;
use App\Jobs\ProcessIncomingMessage;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Queue;

it('dispatches IngestCommentJob on FB feed comment payload', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform'   => 'facebook',
        'event_type' => 'page',
        'payload'    => [
            'object' => 'page',
            'entry'  => [[
                'id'      => 'PAGE_1',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'item' => 'comment', 'verb' => 'add',
                        'comment_id' => 'c1', 'post_id' => 'PAGE_1_POST1',
                        'from' => ['id' => 'user1', 'name' => 'Ada'],
                        'message' => 'How much?',
                        'created_time' => 1756636800,
                    ],
                ]],
            ]],
        ],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(\App\Services\Ai\AiProviderInterface::class));

    Queue::assertPushed(IngestCommentJob::class, function ($job) {
        return $job->parsed['platform_comment_id'] === 'c1'
            && $job->platformPageId === 'PAGE_1';
    });
});

it('dispatches IngestCommentJob on IG comments payload', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform'   => 'instagram',
        'event_type' => 'instagram',
        'payload'    => [
            'object' => 'instagram',
            'entry'  => [[
                'id'      => 'IG_1',
                'changes' => [[
                    'field' => 'comments',
                    'value' => [
                        'id' => 'ig_c1',
                        'text' => 'love it',
                        'from' => ['id' => 'ig_user1', 'username' => 'ada'],
                        'media' => ['id' => 'ig_media1'],
                    ],
                ]],
            ]],
        ],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(\App\Services\Ai\AiProviderInterface::class));

    Queue::assertPushed(IngestCommentJob::class);
});

it('does NOT dispatch IngestCommentJob on messaging (unchanged existing flow)', function () {
    Queue::fake();
    $log = WebhookLog::create([
        'platform' => 'facebook', 'event_type' => 'page',
        'payload'  => ['object' => 'page', 'entry' => [['id' => 'PAGE_1', 'messaging' => []]]],
    ]);

    (new ProcessIncomingMessage($log->id))->handle(app(\App\Services\Ai\AiProviderInterface::class));

    Queue::assertNotPushed(IngestCommentJob::class);
});
```

- [ ] **Step 4: Extend `processMetaMessenger`**

In `app/Jobs/ProcessIncomingMessage.php`, replace the `processMetaMessenger` method body (~lines 85-98) with:

```php
    protected function processMetaMessenger(array $payload, string $platform, AiProviderInterface $ai): void
    {
        $entries = $payload['entry'] ?? [];
        $parser  = app(\App\Services\Comments\MetaCommentPayloadParser::class);

        foreach ($entries as $entry) {
            $pageId = $entry['id'] ?? null;

            // Existing: DM messaging.
            foreach ($entry['messaging'] ?? [] as $event) {
                if (isset($event['message'])) {
                    $this->handleMetaMessage($event, $platform, $pageId, $ai);
                }
            }

            // New: comment changes (FB feed field or IG comments field).
            foreach ($entry['changes'] ?? [] as $change) {
                $parsed = $parser->parse($change);
                if ($parsed && $pageId) {
                    \App\Jobs\IngestCommentJob::dispatch($parsed, $pageId);
                }
            }
        }
    }
```

- [ ] **Step 5: Run tests GREEN**

Run: `vendor/bin/pest tests/Feature/Jobs/ProcessIncomingMessageCommentsTest.php`
Expected: PASS — 3 tests.

- [ ] **Step 6: Run full comments test suite**

Run: `vendor/bin/pest tests/Feature/Jobs/Ingest* tests/Feature/Jobs/Classify* tests/Feature/Jobs/SendAiComment* tests/Feature/Jobs/ProcessIncomingMessageComments* tests/Unit/Models/PagesPost* tests/Unit/Models/Comment* tests/Unit/Services/Comments/* tests/Unit/Services/Platforms/FacebookPlatformScopes*`
Expected: PASS across all comments tests.

- [ ] **Step 7: Commit**

```bash
git add app/Jobs/ProcessIncomingMessage.php app/Services/Platforms/FacebookPlatform.php tests/Feature/Jobs/ProcessIncomingMessageCommentsTest.php
git commit -m "feat(comments): route Meta feed+comments webhook fields to IngestCommentJob"
```

---

## Task 8: Meta App Review submission draft (docs only)

**Files:**
- Create: `docs/meta-app-review/comments-permissions-submission.md`

- [ ] **Step 1: Write submission draft**

Create `docs/meta-app-review/comments-permissions-submission.md` with these sections:

1. **Overview** — what OT1 Pro is (~150 words), why we need the two permissions.
2. **`pages_manage_engagement` submission text** — 200-300 words explaining:
   - We reply to user comments on customers' Facebook Pages on behalf of them (opt-in).
   - We optionally open a one-shot Private Reply DM per comment, per Meta's window.
   - We do NOT hide, delete, or moderate comments.
3. **`instagram_manage_comments` submission text** — 200-300 words, same shape.
4. **Reviewer test-user instructions** — step by step:
   - Log in at `https://ot1-pro.com/register` with the test-user credentials Meta provides in App Review.
   - Go to `/connections`, click "Facebook Login" (managed OAuth).
   - Verify the reviewer's test page appears.
   - Go to `/settings/ai/config`, enable Comments tab (see Phase A UI).
   - Post a test comment on the test page.
   - Verify the AI reply appears publicly within 30 seconds.
   - (For DM permission) Verify a DM appears in the test user's Messenger inbox from the Page.
5. **Screencast script** (30-90s per permission):
   - Show `/settings/ai/config` Comments tab
   - Post a test comment on FB
   - Wait, show the AI reply appearing
   - Open Messenger, show the DM
   - Show the Comments admin log (once we ship one; for MVP, show the DB row via `php artisan tinker`)
6. **Submission checklist for developers.facebook.com** — exact click path:
   - developers.facebook.com/apps/1469090344742803 → App Review → Permissions and Features
   - Search "pages_manage_engagement", click "Request Advanced Access"
   - Paste the submission text from §2
   - Upload screencast
   - Add reviewer credentials from Meta's test-user pool
   - Submit
   - Repeat for "instagram_manage_comments"

- [ ] **Step 2: Commit**

```bash
git add docs/meta-app-review/comments-permissions-submission.md
git commit -m "docs(meta): App Review submission draft for pages_manage_engagement + instagram_manage_comments"
```

- [ ] **Step 3: Owner action (blocking on human)**

Wait for the user (Omar) to submit at developers.facebook.com using the draft. When the user confirms submission in chat, mark this task complete. Do not proceed to Task 9 until confirmed OR until the user says "skip App Review for now."

---

## Task 9: Push, PR, and deploy verification

- [ ] **Step 1: Full regression run locally**

Run: `vendor/bin/pest`
Expected: PASS across all suites. Pre-existing PasswordResetTest failures are known-unrelated (auth notification asserts).

- [ ] **Step 2: Push branch**

```bash
git push -u origin feat/comments-ai-phase-b
```

- [ ] **Step 3: Open PR**

```bash
gh pr create --base main --head feat/comments-ai-phase-b --title "feat(ai): Comments AI Phase B — ingestion + sending" --body "$(cat <<'EOF'
## Summary

Turns Phase A's dormant Comments config into a live system: ingest Meta feed/comment webhooks, filter+dedupe+rate-limit, post AI reply publicly + optional one-shot DM. Ships live for existing customers (managed OAuth places Pages inside our verified Business Portfolio, so Standard Access is sufficient).

- Spec: docs/superpowers/specs/2026-08-31-comments-ai-phase-b-design.md
- Plan: docs/superpowers/plans/2026-08-31-comments-ai-phase-b.md
- App Review draft (submitted separately): docs/meta-app-review/comments-permissions-submission.md

## Test plan

- [x] Full comments test suite green locally (~24 new tests)
- [ ] After merge: verify migrations ran (`php artisan migrate:status | grep -E "pages_posts|comments"`)
- [ ] After merge: enable Comments on one internal test Page, post a test comment, observe AI reply within 30s
- [ ] After merge: check `queue:work` picks up the two new queues (`comments-ingest`, `comments-send`) or that the existing worker consumes them

## Load-bearing pins respected

- Managed OAuth flow untouched (only scope string extended)
- Team::canDispatchAi() gates every AI dispatch in SendAiCommentReplyJob
- Providers still return empty string on failure (no fallback text ever posted publicly)
- AiProviderInterface used (coalesceRoles runs)
EOF
)"
```

- [ ] **Step 4: Merge (with explicit user OK)**

Wait for user "deploy" approval. Then:
```bash
gh pr merge <PR_NUMBER> --squash --subject "feat(ai): Comments AI Phase B — ingestion + sending" --body "See PR body for details."
```

- [ ] **Step 5: Verify auto-deploy**

```bash
gh run watch $(gh run list --branch main --limit 1 --json databaseId --jq '.[0].databaseId') --exit-status
```

- [ ] **Step 6: Verify prod state**

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && echo "===HEAD===" && git log --oneline -1 && echo "===MIGRATIONS===" && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan migrate:status | grep -E "pages_posts|comments" && echo "===HTTP===" && curl -sS -o /dev/null -w "settings/ai/config HTTP %{http_code}\n" -L https://ot1-pro.com/settings/ai/config'
```

Expected: HEAD is the merge SHA, both migrations show `Ran`, HTTP 200.

- [ ] **Step 7: Ensure queues are draining the new queue names**

The systemd `one-inbox-queue` service was configured for the default queue. Check whether the `comments-ingest` and `comments-send` queues have jobs pending after enabling on a test page:

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan queue:monitor comments-ingest,comments-send --max=100'
```

If the existing worker doesn't cover the new queues, add them to the systemd unit's `queue:work --queue=default,comments-ingest,comments-send` args and `systemctl reload one-inbox-queue`. Confirm with the user before editing systemd — this is a live-service change.

- [ ] **Step 8: Append to journal**

Append a section to `tasks/journal.md` (see Phase A entry for shape) with: merge SHA, deploy verification results, App Review submission status, rollback command.

---

## Self-Review

**Spec coverage:**
- §2 OAuth scope extension → Task 1
- §3 Webhook subscription → Task 7 (Step 2 extends subscribe_fields)
- §4 Storage tables → Task 2
- §5 Redis dedupe + rate-limit → embedded in Task 4 (CommentFilterService + IngestCommentJob)
- §6 Two-stage job pipeline → Tasks 4 (Ingest), 5 (Classify), 6 (Send)
- §7 Classifier prompt → Task 5
- §8 Post creation-time fetch → Task 4 (PostCreationTimeCache)
- §9 Graph API endpoints → Tasks 6 (reply + DM), 4 (post created_time)
- §11 Load-bearing pins → enforced in Tasks 6 (canDispatchAi), 5 (empty-string handling)
- §12 App Review draft → Task 8
- §13 Perf budget → satisfied structurally
- §14 Rollout → Task 9

**Placeholder scan:** No TBDs, no "similar to Task N", no "add error handling". Step 2 of Task 7 says "concrete diff depends on exact current implementation" — this is honest, since the subscribe method's exact shape isn't in the plan's context; the executor should grep first, then apply the same pattern.

**Type / name consistency:** Constant names, method signatures, JSON keys all match across tasks (`platform_comment_id`, `comment_settings.enabled_at`, `decision_reason`, `graph_reply_id`, `dm_graph_message_id`, `dm_sent_at` — spelled identically in migration, model, factory, jobs, and tests).

**Judgment calls flagged:**
- Task 4 stubs `ClassifyCommentJob` and `SendAiCommentReplyJob` so IngestCommentJob tests can pass before their real implementations land in Tasks 5-6. The stubs are class-loadable no-ops; `Queue::fake()` covers dispatch verification.
- Task 7's subscribe-fields edit is intentionally under-specified because I haven't confirmed the current subscribe method shape. Executor should read + adapt.
