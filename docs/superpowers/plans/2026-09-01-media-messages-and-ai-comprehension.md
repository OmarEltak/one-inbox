# Media Messages & AI Comprehension Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Render images and voice notes inline in `/inbox` for WhatsApp (then Meta/IG/Telegram/Email), let agents send them from the composer, and let the AI understand them via NaraRouter vision + Groq/whisper.cpp transcription.

**Architecture:** Three isolated subsystems — `MediaStorage` (pure persistence), platform adapters (`WhatsappPlatform`, `FacebookPlatform`, etc. — extended with media methods), and AI comprehension (`VisionRouter`, `TranscriptionRouter`). Transcription runs on an isolated `transcription` queue with a dedicated single-worker systemd service so CPU-bound whisper.cpp jobs cannot starve the 4 existing `default`/`urgent` workers. Groq Whisper is tried first (1-2s latency, free tier); whisper.cpp `medium` (~40s, self-hosted) is the fallback. Vision iterates the NaraRouter fallback chain, skipping text-only models.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI, Alpine.js, Pest (tests), MySQL 8, Redis, systemd, whisper.cpp (C++), ffmpeg, Groq API.

**Spec:** [`docs/superpowers/specs/2026-09-01-media-messages-and-ai-comprehension-design.md`](../specs/2026-09-01-media-messages-and-ai-comprehension-design.md)

**Branch:** `feat/media-messages-and-ai-comprehension` (already created)

---

## Non-obvious codebase facts for the implementing engineer

Read these before touching code — they will save real hours.

1. **Existing `messages` columns:** `media_url` (string), `media_type` (string), `content_type` (string). These already exist and are currently unpopulated for inbound audio/images (ingest stores `[audio]` in `content`). This plan populates them properly.

2. **Queue naming:** Inbound jobs (`ProcessIncomingMessage`) already route to a queue named `urgent` (not `default`) via `$this->onQueue('urgent')`. The 4 `one-inbox-queue@{1..4}.service` systemd workers listen to `--queue=urgent,default`. **New `transcription` queue is separate** — do NOT add it to the existing workers' queue list.

3. **Message content stays authoritative:** Inbound audio message row starts with `content='[voice note]'`. After transcription, `content` is updated to the transcribed text. AI reads `content`. The audio is still playable via `media_url`.

4. **NaraRouter role-alternation invariant (ARCHITECTURE §4):** `NaraRouterProvider::coalesceRoles()` MUST run before any `callChat`. Vision calls added by this plan MUST NOT bypass it.

5. **`Team::canDispatchAi()` is the single AI dispatch gate (ARCHITECTURE §11).** New AI dispatch sites (DescribeImage, transcription-triggered SendAiResponse) MUST check it. Do not scatter conditions.

6. **Providers return `''` on non-quota failure (ARCHITECTURE §12).** Do NOT introduce apology fallback strings. If transcription fails, the message body stays `[voice note — transcription unavailable]` and no AI reply is generated for it.

7. **`config:cache` on prod MUST run as `deploy` user, never root** (memory `feedback_prod_config_cache_user.md`). Any deploy step in this plan follows this rule.

8. **WhatsApp has two parallel send paths** (memory `project_whatsapp_parallel_send_paths.md`): `SendPlatformMessage::sendViaWhatsApp` and `SendAiResponse::sendViaWhatsApp`. Media-send changes MUST land in both.

9. **Test framework is Pest, not PHPUnit.** New tests use `it('...', function () { ... })` and `expect(...)->toBe(...)`. Existing tests in `tests/Feature/` and `tests/Unit/` are the pattern to follow.

10. **`declare(strict_types=1);` at the top of every new PHP file.** Enforced by user's global PHP rules.

---

## File map (what gets created / modified)

**New files:**
```
app/Models/MediaAsset.php
app/Services/Media/MediaStorage.php
app/Services/Ai/VisionRouter.php
app/Services/Ai/TranscriptionRouter.php
app/Services/Ai/Transcription/TranscriptionDriver.php          (interface)
app/Services/Ai/Transcription/GroqDriver.php
app/Services/Ai/Transcription/WhisperCppDriver.php
app/Services/Ai/Transcription/CircuitBreaker.php
app/Jobs/DescribeImage.php
app/Jobs/TranscribeAudio.php
app/Jobs/ConvertAudioToOgg.php
app/Http/Controllers/MediaController.php
app/Http/Controllers/Api/MediaUploadController.php
database/migrations/2026_09_01_150000_create_media_assets_table.php
database/migrations/2026_09_01_150100_add_media_asset_id_to_messages_table.php
database/migrations/2026_09_01_150200_add_audio_transcription_enabled_to_teams_table.php
database/factories/MediaAssetFactory.php
resources/views/components/inbox/media-bubble.blade.php
resources/views/components/inbox/lightbox.blade.php
resources/views/components/inbox/voice-recorder.blade.php
tests/Unit/Services/Media/MediaStorageTest.php
tests/Unit/Services/Ai/VisionRouterTest.php
tests/Unit/Services/Ai/TranscriptionRouterTest.php
tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php
tests/Unit/Services/Ai/Transcription/GroqDriverTest.php
tests/Feature/Media/MediaUploadTest.php
tests/Feature/Media/MediaStreamTest.php
tests/Feature/Jobs/TranscribeAudioTest.php
tests/Feature/Jobs/DescribeImageTest.php
tests/fixtures/audio/arabic-10s.ogg
tests/fixtures/audio/english-10s.ogg
tests/fixtures/images/receipt.jpg
scripts/vps/install-whisper-medium.sh
scripts/vps/one-inbox-whisper.service
```

**Modified files:**
```
config/services.php                                     (add media/groq/whisper config)
config/queue.php                                        (register transcription queue)
config/filesystems.php                                  (register 'media' disk)
.env.example                                            (add new env vars)
app/Services/Platforms/WhatsAppPlatform.php             (+ downloadInboundMedia, uploadOutboundMedia, sendMediaMessage)
app/Services/Platforms/FacebookPlatform.php             (+ media methods)
app/Services/Platforms/TelegramPlatform.php             (+ media methods)
app/Services/Platforms/EmailPlatform.php                (+ media methods)
app/Jobs/ProcessIncomingMessage.php                     (persist media, dispatch DescribeImage/TranscribeAudio)
app/Jobs/SendPlatformMessage.php                        (handle outbound media)
app/Jobs/SendAiResponse.php                             (inject image description into prompt)
app/Models/Message.php                                  (add mediaAsset() relation)
app/Models/Team.php                                     (add audioTranscriptionEnabled scope)
app/Livewire/Inbox/Index.php                            (send-with-media action)
resources/views/livewire/inbox/index.blade.php          (render <x-inbox.media-bubble> instead of [audio]/[image])
resources/views/settings/team.blade.php                 (audio transcription toggle)
routes/web.php                                          (GET /media/{ulid})
routes/api.php                                          (POST /api/media/upload)
docs/ARCHITECTURE.md                                    (add §16 Media Pipeline)
resources/views/legal/privacy.blade.php                 (Groq disclosure)
```

---

## Phase 0 — Configuration foundation (do first)

### Task 0: Add feature flags, env vars, and config keys

**Files:**
- Modify: `.env.example`
- Modify: `config/services.php`
- Modify: `config/filesystems.php`
- Modify: `config/queue.php`

- [ ] **Step 1: Add env keys to `.env.example`**

Append to `.env.example`:
```env
# --- MEDIA / AI COMPREHENSION ---
MEDIA_INGEST_ENABLED=true
VISION_ENABLED=true
TRANSCRIPTION_ENABLED=true

# Groq Whisper (primary transcription driver)
TRANSCRIPTION_GROQ_ENABLED=true
GROQ_API_KEY=
GROQ_WHISPER_MODEL=whisper-large-v3
GROQ_TIMEOUT_SECONDS=5

# Local whisper.cpp fallback
WHISPER_CPP_BIN=/usr/local/bin/whisper.cpp
WHISPER_CPP_MODEL=/opt/whisper-models/ggml-medium.bin
WHISPER_CPP_THREADS=2
# --- End MEDIA ---
```

- [ ] **Step 2: Register `media` section in `config/services.php`**

Append inside the returned array:
```php
    'media' => [
        'ingest_enabled'         => env('MEDIA_INGEST_ENABLED', true),
        'signed_url_ttl_days'    => 7,
        'max_upload_image_bytes' => 5 * 1024 * 1024,
        'max_upload_audio_bytes' => 16 * 1024 * 1024,
    ],

    'ai_media' => [
        'vision_enabled'        => env('VISION_ENABLED', true),
        'transcription_enabled' => env('TRANSCRIPTION_ENABLED', true),
    ],

    'groq' => [
        'enabled' => env('TRANSCRIPTION_GROQ_ENABLED', true),
        'api_key' => env('GROQ_API_KEY'),
        'model'   => env('GROQ_WHISPER_MODEL', 'whisper-large-v3'),
        'timeout' => (int) env('GROQ_TIMEOUT_SECONDS', 5),
    ],

    'whisper_cpp' => [
        'bin'     => env('WHISPER_CPP_BIN', '/usr/local/bin/whisper.cpp'),
        'model'   => env('WHISPER_CPP_MODEL', '/opt/whisper-models/ggml-medium.bin'),
        'threads' => (int) env('WHISPER_CPP_THREADS', 2),
    ],
```

- [ ] **Step 3: Register the `media` disk in `config/filesystems.php`**

Add inside the `disks` array:
```php
        'media' => [
            'driver'     => 'local',
            'root'       => storage_path('app/media'),
            'url'        => env('APP_URL') . '/media',
            'visibility' => 'private',
            'throw'      => true,
        ],
```

- [ ] **Step 4: Register the `transcription` queue in `config/queue.php`**

No structural change needed if using Redis driver — queues are dynamic in Redis. Just document it. Add this comment at the top of the file:
```php
// Named queues in use:
//   urgent       — inbound message processing (existing)
//   default      — outbound sends, AI replies, image description, ffmpeg conversion (existing)
//   transcription — whisper.cpp jobs ONLY (isolated single-worker service)
```

- [ ] **Step 5: Commit**

```bash
git add .env.example config/services.php config/filesystems.php config/queue.php
git commit -m "feat(media): config + env vars for media pipeline"
```

---

## Phase 1 — Media Storage foundation

### Task 1: Migration for `media_assets` table

**Files:**
- Create: `database/migrations/2026_09_01_150000_create_media_assets_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32)->default('media');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 128);
            $table->unsignedInteger('size_bytes');
            $table->enum('kind', ['image', 'audio', 'video', 'document']);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->char('checksum_sha256', 64);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'checksum_sha256']);
            $table->index(['team_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assets');
    }
};
```

- [ ] **Step 2: Run the migration locally**

```bash
php artisan migrate
```
Expected: `Migrated: 2026_09_01_150000_create_media_assets_table (Xms)`

- [ ] **Step 3: Verify schema**

```bash
php artisan tinker --execute="dump(\Schema::getColumnListing('media_assets'));"
```
Expected: array containing `id, team_id, disk, path, original_filename, mime_type, size_bytes, kind, duration_seconds, checksum_sha256, metadata, created_at, updated_at`

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_09_01_150000_create_media_assets_table.php
git commit -m "feat(media): media_assets table (per-team dedup by checksum)"
```

---

### Task 2: `MediaAsset` model + factory

**Files:**
- Create: `app/Models/MediaAsset.php`
- Create: `database/factories/MediaAssetFactory.php`
- Test: `tests/Unit/Models/MediaAssetTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/Models/MediaAssetTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;

it('generates a ULID as primary key', function () {
    $team = Team::factory()->create();
    $asset = MediaAsset::factory()->for($team)->create();

    expect($asset->id)->toBeString()->toHaveLength(26);
});

