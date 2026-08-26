# Bulk WhatsApp Campaigns — Phase A Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a working `/campaigns/whatsapp/new` wizard behind a feature flag that lets a team upload a CSV of phone numbers, test-send one message, and launch a jittered bulk WhatsApp campaign via Wuzapi — without touching the existing `urgent` queue or the existing email wizard.

**Architecture:** New sibling wizard alongside the existing `EmailWizard`. Shared engine components (`SpreadsheetParser`, plus new `CampaignScheduler`, `PhoneNormalizer`, `PhoneContactImporter`) live under `app/Services/Campaigns/`. A single new `WhatsAppSender` in `app/Services/Wuzapi/` wraps the existing `EvolutionApiService` and is the sole Wuzapi call site for campaign sends. A dedicated `campaigns` queue with its own systemd worker keeps bulk work off `urgent`. Scheduler enforces page-circuit-breaker and queue-depth backpressure via direct in-process queries.

**Tech Stack:** Laravel 12, Livewire 4, Flux UI, MySQL 8 (prod) / SQLite (local), Pest 3, `giggsey/libphonenumber-for-php`, `openspout/openspout` (already installed for email wizard), Wuzapi (self-hosted WhatsApp Web bridge).

**Reference spec:** `docs/superpowers/specs/2026-08-26-bulk-multichannel-campaigns-design.md`

---

## File Structure

### New files (Phase A)

| Path | Responsibility |
|---|---|
| `database/migrations/2026_08_26_100000_add_channel_columns_to_campaigns_tables.php` | Additive columns on `campaigns`, `campaign_recipients`, `contact_imports`; hot index on `campaign_recipients (status, scheduled_at)`; `teams.features` JSON. |
| `app/Services/Campaigns/PhoneNormalizer.php` | Thin wrapper over `libphonenumber-for-php`. Normalizes to E.164 + returns ISO2 country. |
| `app/Services/Campaigns/PhoneContactImporter.php` | Streams parsed rows, validates phones, dedupes, upserts Contacts, records `contact_imports`. |
| `app/Services/Campaigns/CampaignScheduler.php` | Channel-agnostic: given a Campaign, produces `campaign_recipients` with jittered `scheduled_at`. |
| `app/Services/Wuzapi/WhatsAppSender.php` | Sole Wuzapi call site for campaign sends. Wraps `EvolutionApiService::sendText`. |
| `app/Jobs/SendCampaignWhatsAppJob.php` | Per-recipient send job. Runs gate stack, calls `WhatsAppSender`, updates recipient row. |
| `app/Console/Commands/DispatchScheduledCampaignMessages.php` | Cron every minute. Runs gate chain, dispatches jobs onto `campaigns` queue. |
| `app/Livewire/Campaigns/WhatsAppWizard.php` | Multi-step Livewire: `upload → map → compose → test → review → launched`. |
| `resources/views/livewire/campaigns/whats-app-wizard.blade.php` | Blade view for the wizard. |
| `app/Http/Controllers/CampaignTestSendController.php` | `POST /campaigns/{campaign}/test-send`, 5/hr/user throttle. |
| `app/Http/Controllers/HealthMetricsController.php` | `GET /health/metrics` — observability only (scheduler does NOT call this). |
| `tests/Unit/Services/Campaigns/PhoneNormalizerTest.php` | Unit tests for phone normalization. |
| `tests/Feature/Campaigns/PhoneContactImporterTest.php` | Feature tests for CSV import. |
| `tests/Feature/Campaigns/CampaignSchedulerTest.php` | Feature tests for scheduler + jitter + backpressure + page circuit-breaker. |
| `tests/Feature/Campaigns/SendCampaignWhatsAppJobTest.php` | Gate stack + retry + suppression behavior. |
| `tests/Feature/Campaigns/WhatsAppSenderTest.php` | Mocked Wuzapi HTTP. |
| `tests/Feature/Campaigns/WhatsAppWizardTest.php` | Livewire end-to-end. |
| `tests/Feature/Campaigns/TestSendThrottleTest.php` | 5/hr enforcement. |
| `tests/Performance/CampaignDispatcherIndexTest.php` | Seeded 100k-row EXPLAIN gate. |

### Modified files (Phase A)

| Path | Change |
|---|---|
| `composer.json` | Add `giggsey/libphonenumber-for-php`. |
| `app/Models/Campaign.php` | Add new fillable + casts for `warmup_bypass`, `quiet_hours_start`, `quiet_hours_end`, `respect_recipient_tz`, `paused_reason`, `use_spintax`. |
| `app/Models/CampaignRecipient.php` | Add `phone`, `phone_country`, `channel` to fillable + casts. |
| `app/Models/Team.php` | Add `features` cast (json). Add `hasFeature(string $key): bool` helper. |
| `app/Livewire/Campaigns/Index.php` | Add `email` to platform options; route to sibling wizard when whatsapp/email + CSV mode chosen. |
| `resources/views/livewire/campaigns/index.blade.php` | Channel-gated CSV upload region; email option in platform picker. |
| `routes/web.php` | Add wizard route, test-send route, `/health/metrics` route. |
| `config/queue.php` | (No change; verify database driver comment.) |
| `deploy/systemd/one-inbox-queue-campaigns.service` | New systemd unit (prod). |
| `README.md` | Local dev: `php artisan queue:work --queue=campaigns` in Terminal 6. |

---

## Task 1: Install libphonenumber and add feature-flag column

**Files:**
- Modify: `composer.json`
- Create: `database/migrations/2026_08_26_100000_add_features_to_teams_table.php`

- [ ] **Step 1: Install libphonenumber**

Run:
```bash
composer require giggsey/libphonenumber-for-php
```
Expected: `Package operations: 1 install`. Verify `composer.lock` updated.

- [ ] **Step 2: Create the migration**

```bash
php artisan make:migration add_features_to_teams_table --table=teams
```

Fill it in:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'features')) {
                $table->json('features')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            if (Schema::hasColumn('teams', 'features')) {
                $table->dropColumn('features');
            }
        });
    }
};
```

- [ ] **Step 3: Run the migration**

Run: `php artisan migrate`
Expected: `Migrating: 2026_08_26_100000_add_features_to_teams_table` → `DONE`.

- [ ] **Step 4: Add `features` cast + `hasFeature` to Team**

Modify `app/Models/Team.php`. Add to `$casts` (or `casts()` method): `'features' => 'array'`. Add method:

```php
public function hasFeature(string $key): bool
{
    return (bool) data_get($this->features, $key, false);
}
```

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock database/migrations/2026_08_26_100000_add_features_to_teams_table.php app/Models/Team.php
git commit -m "feat(campaigns): add libphonenumber + teams.features feature-flag column"
```

---

## Task 2: Additive schema for campaign multi-channel columns

**Files:**
- Create: `database/migrations/2026_08_26_100001_add_channel_columns_to_campaign_tables.php`
- Modify: `app/Models/Campaign.php`
- Modify: `app/Models/CampaignRecipient.php`

- [ ] **Step 1: Generate migration**

Run: `php artisan make:migration add_channel_columns_to_campaign_tables`

Fill it in:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('warmup_bypass')->default(false)->after('jitter_max_seconds');
            $table->unsignedTinyInteger('quiet_hours_start')->default(9)->after('warmup_bypass');
            $table->unsignedTinyInteger('quiet_hours_end')->default(21)->after('quiet_hours_start');
            $table->boolean('respect_recipient_tz')->default(true)->after('quiet_hours_end');
            $table->string('paused_reason')->nullable()->after('status');
            $table->boolean('use_spintax')->default(true)->after('respect_recipient_tz');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->char('phone_country', 2)->nullable()->after('phone');
            $table->string('channel', 20)->default('email')->after('campaign_id');

            // Hot dispatcher-query index. Verified by EXPLAIN in Task 3.
            $table->index(['status', 'scheduled_at'], 'campaign_recipients_status_scheduled_idx');
        });

        Schema::table('contact_imports', function (Blueprint $table) {
            $table->string('channel', 20)->default('email')->after('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_imports', function (Blueprint $table) {
            $table->dropColumn('channel');
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('campaign_recipients_status_scheduled_idx');
            $table->dropColumn(['phone', 'phone_country', 'channel']);
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'warmup_bypass', 'quiet_hours_start', 'quiet_hours_end',
                'respect_recipient_tz', 'paused_reason', 'use_spintax',
            ]);
        });
    }
};
```

- [ ] **Step 2: Run the migration**

Run: `php artisan migrate`
Expected: `DONE`.

- [ ] **Step 3: Extend Campaign fillable + casts**

In `app/Models/Campaign.php` add to `$fillable`:
```php
'warmup_bypass', 'quiet_hours_start', 'quiet_hours_end',
'respect_recipient_tz', 'paused_reason', 'use_spintax',
```

Add to `casts()`:
```php
'warmup_bypass'         => 'boolean',
'quiet_hours_start'     => 'integer',
'quiet_hours_end'       => 'integer',
'respect_recipient_tz'  => 'boolean',
'use_spintax'           => 'boolean',
```

- [ ] **Step 4: Extend CampaignRecipient fillable**

In `app/Models/CampaignRecipient.php` add `phone`, `phone_country`, `channel` to `$fillable`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_08_26_100001_add_channel_columns_to_campaign_tables.php app/Models/Campaign.php app/Models/CampaignRecipient.php
git commit -m "feat(campaigns): additive multi-channel columns + hot dispatcher index"
```

