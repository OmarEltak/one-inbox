<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactImport;
use App\Services\Campaigns\PhoneContactImporter;
use App\Services\Email\SpreadsheetParser;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Phase 3 of the 5-phase load-management plan (docs/OT1_LIMITS.md §11).
 *
 * Moves the campaign-recipient spreadsheet parse out of the Livewire HTTP
 * request. The wizard creates a ContactImport row + dispatches this job,
 * then polls the row for progress. Removes the sync-parse FPM landmine
 * and lets users close the tab while the import runs.
 *
 * Per-team throttle mirrors TranscribeAudio's `Cache::add()` pattern — one
 * import in-flight per team at a time so a single user can't monopolize
 * the queue worker with a big list.
 */
class ImportCampaignRecipients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600; // 10 min — 100k rows realistic

    /**
     * @param  array{
     *     phoneColumn: string,
     *     defaultCountry: string,
     *     nameColumn: ?string,
     *     customColumns: array<int, string>,
     * } $mapping
     */
    public function __construct(
        public int    $contactImportId,
        public string $storedPath,
        public string $extension,
        public array  $mapping,
    ) {
        $this->onQueue('default');
    }

    public function handle(PhoneContactImporter $importer): void
    {
        $import = ContactImport::find($this->contactImportId);
        if ($import === null) {
            return;
        }

        // Per-team throttle. 1 in-flight per team — if the current team
        // already has one running, release with a 10s delay. Frees other
        // teams to run concurrently.
        $lockKey = "campaign:import:inflight:{$import->team_id}";
        if (! Cache::add($lockKey, 1, 900)) {
            $this->release(10);
            return;
        }

        try {
            $import->update(['status' => ContactImport::STATUS_PROCESSING]);

            $absolute = Storage::path($this->storedPath);
            $parser   = new SpreadsheetParser($absolute, $this->extension);
            $rows     = iterator_to_array($parser->stream());

            $result = $importer->import(
                teamId:          $import->team_id,
                channel:         $import->channel ?: 'whatsapp',
                filename:        $import->original_name ?: $import->filename,
                defaultCountry:  $this->mapping['defaultCountry'],
                phoneColumn:     $this->mapping['phoneColumn'],
                nameColumn:      $this->mapping['nameColumn']    ?? null,
                optedInAtColumn: null,
                customColumns:   $this->mapping['customColumns'] ?? [],
                rows:            $rows,
            );

            // PhoneContactImporter creates its OWN ContactImport row for
            // bookkeeping. Sync final counts back to our wizard-visible row,
            // then delete the duplicate so the team's import list stays clean.
            $created = ContactImport::find($result->importId);
            if ($created && $created->id !== $import->id) {
                $import->update([
                    'total_rows'    => $result->totalRows,
                    'imported_rows' => $result->importedRows,
                    'skipped_rows'  => $result->skippedRows,
                    'invalid_rows'  => $result->invalidRows,
                    'status'        => ContactImport::STATUS_COMPLETED,
                    'tag'           => $created->tag,
                ]);
                $created->delete();
            } else {
                $import->update([
                    'total_rows'    => $result->totalRows,
                    'imported_rows' => $result->importedRows,
                    'skipped_rows'  => $result->skippedRows,
                    'invalid_rows'  => $result->invalidRows,
                    'status'        => ContactImport::STATUS_COMPLETED,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Campaign recipient import failed', [
                'contact_import_id' => $import->id,
                'team_id'           => $import->team_id,
                'error'             => $e->getMessage(),
            ]);
            $import->update([
                'status'     => ContactImport::STATUS_FAILED,
                'last_error' => mb_substr($e->getMessage(), 0, 500),
            ]);
        } finally {
            Cache::forget($lockKey);
        }
    }
}