it('scopes checksum uniqueness per team', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    MediaAsset::factory()->for($teamA)->create(['checksum_sha256' => str_repeat('a', 64)]);

    // Same checksum on different team must succeed.
    $ok = MediaAsset::factory()->for($teamB)->create(['checksum_sha256' => str_repeat('a', 64)]);
    expect($ok->exists)->toBeTrue();

    // Same checksum on same team must fail.
    expect(fn () => MediaAsset::factory()->for($teamA)->create(['checksum_sha256' => str_repeat('a', 64)]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('casts metadata to array', function () {
    $asset = MediaAsset::factory()->create(['metadata' => ['w' => 100, 'h' => 200]]);

    expect($asset->metadata)->toBe(['w' => 100, 'h' => 200]);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Models/MediaAssetTest.php
```
Expected: FAIL (`Class "App\Models\MediaAsset" not found`)

- [ ] **Step 3: Write the model**

`app/Models/MediaAsset.php`:
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'team_id',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'kind',
        'duration_seconds',
        'checksum_sha256',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes'       => 'integer',
            'duration_seconds' => 'integer',
            'metadata'         => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

- [ ] **Step 4: Write the factory**

`database/factories/MediaAssetFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        return [
            'team_id'           => Team::factory(),
            'disk'              => 'media',
            'path'              => 'test/'.$this->faker->uuid().'.jpg',
            'original_filename' => 'photo.jpg',
            'mime_type'         => 'image/jpeg',
            'size_bytes'        => 12345,
            'kind'              => 'image',
            'duration_seconds'  => null,
            'checksum_sha256'   => hash('sha256', $this->faker->uuid()),
            'metadata'          => [],
        ];
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Models/MediaAssetTest.php
```
Expected: PASS (3 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Models/MediaAsset.php database/factories/MediaAssetFactory.php tests/Unit/Models/MediaAssetTest.php
git commit -m "feat(media): MediaAsset model + factory with per-team checksum uniqueness"
```

---

### Task 3: Migration for `messages.media_asset_id`

**Files:**
- Create: `database/migrations/2026_09_01_150100_add_media_asset_id_to_messages_table.php`
- Modify: `app/Models/Message.php`

- [ ] **Step 1: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->ulid('media_asset_id')->nullable()->after('media_type');
            $table->foreign('media_asset_id')->references('id')->on('media_assets')->nullOnDelete();
            $table->index('media_asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['media_asset_id']);
            $table->dropIndex(['media_asset_id']);
            $table->dropColumn('media_asset_id');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```
Expected: PASS

- [ ] **Step 3: Update `Message` model — add fillable + relation**

In `app/Models/Message.php`, add `'media_asset_id'` to the `$fillable` array (between `'media_type'` and `'reply_to_message_id'`), and add this method:

```php
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }
```

Add `use App\Models\MediaAsset;` if not already imported (it will be — `Message` is in same namespace).

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_09_01_150100_add_media_asset_id_to_messages_table.php app/Models/Message.php
git commit -m "feat(media): link messages to media_assets via nullable FK"
```

---

### Task 4: `MediaStorage` service (store, dedup, streamUrl)

**Files:**
- Create: `app/Services/Media/MediaStorage.php`
- Test: `tests/Unit/Services/Media/MediaStorageTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Media/MediaStorageTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->storage = app(MediaStorage::class);
    $this->team = Team::factory()->create();
});

it('stores a binary blob and returns a MediaAsset', function () {
    $bytes = random_bytes(256);

    $asset = $this->storage->storeBytes(
        team: $this->team,
        bytes: $bytes,
        mimeType: 'image/jpeg',
        kind: 'image',
        originalFilename: 'photo.jpg',
    );

    expect($asset)->toBeInstanceOf(MediaAsset::class)
        ->and($asset->team_id)->toBe($this->team->id)
        ->and($asset->mime_type)->toBe('image/jpeg')
        ->and($asset->kind)->toBe('image')
        ->and($asset->size_bytes)->toBe(256)
        ->and($asset->checksum_sha256)->toBe(hash('sha256', $bytes));

    Storage::disk('media')->assertExists($asset->path);
});

it('returns the existing asset when the same checksum is stored again (dedup)', function () {
    $bytes = random_bytes(128);

    $first  = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');
    $second = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');

    expect($second->id)->toBe($first->id);
    expect(MediaAsset::where('team_id', $this->team->id)->count())->toBe(1);
});

it('allows the same checksum for a different team', function () {
    $bytes = random_bytes(128);
    $otherTeam = Team::factory()->create();

    $a = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');
    $b = $this->storage->storeBytes($otherTeam, $bytes, 'image/png', 'image');

    expect($a->id)->not->toBe($b->id);
});

it('places files in team-scoped, date-partitioned paths', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    expect($asset->path)->toStartWith("{$this->team->id}/".now()->format('Y/m').'/');
});

it('generates a signed URL that expires', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    $url = $this->storage->streamUrl($asset);

    expect($url)->toContain('/media/'.$asset->id)
        ->and($url)->toContain('signature=');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Unit/Services/Media/MediaStorageTest.php
```
Expected: FAIL (`Class "App\Services\Media\MediaStorage" not found`)

- [ ] **Step 3: Write the service**

`app/Services/Media/MediaStorage.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Models\MediaAsset;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class MediaStorage
{
    private const DEFAULT_DISK = 'media';

    public function storeBytes(
        Team $team,
        string $bytes,
        string $mimeType,
        string $kind,
        ?string $originalFilename = null,
        ?int $durationSeconds = null,
        array $metadata = [],
    ): MediaAsset {
        $checksum = hash('sha256', $bytes);

        // Dedup within team.
        $existing = MediaAsset::where('team_id', $team->id)
            ->where('checksum_sha256', $checksum)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $extension = $this->extensionFor($mimeType, $originalFilename);
        $ulid = (string) Str::ulid();
        $path = sprintf(
            '%d/%s/%s.%s',
            $team->id,
            now()->format('Y/m'),
            $ulid,
            $extension,
        );

        Storage::disk(self::DEFAULT_DISK)->put($path, $bytes);

        return MediaAsset::create([
            'id'                => $ulid,
            'team_id'           => $team->id,
            'disk'              => self::DEFAULT_DISK,
            'path'              => $path,
            'original_filename' => $originalFilename,
            'mime_type'         => $mimeType,
            'size_bytes'        => strlen($bytes),
            'kind'              => $kind,
            'duration_seconds'  => $durationSeconds,
            'checksum_sha256'   => $checksum,
            'metadata'          => $metadata,
        ]);
    }

    public function streamUrl(MediaAsset $asset): string
    {
        $ttl = (int) config('services.media.signed_url_ttl_days', 7);

        return URL::temporarySignedRoute(
            'media.stream',
            now()->addDays($ttl),
            ['ulid' => $asset->id],
        );
    }

    public function readBytes(MediaAsset $asset): string
    {
        return Storage::disk($asset->disk)->get($asset->path)
            ?? throw new \RuntimeException("Media asset file missing on disk: {$asset->id}");
    }

    public function absolutePath(MediaAsset $asset): string
    {
        return Storage::disk($asset->disk)->path($asset->path);
    }

    private function extensionFor(string $mimeType, ?string $originalFilename): string
    {
        if ($originalFilename !== null) {
            $ext = pathinfo($originalFilename, PATHINFO_EXTENSION);
            if ($ext !== '') {
                return strtolower($ext);
            }
        }

        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/gif'               => 'gif',
            'image/webp'              => 'webp',
            'audio/ogg'               => 'ogg',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/wav', 'audio/wave' => 'wav',
            'audio/webm'              => 'webm',
            'audio/mp4', 'audio/m4a'  => 'm4a',
            'video/mp4'               => 'mp4',
            'application/pdf'         => 'pdf',
            default                   => 'bin',
        };
    }
}
```

- [ ] **Step 4: Add the placeholder named route (Task 5 replaces the handler)**

In `routes/web.php`, add:
```php
Route::get('/media/{ulid}', [\App\Http\Controllers\MediaController::class, 'stream'])
    ->name('media.stream')
    ->middleware('signed');
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Unit/Services/Media/MediaStorageTest.php
```
Expected: PASS (5 tests). One test (`streamUrl`) will fail with route-not-registered if you skip Step 4.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Media/MediaStorage.php tests/Unit/Services/Media/MediaStorageTest.php routes/web.php
git commit -m "feat(media): MediaStorage service with per-team dedup + signed URLs"
```

---

### Task 5: `MediaController` for signed URL streaming

**Files:**
- Create: `app/Http/Controllers/MediaController.php`
- Test: `tests/Feature/Media/MediaStreamTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Media/MediaStreamTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->storage = app(MediaStorage::class);
});

it('streams media bytes with correct content-type when signed URL is valid', function () {
    $bytes = random_bytes(64);
    $asset = $this->storage->storeBytes($this->team, $bytes, 'image/png', 'image');

    $url = $this->storage->streamUrl($asset);

    $response = $this->get($url);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('image/png');
    expect($response->getContent())->toBe($bytes);
});

it('returns 403 when the signature is missing', function () {
    $asset = $this->storage->storeBytes($this->team, random_bytes(10), 'image/png', 'image');

    $this->get("/media/{$asset->id}")->assertForbidden();
});

it('returns 404 when the asset does not exist', function () {
    // Create a valid-looking signed URL for a non-existent ULID.
    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
        'media.stream',
        now()->addDay(),
        ['ulid' => (string) \Illuminate\Support\Str::ulid()],
    );

    $this->get($url)->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Feature/Media/MediaStreamTest.php
```
Expected: FAIL (`Class "App\Http\Controllers\MediaController" not found`)

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/MediaController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function stream(Request $request, string $ulid): StreamedResponse
    {
        $asset = MediaAsset::find($ulid);
        abort_if($asset === null, 404);

        $absolute = $this->storage->absolutePath($asset);
        abort_unless(is_file($absolute), 404);

        return response()->stream(
            callback: function () use ($absolute) {
                $fh = fopen($absolute, 'rb');
                while (! feof($fh)) {
                    echo fread($fh, 8192);
                }
                fclose($fh);
            },
            status: 200,
            headers: [
                'Content-Type'   => $asset->mime_type,
                'Content-Length' => (string) $asset->size_bytes,
                'Cache-Control'  => 'private, max-age=604800', // 7 days, matches signed URL TTL
            ],
        );
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Feature/Media/MediaStreamTest.php
```
Expected: PASS (3 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/MediaController.php tests/Feature/Media/MediaStreamTest.php
git commit -m "feat(media): MediaController streams signed URLs with correct mime type"
```

---

## Phase 2 — WhatsApp inbound media rendering

### Task 6: `WhatsappPlatform::downloadInboundMedia`

**Files:**
- Modify: `app/Services/Platforms/WhatsAppPlatform.php`
- Test: `tests/Unit/Services/Platforms/WhatsAppMediaDownloadTest.php`

**WhatsApp Cloud API media flow:** webhook delivers `messages[].image.id` (or `audio.id`, `video.id`, `document.id`). Two-step fetch: `GET /{media-id}` returns `{ url, mime_type, sha256, file_size }`, then `GET {url}` (with same bearer token) returns raw bytes.

- [ ] **Step 1: Write the failing test**

`tests/Unit/Services/Platforms/WhatsAppMediaDownloadTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Page;
use App\Models\Team;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->account = ConnectedAccount::factory()->for($this->team)->create([
        'platform'     => 'whatsapp',
        'access_token' => 'test-token',
    ]);
    $this->page = Page::factory()->for($this->team)->create([
        'platform'                 => 'whatsapp',
        'connected_account_id'     => $this->account->id,
    ]);
});

it('downloads inbound media using two-step WA Cloud API pattern', function () {
    Http::fake([
        'graph.facebook.com/*/MEDIA_ID' => Http::response([
            'url'       => 'https://cdn.example/blob',
            'mime_type' => 'audio/ogg',
            'sha256'    => hash('sha256', 'audiobytes'),
            'file_size' => 10,
            'id'        => 'MEDIA_ID',
        ]),
        'cdn.example/blob' => Http::response('audiobytes', 200, ['Content-Type' => 'audio/ogg']),
    ]);

    $platform = app(WhatsAppPlatform::class);
    $asset = $platform->downloadInboundMedia(
        page: $this->page,
        mediaId: 'MEDIA_ID',
        kind: 'audio',
    );

    expect($asset->mime_type)->toBe('audio/ogg')
        ->and($asset->kind)->toBe('audio')
        ->and($asset->size_bytes)->toBe(10);

    Http::assertSent(fn (Request $r) =>
        str_contains($r->url(), '/MEDIA_ID') && $r->hasHeader('Authorization', 'Bearer test-token')
    );
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Services/Platforms/WhatsAppMediaDownloadTest.php
```
Expected: FAIL (method `downloadInboundMedia` does not exist)

- [ ] **Step 3: Add the method to `WhatsAppPlatform`**

At the bottom of `WhatsAppPlatform` class body (before the closing `}`), add:

```php
    public function downloadInboundMedia(Page $page, string $mediaId, string $kind): \App\Models\MediaAsset
    {
        $token = $page->connectedAccount?->access_token
            ?? throw new \RuntimeException("Page {$page->id} has no WhatsApp connected account");

        // Step 1: resolve media metadata + short-lived CDN URL.
        $meta = Http::withToken($token)
            ->timeout(10)
            ->retry(3, 500)
            ->get("{$this->graphUrl}/{$mediaId}")
            ->throw()
            ->json();

        // Step 2: download bytes from the CDN URL (still requires bearer token).
        $bytes = Http::withToken($token)
            ->timeout(30)
            ->retry(3, 500)
            ->get($meta['url'])
            ->throw()
            ->body();

        /** @var \App\Services\Media\MediaStorage $storage */
        $storage = app(\App\Services\Media\MediaStorage::class);

        return $storage->storeBytes(
            team: $page->team,
            bytes: $bytes,
            mimeType: $meta['mime_type'] ?? 'application/octet-stream',
            kind: $kind,
        );
    }
```

Add `use App\Models\MediaAsset;` and `use App\Models\Page;` to imports if not already present.

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Services/Platforms/WhatsAppMediaDownloadTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Platforms/WhatsAppPlatform.php tests/Unit/Services/Platforms/WhatsAppMediaDownloadTest.php
git commit -m "feat(whatsapp): downloadInboundMedia via two-step Cloud API pattern"
```

---

### Task 7: Wire `ProcessIncomingMessage` to persist media (WhatsApp only for now)

**Files:**
- Modify: `app/Jobs/ProcessIncomingMessage.php`

**Context:** the WA branch is `processWhatsApp()` in that file. Find where it currently creates the `Message` row for image/audio/video/document types and replaces the placeholder body with actual media persistence. Look for the string `[audio]` or `[image]` in the file to find the exact insertion points.

- [ ] **Step 1: Locate the WA message-type handling**

```bash
grep -n "processWhatsApp\|\[audio\]\|\[image\]\|'image'\|'audio'" app/Jobs/ProcessIncomingMessage.php
```
Expected: shows line numbers where WA message-type handling lives.

- [ ] **Step 2: Add media ingest to WA message handler**

Wherever the WA handler currently determines the body (e.g. `$body = '[audio]'` or similar), replace with:

```php
$mediaAssetId = null;
$mediaUrl     = null;
$mediaType    = null;

$mediaId = $waMessage['image']['id']
    ?? $waMessage['audio']['id']
    ?? $waMessage['video']['id']
    ?? $waMessage['document']['id']
    ?? null;

$mediaKind = match (true) {
    isset($waMessage['image'])    => 'image',
    isset($waMessage['audio'])    => 'audio',
    isset($waMessage['video'])    => 'video',
    isset($waMessage['document']) => 'document',
    default                       => null,
};

if ($mediaId !== null && $mediaKind !== null && config('services.media.ingest_enabled')) {
    try {
        $platform = app(\App\Services\Platforms\WhatsAppPlatform::class);
        $asset = $platform->downloadInboundMedia($page, $mediaId, $mediaKind);

        $mediaAssetId = $asset->id;
        $mediaType    = $asset->mime_type;
        $mediaUrl     = app(\App\Services\Media\MediaStorage::class)->streamUrl($asset);

        $body = match ($mediaKind) {
            'image'    => $waMessage['image']['caption']    ?? '[image]',
            'audio'    => '[voice note]',
            'video'    => $waMessage['video']['caption']    ?? '[video]',
            'document' => $waMessage['document']['filename'] ?? '[document]',
        };
    } catch (\Throwable $e) {
        \Log::error('WA media download failed', [
            'media_id' => $mediaId,
            'kind'     => $mediaKind,
            'error'    => $e->getMessage(),
        ]);
        $body = '[media unavailable]';
    }
}

// Then continue with the existing Message::create pattern, adding:
//     'media_asset_id' => $mediaAssetId,
//     'media_url'      => $mediaUrl,
//     'media_type'     => $mediaType,
```

- [ ] **Step 3: After Message::create, dispatch AI comprehension jobs (guard on feature flags)**

After the `Message::create()` call for the incoming WA message:
```php
if ($message->media_asset_id !== null && $conversation->team->canDispatchAi()) {
    if ($mediaKind === 'image' && config('services.ai_media.vision_enabled')) {
        \App\Jobs\DescribeImage::dispatch($message->id);
    } elseif ($mediaKind === 'audio' && config('services.ai_media.transcription_enabled')
        && $conversation->team->audio_transcription_enabled) {
        \App\Jobs\TranscribeAudio::dispatch($message->id);
    } else {
        // Text / non-audio-non-image: fall through to existing SendAiResponse dispatch.
    }
}
```

**IMPORTANT:** the existing `SendAiResponse::dispatch()` call in this handler must be **skipped** when `$mediaKind === 'audio'` — transcription will re-dispatch it after transcription completes. For images, `DescribeImage` handles dispatch. Wrap the existing dispatch:
```php
if ($mediaKind === null || ($mediaKind !== 'audio' && $mediaKind !== 'image')) {
    // existing SendAiResponse::dispatch(...) stays here
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/ProcessIncomingMessage.php
git commit -m "feat(whatsapp): persist inbound media + gate AI dispatch on kind"
```

---

### Task 8: Blade component to render media in the inbox

**Files:**
- Create: `resources/views/components/inbox/media-bubble.blade.php`
- Create: `resources/views/components/inbox/lightbox.blade.php`
- Modify: `resources/views/livewire/inbox/index.blade.php`

- [ ] **Step 1: Write `<x-inbox.media-bubble>`**

`resources/views/components/inbox/media-bubble.blade.php`:
```blade
@props(['message'])

@php
    $asset = $message->mediaAsset;
    $kind  = $asset?->kind;
@endphp

@if($kind === 'image')
    <button
        type="button"
        x-data
        @click="$dispatch('inbox-lightbox', { url: @js($message->media_url), alt: @js($asset->original_filename ?? 'Image') })"
        class="block max-w-xs overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 hover:opacity-90 transition"
    >
        <img
            src="{{ $message->media_url }}"
            alt="{{ $asset->original_filename ?? 'Image from customer' }}"
            loading="lazy"
            class="max-w-full h-auto max-h-64 object-cover"
        />
    </button>
    @if($message->content && $message->content !== '[image]')
        <p class="mt-1 text-sm">{{ $message->content }}</p>
    @endif

@elseif($kind === 'audio')
    <div class="max-w-xs">
        <audio controls preload="metadata" class="w-full">
            <source src="{{ $message->media_url }}" type="{{ $asset->mime_type }}">
            Your browser does not support audio playback.
        </audio>
        @if($message->content && ! in_array($message->content, ['[voice note]', '[audio]', '[media unavailable]'], true))
            <p class="mt-1 text-xs italic text-zinc-500 dark:text-zinc-400">
                <span class="font-semibold">Transcript:</span> {{ $message->content }}
            </p>
        @elseif($message->content === '[media unavailable]')
            <p class="mt-1 text-xs italic text-red-500">Media could not be loaded.</p>
        @endif
    </div>

@elseif($kind === 'video')
    <div class="max-w-xs">
        <video controls preload="metadata" class="w-full rounded-lg max-h-64">
            <source src="{{ $message->media_url }}" type="{{ $asset->mime_type }}">
        </video>
    </div>

@elseif($kind === 'document')
    <a href="{{ $message->media_url }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800">
        <flux:icon.document class="size-5" />
        <span class="text-sm">{{ $asset->original_filename ?? 'Document' }}</span>
    </a>

@else
    <p class="text-sm">{{ $message->content }}</p>
@endif
```

- [ ] **Step 2: Write `<x-inbox.lightbox>`**

`resources/views/components/inbox/lightbox.blade.php`:
```blade
<div
    x-data="{ open: false, url: '', alt: '' }"
    @inbox-lightbox.window="open = true; url = $event.detail.url; alt = $event.detail.alt"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4"
    @click.self="open = false"
    style="display: none;"
>
    <button type="button" @click="open = false"
        class="absolute top-4 right-4 text-white/80 hover:text-white text-3xl leading-none">&times;</button>
    <img :src="url" :alt="alt" class="max-h-[90vh] max-w-[90vw] object-contain rounded shadow-2xl" />
</div>
```

- [ ] **Step 3: Wire into the inbox view**

In `resources/views/livewire/inbox/index.blade.php`, find where each message bubble renders its content (search for a `foreach` over messages). Replace the current content-body rendering for the loop item with:

```blade
@if($message->mediaAsset)
    <x-inbox.media-bubble :message="$message" />
@else
    {{-- existing text rendering --}}
    <p>{{ $message->content }}</p>
@endif
```

Also add ONCE (near the top of the file, outside the message loop):
```blade
<x-inbox.lightbox />
```

- [ ] **Step 4: Manual smoke test in browser**

Load `https://one-inbox.test/inbox?pageId=<any-page-with-media>` and confirm images/audio render. Send a WhatsApp image + voice note through Meta to a test conversation and confirm they appear inline.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/inbox/ resources/views/livewire/inbox/index.blade.php
git commit -m "feat(inbox): render inline audio player, images with lightbox, docs"
```

---

## Phase 3 — WhatsApp outbound media (agent-sent)

### Task 9: `POST /api/media/upload` endpoint

**Files:**
- Create: `app/Http/Controllers/Api/MediaUploadController.php`
- Modify: `routes/api.php`
- Test: `tests/Feature/Media/MediaUploadTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Feature/Media/MediaUploadTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->user = User::factory()->for($this->team, 'currentTeam')->create();
    $this->user->teams()->attach($this->team);
    $this->actingAs($this->user);
});

it('accepts image uploads under the size limit', function () {
    $file = UploadedFile::fake()->image('photo.jpg', 100, 100)->size(300);

    $response = $this->postJson('/api/media/upload', [
        'file' => $file,
        'kind' => 'image',
    ]);

    $response->assertOk()->assertJsonStructure(['id', 'mime_type', 'url', 'kind']);
});

it('accepts audio uploads and enqueues webm-to-ogg conversion', function () {
    \Illuminate\Support\Facades\Bus::fake();

    $file = UploadedFile::fake()->create('voice.webm', 500, 'audio/webm');

    $response = $this->postJson('/api/media/upload', [
        'file' => $file,
        'kind' => 'audio',
    ]);

    $response->assertOk();
    \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\ConvertAudioToOgg::class);
});

it('rejects images larger than 5 MB', function () {
    $file = UploadedFile::fake()->image('big.jpg')->size(6 * 1024);

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'image'])
        ->assertStatus(422);
});