---

## Task 3: EXPLAIN gate — verify the hot index

**Files:**
- Create: `tests/Performance/CampaignDispatcherIndexTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CampaignDispatcherIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatcher_query_uses_status_scheduled_index_and_scans_few_rows(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('EXPLAIN gate is MySQL-only; run in CI or against local MySQL.');
        }

        // Seed 100k recipients across 10 campaigns, mostly 'sent' (needle in haystack).
        $campaigns = Campaign::factory()->count(10)->create();
        foreach ($campaigns as $c) {
            CampaignRecipient::factory()->count(10_000)->create([
                'campaign_id'  => $c->id,
                'status'       => 'sent',
                'channel'      => 'whatsapp',
                'scheduled_at' => now()->subDay(),
            ]);
        }
        // 50 pending rows eligible now.
        CampaignRecipient::factory()->count(50)->create([
            'campaign_id'  => $campaigns->first()->id,
            'status'       => 'pending',
            'channel'      => 'whatsapp',
            'scheduled_at' => now()->subMinute(),
        ]);

        $explain = DB::select("
            EXPLAIN
            SELECT id
            FROM campaign_recipients
            WHERE status = 'pending' AND scheduled_at <= NOW()
            ORDER BY scheduled_at
            LIMIT 50
        ");

        $row = (array) $explain[0];

        $this->assertSame('campaign_recipients_status_scheduled_idx', $row['key'] ?? null,
            'Dispatcher query MUST use the (status, scheduled_at) index.');
        $this->assertLessThan(200, (int) ($row['rows'] ?? 0),
            'Dispatcher query rows estimate must be < 200; got ' . ($row['rows'] ?? 'null'));
        $this->assertStringNotContainsString('filesort', strtolower((string) ($row['Extra'] ?? '')),
            'Dispatcher query MUST NOT require filesort.');
    }
}
```

- [ ] **Step 2: Add a CampaignRecipient factory if missing**

Verify `database/factories/CampaignRecipientFactory.php` exists. If not, create:
```php
<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignRecipientFactory extends Factory
{
    protected $model = CampaignRecipient::class;

    public function definition(): array
    {
        return [
            'campaign_id'  => Campaign::factory(),
            'channel'      => 'email',
            'email'        => $this->faker->unique()->safeEmail(),
            'status'       => 'pending',
            'attempts'     => 0,
            'scheduled_at' => now(),
        ];
    }
}
```

- [ ] **Step 3: Run the test**

Run against local MySQL if available:
```bash
DB_CONNECTION=mysql DB_DATABASE=one_inbox_test vendor/bin/pest tests/Performance/CampaignDispatcherIndexTest.php -v
```
Expected: PASS with rows estimate < 200 and `Using index condition` in Extra.

If it fails with wrong index, adjust the migration (Task 2) — do NOT relax the test.

- [ ] **Step 4: Commit**

```bash
git add tests/Performance/CampaignDispatcherIndexTest.php database/factories/CampaignRecipientFactory.php
git commit -m "test(campaigns): EXPLAIN gate for dispatcher hot-path index"
```

---

## Task 4: PhoneNormalizer service

**Files:**
- Create: `app/Services/Campaigns/PhoneNormalizer.php`
- Create: `tests/Unit/Services/Campaigns/PhoneNormalizerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Campaigns;

use App\Services\Campaigns\InvalidPhoneException;
use App\Services\Campaigns\PhoneNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNormalizerTest extends TestCase
{
    private PhoneNormalizer $sut;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sut = new PhoneNormalizer();
    }

    public function test_normalizes_egyptian_local_format(): void
    {
        $result = $this->sut->normalize('01026361218', 'EG');
        $this->assertSame('+201026361218', $result->e164);
        $this->assertSame('EG', $result->countryIso2);
    }

    public function test_accepts_e164_input(): void
    {
        $result = $this->sut->normalize('+971501234567', 'EG');
        $this->assertSame('+971501234567', $result->e164);
        $this->assertSame('AE', $result->countryIso2);
    }

    public function test_strips_whitespace_and_dashes(): void
    {
        $result = $this->sut->normalize('+20 102 636 1218', 'EG');
        $this->assertSame('+201026361218', $result->e164);
    }

    public function test_rejects_garbage(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->sut->normalize('not-a-phone', 'EG');
    }

    public function test_rejects_too_short(): void
    {
        $this->expectException(InvalidPhoneException::class);
        $this->sut->normalize('123', 'EG');
    }
}
```

- [ ] **Step 2: Run the test to verify failure**

Run: `vendor/bin/pest tests/Unit/Services/Campaigns/PhoneNormalizerTest.php`
Expected: FAIL — `PhoneNormalizer not found`.

- [ ] **Step 3: Implement PhoneNormalizer**

Create `app/Services/Campaigns/PhoneNormalizer.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

readonly class NormalizedPhone
{
    public function __construct(
        public string $e164,
        public string $countryIso2,
    ) {}
}

class InvalidPhoneException extends \RuntimeException {}

class PhoneNormalizer
{
    private PhoneNumberUtil $util;

    public function __construct()
    {
        $this->util = PhoneNumberUtil::getInstance();
    }

    public function normalize(string $raw, string $defaultCountry): NormalizedPhone
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            throw new InvalidPhoneException('Empty phone value.');
        }

        try {
            $parsed = $this->util->parse($trimmed, strtoupper($defaultCountry));
        } catch (NumberParseException $e) {
            throw new InvalidPhoneException("Unparseable phone: {$raw}", 0, $e);
        }

        if (! $this->util->isValidNumber($parsed)) {
            throw new InvalidPhoneException("Invalid phone: {$raw}");
        }

        return new NormalizedPhone(
            e164: $this->util->format($parsed, PhoneNumberFormat::E164),
            countryIso2: $this->util->getRegionCodeForNumber($parsed) ?? strtoupper($defaultCountry),
        );
    }
}
```

- [ ] **Step 4: Re-run tests**

