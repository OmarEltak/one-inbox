<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactImport;
use App\Services\Email\ContactImporter;
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
 * Phase 3b of the 5-phase load-management plan (docs/OT1_LIMITS.md §11).
 *
 * Email-wizard mirror of ImportCampaignRecipients — kills the sync-parse
 * FPM landmine on the EmailWizard@confirmMapAndImport path. Same per-team
 * throttle, same status/progress reporting via the ContactImport row that
 * the wizard polls.
 */
class ImportEmailRecipients implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600;

    /**
     * @param  array{email: string, name: ?string, custom: array<int,string>} $map
     */
    public function __construct(
        public int    $contactImportId,
        public string $storedPath,
        public string $extension,
        public array  $map,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $import = ContactImport::find($this->contactImportId);
        if ($import === null) {
            return;
        }

        $lockKey = "email:import:inflight:{$import->team_id}";
        if (! Cache::add($lockKey, 1, 900)) {
            $this->release(10);
            return;
        }

        try {
            $import->update(['status' => ContactImport::STATUS_PROCESSING]);

            $absolute = Storage::disk('local')->path($this->storedPath);
            $parser   = new SpreadsheetParser($absolute, $this->extension);
            $importer = new ContactImporter($parser);

            $real = $importer->import(
                teamId:       $import->team_id,
                userId:       $import->user_id,
                filename:     $import->filename,
                originalName: $import->original_name,
                map:          $this->map,
            );

            // ContactImporter creates its own ContactImport row for bookkeeping.
            // Roll its totals onto our wizard-visible row + delete the duplicate.
            $import->update([
                'total_rows'    => $real->total_rows,
                'imported_rows' => $real->imported_rows,
                'skipped_rows'  => $real->skipped_rows,
                'invalid_rows'  => $real->invalid_rows,
                'status'        => ContactImport::STATUS_COMPLETED,
                'tag'           => $real->tag,
            ]);
            if ($real->id !== $import->id) {
                $real->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Email recipient import failed', [
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