it('rejects audio larger than 16 MB', function () {
    $file = UploadedFile::fake()->create('big.webm', 17 * 1024, 'audio/webm');

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'audio'])
        ->assertStatus(422);
});

it('requires authentication', function () {
    auth()->logout();

    $file = UploadedFile::fake()->image('photo.jpg');

    $this->postJson('/api/media/upload', ['file' => $file, 'kind' => 'image'])
        ->assertStatus(401);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Feature/Media/MediaUploadTest.php
```
Expected: FAIL (route + controller do not exist)

- [ ] **Step 3: Write the controller**

`app/Http/Controllers/Api/MediaUploadController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ConvertAudioToOgg;
use App\Services\Media\MediaStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $maxImage = (int) config('services.media.max_upload_image_bytes', 5 * 1024 * 1024);
        $maxAudio = (int) config('services.media.max_upload_audio_bytes', 16 * 1024 * 1024);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.max($maxImage, $maxAudio) / 1024],
            'kind' => ['required', 'in:image,audio,video,document'],
        ]);

        $file = $data['file'];
        $kind = $data['kind'];

        // Per-kind size checks (validation above is the outer cap).
        if ($kind === 'image' && $file->getSize() > $maxImage) {
            return response()->json(['message' => 'Image exceeds 5 MB limit'], 422);
        }
        if ($kind === 'audio' && $file->getSize() > $maxAudio) {
            return response()->json(['message' => 'Audio exceeds 16 MB limit'], 422);
        }

        $team = $request->user()->currentTeam ?? $request->user()->teams()->first();
        abort_if($team === null, 403, 'No team context.');

        $asset = $this->storage->storeBytes(
            team: $team,
            bytes: file_get_contents($file->getRealPath()),
            mimeType: $file->getMimeType() ?? 'application/octet-stream',
            kind: $kind,
            originalFilename: $file->getClientOriginalName(),
        );

        // If audio came in as webm/opus (browser MediaRecorder default), convert to
        // WhatsApp-compatible .ogg out-of-band. The DB row is updated in place.
        if ($kind === 'audio' && in_array($asset->mime_type, ['audio/webm', 'audio/mp4'], true)) {
            ConvertAudioToOgg::dispatch($asset->id);
        }

        return response()->json([
            'id'        => $asset->id,
            'mime_type' => $asset->mime_type,
            'url'       => $this->storage->streamUrl($asset),
            'kind'      => $asset->kind,
        ]);
    }
}
```

- [ ] **Step 4: Register the route**

In `routes/api.php`, add inside the authenticated group:
```php
Route::post('/media/upload', \App\Http\Controllers\Api\MediaUploadController::class)
    ->middleware('auth:sanctum')
    ->name('api.media.upload');