Run: `vendor/bin/pest tests/Unit/Services/Campaigns/PhoneNormalizerTest.php`
Expected: 5 tests passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Campaigns/PhoneNormalizer.php tests/Unit/Services/Campaigns/PhoneNormalizerTest.php
git commit -m "feat(campaigns): PhoneNormalizer with libphonenumber-backed E.164 + country detection"
```

---

## Task 5: PhoneContactImporter service

**Files:**
- Create: `app/Services/Campaigns/PhoneContactImporter.php`
- Create: `tests/Feature/Campaigns/PhoneContactImporterTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\Team;
use App\Services\Campaigns\PhoneContactImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneContactImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_valid_rows_and_records_counts(): void
    {
        $team = Team::factory()->create();
        $importer = app(PhoneContactImporter::class);

        $rows = [
            ['phone' => '01026361218', 'name' => 'Ahmed'],
            ['phone' => '+971501234567', 'name' => 'Fatima'],
            ['phone' => 'garbage',      'name' => 'Bad'],
            ['phone' => '01026361218', 'name' => 'DupAhmed'], // duplicate
        ];

        $result = $importer->import(
            teamId: $team->id,
            channel: 'whatsapp',
            filename: 'list.csv',
            defaultCountry: 'EG',
            phoneColumn: 'phone',
            nameColumn: 'name',
            optedInAtColumn: null,
            customColumns: [],
            rows: $rows,
        );

        $this->assertSame(2, $result->importedRows);
        $this->assertSame(1, $result->skippedRows);   // duplicate
        $this->assertSame(1, $result->invalidRows);   // garbage
        $this->assertDatabaseCount('contacts', 2);
        $this->assertDatabaseHas('contacts', ['phone' => '+201026361218', 'team_id' => $team->id]);
        $this->assertDatabaseHas('contacts', ['phone' => '+971501234567', 'team_id' => $team->id]);

        /** @var ContactImport $import */
        $import = ContactImport::sole();
        $this->assertSame('whatsapp', $import->channel);
        $this->assertSame(4, $import->total_rows);
    }
}
```

- [ ] **Step 2: Run and verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/PhoneContactImporterTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Verify Contact model has `phone` column**

Run: `grep -r 'phone' database/migrations/*contacts* 2>&1 | head`

If Contact has no `phone` column, add a migration first:
```php
Schema::table('contacts', function (Blueprint $table) {
    if (! Schema::hasColumn('contacts', 'phone')) {
        $table->string('phone')->nullable()->after('email');
        $table->index(['team_id', 'phone']);
    }
});
```
Add `phone` to `Contact::$fillable`.

- [ ] **Step 4: Implement PhoneContactImporter**

Create `app/Services/Campaigns/PhoneContactImporter.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Models\Contact;
use App\Models\ContactImport;
use Illuminate\Support\Facades\DB;

readonly class ImportResult
{
    public function __construct(
        public int $importId,
        public int $totalRows,
        public int $importedRows,
        public int $skippedRows,
        public int $invalidRows,
    ) {}
}

class PhoneContactImporter
{
    public function __construct(private PhoneNormalizer $normalizer) {}

    public function import(
        int $teamId,
        string $channel,
        string $filename,
        string $defaultCountry,
        string $phoneColumn,
        ?string $nameColumn,
        ?string $optedInAtColumn,
        array $customColumns,
        iterable $rows,
    ): ImportResult {
        $import = ContactImport::create([
            'team_id'       => $teamId,
            'user_id'       => auth()->id(),
            'channel'       => $channel,
            'filename'      => $filename,
            'original_name' => $filename,
            'total_rows'    => 0,
            'imported_rows' => 0,
            'skipped_rows'  => 0,
            'invalid_rows'  => 0,
            'tag'           => 'imported:' . pathinfo($filename, PATHINFO_FILENAME),
            'status'        => 'running',
        ]);

        $seenPhones = [];
        $total = $imported = $skipped = $invalid = 0;

        DB::transaction(function () use (
            $teamId, $channel, $defaultCountry, $phoneColumn, $nameColumn,
            $optedInAtColumn, $customColumns, $rows, $import,
            &$total, &$imported, &$skipped, &$invalid, &$seenPhones
        ) {
            foreach ($rows as $row) {
                $total++;
                $raw = trim((string) ($row[$phoneColumn] ?? ''));
                try {
                    $normalized = $this->normalizer->normalize($raw, $defaultCountry);
                } catch (InvalidPhoneException) {
                    $invalid++;
                    continue;
                }

                if (isset($seenPhones[$normalized->e164])) {
                    $skipped++;
                    continue;
                }
                $seenPhones[$normalized->e164] = true;

                $meta = [];
                foreach ($customColumns as $col) {
                    if (isset($row[$col])) {
                        $meta[$col] = $row[$col];
                    }
                }
                if ($optedInAtColumn && ! empty($row[$optedInAtColumn])) {
                    $meta['opted_in_at'] = $row[$optedInAtColumn];
                }

                Contact::updateOrCreate(
                    ['team_id' => $teamId, 'phone' => $normalized->e164],
                    [
                        'name'         => $nameColumn ? ($row[$nameColumn] ?? null) : null,
                        'phone_country'=> $normalized->countryIso2,
                        'metadata'     => $meta,
                        'tags'         => [$import->tag],
                    ],
                );
                $imported++;
            }
        });

        $import->update([
            'total_rows'    => $total,
            'imported_rows' => $imported,
            'skipped_rows'  => $skipped,
            'invalid_rows'  => $invalid,
            'status'        => 'completed',
        ]);

        return new ImportResult(
            importId: $import->id,
            totalRows: $total,
            importedRows: $imported,
            skippedRows: $skipped,
            invalidRows: $invalid,
        );
    }
}
```

**Note for implementer:** the exact `Contact` columns (`metadata`, `tags`, `phone_country`) may not all exist. If a column is missing, either add it in a small additive migration in this task OR strip the field from the import call and note it as a Phase B TODO. Do NOT invent columns.

- [ ] **Step 5: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/PhoneContactImporterTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/Campaigns/PhoneContactImporter.php tests/Feature/Campaigns/PhoneContactImporterTest.php app/Models/Contact.php database/migrations/*
git commit -m "feat(campaigns): PhoneContactImporter — normalize + dedupe + upsert contacts"
```

---

## Task 6: WhatsAppSender (sole Wuzapi call site for campaigns)

**Files:**
- Create: `app/Services/Wuzapi/WhatsAppSender.php`
- Create: `tests/Feature/Campaigns/WhatsAppSenderTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Page;
use App\Models\Team;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_success_returns_sent_result(): void
    {
        Http::fake([
            '*chat/send/text*' => Http::response(['success' => true, 'data' => ['Id' => 'wa-msg-1']], 200),
        ]);

        $team = Team::factory()->create();
        $page = Page::factory()->create([
            'team_id'           => $team->id,
            'platform'          => 'whatsapp',
            'is_active'         => true,
            'platform_page_id'  => 'inst-x',
            'page_access_token' => encrypt('user-token'),
        ]);

        $result = app(WhatsAppSender::class)->send($page, '+201026361218', 'Hello!');

        $this->assertInstanceOf(SendResult::class, $result);
        $this->assertTrue($result->sent);
        $this->assertSame('wa-msg-1', $result->providerMessageId);
    }

    public function test_send_5xx_returns_transient_failure(): void
    {
        Http::fake(['*' => Http::response('boom', 502)]);
        $page = Page::factory()->create([
            'platform'          => 'whatsapp',
            'is_active'         => true,
            'platform_page_id'  => 'inst-x',
            'page_access_token' => encrypt('user-token'),
        ]);

        $result = app(WhatsAppSender::class)->send($page, '+201026361218', 'Hi');

        $this->assertFalse($result->sent);
        $this->assertTrue($result->transient);
    }
}
```

- [ ] **Step 2: Verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/WhatsAppSenderTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement WhatsAppSender**

Create `app/Services/Wuzapi/WhatsAppSender.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Wuzapi;

use App\Models\Page;
use App\Services\EvolutionApiService;
use Throwable;

readonly class SendResult
{
    public function __construct(
        public bool $sent,
        public bool $transient,
        public ?string $providerMessageId,
        public ?string $error,
    ) {}

    public static function ok(?string $id): self       { return new self(true,  false, $id, null); }
    public static function transient(string $err): self{ return new self(false, true,  null, $err); }
    public static function permanent(string $err): self{ return new self(false, false, null, $err); }
}

class WhatsAppSender
{
    public function __construct(private EvolutionApiService $wuzapi) {}

    public function send(Page $page, string $toE164, string $body): SendResult
    {
        try {
            $token = decrypt($page->page_access_token);
            $result = $this->wuzapi->sendText(
                (string) $page->platform_page_id,
                (string) $token,
                ltrim($toE164, '+'),
                $body,
            );

            if (($result['success'] ?? false) === true) {
                return SendResult::ok($result['data']['Id'] ?? null);
            }

            $err = $result['error'] ?? 'unknown Wuzapi failure';
            return $this->classify($err, $result['status'] ?? 0);
        } catch (Throwable $e) {
            return SendResult::transient($e->getMessage());
        }
    }

    private function classify(string $err, int $status): SendResult
    {
        // Permanent: invalid recipient, blocked, session-banned.
        $permanent = ['invalid', 'not registered', 'blocked', 'banned'];
        foreach ($permanent as $needle) {
            if (stripos($err, $needle) !== false) {
                return SendResult::permanent($err);
            }
        }
        // Anything else = transient (5xx, network, rate-limit).
        return SendResult::transient($err);
    }
}
```

**Note:** The `EvolutionApiService::sendText` signature returns whatever the underlying HTTP response is. Verify by reading `app/Services/EvolutionApiService.php` — if it doesn't currently return status/error keys, wrap it here or add those keys. Do NOT change `EvolutionApiService`'s existing contract used by `SendPlatformMessage`.

- [ ] **Step 4: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/WhatsAppSenderTest.php`
Expected: 2 tests passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Wuzapi/WhatsAppSender.php tests/Feature/Campaigns/WhatsAppSenderTest.php
git commit -m "feat(campaigns): WhatsAppSender — sole Wuzapi call site for campaign sends"
```

---

## Task 7: CampaignScheduler — jittered scheduled_at with dispatch ceiling

**Files:**
- Create: `app/Services/Campaigns/CampaignScheduler.php`
- Create: `tests/Feature/Campaigns/CampaignSchedulerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\Team;
use App\Services\Campaigns\CampaignScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CampaignSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_recipients_with_jittered_scheduled_at(): void
    {
        Carbon::setTestNow('2026-08-26 10:00:00');
        $team = Team::factory()->create();
        $campaign = Campaign::factory()->create([
            'team_id'            => $team->id,
            'platform'           => 'whatsapp',
            'jitter_min_seconds' => 30,
            'jitter_max_seconds' => 60,
            'status'             => 'draft',
        ]);
        $contacts = Contact::factory()->count(5)->create([
            'team_id' => $team->id,
        ])->each(fn ($c) => $c->update(['phone' => '+2010000000' . $c->id]));

        app(CampaignScheduler::class)->schedule(
            $campaign,
            $contacts->pluck('phone')->all(),
            channel: 'whatsapp',
        );

        $recipients = CampaignRecipient::where('campaign_id', $campaign->id)
            ->orderBy('scheduled_at')
            ->get();

        $this->assertCount(5, $recipients);
        $prev = null;
        foreach ($recipients as $r) {
            $this->assertSame('pending', $r->status);
            $this->assertSame('whatsapp', $r->channel);
            if ($prev) {
                $gap = $r->scheduled_at->diffInSeconds($prev);
                $this->assertGreaterThanOrEqual(30, $gap);
                $this->assertLessThanOrEqual(60, $gap);
            }
            $prev = $r->scheduled_at;
        }
    }

    public function test_dispatch_ceiling_never_exceeds_fifty_per_tick(): void
    {
        // Sentinel: 60 seed rows all eligible NOW.
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        CampaignRecipient::factory()->count(60)->create([
            'campaign_id'  => $campaign->id,
            'status'       => 'pending',
            'channel'      => 'whatsapp',
            'scheduled_at' => now()->subMinute(),
        ]);

        $claimed = app(CampaignScheduler::class)->claimNextBatch(50);
        $this->assertLessThanOrEqual(50, $claimed->count());
    }
}
```

- [ ] **Step 2: Verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/CampaignSchedulerTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement CampaignScheduler**

Create `app/Services/Campaigns/CampaignScheduler.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CampaignScheduler
{
    public const HARD_DISPATCH_CEILING = 50;

    /**
     * Create campaign_recipients rows with jittered scheduled_at.
     * Phase A: no warmup, no quiet hours (added in phase B).
     *
     * @param  array<int, string>  $identifiers  E.164 phones or emails
     */
    public function schedule(Campaign $campaign, array $identifiers, string $channel): void
    {
        $now = Carbon::now();
        $min = max(15, $campaign->jitter_min_seconds ?? 30);
        $max = max($min, $campaign->jitter_max_seconds ?? 60);
        $cursor = $now->copy();

        DB::transaction(function () use ($campaign, $identifiers, $channel, $min, $max, &$cursor) {
            foreach ($identifiers as $id) {
                CampaignRecipient::create([
                    'campaign_id'  => $campaign->id,
                    'channel'      => $channel,
                    'phone'        => $channel === 'whatsapp' ? $id : null,
                    'email'        => $channel === 'email'    ? $id : null,
                    'status'       => 'pending',
                    'attempts'     => 0,
                    'scheduled_at' => $cursor->copy(),
                ]);
                $cursor->addSeconds(random_int($min, $max));
            }

            $campaign->update(['total_contacts' => count($identifiers)]);
        });
    }

    /**
     * Atomically claim up to $limit pending rows whose scheduled_at is due.
     * Sets status = 'queued' to prevent double-dispatch on the next tick.
     */
    public function claimNextBatch(int $limit): Collection
    {
        $limit = min($limit, self::HARD_DISPATCH_CEILING);

        return DB::transaction(function () use ($limit) {
            $ids = CampaignRecipient::query()
                ->where('status', 'pending')
                ->where('scheduled_at', '<=', now())
                ->orderBy('scheduled_at')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id');

            if ($ids->isEmpty()) {
                return collect();
            }

            CampaignRecipient::whereIn('id', $ids)->update(['status' => 'queued']);
            return CampaignRecipient::whereIn('id', $ids)->get();
        });
    }
}
```

- [ ] **Step 4: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/CampaignSchedulerTest.php`
Expected: 2 tests passed.

