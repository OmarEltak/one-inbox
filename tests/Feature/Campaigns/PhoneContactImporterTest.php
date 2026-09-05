<?php

declare(strict_types=1);

namespace Tests\Feature\Campaigns;

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
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $team = Team::factory()->create(['owner_id' => $user->id]);
        $importer = app(PhoneContactImporter::class);

        $rows = [
            ['phone' => '01026361218', 'name' => 'Ahmed'],
            ['phone' => '+971501234567', 'name' => 'Fatima'],
            ['phone' => 'garbage',       'name' => 'Bad'],
            ['phone' => '01026361218',   'name' => 'DupAhmed'],
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
        $this->assertSame(1, $result->skippedRows);
        $this->assertSame(1, $result->invalidRows);
        $this->assertDatabaseCount('contacts', 2);
        $this->assertDatabaseHas('contacts', ['phone' => '+201026361218', 'team_id' => $team->id]);
        $this->assertDatabaseHas('contacts', ['phone' => '+971501234567', 'team_id' => $team->id]);

        /** @var ContactImport $import */
        $import = ContactImport::sole();
        $this->assertSame('whatsapp', $import->channel);
        $this->assertSame(4, $import->total_rows);
    }
}