```

If the project uses session auth for internal Livewire calls instead of Sanctum, use `->middleware('auth')` instead. Grep existing api.php routes to see the convention.

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Feature/Media/MediaUploadTest.php
```
Expected: PASS (5 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Api/MediaUploadController.php routes/api.php tests/Feature/Media/MediaUploadTest.php
git commit -m "feat(media): POST /api/media/upload with per-kind size gates"
```

---

### Task 10: `ConvertAudioToOgg` job (ffmpeg webm→ogg)

**Files:**
- Create: `app/Jobs/ConvertAudioToOgg.php`
- Test: `tests/Feature/Jobs/ConvertAudioToOggTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Jobs/ConvertAudioToOggTest.php`:
```php
<?php

declare(strict_types=1);

use App\Jobs\ConvertAudioToOgg;
use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
});

it('runs ffmpeg and updates the asset mime type + path', function () {
    Process::fake([
        '*' => Process::result(exitCode: 0),
    ]);

    // Seed a webm asset.
    $webmBytes = random_bytes(100);
    $asset = app(MediaStorage::class)->storeBytes(
        $this->team, $webmBytes, 'audio/webm', 'audio', 'voice.webm'
    );

    // Simulate ffmpeg producing an output file: write bytes at the expected output path.
    Storage::disk('media')->put(
        preg_replace('/\.webm$/', '.ogg', $asset->path),
        random_bytes(80)
    );

    (new ConvertAudioToOgg($asset->id))->handle();

    $asset->refresh();
    expect($asset->mime_type)->toBe('audio/ogg')
        ->and($asset->path)->toEndWith('.ogg');

    Process::assertRan(fn (\Illuminate\Process\PendingProcess $p) =>
        str_contains(implode(' ', (array) $p->command), 'ffmpeg')
    );
});

it('does nothing if the asset is already ogg', function () {
    Process::fake();

    $ogg = MediaAsset::factory()->for($this->team)->create(['mime_type' => 'audio/ogg', 'path' => 't/a.ogg']);

    (new ConvertAudioToOgg($ogg->id))->handle();

    Process::assertNothingRan();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Feature/Jobs/ConvertAudioToOggTest.php
```
Expected: FAIL (`ConvertAudioToOgg` not found)

- [ ] **Step 3: Write the job**

`app/Jobs/ConvertAudioToOgg.php`:
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MediaAsset;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class ConvertAudioToOgg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(public string $mediaAssetId)
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $asset = MediaAsset::find($this->mediaAssetId);
        if ($asset === null || $asset->mime_type === 'audio/ogg') {
            return;
        }

        $inputPath  = Storage::disk($asset->disk)->path($asset->path);
        $outputPath = preg_replace('/\.[^.]+$/', '.ogg', $inputPath);

        $result = Process::timeout(45)->run([
            'ffmpeg',
            '-y',
            '-i', $inputPath,
            '-c:a', 'libopus',
            '-b:a', '32k',
            '-vbr', 'on',
            $outputPath,
        ]);

        if (! $result->successful()) {
            Log::error('ffmpeg webm→ogg conversion failed', [
                'asset_id'   => $asset->id,
                'stderr'     => $result->errorOutput(),
                'exit_code'  => $result->exitCode(),
            ]);
            $this->fail(new \RuntimeException('ffmpeg failed'));
            return;
        }

        // Update DB row to point at the new ogg path.
        $newRelativePath = preg_replace('/\.[^.]+$/', '.ogg', $asset->path);
        $newBytes = filesize($outputPath);

        $asset->update([
            'mime_type'  => 'audio/ogg',
            'path'       => $newRelativePath,
            'size_bytes' => $newBytes,
        ]);

        // Optionally delete original webm (safe — it's been converted).
        @unlink($inputPath);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Feature/Jobs/ConvertAudioToOggTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/ConvertAudioToOgg.php tests/Feature/Jobs/ConvertAudioToOggTest.php
git commit -m "feat(media): ConvertAudioToOgg job (webm→ogg via ffmpeg)"
```

---

### Task 11: Composer UI — image + voice recorder

**Files:**
- Create: `resources/views/components/inbox/voice-recorder.blade.php`
- Modify: `resources/views/livewire/inbox/index.blade.php` (composer section)
- Modify: `app/Livewire/Inbox/Index.php` (add `sendWithMedia` action)

- [ ] **Step 1: Write the voice-recorder component**

`resources/views/components/inbox/voice-recorder.blade.php`:
```blade
@props(['onUploaded'])

<div x-data="voiceRecorder({ onUploaded: {{ Js::from($onUploaded) }} })" class="inline-flex items-center gap-2">
    <button type="button" x-show="!recording" @mousedown="start" @touchstart.prevent="start"
        class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800">
        <flux:icon.microphone class="size-5" />
    </button>
    <div x-show="recording" class="flex items-center gap-2">
        <span class="size-3 rounded-full bg-red-500 animate-pulse"></span>
        <span x-text="elapsed + 's'" class="text-sm font-mono"></span>
        <button type="button" @click="stop" class="px-3 py-1 rounded bg-red-500 text-white text-sm">Send</button>
        <button type="button" @click="cancel" class="px-3 py-1 rounded bg-zinc-200 dark:bg-zinc-700 text-sm">Cancel</button>
    </div>
</div>

<script>
function voiceRecorder({ onUploaded }) {
    return {
        recording: false,
        elapsed: 0,
        mediaRecorder: null,
        chunks: [],
        interval: null,

        async start() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm;codecs=opus' });
                this.chunks = [];
                this.elapsed = 0;
                this.recording = true;

                this.mediaRecorder.ondataavailable = (e) => this.chunks.push(e.data);
                this.mediaRecorder.onstop = () => this.upload(stream);

                this.mediaRecorder.start();
                this.interval = setInterval(() => this.elapsed++, 1000);
            } catch (e) {
                alert('Microphone access denied.');
            }
        },

        stop() {
            if (this.mediaRecorder && this.recording) {
                this.mediaRecorder.stop();
                clearInterval(this.interval);
                this.recording = false;
            }
        },

        cancel() {
            if (this.mediaRecorder && this.recording) {
                this.mediaRecorder.stream.getTracks().forEach(t => t.stop());
                clearInterval(this.interval);
                this.recording = false;
                this.chunks = [];
            }
        },

        async upload(stream) {
            stream.getTracks().forEach(t => t.stop());

            const blob = new Blob(this.chunks, { type: 'audio/webm' });
            const form = new FormData();
            form.append('file', blob, 'voice.webm');
            form.append('kind', 'audio');

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

            const res = await fetch('/api/media/upload', {
                method: 'POST',
                body: form,
                headers: csrf ? { 'X-CSRF-TOKEN': csrf } : {},
                credentials: 'same-origin',
            });

            if (!res.ok) {
                alert('Voice note upload failed.');
                return;
            }

            const asset = await res.json();
            // Dispatch to Livewire — component reads it in `sendWithMedia`.
            window.Livewire.dispatch(onUploaded, { mediaAssetId: asset.id });
        },
    };
}
</script>
```

- [ ] **Step 2: Add `sendWithMedia` action to Livewire component**

In `app/Livewire/Inbox/Index.php`, add a method:
```php
    public function sendWithMedia(string $mediaAssetId): void
    {
        $asset = \App\Models\MediaAsset::find($mediaAssetId);
        abort_if($asset === null, 404);
        abort_unless($asset->team_id === auth()->user()->currentTeam?->id, 403);

        $conversation = \App\Models\Conversation::find($this->activeConversationId);
        abort_if($conversation === null, 404);

        \App\Jobs\SendPlatformMessage::dispatch(
            conversationId: $conversation->id,
            body: null,
            mediaAssetId: $mediaAssetId,
            userId: auth()->id(),
        );
    }
```

Adjust the SendPlatformMessage constructor call to match whatever signature it currently uses — Task 12 updates that signature.

Also add an image picker button in the composer section of `resources/views/livewire/inbox/index.blade.php` — pattern is identical to the voice recorder but uses `<input type="file" accept="image/*">`:
```blade
<label class="p-2 rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 cursor-pointer">
    <flux:icon.photo class="size-5" />
    <input type="file" accept="image/*" class="hidden"
        x-on:change="uploadImage($event.target.files[0]).then(id => $wire.sendWithMedia(id))" />
</label>
```

And add the `uploadImage(file)` helper in a shared Alpine store — see the voice recorder script for the equivalent fetch pattern.

Place `<x-inbox.voice-recorder :on-uploaded="'sendWithMedia'" />` in the composer.

- [ ] **Step 3: Manual smoke test**

Load the inbox in browser, click microphone, speak 5 seconds, click Send. Verify audio bubble appears in the conversation. Click image picker, choose a JPG. Verify image bubble appears.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/inbox/voice-recorder.blade.php resources/views/livewire/inbox/index.blade.php app/Livewire/Inbox/Index.php
git commit -m "feat(inbox): composer supports hold-to-record voice + image upload"
```

---

### Task 12: `WhatsappPlatform` outbound media methods

**Files:**
- Modify: `app/Services/Platforms/WhatsAppPlatform.php`
- Test: `tests/Unit/Services/Platforms/WhatsAppMediaOutboundTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Platforms/WhatsAppMediaOutboundTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\ConnectedAccount;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\Team;
use App\Services\Platforms\WhatsAppPlatform;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    $this->account = ConnectedAccount::factory()->for($this->team)->create([
        'platform' => 'whatsapp', 'access_token' => 'tok',
    ]);
    $this->page = Page::factory()->for($this->team)->create([
        'platform' => 'whatsapp',
        'platform_page_id' => '1234567890',
        'connected_account_id' => $this->account->id,
    ]);
    // Seed a real file on the fake disk.
    Storage::disk('media')->put('1/2026/09/asset.jpg', 'fakebytes');
    $this->asset = MediaAsset::factory()->for($this->team)->create([
        'path' => '1/2026/09/asset.jpg', 'mime_type' => 'image/jpeg', 'kind' => 'image',
    ]);
});

it('uploads media to WA in one step and returns the WA media id', function () {
    Http::fake([
        'graph.facebook.com/*/media' => Http::response(['id' => 'wa-media-42']),
    ]);

    $platform = app(WhatsAppPlatform::class);
    $waId = $platform->uploadOutboundMedia($this->page, $this->asset);

    expect($waId)->toBe('wa-media-42');
});