- [ ] **Step 5: Commit**

```bash
git add app/Services/Campaigns/CampaignScheduler.php tests/Feature/Campaigns/CampaignSchedulerTest.php
git commit -m "feat(campaigns): CampaignScheduler — jittered scheduled_at + atomic batch claim"
```

---

## Task 8: SendCampaignWhatsAppJob

**Files:**
- Create: `app/Jobs/SendCampaignWhatsAppJob.php`
- Create: `tests/Feature/Campaigns/SendCampaignWhatsAppJobTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Page;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendCampaignWhatsAppJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_paused_campaign_bails_without_calling_sender(): void
    {
        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldNotReceive('send');
        $this->app->instance(WhatsAppSender::class, $sender);

        $campaign = Campaign::factory()->create(['status' => 'paused', 'platform' => 'whatsapp']);
        $recipient = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'queued', 'phone' => '+201026361218',
        ]);

        (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

        $this->assertSame('queued', $recipient->fresh()->status);
    }

    public function test_successful_send_marks_recipient_sent(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign->update(['sender_page_id' => $page->id, 'message_template' => 'Hi']);

        $recipient = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'queued', 'phone' => '+201026361218',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::ok('wa-1'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

        $this->assertSame('sent', $recipient->fresh()->status);
        $this->assertNotNull($recipient->fresh()->sent_at);
    }

    public function test_transient_failure_increments_attempts_and_reschedules(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign->update(['sender_page_id' => $page->id, 'message_template' => 'Hi']);

        $recipient = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'queued', 'attempts' => 0, 'phone' => '+201026361218',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::transient('5xx'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

        $fresh = $recipient->fresh();
        $this->assertSame('pending', $fresh->status);
        $this->assertSame(1, $fresh->attempts);
        $this->assertTrue($fresh->scheduled_at->isFuture());
    }

    public function test_permanent_failure_marks_failed(): void
    {
        $campaign = Campaign::factory()->create(['status' => 'active', 'platform' => 'whatsapp']);
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign->update(['sender_page_id' => $page->id, 'message_template' => 'Hi']);

        $recipient = CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'queued', 'phone' => '+201026361218',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->once()->andReturn(SendResult::permanent('invalid number'));
        $this->app->instance(WhatsAppSender::class, $sender);

        (new SendCampaignWhatsAppJob($recipient->id))->handle($sender);

        $this->assertSame('failed', $recipient->fresh()->status);
    }
}
```

- [ ] **Step 2: Verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/SendCampaignWhatsAppJobTest.php`
Expected: FAIL — class not found.

- [ ] **Step 3: Implement SendCampaignWhatsAppJob**

Create `app/Jobs/SendCampaignWhatsAppJob.php`:
```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Services\Campaigns\TemplateRenderer;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1; // We manage our own retries via scheduled_at bump.
    public int $timeout = 45;

    public function __construct(public int $recipientId)
    {
        $this->onQueue('campaigns'); // NEVER dispatch to urgent from campaign code.
    }

    public function handle(WhatsAppSender $sender): void
    {
        /** @var CampaignRecipient|null $r */
        $r = CampaignRecipient::with('campaign.senderPage')->find($this->recipientId);
        if (! $r || $r->status !== 'queued') {
            return; // Already handled or vanished.
        }

        $campaign = $r->campaign;
        if ($campaign->status !== 'active') {
            return;
        }

        $page = $campaign->senderPage;
        if (! $page || ! $page->is_active) {
            $r->update(['status' => 'failed', 'last_error' => 'sender page unavailable']);
            return;
        }

        // Phase A: simple render — no spintax, no AI. Straight template.
        $body = $this->renderBody($campaign->message_template, $r);

        $result = $sender->send($page, (string) $r->phone, $body);

        if ($result->sent) {
            $r->update([
                'status'  => 'sent',
                'sent_at' => now(),
            ]);
            $campaign->increment('sent_count');
            return;
        }

        if ($result->transient) {
            $attempts = $r->attempts + 1;
            if ($attempts >= 3) {
                $r->update(['status' => 'failed', 'attempts' => $attempts, 'last_error' => $result->error]);
                return;
            }
            $r->update([
                'status'       => 'pending',
                'attempts'     => $attempts,
                'last_error'   => $result->error,
                'scheduled_at' => now()->addSeconds((int) pow(2, $attempts) * 60),
            ]);
            return;
        }

        // Permanent.
        $r->update(['status' => 'failed', 'last_error' => $result->error]);
    }

    private function renderBody(string $template, CampaignRecipient $r): string
    {
        $name = trim((string) ($r->name ?? '')) ?: 'there';
        return str_replace(['{{name}}', '{{phone}}'], [$name, (string) $r->phone], $template);
    }
}
```

- [ ] **Step 4: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/SendCampaignWhatsAppJobTest.php`
Expected: 4 tests passed.

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SendCampaignWhatsAppJob.php tests/Feature/Campaigns/SendCampaignWhatsAppJobTest.php
git commit -m "feat(campaigns): SendCampaignWhatsAppJob — gate stack + retry classification"
```

---

## Task 9: DispatchScheduledCampaignMessages command with backpressure + circuit-breaker

**Files:**
- Create: `app/Console/Commands/DispatchScheduledCampaignMessages.php`
- Modify: `app/Console/Kernel.php` (or `bootstrap/app.php` in Laravel 11+ style)
- Create: `tests/Feature/Campaigns/DispatchScheduledCampaignMessagesTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchScheduledCampaignMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_jobs_for_eligible_pending_rows(): void
    {
        Queue::fake();
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status' => 'active', 'platform' => 'whatsapp', 'sender_page_id' => $page->id,
        ]);
        CampaignRecipient::factory()->count(3)->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'pending', 'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('campaigns:dispatch-scheduled')->assertSuccessful();

        Queue::assertPushedOn('campaigns', SendCampaignWhatsAppJob::class);
        Queue::assertPushed(SendCampaignWhatsAppJob::class, 3);
    }

    public function test_skips_when_sender_page_is_disconnected(): void
    {
        Queue::fake();
        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => false]);
        $campaign = Campaign::factory()->create([
            'status' => 'active', 'platform' => 'whatsapp', 'sender_page_id' => $page->id,
        ]);
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'pending', 'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('campaigns:dispatch-scheduled')->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertSame('paused', $campaign->fresh()->status);
        $this->assertSame('sender page unavailable', $campaign->fresh()->paused_reason);
    }

    public function test_backpressure_skips_when_campaigns_queue_depth_over_threshold(): void
    {
        Queue::fake();
        config(['campaigns.backpressure_threshold' => 5]);

        // Simulate high depth via a stub method — implementation may vary.
        $this->mock(\App\Services\Campaigns\QueueDepthProbe::class, function ($m) {
            $m->shouldReceive('depthFor')->with('campaigns')->andReturn(999);
        });

        $page = Page::factory()->create(['platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'status' => 'active', 'platform' => 'whatsapp', 'sender_page_id' => $page->id,
        ]);
        CampaignRecipient::factory()->create([
            'campaign_id' => $campaign->id, 'channel' => 'whatsapp',
            'status' => 'pending', 'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('campaigns:dispatch-scheduled')->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
```

- [ ] **Step 2: Create QueueDepthProbe helper**

Create `app/Services/Campaigns/QueueDepthProbe.php`:
```php
<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

/**
 * Direct in-process queue-depth measurement. Scheduler MUST NOT read /health/metrics.
 */
class QueueDepthProbe
{
    public function depthFor(string $queue): int
    {
        // Laravel's database queue stores jobs in the `jobs` table with a `queue` column.
        // Falls back to Queue::size() for other drivers (redis, sqs) once we migrate.
        if (config('queue.default') === 'database') {
            return (int) DB::table(config('queue.connections.database.table', 'jobs'))
                ->where('queue', $queue)
                ->count();
        }
        return (int) Queue::size($queue);
    }
}
```

- [ ] **Step 3: Verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/DispatchScheduledCampaignMessagesTest.php`
Expected: FAIL — command not found.

- [ ] **Step 4: Add config file**

Create `config/campaigns.php`:
```php
<?php

return [
    'backpressure_threshold'    => (int) env('CAMPAIGNS_BACKPRESSURE_THRESHOLD', 500),
    'dispatch_ceiling_per_tick' => (int) env('CAMPAIGNS_DISPATCH_CEILING', 50),
    'default_country'           => env('CAMPAIGNS_DEFAULT_COUNTRY', 'EG'),
];
```

- [ ] **Step 5: Implement the command**

Create `app/Console/Commands/DispatchScheduledCampaignMessages.php`:
```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Jobs\SendCampaignWhatsAppJob;
use App\Models\Campaign;
use App\Services\Campaigns\CampaignScheduler;
use App\Services\Campaigns\QueueDepthProbe;
use Illuminate\Console\Command;

class DispatchScheduledCampaignMessages extends Command
{
    protected $signature   = 'campaigns:dispatch-scheduled';
    protected $description = 'Dispatch due campaign_recipients onto the campaigns queue (gated).';

    public function handle(CampaignScheduler $scheduler, QueueDepthProbe $probe): int
    {
        // Gate 0: backpressure — never let the scheduler blow up the queue.
        $depth = $probe->depthFor('campaigns');
        $threshold = (int) config('campaigns.backpressure_threshold', 500);
        if ($depth >= $threshold) {
            $this->warn("Backpressure: campaigns queue depth {$depth} >= {$threshold}. Skipping tick.");
            return self::SUCCESS;
        }

        // Gate 1+2: page circuit-breaker — for every active campaign, auto-pause if its sender is dead.
        foreach (Campaign::query()->where('status', 'active')->with('senderPage')->get() as $c) {
            if (! $c->senderPage || ! $c->senderPage->is_active) {
                $c->update(['status' => 'paused', 'paused_reason' => 'sender page unavailable']);
                $this->warn("Paused campaign #{$c->id}: sender page unavailable.");
            }
        }

        // Gate 5: claim + dispatch.
        $ceiling = (int) config('campaigns.dispatch_ceiling_per_tick', 50);
        $batch = $scheduler->claimNextBatch($ceiling);

        foreach ($batch as $r) {
            match ($r->channel) {
                'whatsapp' => SendCampaignWhatsAppJob::dispatch($r->id),
                'email'    => SendCampaignEmailJob::dispatch($r->id), // existing
                default    => $this->error("Unknown channel: {$r->channel}"),
            };
        }

        $this->info("Dispatched {$batch->count()} campaign message(s).");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 6: Schedule it every minute**

In `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` schedule() method, add:
```php
Schedule::command('campaigns:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

- [ ] **Step 7: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/DispatchScheduledCampaignMessagesTest.php`
Expected: 3 tests passed.

- [ ] **Step 8: Commit**

```bash
git add app/Console/Commands/DispatchScheduledCampaignMessages.php app/Services/Campaigns/QueueDepthProbe.php config/campaigns.php routes/console.php app/Console/Kernel.php tests/Feature/Campaigns/DispatchScheduledCampaignMessagesTest.php
git commit -m "feat(campaigns): scheduler command with backpressure + page circuit-breaker"
```

---

## Task 10: WhatsAppWizard Livewire component

**Files:**
- Create: `app/Livewire/Campaigns/WhatsAppWizard.php`
- Create: `resources/views/livewire/campaigns/whats-app-wizard.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Campaigns/WhatsAppWizardTest.php`

- [ ] **Step 1: Write the failing Livewire test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Livewire\Campaigns\WhatsAppWizard;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_flow_from_upload_to_launch(): void
    {
        Storage::fake('local');
        $team = Team::factory()->create(['features' => ['bulk_whatsapp_campaigns' => true]]);
        $user = User::factory()->create(['current_team_id' => $team->id]);
        Page::factory()->create([
            'team_id' => $team->id, 'platform' => 'whatsapp', 'is_active' => true,
        ]);

        $csv = "phone,name\n01026361218,Ahmed\n+971501234567,Fatima\n";
        $file = UploadedFile::fake()->createWithContent('list.csv', $csv);

        Livewire::actingAs($user)
            ->test(WhatsAppWizard::class)
            ->set('file', $file)
            ->call('advanceToMap')
            ->assertSet('step', 'map')
            ->set('phoneColumn', 'phone')
            ->set('nameColumn', 'name')
            ->set('defaultCountry', 'EG')
            ->call('advanceToCompose')
            ->assertSet('step', 'compose')
            ->set('campaignName', 'August Promo')
            ->set('body', 'Hi {{name}}, we have a deal for you')
            ->call('advanceToReview')
            ->assertSet('step', 'review')
            ->call('launch')
            ->assertSet('step', 'launched');

        $this->assertDatabaseHas('campaigns', [
            'name' => 'August Promo', 'platform' => 'whatsapp', 'status' => 'active',
        ]);
        $this->assertDatabaseCount('campaign_recipients', 2);
    }

    public function test_wizard_is_gated_by_feature_flag(): void
    {
        $team = Team::factory()->create(['features' => []]); // flag off
        $user = User::factory()->create(['current_team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(WhatsAppWizard::class)
            ->assertForbidden();
    }
}
```

- [ ] **Step 2: Verify failure**

Run: `vendor/bin/pest tests/Feature/Campaigns/WhatsAppWizardTest.php`
Expected: FAIL — component not found.

- [ ] **Step 3: Implement WhatsAppWizard**

Create `app/Livewire/Campaigns/WhatsAppWizard.php`:
```php
<?php

declare(strict_types=1);

namespace App\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Page;
use App\Services\Campaigns\CampaignScheduler;
use App\Services\Campaigns\PhoneContactImporter;
use App\Services\Email\SpreadsheetParser;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WhatsAppWizard extends Component
{
    use WithFileUploads;

    public string $step = 'upload';
    public $file = null;
    public ?string $storedPath = null;
    public array $detectedHeaders = [];
    public array $previewRows = [];

    public string $phoneColumn = '';
    public string $nameColumn = '';
    public array $customColumns = [];
    public string $defaultCountry = 'EG';

    public string $campaignName = '';
    public string $body = "Hi {{name}},\n\n";
    public ?int $senderPageId = null;
    public int $jitterMin = 30;
    public int $jitterMax = 60;

    public ?int $importId = null;
    public int $importedCount = 0;
    public ?int $createdCampaignId = null;

    public function mount(): void
    {
        $team = Auth::user()?->currentTeam;
        if (! $team || ! $team->hasFeature('bulk_whatsapp_campaigns')) {
            throw new AccessDeniedHttpException('Bulk WhatsApp campaigns is not enabled for this team.');
        }

        $first = Page::where('team_id', $team->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->first();
        $this->senderPageId = $first?->id;
        $this->defaultCountry = config('campaigns.default_country', 'EG');
    }

    #[Computed]
    public function whatsappSenders()
    {
        return Page::where('team_id', Auth::user()->currentTeam->id)
            ->where('platform', 'whatsapp')
            ->where('is_active', true)
            ->get();
    }

    public function advanceToMap(SpreadsheetParser $parser): void
    {
        $this->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
        ]);

        $this->storedPath = $this->file->store("imports/{$this->currentTeamId()}");
        [$this->detectedHeaders, $this->previewRows] = $parser->previewFromPath(
            storage_path("app/{$this->storedPath}"), 20
        );

        $this->step = 'map';
    }

    public function advanceToCompose(PhoneContactImporter $importer, SpreadsheetParser $parser): void
    {
        $this->validate([
            'phoneColumn'    => 'required|in:' . implode(',', $this->detectedHeaders),
            'defaultCountry' => 'required|string|size:2',
        ]);

        $rows = iterator_to_array($parser->streamRowsFromPath(storage_path("app/{$this->storedPath}")));
        $result = $importer->import(
            teamId: $this->currentTeamId(),
            channel: 'whatsapp',
            filename: basename($this->storedPath),
            defaultCountry: strtoupper($this->defaultCountry),
            phoneColumn: $this->phoneColumn,
            nameColumn: $this->nameColumn ?: null,
            optedInAtColumn: null,
            customColumns: $this->customColumns,
            rows: $rows,
        );

        $this->importId = $result->importId;
        $this->importedCount = $result->importedRows;
        $this->step = 'compose';
    }

    public function advanceToReview(): void
    {
        $this->validate([
            'campaignName' => 'required|string|max:100',
            'body'         => 'required|string|max:2000',
            'senderPageId' => 'required|integer',
            'jitterMin'    => 'required|integer|min:15|max:600',
            'jitterMax'    => 'required|integer|min:15|max:600|gte:jitterMin',
        ]);
        $this->step = 'review';
    }

    public function launch(CampaignScheduler $scheduler): void
    {
        $team = Auth::user()->currentTeam;

        $campaign = Campaign::create([
            'team_id'            => $team->id,
            'created_by'         => Auth::id(),
            'name'               => $this->campaignName,
            'type'               => 'promotion',
            'platform'           => 'whatsapp',
            'sender_page_id'     => $this->senderPageId,
            'message_template'   => $this->body,
            'target_criteria'    => ['contact_import_id' => $this->importId],
            'jitter_min_seconds' => $this->jitterMin,
            'jitter_max_seconds' => $this->jitterMax,
            'status'             => 'active',
        ]);

        $phones = Contact::where('team_id', $team->id)
            ->whereJsonContains('tags', "imported:" . pathinfo($this->storedPath, PATHINFO_FILENAME))
            ->pluck('phone')
            ->filter()
            ->values()
            ->all();

        $scheduler->schedule($campaign, $phones, channel: 'whatsapp');

        $this->createdCampaignId = $campaign->id;
        $this->step = 'launched';
    }

    private function currentTeamId(): int
    {
        return (int) Auth::user()->currentTeam->id;
    }

    public function render()
    {
        return view('livewire.campaigns.whats-app-wizard')
            ->layout('layouts.app', ['title' => 'New WhatsApp Campaign']);
    }
}
```

- [ ] **Step 4: Create the Blade view (minimum viable)**

Create `resources/views/livewire/campaigns/whats-app-wizard.blade.php`:
```blade
<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-2xl font-semibold mb-6">New WhatsApp Campaign</h1>

    @if ($step === 'upload')
        <div class="space-y-4">
            <flux:input type="file" wire:model="file" accept=".csv,.xlsx" />
            <flux:button wire:click="advanceToMap" variant="primary">Next</flux:button>
        </div>

    @elseif ($step === 'map')
        <div class="space-y-4">
            <flux:select wire:model="phoneColumn" label="Phone column">
                @foreach ($detectedHeaders as $h)
                    <flux:select.option value="{{ $h }}">{{ $h }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:select wire:model="nameColumn" label="Name column (optional)">
                <flux:select.option value="">— none —</flux:select.option>
                @foreach ($detectedHeaders as $h)
                    <flux:select.option value="{{ $h }}">{{ $h }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:input wire:model="defaultCountry" label="Default country (ISO2)" />
            <flux:button wire:click="advanceToCompose" variant="primary">Import & Continue</flux:button>
        </div>

    @elseif ($step === 'compose')
        <div class="space-y-4">
            <p class="text-sm text-zinc-500">Imported {{ $importedCount }} contacts.</p>
            <flux:input wire:model="campaignName" label="Campaign name" />
            <flux:select wire:model="senderPageId" label="Send from">
                @foreach ($this->whatsappSenders as $p)
                    <flux:select.option value="{{ $p->id }}">{{ $p->name }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:textarea wire:model="body" label="Message (supports {{ '{{name}}' }})" rows="6" />
            <div class="flex gap-3">
                <flux:input wire:model="jitterMin" type="number" label="Jitter min (sec)" />
                <flux:input wire:model="jitterMax" type="number" label="Jitter max (sec)" />
            </div>
            <flux:button wire:click="advanceToReview" variant="primary">Review</flux:button>
        </div>

    @elseif ($step === 'review')
        <div class="space-y-4">
            <p>Ready to send to <strong>{{ $importedCount }}</strong> recipients with {{ $jitterMin }}–{{ $jitterMax }}s jitter.</p>
            <flux:button wire:click="launch" variant="primary">Launch campaign</flux:button>
        </div>

    @elseif ($step === 'launched')
        <div class="space-y-4">
            <p class="text-green-600">Campaign launched. Sending in progress.</p>
            @if ($createdCampaignId)
                <a href="{{ route('campaigns.show', $createdCampaignId) }}" class="text-blue-600 underline">View campaign</a>
            @endif
        </div>
    @endif
</div>
```

- [ ] **Step 5: Register the route**

In `routes/web.php`, add inside the auth middleware group:
```php
Route::get('/campaigns/whatsapp/new', \App\Livewire\Campaigns\WhatsAppWizard::class)
    ->name('campaigns.whatsapp.new');
```

- [ ] **Step 6: Ensure SpreadsheetParser exposes path-based helpers**

Check `app/Services/Email/SpreadsheetParser.php`. If it only accepts uploaded-file paths internally, add:
- `previewFromPath(string $path, int $rows): array` returning `[$headers, $previewRows]`
- `streamRowsFromPath(string $path): iterable`

If those methods already exist under different names, adjust the wizard to use them. Do NOT duplicate the parser.

- [ ] **Step 7: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/WhatsAppWizardTest.php`
Expected: 2 tests passed.

- [ ] **Step 8: Commit**

```bash
git add app/Livewire/Campaigns/WhatsAppWizard.php resources/views/livewire/campaigns/whats-app-wizard.blade.php routes/web.php app/Services/Email/SpreadsheetParser.php tests/Feature/Campaigns/WhatsAppWizardTest.php
git commit -m "feat(campaigns): WhatsAppWizard Livewire — upload → map → compose → review → launched"
```

---

## Task 11: Test-send endpoint with 5/hr/user throttle

**Files:**
- Create: `app/Http/Controllers/CampaignTestSendController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Campaigns/TestSendThrottleTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

use App\Models\Campaign;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Wuzapi\SendResult;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TestSendThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_five_test_sends_succeed_sixth_throttled(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['current_team_id' => $team->id]);
        $page = Page::factory()->create(['team_id' => $team->id, 'platform' => 'whatsapp', 'is_active' => true]);
        $campaign = Campaign::factory()->create([
            'team_id' => $team->id, 'platform' => 'whatsapp',
            'sender_page_id' => $page->id, 'message_template' => 'Hi {{name}}',
        ]);

        $sender = Mockery::mock(WhatsAppSender::class);
        $sender->shouldReceive('send')->times(5)->andReturn(SendResult::ok('id'));
        $this->app->instance(WhatsAppSender::class, $sender);

        $this->actingAs($user);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson("/campaigns/{$campaign->id}/test-send", [
                'phone' => '+201026361218',
                'name'  => 'Test',
            ])->assertOk();
        }

        $this->postJson("/campaigns/{$campaign->id}/test-send", [
            'phone' => '+201026361218',
            'name'  => 'Test',
        ])->assertStatus(429);
    }
}
```

- [ ] **Step 2: Implement the controller**

Create `app/Http/Controllers/CampaignTestSendController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\Wuzapi\WhatsAppSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class CampaignTestSendController extends Controller
{
    public function __invoke(Request $request, Campaign $campaign, WhatsAppSender $sender): JsonResponse
    {
        $this->authorize('update', $campaign); // uses existing policy or replace with team check
        abort_unless($campaign->platform === 'whatsapp', 400, 'Test send only implemented for whatsapp in phase A.');

        $key = 'campaigns:test-send:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(['error' => 'Too many test sends; try again in an hour.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'phone' => 'required|string',
            'name'  => 'nullable|string|max:80',
        ]);

        $body = str_replace(['{{name}}', '{{phone}}'], [$data['name'] ?? 'there', $data['phone']],
            $campaign->message_template);

        $result = $sender->send($campaign->senderPage, $data['phone'], $body);

        return response()->json([
            'sent'  => $result->sent,
            'error' => $result->error,
        ], $result->sent ? 200 : 502);
    }
}
```

- [ ] **Step 3: Register the route**

In `routes/web.php`:
```php
Route::post('/campaigns/{campaign}/test-send', \App\Http\Controllers\CampaignTestSendController::class)
    ->middleware(['auth'])
    ->name('campaigns.test-send');