it('sends a media message referencing the WA media id', function () {
    Http::fake([
        'graph.facebook.com/*/messages' => Http::response(['messages' => [['id' => 'wamid.abc']]]),
    ]);

    $conversation = Conversation::factory()->for($this->page)->create(['platform_conversation_id' => '20111234567']);

    $platform = app(WhatsAppPlatform::class);
    $messageId = $platform->sendMediaMessage(
        page: $this->page,
        recipientPlatformId: $conversation->platform_conversation_id,
        mediaAsset: $this->asset,
        waMediaId: 'wa-media-42',
        caption: 'here',
    );

    expect($messageId)->toBe('wamid.abc');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Services/Platforms/WhatsAppMediaOutboundTest.php
```
Expected: FAIL

- [ ] **Step 3: Add the methods to `WhatsAppPlatform`**

```php
    public function uploadOutboundMedia(Page $page, \App\Models\MediaAsset $asset): string
    {
        $token = $page->connectedAccount?->access_token
            ?? throw new \RuntimeException("Page {$page->id} has no WhatsApp connected account");

        $absolutePath = app(\App\Services\Media\MediaStorage::class)->absolutePath($asset);

        $response = Http::withToken($token)
            ->timeout(30)
            ->attach('file', file_get_contents($absolutePath), $asset->original_filename ?? basename($asset->path), [
                'Content-Type' => $asset->mime_type,
            ])
            ->post("{$this->graphUrl}/{$page->platform_page_id}/media", [
                'messaging_product' => 'whatsapp',
                'type'              => $asset->mime_type,
            ])
            ->throw()
            ->json();

        return (string) ($response['id'] ?? throw new \RuntimeException('WA upload returned no id'));
    }

    public function sendMediaMessage(
        Page $page,
        string $recipientPlatformId,
        \App\Models\MediaAsset $asset,
        string $waMediaId,
        ?string $caption = null,
    ): string {
        $token = $page->connectedAccount?->access_token
            ?? throw new \RuntimeException("Page {$page->id} has no WhatsApp connected account");

        $typeKey = match ($asset->kind) {
            'image'    => 'image',
            'audio'    => 'audio',
            'video'    => 'video',
            'document' => 'document',
            default    => throw new \InvalidArgumentException("Unsupported media kind: {$asset->kind}"),
        };

        $body = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $recipientPlatformId,
            'type'              => $typeKey,
            $typeKey            => array_filter([
                'id'      => $waMediaId,
                'caption' => in_array($typeKey, ['image', 'video', 'document'], true) ? $caption : null,
            ]),
        ];

        $response = Http::withToken($token)
            ->timeout(30)
            ->post("{$this->graphUrl}/{$page->platform_page_id}/messages", $body)
            ->throw()
            ->json();

        return (string) ($response['messages'][0]['id']
            ?? throw new \RuntimeException('WA send returned no message id'));
    }
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Unit/Services/Platforms/WhatsAppMediaOutboundTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Platforms/WhatsAppPlatform.php tests/Unit/Services/Platforms/WhatsAppMediaOutboundTest.php
git commit -m "feat(whatsapp): uploadOutboundMedia + sendMediaMessage"
```

---

### Task 13: Extend `SendPlatformMessage` to handle media

**Files:**
- Modify: `app/Jobs/SendPlatformMessage.php`

- [ ] **Step 1: Change constructor signature to accept `mediaAssetId`**

Add `?string $mediaAssetId = null` parameter after existing `body` param. Store on public property.

- [ ] **Step 2: Route by media presence at dispatch time**

Inside `handle()`, before calling `sendViaWhatsApp` (or platform-appropriate method), add:
```php
if ($this->mediaAssetId !== null) {
    $asset = \App\Models\MediaAsset::findOrFail($this->mediaAssetId);
    $waMediaId = $platform->uploadOutboundMedia($page, $asset);
    $platformMessageId = $platform->sendMediaMessage(
        $page, $conversation->platform_conversation_id, $asset, $waMediaId, $this->body
    );

    Message::create([
        'conversation_id'     => $conversation->id,
        'platform_message_id' => $platformMessageId,
        'direction'           => 'outbound',
        'sender_type'         => 'user',
        'sender_id'           => $this->userId,
        'content_type'        => $asset->kind,
        'content'             => $this->body ?? match($asset->kind) {
            'image'    => '[image]',
            'audio'    => '[voice note]',
            'video'    => '[video]',
            'document' => '[document]',
        },
        'media_asset_id'      => $asset->id,
        'media_url'           => app(\App\Services\Media\MediaStorage::class)->streamUrl($asset),
        'media_type'          => $asset->mime_type,
        'platform_sent_at'    => now(),
    ]);
    return;
}
```

Apply the same pattern to any other platform branch in this job (Facebook, Instagram, Telegram). For Phase 3 we only need WA — for others, throw `not implemented` and fill in during Phase 7.

- [ ] **Step 3: Manual smoke test — send a voice note from composer**

Load inbox, record 3s voice note, send. Confirm the outbound bubble appears with a working `<audio>` player, and the customer's WhatsApp app receives the voice note.

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/SendPlatformMessage.php
git commit -m "feat(whatsapp): SendPlatformMessage handles outbound media via mediaAssetId"
```

---

## Phase 4 — Vision AI (image comprehension)

### Task 14: `VisionRouter` + NaraRouter capability map

**Files:**
- Create: `app/Services/Ai/VisionRouter.php`
- Test: `tests/Unit/Services/Ai/VisionRouterTest.php`

**Context:** NaraRouter's model chain is in `config/services.php` under `nararouter.fallback_models`. Vision capability is not queryable from the chain — hardcoded map based on `docs/VPS.md` §NaraRouter table. Update the map when the chain changes (this map lives in one place: `VisionRouter::VISION_CAPABLE`).

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Ai/VisionRouterTest.php`:
```php
<?php

declare(strict_types=1);

use App\Services\Ai\VisionRouter;

it('iterates the chain and returns the first vision-capable model', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,agnes-2.5-flash,agnes-2.0-flash']);

    $router = new VisionRouter();
    expect($router->firstVisionCapableModel())->toBe('agnes-2.5-flash');
});

it('returns null when no vision-capable model exists in the chain', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,deepseek-v4-flash']);

    $router = new VisionRouter();
    expect($router->firstVisionCapableModel())->toBeNull();
});