```

- [ ] **Step 4: If `authorize` fails (no policy), swap for team check**

If `CampaignPolicy` doesn't exist, replace the `$this->authorize(...)` line with:
```php
abort_unless($campaign->team_id === Auth::user()->current_team_id, 403);
```

- [ ] **Step 5: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/TestSendThrottleTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CampaignTestSendController.php routes/web.php tests/Feature/Campaigns/TestSendThrottleTest.php
git commit -m "feat(campaigns): test-send endpoint with 5/hr/user rate limit"
```

---

## Task 12: Wire test-send into WhatsAppWizard as a step

**Files:**
- Modify: `app/Livewire/Campaigns/WhatsAppWizard.php`
- Modify: `resources/views/livewire/campaigns/whats-app-wizard.blade.php`

- [ ] **Step 1: Add a `test` step between `compose` and `review`**

In `WhatsAppWizard.php`, add properties + method:
```php
public string $testPhone = '';
public string $testName = 'Test';
public ?bool $testResult = null;
public ?string $testError = null;

public function advanceToTest(): void
{
    $this->validate([
        'body'         => 'required|string',
        'senderPageId' => 'required|integer',
    ]);
    $this->step = 'test';
}

public function sendTest(\App\Services\Wuzapi\WhatsAppSender $sender): void
{
    $this->validate(['testPhone' => 'required|string']);
    $page = \App\Models\Page::findOrFail($this->senderPageId);
    $body = str_replace(['{{name}}', '{{phone}}'], [$this->testName, $this->testPhone], $this->body);
    $result = $sender->send($page, $this->testPhone, $body);
    $this->testResult = $result->sent;
    $this->testError = $result->error;
}
```

Change `advanceToCompose` chain so that `advanceToReview` follows `test` not `compose`. Update `advanceToReview` guard to require `testResult === true` (or offer a "skip test" override with warning).

- [ ] **Step 2: Update the Blade view**

Insert `@elseif ($step === 'test')` block between compose and review:
```blade
@elseif ($step === 'test')
    <div class="space-y-4">
        <p class="text-sm text-zinc-500">Send one test message to verify the format and connection.</p>
        <flux:input wire:model="testPhone" label="Test phone (E.164)" placeholder="+201026361218" />
        <flux:input wire:model="testName"  label="Test name" />
        <flux:button wire:click="sendTest">Send test</flux:button>

        @if ($testResult === true)
            <p class="text-green-600">✅ Test message sent. Check WhatsApp.</p>
            <flux:button wire:click="advanceToReview" variant="primary">Looks good — review</flux:button>
        @elseif ($testResult === false)
            <p class="text-red-600">❌ Test failed: {{ $testError }}</p>
        @endif
    </div>
@endif
```

Also change the compose step's button to `wire:click="advanceToTest"` instead of `advanceToReview`.

- [ ] **Step 3: Update the wizard test to include the test step**

In `WhatsAppWizardTest::test_full_flow_from_upload_to_launch`, insert between compose and review:
```php
$this->app->instance(\App\Services\Wuzapi\WhatsAppSender::class, Mockery::mock(\App\Services\Wuzapi\WhatsAppSender::class, function ($m) {
    $m->shouldReceive('send')->once()->andReturn(\App\Services\Wuzapi\SendResult::ok('id'));
}));

// ...->call('advanceToCompose')...
->call('advanceToTest')->assertSet('step', 'test')
->set('testPhone', '+201026361218')
->call('sendTest')->assertSet('testResult', true)
->call('advanceToReview')->assertSet('step', 'review')
// ...
```

- [ ] **Step 4: Re-run tests**

Run: `vendor/bin/pest tests/Feature/Campaigns/WhatsAppWizardTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Campaigns/WhatsAppWizard.php resources/views/livewire/campaigns/whats-app-wizard.blade.php tests/Feature/Campaigns/WhatsAppWizardTest.php
git commit -m "feat(campaigns): inline test-send step in WhatsAppWizard"
```

---

## Task 13: Channel-gate the Campaigns\Index modal + add email option

**Files:**
- Modify: `app/Livewire/Campaigns/Index.php`
- Modify: `resources/views/livewire/campaigns/index.blade.php`

- [ ] **Step 1: Expand platform validation**

In `Index.php::save()`, change:
```php
'platform' => 'required|string|in:facebook,instagram,telegram,whatsapp',
```
to:
```php
'platform' => 'required|string|in:facebook,instagram,telegram,whatsapp,email',
```

- [ ] **Step 2: Add a redirect method for CSV-mode channels**

In `Index.php`, add:
```php
public bool $csvMode = false;

public function updatedPlatform(): void
{
    parent::__call('updatedPlatform', []); // preserve existing logic if it exists
    $this->csvMode = in_array($this->platform, ['whatsapp', 'email'], true);
}

public function goToWizard(): void
{
    if ($this->platform === 'whatsapp') {
        $this->redirect(route('campaigns.whatsapp.new'), navigate: true);
    } elseif ($this->platform === 'email') {
        $this->redirect(route('campaigns.email.new'), navigate: true);
    }
}
```