it('produces the multipart payload for vision calls', function () {
    $router = new VisionRouter();
    $payload = $router->buildPayload(
        model: 'agnes-2.5-flash',
        imageUrl: 'https://example/img.jpg',
        prompt: 'Describe',
    );

    expect($payload['model'])->toBe('agnes-2.5-flash')
        ->and($payload['messages'][0]['content'][0]['type'])->toBe('text')
        ->and($payload['messages'][0]['content'][1]['type'])->toBe('image_url')
        ->and($payload['messages'][0]['content'][1]['image_url']['url'])->toBe('https://example/img.jpg');
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Unit/Services/Ai/VisionRouterTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the router**

`app/Services/Ai/VisionRouter.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

class VisionRouter
{
    /**
     * NaraRouter models that accept image_url input. Update when the chain
     * changes — verified against NaraRouter dashboard 2026-09-01.
     * See docs/VPS.md.
     */
    private const VISION_CAPABLE = [
        'agnes-2.5-flash',
        'agnes-2.0-flash',
        'glm-5.3-flash-free',
        'minimax-m3-free',
        'mistral-medium-3-5',
        'stepfun-3.7-flash',
    ];

    public function firstVisionCapableModel(): ?string
    {
        $chainRaw = (string) config('services.nararouter.fallback_models', '');
        $chain    = array_filter(array_map('trim', explode(',', $chainRaw)));

        foreach ($chain as $model) {
            if (in_array($model, self::VISION_CAPABLE, true)) {
                return $model;
            }
        }

        return null;
    }

    public function buildPayload(string $model, string $imageUrl, string $prompt): array
    {
        return [
            'model'    => $model,
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        ['type' => 'text',      'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ],
                ],
            ],
            'max_tokens' => 400,
        ];
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Unit/Services/Ai/VisionRouterTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ai/VisionRouter.php tests/Unit/Services/Ai/VisionRouterTest.php
git commit -m "feat(ai): VisionRouter selects first vision-capable model in NaraRouter chain"
```

---

### Task 15: `DescribeImage` job

**Files:**
- Create: `app/Jobs/DescribeImage.php`
- Test: `tests/Feature/Jobs/DescribeImageTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Jobs/DescribeImageTest.php`:
```php
<?php

declare(strict_types=1);

use App\Jobs\DescribeImage;
use App\Jobs\SendAiResponse;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Bus::fake([SendAiResponse::class]);
    $this->team = Team::factory()->create();
    $this->page = Page::factory()->for($this->team)->create();
    $this->conversation = Conversation::factory()->for($this->page)->create();
    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'image']);
    $this->message = Message::factory()->for($this->conversation)->create([
        'media_asset_id' => $this->asset->id,
        'content'        => '[image]',
    ]);
    config(['services.nararouter.fallback_models' => 'agnes-2.5-flash,mistral-large']);
});

it('caches the AI description on the media_asset and dispatches SendAiResponse', function () {
    Http::fake([
        '*' => Http::response([
            'choices' => [['message' => ['content' => 'A receipt showing a $19.99 total.']]],
        ]),
    ]);

    (new DescribeImage($this->message->id))->handle();

    $this->asset->refresh();
    expect($this->asset->metadata['ai_description'] ?? null)->toBe('A receipt showing a $19.99 total.');

    Bus::assertDispatched(SendAiResponse::class);
});

it('skips vision call and still dispatches SendAiResponse if no vision-capable model available', function () {
    config(['services.nararouter.fallback_models' => 'mistral-large,deepseek-v4-flash']);

    (new DescribeImage($this->message->id))->handle();

    $this->asset->refresh();
    expect($this->asset->metadata['ai_description'] ?? null)->toBeNull();

    Bus::assertDispatched(SendAiResponse::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Feature/Jobs/DescribeImageTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the job**

`app/Jobs/DescribeImage.php`:
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\Ai\VisionRouter;
use App\Services\Media\MediaStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DescribeImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 30;

    private const PROMPT = 'Describe this image for a customer-service AI. Note any product, defect, receipt, screen, text visible, or any signal about what the customer is asking. Keep under 120 words.';

    public function __construct(public int $messageId)
    {
        $this->onQueue('default');
    }

    public function handle(VisionRouter $router, MediaStorage $storage): void
    {
        $message = Message::with(['mediaAsset', 'conversation.page.team'])->find($this->messageId);
        if ($message === null || $message->mediaAsset === null) {
            return;
        }

        $model = $router->firstVisionCapableModel();
        if ($model === null) {
            Log::warning('No vision-capable model in NaraRouter chain', [
                'chain' => config('services.nararouter.fallback_models'),
            ]);
            // Still dispatch SendAiResponse so the AI can respond to any caption text.
            SendAiResponse::dispatch($message->id);
            return;
        }

        $imageUrl = $storage->streamUrl($message->mediaAsset);
        $payload  = $router->buildPayload($model, $imageUrl, self::PROMPT);

        try {
            $response = Http::withToken(config('services.nararouter.api_key'))
                ->timeout(20)
                ->post(config('services.nararouter.base_url').'/chat/completions', $payload)
                ->throw()
                ->json();

            $description = $response['choices'][0]['message']['content'] ?? null;

            if (is_string($description) && $description !== '') {
                $meta = $message->mediaAsset->metadata ?? [];
                $meta['ai_description'] = $description;
                $message->mediaAsset->update(['metadata' => $meta]);
            }
        } catch (\Throwable $e) {
            Log::warning('Vision call failed', ['error' => $e->getMessage(), 'model' => $model]);
        }

        SendAiResponse::dispatch($message->id);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Feature/Jobs/DescribeImageTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/DescribeImage.php tests/Feature/Jobs/DescribeImageTest.php
git commit -m "feat(ai): DescribeImage job caches vision description on media_asset"
```

---

### Task 16: Wire image description into `SendAiResponse` prompt

**Files:**
- Modify: `app/Jobs/SendAiResponse.php`

- [ ] **Step 1: Inject image description into the AI system prompt**

Find where `SendAiResponse` builds its message history / system prompt. Where it currently reads `$message->content`, add:

```php
// If the triggering message had an image with a cached vision description,
// prepend it as extra context so the text-model has something to reason about.
if ($message->mediaAsset && $message->mediaAsset->kind === 'image') {
    $desc = $message->mediaAsset->metadata['ai_description'] ?? null;
    if ($desc !== null) {
        $systemContext .= "\n\nThe customer's most recent message included an image. Vision description of that image: \"{$desc}\"";
    }
}
```

(Adjust variable names to match the file's actual conventions — grep for `system_prompt` or similar.)

- [ ] **Step 2: Manual smoke test**

Send a WhatsApp image to a test AI-enabled conversation. Verify the AI responds referencing the image content (e.g. "I see you've sent a photo of ..." with actual content, not generic).

- [ ] **Step 3: Commit**

```bash
git add app/Jobs/SendAiResponse.php
git commit -m "feat(ai): inject cached vision description into text-model prompt"
```

---

## Phase 5 — Local Whisper (fallback path)

### Task 17: VPS install script for ffmpeg + whisper.cpp

**Files:**
- Create: `scripts/vps/install-whisper-medium.sh`

- [ ] **Step 1: Write the install script**

`scripts/vps/install-whisper-medium.sh`:
```bash
#!/usr/bin/env bash
set -euo pipefail

# Run as root on the prod VPS.
# Installs ffmpeg, builds whisper.cpp, downloads the medium ggml model.

apt-get update
apt-get install -y --no-install-recommends \
    build-essential cmake git ffmpeg curl ca-certificates

# Build whisper.cpp
if [[ ! -d /opt/whisper.cpp ]]; then
    git clone https://github.com/ggerganov/whisper.cpp.git /opt/whisper.cpp
fi
cd /opt/whisper.cpp
git pull --ff-only
cmake -B build
cmake --build build --config Release -j 2
install -m 0755 build/bin/whisper-cli /usr/local/bin/whisper.cpp

# Download the medium model (~1.5 GB)
mkdir -p /opt/whisper-models
if [[ ! -f /opt/whisper-models/ggml-medium.bin ]]; then
    curl -L --fail --retry 5 \
        -o /opt/whisper-models/ggml-medium.bin \
        https://huggingface.co/ggerganov/whisper.cpp/resolve/main/ggml-medium.bin
fi

# Sanity check
/usr/local/bin/whisper.cpp --help >/dev/null
echo "whisper.cpp installed OK. Model: /opt/whisper-models/ggml-medium.bin"
```

- [ ] **Step 2: Make executable**

```bash
chmod +x scripts/vps/install-whisper-medium.sh
```

- [ ] **Step 3: Copy to VPS and run (production step — coordinate with user)**

```bash
scp scripts/vps/install-whisper-medium.sh root@187.77.67.94:/root/
ssh root@187.77.67.94 "bash /root/install-whisper-medium.sh"
```
Expected: `whisper.cpp installed OK. Model: /opt/whisper-models/ggml-medium.bin`

- [ ] **Step 4: Sanity-test on VPS with a fixture clip**

```bash
scp tests/fixtures/audio/english-10s.ogg root@187.77.67.94:/tmp/test.ogg
ssh root@187.77.67.94 "ffmpeg -y -i /tmp/test.ogg -ar 16000 -ac 1 -c:a pcm_s16le /tmp/test.wav && \
    /usr/local/bin/whisper.cpp -m /opt/whisper-models/ggml-medium.bin -f /tmp/test.wav -t 2 -otxt -of /tmp/test"
ssh root@187.77.67.94 "cat /tmp/test.txt"
```
Expected: coherent English transcription.

- [ ] **Step 5: Commit**

```bash
git add scripts/vps/install-whisper-medium.sh
git commit -m "chore(vps): install script for ffmpeg + whisper.cpp medium model"
```

---

### Task 18: `WhisperCppDriver` (local transcription driver)

**Files:**
- Create: `app/Services/Ai/Transcription/TranscriptionDriver.php`
- Create: `app/Services/Ai/Transcription/WhisperCppDriver.php`
- Test: `tests/Unit/Services/Ai/Transcription/WhisperCppDriverTest.php`

- [ ] **Step 1: Write the interface**

`app/Services/Ai/Transcription/TranscriptionDriver.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;

interface TranscriptionDriver
{
    public function name(): string;

    /**
     * @return string|null transcribed text, or null on failure
     * @throws \RuntimeException on rate-limit / circuit-trip-worthy failures
     */
    public function transcribe(MediaAsset $asset): ?string;
}
```

- [ ] **Step 2: Write the failing test**

`tests/Unit/Services/Ai/Transcription/WhisperCppDriverTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Ai\Transcription\WhisperCppDriver;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('media');
    $this->team = Team::factory()->create();
    Storage::disk('media')->put('a/b/c.ogg', 'oggbytes');
    $this->asset = MediaAsset::factory()->for($this->team)->create([
        'path' => 'a/b/c.ogg', 'mime_type' => 'audio/ogg', 'kind' => 'audio',
    ]);
});

it('runs ffmpeg then whisper.cpp and returns the transcript', function () {
    Process::fake([
        '*ffmpeg*'      => Process::result(exitCode: 0),
        '*whisper.cpp*' => Process::result(exitCode: 0),
    ]);

    // Simulate whisper output file created by the fake process.
    $expectedOut = sys_get_temp_dir().'/whisper-'.$this->asset->id.'.txt';
    file_put_contents($expectedOut, "  Hello world.\n");

    $driver = app(WhisperCppDriver::class);
    $text = $driver->transcribe($this->asset);

    expect($text)->toBe('Hello world.');
});

it('returns null on whisper failure', function () {
    Process::fake([
        '*ffmpeg*'      => Process::result(exitCode: 0),
        '*whisper.cpp*' => Process::result(exitCode: 1, errorOutput: 'bad model'),
    ]);

    $driver = app(WhisperCppDriver::class);
    expect($driver->transcribe($this->asset))->toBeNull();
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/WhisperCppDriverTest.php
```
Expected: FAIL

- [ ] **Step 4: Write the driver**

`app/Services/Ai/Transcription/WhisperCppDriver.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WhisperCppDriver implements TranscriptionDriver
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function name(): string
    {
        return 'whisper_cpp';
    }

    public function transcribe(MediaAsset $asset): ?string
    {
        $bin     = (string) config('services.whisper_cpp.bin');
        $model   = (string) config('services.whisper_cpp.model');
        $threads = (int)    config('services.whisper_cpp.threads', 2);

        $inputPath = $this->storage->absolutePath($asset);
        $wavPath   = sys_get_temp_dir().'/whisper-'.$asset->id.'.wav';
        $outStem   = sys_get_temp_dir().'/whisper-'.$asset->id;
        $outTxt    = $outStem.'.txt';

        // whisper.cpp requires 16 kHz mono PCM WAV.
        $ffmpeg = Process::timeout(30)->run([
            'ffmpeg', '-y', '-i', $inputPath, '-ar', '16000', '-ac', '1', '-c:a', 'pcm_s16le', $wavPath,
        ]);
        if (! $ffmpeg->successful()) {
            Log::warning('ffmpeg conversion failed for whisper input', ['stderr' => $ffmpeg->errorOutput()]);
            return null;
        }

        $whisper = Process::timeout(55)->run([
            $bin, '-m', $model, '-f', $wavPath, '-t', (string) $threads, '-otxt', '-of', $outStem,
        ]);

        @unlink($wavPath);

        if (! $whisper->successful()) {
            Log::warning('whisper.cpp failed', ['stderr' => $whisper->errorOutput()]);
            @unlink($outTxt);
            return null;
        }

        if (! is_file($outTxt)) {
            return null;
        }

        $text = trim((string) file_get_contents($outTxt));
        @unlink($outTxt);

        return $text !== '' ? $text : null;
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/WhisperCppDriverTest.php
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Services/Ai/Transcription/TranscriptionDriver.php app/Services/Ai/Transcription/WhisperCppDriver.php tests/Unit/Services/Ai/Transcription/WhisperCppDriverTest.php
git commit -m "feat(ai): WhisperCppDriver runs ffmpeg + whisper.cpp locally"
```

---

### Task 19: `CircuitBreaker` (Redis-backed)

**Files:**
- Create: `app/Services/Ai/Transcription/CircuitBreaker.php`
- Test: `tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php`:
```php
<?php

declare(strict_types=1);

use App\Services\Ai\Transcription\CircuitBreaker;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
    $this->breaker = new CircuitBreaker('test');
});

it('reports healthy by default', function () {
    expect($this->breaker->isOpen())->toBeFalse();
});

it('opens after 3 failures in 60s and stays open for 5 minutes', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeFalse();

    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeTrue();
});

it('cooling window blocks calls temporarily', function () {
    $this->breaker->cool(seconds: 60);
    expect($this->breaker->isOpen())->toBeTrue();
});

it('closing on success resets failure counter', function () {
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    $this->breaker->recordSuccess();
    $this->breaker->recordFailure();
    $this->breaker->recordFailure();
    expect($this->breaker->isOpen())->toBeFalse(); // only 2 since last success
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the class**

`app/Services/Ai/Transcription/CircuitBreaker.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use Illuminate\Support\Facades\Redis;

class CircuitBreaker
{
    private const FAILURE_THRESHOLD = 3;
    private const FAILURE_WINDOW    = 60;   // seconds
    private const OPEN_DURATION     = 300;  // 5 minutes

    public function __construct(private readonly string $driverName) {}

    public function isOpen(): bool
    {
        if (Redis::exists($this->openKey())) {
            return true;
        }
        if (Redis::exists($this->coolKey())) {
            return true;
        }
        return false;
    }

    public function recordFailure(): void
    {
        $count = Redis::incr($this->countKey());
        if ($count === 1) {
            Redis::expire($this->countKey(), self::FAILURE_WINDOW);
        }
        if ($count >= self::FAILURE_THRESHOLD) {
            Redis::setex($this->openKey(), self::OPEN_DURATION, '1');
            Redis::del($this->countKey());
        }
    }

    public function recordSuccess(): void
    {
        Redis::del($this->countKey());
        Redis::del($this->openKey());
        Redis::del($this->coolKey());
    }

    public function cool(int $seconds): void
    {
        Redis::setex($this->coolKey(), $seconds, '1');
    }

    private function countKey(): string { return "cb:{$this->driverName}:fails"; }
    private function openKey(): string  { return "cb:{$this->driverName}:open"; }
    private function coolKey(): string  { return "cb:{$this->driverName}:cool"; }
}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ai/Transcription/CircuitBreaker.php tests/Unit/Services/Ai/Transcription/CircuitBreakerTest.php
git commit -m "feat(ai): Redis-backed CircuitBreaker for transcription drivers"
```

---

### Task 20: `TranscriptionRouter` (multi-driver with fallback)

**Files:**
- Create: `app/Services/Ai/TranscriptionRouter.php`
- Test: `tests/Unit/Services/Ai/TranscriptionRouterTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Ai/TranscriptionRouterTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Ai\Transcription\CircuitBreaker;
use App\Services\Ai\Transcription\TranscriptionDriver;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Support\Facades\Redis;

beforeEach(function () {
    Redis::flushdb();
    $this->team = Team::factory()->create();
    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'audio']);
});

it('returns the first successful driver result', function () {
    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { return 'primary result'; }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    $router = new TranscriptionRouter([$primary, $fallback]);
    expect($router->transcribe($this->asset))->toBe('primary result');
});

it('falls through to next driver if primary returns null', function () {
    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { return null; }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    $router = new TranscriptionRouter([$primary, $fallback]);
    expect($router->transcribe($this->asset))->toBe('fallback result');
});

it('skips drivers with open circuit breakers', function () {
    // Trip primary's breaker.
    (new CircuitBreaker('primary'))->recordFailure();
    (new CircuitBreaker('primary'))->recordFailure();
    (new CircuitBreaker('primary'))->recordFailure();

    $primary = new class implements TranscriptionDriver {
        public function name(): string { return 'primary'; }
        public function transcribe(MediaAsset $a): ?string { throw new \Exception('should not be called'); }
    };
    $fallback = new class implements TranscriptionDriver {
        public function name(): string { return 'fallback'; }
        public function transcribe(MediaAsset $a): ?string { return 'fallback result'; }
    };

    $router = new TranscriptionRouter([$primary, $fallback]);
    expect($router->transcribe($this->asset))->toBe('fallback result');
});

it('returns null when all drivers fail', function () {
    $a = new class implements TranscriptionDriver {
        public function name(): string { return 'a'; }
        public function transcribe(MediaAsset $x): ?string { return null; }
    };
    $b = new class implements TranscriptionDriver {
        public function name(): string { return 'b'; }
        public function transcribe(MediaAsset $x): ?string { return null; }
    };

    expect((new TranscriptionRouter([$a, $b]))->transcribe($this->asset))->toBeNull();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
vendor/bin/pest tests/Unit/Services/Ai/TranscriptionRouterTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the router**

`app/Services/Ai/TranscriptionRouter.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\MediaAsset;
use App\Services\Ai\Transcription\CircuitBreaker;
use App\Services\Ai\Transcription\TranscriptionDriver;
use Illuminate\Support\Facades\Log;

class TranscriptionRouter
{
    /**
     * @param  array<int, TranscriptionDriver>  $drivers  ordered: primary first
     */
    public function __construct(private readonly array $drivers) {}

    public function transcribe(MediaAsset $asset): ?string
    {
        foreach ($this->drivers as $driver) {
            $breaker = new CircuitBreaker($driver->name());

            if ($breaker->isOpen()) {
                continue;
            }

            try {
                $text = $driver->transcribe($asset);
                if ($text !== null && $text !== '') {
                    $breaker->recordSuccess();
                    return $text;
                }
                // null = failure, but not exception. Record but don't cool.
                $breaker->recordFailure();
            } catch (\App\Services\Ai\Transcription\RateLimitedException $e) {
                $breaker->cool($e->coolSeconds ?? 60);
                Log::info("Transcription driver {$driver->name()} rate-limited", ['e' => $e->getMessage()]);
            } catch (\Throwable $e) {
                $breaker->recordFailure();
                Log::warning("Transcription driver {$driver->name()} threw", ['error' => $e->getMessage()]);
            }
        }

        return null;
    }
}
```

Also create `app/Services/Ai/Transcription/RateLimitedException.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

class RateLimitedException extends \RuntimeException
{
    public function __construct(string $message = '', public readonly int $coolSeconds = 60)
    {
        parent::__construct($message);
    }
}
```

- [ ] **Step 4: Register the router in a service provider**

Add to `app/Providers/AppServiceProvider.php` (in `register()`):
```php
$this->app->singleton(\App\Services\Ai\TranscriptionRouter::class, function () {
    $drivers = [];

    if (config('services.groq.enabled') && config('services.groq.api_key')) {
        $drivers[] = app(\App\Services\Ai\Transcription\GroqDriver::class);
    }
    $drivers[] = app(\App\Services\Ai\Transcription\WhisperCppDriver::class);

    return new \App\Services\Ai\TranscriptionRouter($drivers);
});
```

(GroqDriver is added in Task 22 — this provider registration handles both phases 5 and 6.)

- [ ] **Step 5: Run tests to verify they pass**

```bash
vendor/bin/pest tests/Unit/Services/Ai/TranscriptionRouterTest.php
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Services/Ai/TranscriptionRouter.php app/Services/Ai/Transcription/RateLimitedException.php app/Providers/AppServiceProvider.php tests/Unit/Services/Ai/TranscriptionRouterTest.php
git commit -m "feat(ai): TranscriptionRouter with driver chain + circuit-breaker skip"
```

---

### Task 21: `TranscribeAudio` job (routes to `transcription` queue)

**Files:**
- Create: `app/Jobs/TranscribeAudio.php`
- Test: `tests/Feature/Jobs/TranscribeAudioTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/Jobs/TranscribeAudioTest.php`:
```php
<?php

declare(strict_types=1);

use App\Jobs\SendAiResponse;
use App\Jobs\TranscribeAudio;
use App\Models\Conversation;
use App\Models\MediaAsset;
use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Support\Facades\Bus;

beforeEach(function () {
    Bus::fake([SendAiResponse::class]);
    $this->team = Team::factory()->create();
    $this->page = Page::factory()->for($this->team)->create();
    $this->conversation = Conversation::factory()->for($this->page)->create();
    $this->asset = MediaAsset::factory()->for($this->team)->create(['kind' => 'audio']);
    $this->message = Message::factory()->for($this->conversation)->create([
        'media_asset_id' => $this->asset->id,
        'content'        => '[voice note]',
    ]);
});

it('updates message.content with transcription and dispatches SendAiResponse', function () {
    $router = Mockery::mock(TranscriptionRouter::class);
    $router->expects('transcribe')->andReturn('This is what the customer said.');
    app()->instance(TranscriptionRouter::class, $router);

    (new TranscribeAudio($this->message->id))->handle($router);

    $this->message->refresh();
    expect($this->message->content)->toBe('This is what the customer said.');
    Bus::assertDispatched(SendAiResponse::class);
});

it('marks message unavailable when all drivers fail and does NOT dispatch AI', function () {
    $router = Mockery::mock(TranscriptionRouter::class);
    $router->expects('transcribe')->andReturn(null);
    app()->instance(TranscriptionRouter::class, $router);

    (new TranscribeAudio($this->message->id))->handle($router);

    $this->message->refresh();
    expect($this->message->content)->toBe('[voice note — transcription unavailable]');
    Bus::assertNotDispatched(SendAiResponse::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Feature/Jobs/TranscribeAudioTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the job**

`app/Jobs/TranscribeAudio.php`:
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\Ai\TranscriptionRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscribeAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 90;

    public function __construct(public int $messageId)
    {
        $this->onQueue('transcription');
    }

    public function handle(TranscriptionRouter $router): void
    {
        $message = Message::with('mediaAsset', 'conversation.page.team')->find($this->messageId);
        if ($message === null || $message->mediaAsset === null) {
            return;
        }

        $text = $router->transcribe($message->mediaAsset);

        if ($text === null) {
            $message->update(['content' => '[voice note — transcription unavailable]']);
            return;
        }

        $message->update(['content' => $text]);

        if ($message->conversation?->team?->canDispatchAi()) {
            SendAiResponse::dispatch($message->id);
        }
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Feature/Jobs/TranscribeAudioTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/TranscribeAudio.php tests/Feature/Jobs/TranscribeAudioTest.php
git commit -m "feat(ai): TranscribeAudio job on isolated transcription queue"
```

---

### Task 22: systemd unit for the whisper worker

**Files:**
- Create: `scripts/vps/one-inbox-whisper.service`

- [ ] **Step 1: Write the systemd unit**

`scripts/vps/one-inbox-whisper.service`:
```ini
[Unit]
Description=One Inbox — Whisper transcription worker
After=network.target redis-server.service mysql.service
Requires=redis-server.service

[Service]
Type=simple
User=deploy
Group=www-data
WorkingDirectory=/var/www/ot1-pro.com
ExecStart=/usr/bin/php artisan queue:work redis --queue=transcription --sleep=3 --tries=1 --max-time=3600 --timeout=90
Restart=on-failure
RestartSec=5
Nice=10
CPUQuota=80%
MemoryMax=3G

# So a wedged whisper.cpp child gets killed with the worker.
KillMode=mixed
KillSignal=SIGTERM
TimeoutStopSec=30

[Install]
WantedBy=multi-user.target
```

- [ ] **Step 2: Deploy to VPS**

```bash
scp scripts/vps/one-inbox-whisper.service root@187.77.67.94:/etc/systemd/system/
ssh root@187.77.67.94 "systemctl daemon-reload && systemctl enable --now one-inbox-whisper.service && systemctl status one-inbox-whisper.service --no-pager"
```
Expected: service is `active (running)`.

- [ ] **Step 3: Commit**

```bash
git add scripts/vps/one-inbox-whisper.service
git commit -m "chore(vps): systemd unit for one-inbox-whisper worker with CPU quota"
```

---

### Task 23: Per-team in-flight rate limit for transcription

**Files:**
- Modify: `app/Jobs/TranscribeAudio.php`

- [ ] **Step 1: Add rate-limit gate + release in `handle()`**

Wrap the body of `handle()`:
```php
public function handle(TranscriptionRouter $router): void
{
    $message = Message::with('mediaAsset', 'conversation.page.team')->find($this->messageId);
    if ($message === null || $message->mediaAsset === null) {
        return;
    }

    $teamId = $message->conversation?->page?->team_id;
    $key = "transcribe:inflight:{$teamId}";
    $limit = 5;

    // Atomic increment; if over the cap, release the job back and re-queue in 3s.
    $current = \Illuminate\Support\Facades\Redis::incr($key);
    if ($current === 1) {
        \Illuminate\Support\Facades\Redis::expire($key, 300); // safety expiry
    }

    if ($current > $limit) {
        \Illuminate\Support\Facades\Redis::decr($key);
        $this->release(3);
        return;
    }

    try {
        // ... existing transcription body ...
    } finally {
        \Illuminate\Support\Facades\Redis::decr($key);
    }
}
```

- [ ] **Step 2: Add a test for the rate limit**

Append to `tests/Feature/Jobs/TranscribeAudioTest.php`:
```php
it('releases the job back to the queue when team is at the 5-inflight limit', function () {
    \Illuminate\Support\Facades\Redis::set("transcribe:inflight:{$this->team->id}", 5);

    $router = Mockery::mock(TranscriptionRouter::class);
    $router->shouldNotReceive('transcribe');
    app()->instance(TranscriptionRouter::class, $router);

    $job = Mockery::mock(TranscribeAudio::class.'[release]', [$this->message->id]);
    $job->shouldReceive('release')->once()->with(3);
    $job->handle($router);
});
```

- [ ] **Step 3: Run tests**

```bash
vendor/bin/pest tests/Feature/Jobs/TranscribeAudioTest.php
```
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add app/Jobs/TranscribeAudio.php tests/Feature/Jobs/TranscribeAudioTest.php
git commit -m "feat(ai): per-team 5-inflight rate limit for transcription queue"
```

---

## Phase 6 — Groq primary transcription driver

### Task 24: `GroqDriver`

**Files:**
- Create: `app/Services/Ai/Transcription/GroqDriver.php`
- Test: `tests/Unit/Services/Ai/Transcription/GroqDriverTest.php`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/Services/Ai/Transcription/GroqDriverTest.php`:
```php
<?php

declare(strict_types=1);

use App\Models\MediaAsset;
use App\Models\Team;
use App\Services\Ai\Transcription\GroqDriver;
use App\Services\Ai\Transcription\RateLimitedException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'services.groq.api_key' => 'gsk_fake',
        'services.groq.model'   => 'whisper-large-v3',
        'services.groq.timeout' => 5,
    ]);
    Storage::fake('media');
    $this->team = Team::factory()->create();
    Storage::disk('media')->put('a/b/voice.ogg', 'bytes');
    $this->asset = MediaAsset::factory()->for($this->team)->create([
        'path' => 'a/b/voice.ogg', 'mime_type' => 'audio/ogg', 'kind' => 'audio',
    ]);
});

it('returns transcript on 200', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['text' => 'Hello from Groq.']),
    ]);

    $driver = app(GroqDriver::class);
    expect($driver->transcribe($this->asset))->toBe('Hello from Groq.');
});

it('throws RateLimitedException on 429', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => 'rate limited'], 429, ['Retry-After' => '30']),
    ]);

    $driver = app(GroqDriver::class);
    expect(fn () => $driver->transcribe($this->asset))->toThrow(RateLimitedException::class);
});

it('returns null on 5xx', function () {
    Http::fake([
        'api.groq.com/*' => Http::response(['error' => 'oops'], 502),
    ]);

    $driver = app(GroqDriver::class);
    expect($driver->transcribe($this->asset))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/GroqDriverTest.php
```
Expected: FAIL

- [ ] **Step 3: Write the driver**

`app/Services/Ai/Transcription/GroqDriver.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Ai\Transcription;

use App\Models\MediaAsset;
use App\Services\Media\MediaStorage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqDriver implements TranscriptionDriver
{
    public function __construct(
        private readonly MediaStorage $storage,
    ) {}

    public function name(): string
    {
        return 'groq';
    }

    public function transcribe(MediaAsset $asset): ?string
    {
        $apiKey  = (string) config('services.groq.api_key');
        $model   = (string) config('services.groq.model');
        $timeout = (int)    config('services.groq.timeout', 5);

        if ($apiKey === '') {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout($timeout)
                ->attach('file', $this->storage->readBytes($asset), basename($asset->path))
                ->asMultipart()
                ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                    'model'           => $model,
                    'response_format' => 'json',
                ]);
        } catch (ConnectionException $e) {
            Log::info('Groq connection failure', ['e' => $e->getMessage()]);
            return null;
        }

        if ($response->status() === 429) {
            $cool = (int) ($response->header('Retry-After') ?: 60);
            throw new RateLimitedException('Groq rate limited', coolSeconds: $cool);
        }

        if (! $response->successful()) {
            Log::info('Groq non-2xx', ['status' => $response->status()]);
            return null;
        }

        $text = $response->json('text');
        return is_string($text) && $text !== '' ? trim($text) : null;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

```bash
vendor/bin/pest tests/Unit/Services/Ai/Transcription/GroqDriverTest.php
```
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/Ai/Transcription/GroqDriver.php tests/Unit/Services/Ai/Transcription/GroqDriverTest.php
git commit -m "feat(ai): GroqDriver as primary transcription with 429 → cool signal"
```

---

### Task 25: Push GROQ_API_KEY to prod and enable

**Files:**
- Prod `.env` (on VPS)

**⚠️ Security note:** The user pasted a Groq API key in chat. That key should be considered leaked. After enabling, rotate it: go to console.groq.com, delete the current key, generate a new one, and repeat Step 1 with the new key.

- [ ] **Step 1: Add key to prod .env**

```bash
ssh root@187.77.67.94 "cp /var/www/ot1-pro.com/.env /var/www/ot1-pro.com/.env.bak.$(date +%s)"
ssh root@187.77.67.94 "cat >> /var/www/ot1-pro.com/.env <<'EOF'

# --- MEDIA / AI COMPREHENSION ---
MEDIA_INGEST_ENABLED=true
VISION_ENABLED=true
TRANSCRIPTION_ENABLED=true
TRANSCRIPTION_GROQ_ENABLED=true
GROQ_API_KEY=PASTE_KEY_HERE
GROQ_WHISPER_MODEL=whisper-large-v3
GROQ_TIMEOUT_SECONDS=5
WHISPER_CPP_BIN=/usr/local/bin/whisper.cpp
WHISPER_CPP_MODEL=/opt/whisper-models/ggml-medium.bin
WHISPER_CPP_THREADS=2
# --- End MEDIA ---
EOF"
```

Then manually edit `/var/www/ot1-pro.com/.env` on the VPS to replace `PASTE_KEY_HERE` with the actual key. Do NOT put the raw key in the plan file or any git-tracked file.

- [ ] **Step 2: Rebuild config cache as `deploy` user**

```bash
ssh root@187.77.67.94 "sudo -u deploy php -d display_errors=0 /var/www/ot1-pro.com/artisan config:cache && systemctl reload php8.4-fpm"
```

- [ ] **Step 3: Restart queue + whisper workers to pick up new env**

```bash
ssh root@187.77.67.94 "systemctl restart 'one-inbox-queue@*' one-inbox-whisper.service"
```

- [ ] **Step 4: Verify by sending a real WA voice note to a test conversation and checking Groq gets the call**

```bash
ssh root@187.77.67.94 "journalctl -u one-inbox-whisper.service -n 50 --no-pager | grep -i groq"
```
Expected: log lines indicating Groq call succeeded, latency < 3s.

- [ ] **Step 5: Rotate the leaked key**

Log in to console.groq.com → delete the pasted key → generate new key → repeat Steps 1-3.

---

## Phase 7 — Extend to other platforms

Each of the following tasks follows the WhatsApp template with platform-specific adaptations. Complete code is not repeated — the pattern from Tasks 6, 12, 13 applies.

### Task 26: Facebook Messenger media

**Files:**
- Modify: `app/Services/Platforms/FacebookPlatform.php`
- Modify: `app/Jobs/ProcessIncomingMessage.php` (extend `processMetaMessenger` to persist media)

- [ ] **Step 1:** Add `downloadInboundMedia(Page $page, string $attachmentUrl, string $kind): MediaAsset` — direct download from `$attachmentUrl` (Meta signs it, no bearer token needed inside its short TTL).
- [ ] **Step 2:** Add `uploadOutboundMedia(Page $page, MediaAsset $asset): string` — POST multipart to `/{page-id}/message_attachments` with `access_token` + `message[attachment]` structure. Returns `attachment_id`.
- [ ] **Step 3:** Add `sendMediaMessage(Page $page, string $recipientId, MediaAsset $asset, string $waMediaId, ?string $caption): string` — POST to `/me/messages` with `recipient: {id}` and `message: { attachment: { type, payload: { attachment_id: waMediaId } } }`.
- [ ] **Step 4:** In `ProcessIncomingMessage::handleMetaMessage`, parse `$event['message']['attachments']` and persist media same as WA path.
- [ ] **Step 5:** Write feature test mirroring the WA test.
- [ ] **Step 6:** Commit `feat(facebook): media send/receive + AI comprehension`.

### Task 27: Instagram media

Instagram Direct uses the same Meta Graph API as Facebook Messenger. In this repo, IG webhooks are routed through `processMetaMessenger` in `ProcessIncomingMessage.php` (same branch as `facebook`), and outbound sends already reuse `FacebookPlatform`.

- [ ] **Step 1: Check whether Task 26's changes already handle IG**

```bash
grep -n "'instagram'" app/Services/Platforms/FacebookPlatform.php app/Jobs/ProcessIncomingMessage.php
```

If IG is a shared branch (very likely): the media persistence code in `handleMetaMessage()` (Task 26 Step 4) already fires for both platforms. **No new code needed — just add an IG-specific feature test to prove parity.**

- [ ] **Step 2: Add feature test for IG media ingest**

Duplicate the WA feature test template with `platform => 'instagram'` on the seeded `Page` and an IG-shaped payload. Assert the same `MediaAsset` row is created and the same job chain fires.

- [ ] **Step 3: Commit**

```bash
git commit --allow-empty -m "test(instagram): confirm media pipeline parity with facebook"
```

(If IG turns out to have a separate handler after all, mirror Task 26 Steps 1-4 into an `InstagramPlatform.php` file. The API calls are identical to FB — only class name differs.)

### Task 28: Telegram media

**Files:**
- Modify: `app/Services/Platforms/TelegramPlatform.php`

Telegram: incoming webhook has `message.photo[]`, `message.voice`, `message.audio`, `message.video`, `message.document`. Each has a `file_id`. Two-step: `getFile?file_id=X` returns `file_path`, then download from `https://api.telegram.org/file/bot<TOKEN>/<file_path>`.

Outbound: `sendPhoto`, `sendVoice`, `sendAudio`, `sendVideo`, `sendDocument` — multipart POST with file bytes.

- [ ] **Step 1:** `downloadInboundMedia` — implement `getFile` + download pattern.
- [ ] **Step 2:** `uploadOutboundMedia` — Telegram bundles upload+send in one call, so `uploadOutboundMedia` can return the asset id as-is (no separate upload step), and `sendMediaMessage` does the actual multipart POST.
- [ ] **Step 3:** Wire into `ProcessIncomingMessage::processTelegram`.
- [ ] **Step 4:** Test.
- [ ] **Step 5:** Commit.

### Task 29: Email attachments

**Files:**
- Modify: `app/Services/Platforms/EmailPlatform.php`
- Modify: `app/Jobs/FetchEmailsForPageJob.php` (persist MIME attachments)

- [ ] **Step 1:** In the email fetch job, iterate `$mail->getAttachments()`, for each: call `MediaStorage::storeBytes()` with the attachment's mime + name.
- [ ] **Step 2:** Persist first N attachments' asset IDs in `messages.metadata->attachment_ids` (array; not just the single `media_asset_id` FK, since email can have many attachments per message).
- [ ] **Step 3:** Update `<x-inbox.media-bubble>` to render an attachment list when `metadata.attachment_ids` present.
- [ ] **Step 4:** For outbound: `EmailPlatform::send` — attach the media via the mail package's `attach()` method.
- [ ] **Step 5:** Test + commit.

### Task 30: WhatsApp gateway (Evolution + Wuzapi)

**Files:**
- Modify: `app/Jobs/ProcessIncomingMessage.php` — `processEvolution` and `processWuzapi`

Evolution: webhook payload contains `message.imageMessage.url` or `audioMessage.url` (Evolution's server has already downloaded and re-hosted). Direct `Http::get($url)->body()` → `MediaStorage::storeBytes()`.

Wuzapi: similar — check the payload shape in `processWuzapi` and adapt.

- [ ] **Step 1-4:** Persist media, wire AI dispatch, test, commit.

---

## Phase 8 — Privacy + team-level opt-out

### Task 31: `teams.audio_transcription_enabled` column

**Files:**
- Create: `database/migrations/2026_09_01_150200_add_audio_transcription_enabled_to_teams_table.php`
- Modify: `app/Models/Team.php` — add to `$fillable` + cast to bool
- Modify: settings view — add toggle

- [ ] **Step 1: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->boolean('audio_transcription_enabled')->default(true)->after('name');
        });
    }
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('audio_transcription_enabled');
        });
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

- [ ] **Step 3: Update Team model — add to `$fillable` and cast**

Add `'audio_transcription_enabled'` to `$fillable`; add `'audio_transcription_enabled' => 'boolean'` to casts.

- [ ] **Step 4: Add toggle in team settings view**

Add to `resources/views/settings/team.blade.php` (or wherever team settings live — grep for existing team settings form):
```blade
<flux:switch
    wire:model.live="audioTranscriptionEnabled"
    label="Transcribe customer voice notes for AI"
    description="When off, voice notes are still shown in the inbox but the AI ignores them. Human agents must reply." />
```

Add the property + wire logic in the corresponding Livewire component.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_09_01_150200_add_audio_transcription_enabled_to_teams_table.php app/Models/Team.php resources/views/settings/team.blade.php
git commit -m "feat(privacy): per-team audio transcription opt-out toggle"
```

### Task 32: Update `/privacy` + `docs/ARCHITECTURE.md`

**Files:**
- Modify: `resources/views/legal/privacy.blade.php`
- Modify: `docs/ARCHITECTURE.md`

- [ ] **Step 1: Add Groq disclosure to privacy page**

In the "Third-party processors" section (or create it), add:
```blade
<h3>Voice-note transcription</h3>
<p>When enabled by your team, customer voice notes are sent to Groq (a US-based AI infrastructure provider) for transcription. The audio is transmitted over TLS, is not retained by Groq per their policies, and is only used to generate a text transcript. If your team has disabled AI voice-note handling in Settings, no audio leaves our servers.</p>
```

- [ ] **Step 2: Add §16 to `docs/ARCHITECTURE.md`**

Append:
```markdown
## §16 Media Pipeline (added 2026-09-01)