(The exact route name for email is what the existing wizard uses — verify in `routes/web.php`.)

- [ ] **Step 3: Update the Blade modal**

In `resources/views/livewire/campaigns/index.blade.php`, add to the platform picker:
```blade
<flux:select.option value="email">Email</flux:select.option>
```

And gate a new CSV-upload region:
```blade
@if (in_array($platform, ['whatsapp', 'email']))
    <div class="rounded border border-dashed p-4 space-y-2 bg-zinc-50">
        <p class="text-sm">This channel supports bulk CSV upload.</p>
        <flux:button wire:click="goToWizard" variant="primary">Open upload wizard →</flux:button>
    </div>
@endif
```

- [ ] **Step 4: Manual verification**

Run: `php artisan serve` (or use Herd)
Open `https://one-inbox.test/campaigns`, click "New campaign", pick "WhatsApp" → CSV region should appear with "Open upload wizard" button. Click → lands on `/campaigns/whatsapp/new`. Same for "Email".

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Campaigns/Index.php resources/views/livewire/campaigns/index.blade.php
git commit -m "feat(campaigns): channel-gate CSV upload in Index modal + add email option"
```

---

## Task 14: /health/metrics observability endpoint

**Files:**
- Create: `app/Http/Controllers/HealthMetricsController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Implement the controller**

Create `app/Http/Controllers/HealthMetricsController.php`:
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Campaigns\QueueDepthProbe;
use Illuminate\Http\JsonResponse;

class HealthMetricsController extends Controller
{
    public function __invoke(QueueDepthProbe $probe): JsonResponse
    {
        return response()->json([
            'queues' => [
                'urgent'    => $probe->depthFor('urgent'),
                'default'   => $probe->depthFor('default'),
                'campaigns' => $probe->depthFor('campaigns'),
            ],
            'note' => 'Observability only — the scheduler does NOT read this endpoint.',
        ]);
    }
}
```

- [ ] **Step 2: Register the route**

In `routes/web.php` (no auth — this is for external monitoring, protect via HTTP basic or firewall if sensitive):
```php
Route::get('/health/metrics', \App\Http\Controllers\HealthMetricsController::class)
    ->name('health.metrics');
```

- [ ] **Step 3: Smoke test**

Run: `php artisan serve` then `curl http://127.0.0.1:8000/health/metrics`
Expected: JSON with `queues` object and note.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/HealthMetricsController.php routes/web.php
git commit -m "feat(observability): /health/metrics endpoint (external monitoring only)"
```

---

## Task 15: systemd unit for `campaigns` queue + local NSSM note

**Files:**
- Create: `deploy/systemd/one-inbox-queue-campaigns.service`
- Modify: `README.md`

- [ ] **Step 1: Write the systemd unit**

Create `deploy/systemd/one-inbox-queue-campaigns.service`:
```ini
[Unit]
Description=One Inbox — campaigns queue worker
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=simple
User=deploy
Group=deploy
WorkingDirectory=/var/www/ot1-pro.com
ExecStart=/usr/bin/php artisan queue:work --queue=campaigns --tries=3 --timeout=60 --sleep=5 --max-jobs=1000 --max-time=3600
Restart=always
RestartSec=3
StandardOutput=append:/var/log/one-inbox/queue-campaigns.log
StandardError=append:/var/log/one-inbox/queue-campaigns.log

[Install]
WantedBy=multi-user.target
```

- [ ] **Step 2: Document the deploy steps in README**

Append to `README.md` under a "Production" section:
```markdown
### campaigns queue worker (new — phase A of bulk-campaigns feature)

Install once on the VPS:
```
sudo cp deploy/systemd/one-inbox-queue-campaigns.service /etc/systemd/system/
sudo mkdir -p /var/log/one-inbox && sudo chown deploy:deploy /var/log/one-inbox
sudo systemctl daemon-reload
sudo systemctl enable --now one-inbox-queue-campaigns
```

Verify:
```
sudo systemctl status one-inbox-queue-campaigns
```
```

- [ ] **Step 3: Local dev — add Terminal 6 note**

In the "dev server startup" README section, add:
```
# Terminal 6 - Campaigns queue worker (bulk sends stay off `urgent`)
php artisan queue:work --queue=campaigns --sleep=5
```

- [ ] **Step 4: Commit**

```bash
git add deploy/systemd/one-inbox-queue-campaigns.service README.md
git commit -m "chore(campaigns): systemd unit for campaigns queue + local dev docs"
```

---

## Task 16: Enable feature flag for OT1 team + dogfood

**Files:**
- Create: `database/seeders/EnableBulkWhatsAppForOt1Seeder.php` (optional; can be done via tinker)

- [ ] **Step 1: Enable via tinker on prod**

SSH to prod:
```bash
ssh root@187.77.67.94
cd /var/www/ot1-pro.com
sudo -u deploy php artisan tinker
```

Inside tinker:
```php
$team = \App\Models\Team::where('name', 'like', '%OT1%')->first();
$team->features = array_merge($team->features ?? [], ['bulk_whatsapp_campaigns' => true]);
$team->save();
```

- [ ] **Step 2: Smoke-test end-to-end on prod**

- Visit `https://ot1-pro.com/campaigns`
- Pick "WhatsApp" → click "Open upload wizard"
- Upload a tiny CSV (2 rows) with your own numbers
- Test-send to your own number, verify receipt
- Launch → wait one minute → verify both messages arrive with 30–60s gap

- [ ] **Step 3: Verify the campaigns worker is running**

```bash
sudo systemctl status one-inbox-queue-campaigns
```
Expected: `active (running)`.

- [ ] **Step 4: Verify `urgent` queue latency unaffected**

```bash
sudo -u deploy php artisan tinker
```
```php
\App\Services\Campaigns\QueueDepthProbe::class; // sanity
DB::table('jobs')->where('queue', 'urgent')->count(); // should be low
```

Send a WhatsApp to a connected team page from another phone; verify the auto-reply arrives in < 5 seconds (existing behavior).

- [ ] **Step 5: No-code commit — record the smoke-test result**

If everything works, commit an empty marker or update `tasks/journal.md` with the phase-a launch entry. If anything fails, roll back the feature flag:
```php
$team->features = collect($team->features ?? [])->except('bulk_whatsapp_campaigns')->toArray();
$team->save();
```

Then diagnose before opening the flag to any other team.

---

## Self-Review

**Spec coverage:**
- ✅ CSV upload with column mapping → Tasks 5, 10.
- ✅ Phone normalization via libphonenumber with default-country picker → Tasks 4, 10.
- ✅ Test-send with 5/hr throttle → Tasks 11, 12.
- ✅ Jitter as varied `scheduled_at` (never `sleep()`) → Task 7.
- ✅ Feature flag `teams.features->>'bulk_whatsapp_campaigns'` → Tasks 1, 10, 16.
- ✅ Email added to Index modal + channel-gated CSV → Task 13.
- ✅ New `campaigns` queue + systemd unit → Task 15.
- ✅ Page circuit-breaker in scheduler → Task 9.
- ✅ Queue-depth backpressure via direct in-process query → Task 9.
- ✅ EXPLAIN gate on hot index → Task 3.
- ✅ Sole Wuzapi call site (`WhatsAppSender`) → Task 6.
- ✅ `/health/metrics` observability endpoint (scheduler does NOT call it) → Task 14.
- ✅ Dogfood on OT1 team → Task 16.

**Placeholder scan:** No TBDs or "add appropriate error handling" fluff. Two spots flag the implementer to check existing signatures before diverging (Task 5 Contact columns, Task 6 EvolutionApiService return shape, Task 10 SpreadsheetParser methods) — these are honest verification steps, not placeholders.

**Type consistency:** `SendResult`, `NormalizedPhone`, `ImportResult`, `WhatsAppSender::send()`, `CampaignScheduler::schedule()` / `claimNextBatch()`, `QueueDepthProbe::depthFor()` — signatures consistent across all task references.

**Deferred to Phase B:** warmup ramp, `page_send_counters`, quiet hours, `contact_suppressions` unified table, AI personalization, `ai-bulk` queue, batch-claim replacing single-batch, backpressure hysteresis (500/300).

**Deferred to Phase C:** spintax, auto-pause on signals, `campaign_events` table + retention + daily aggregation, reply-inbox filter.

---

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-08-26-bulk-whatsapp-campaigns-phase-a.md`. Two execution options:

**1. Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration. Best fit for a 16-task plan where each task is a clean commit.

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints. Slower per task but you see everything happen.

**Which approach?**