- **Storage:** `MediaAsset` model + `MediaStorage` service. Per-team dedup by SHA-256. Local disk `storage/app/media/{team}/YYYY/MM/{ulid}.{ext}`, abstracted so S3 swap is config-only.
- **Ingest:** each `PlatformInterface` implementer has `downloadInboundMedia`. Called from `ProcessIncomingMessage` per platform branch.
- **Egress:** `SendPlatformMessage` handles a `mediaAssetId` param, calls `uploadOutboundMedia` + `sendMediaMessage`.
- **AI vision:** `VisionRouter` iterates NaraRouter chain, first vision-capable model wins. Result cached on `media_assets.metadata.ai_description`. Injected into `SendAiResponse` system prompt.
- **AI transcription:** `TranscriptionRouter` with `GroqDriver` (primary) + `WhisperCppDriver` (fallback). Redis-backed circuit breaker per driver (3 failures/60s → 5-min open). Isolated `transcription` queue served by single-worker systemd service `one-inbox-whisper.service` with `CPUQuota=80%`.
- **Rate limit:** 5 in-flight transcription jobs per team, Redis counter, releases job back on limit hit.
- **Kill switches (env):** `MEDIA_INGEST_ENABLED`, `VISION_ENABLED`, `TRANSCRIPTION_ENABLED`, `TRANSCRIPTION_GROQ_ENABLED`.
- **Per-team opt-out:** `teams.audio_transcription_enabled` (default true).
```

- [ ] **Step 3: Commit**

```bash
git add resources/views/legal/privacy.blade.php docs/ARCHITECTURE.md
git commit -m "docs(privacy): disclose Groq transcription + document media pipeline in ARCHITECTURE"
```

---

## Phase 9 — Final integration test + PR

### Task 33: End-to-end smoke test on staging (feat branch)

- [ ] **Step 1: Deploy feature branch to a staging URL** (or use ngrok tunnel to local dev)
- [ ] **Step 2: Real WA test flow:**
  1. Send text — verify text bubble
  2. Send image — verify image bubble + AI reply references image content
  3. Send voice note — verify audio bubble + AI reply references what was said
  4. Send from composer: image → verify customer receives
  5. Send from composer: voice note → verify customer receives
- [ ] **Step 3: Verify queue isolation** — while a voice note is transcribing, send 3 text messages, all should get AI replies within seconds (not blocked by whisper)
- [ ] **Step 4: Verify circuit breaker** — set an invalid Groq key temporarily, send 5 voice notes, confirm they all fall through to whisper.cpp, then restore key

### Task 34: Open PR to `main`

- [ ] **Step 1:** `git push origin feat/media-messages-and-ai-comprehension`
- [ ] **Step 2:** Open PR titled `feat: media messages (audio + image) with AI comprehension`
- [ ] **Step 3:** PR body references `docs/superpowers/specs/2026-09-01-media-messages-and-ai-comprehension-design.md`
- [ ] **Step 4:** Include test plan checklist covering all 5 real WA scenarios from Task 33

---

## Rollback quick-reference

| If this breaks | Do this |
|---|---|
| Inbox renders broken bubbles | `ssh` → set `MEDIA_INGEST_ENABLED=false` → `config:cache` as deploy → reload FPM |
| Vision spam / bad descriptions | Set `VISION_ENABLED=false` |
| Whisper CPU pinning entire box | `systemctl stop one-inbox-whisper.service` |
| Groq costs money unexpectedly (shouldn't on free tier) | Set `TRANSCRIPTION_GROQ_ENABLED=false` |
| Full audio pipeline broken | Set `TRANSCRIPTION_ENABLED=false` |
| Migration issue in prod | `php artisan migrate:rollback --step=3` reverses all three new migrations safely (they are additive) |

All rollbacks are env-flag flips + service reloads — no code deploy required.
