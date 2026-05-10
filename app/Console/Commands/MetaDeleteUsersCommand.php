<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ConnectedAccount;
use App\Models\ContactPlatform;
use App\Models\DataDeletionRequest;
use App\Services\Compliance\MetaUserDataDeleter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Process Meta's User Data Deletion request file.
 *
 * Usage:
 *   php artisan meta:delete-users storage/app/meta-deletions/2026-05-09.txt
 *   php artisan meta:delete-users path/to/file.csv --dry-run
 *
 * Each line of the file is one Facebook/Instagram user ID (PSID, ASID, or IGSID).
 * The command finds matching rows in `connected_accounts` and `contact_platform`
 * and deletes them — cascading through conversations, messages, and lead score
 * events via existing foreign-key constraints.
 *
 * Run with --dry-run first to preview matches before deleting.
 *
 * Compliance: Meta Platform Terms § 3(d)(i), GDPR Art. 17, CCPA § 1798.105.
 */
final class MetaDeleteUsersCommand extends Command
{
    protected $signature = 'meta:delete-users
                            {file : Path to file with one Facebook/Instagram user ID per line}
                            {--dry-run : Preview matches without deleting}';

    protected $description = 'Process a Meta User Data Deletion request file (Section 3(d)(i) of Platform Terms).';

    public function handle(MetaUserDataDeleter $deleter): int
    {
        $path = $this->argument('file');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        $ids = $this->parseIds($path);

        if (empty($ids)) {
            $this->error('No valid IDs found in file. Each line should be a numeric Facebook user ID.');

            return self::FAILURE;
        }

        $this->info('Loaded ' . count($ids) . ' user ID(s) from ' . $path);

        $accounts = ConnectedAccount::whereIn('platform_user_id', $ids)->get();
        $platforms = ContactPlatform::with('contact')->whereIn('platform_contact_id', $ids)->get();

        $this->newLine();
        $this->line('<fg=cyan>=== connected_accounts (team owners who used FB/IG OAuth) ===</>');
        if ($accounts->isEmpty()) {
            $this->line('  no matches');
        } else {
            foreach ($accounts as $a) {
                $this->line("  • id={$a->id} team={$a->team_id} platform={$a->platform} uid={$a->platform_user_id} name=\"{$a->name}\"");
            }
        }

        $this->newLine();
        $this->line('<fg=cyan>=== contact_platform (customers on connected Pages) ===</>');
        if ($platforms->isEmpty()) {
            $this->line('  no matches');
        } else {
            foreach ($platforms as $p) {
                $contactName = $p->contact?->name ?? '(no contact name)';
                $this->line("  • id={$p->id} contact={$p->contact_id} platform={$p->platform} pcid={$p->platform_contact_id} name=\"{$p->platform_name}\" linked_contact=\"{$contactName}\"");
            }
        }

        $totalMatches = $accounts->count() + $platforms->count();

        $this->newLine();
        $this->info("Total matches: {$totalMatches}");

        if ($this->option('dry-run')) {
            $this->warn('Dry-run mode — no records deleted.');

            return self::SUCCESS;
        }

        if ($totalMatches === 0) {
            $this->info('Nothing to delete. Done.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Delete these {$totalMatches} record(s) and all related data (conversations, messages, score events)? This cannot be undone.")) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        // Record one DataDeletionRequest per ID for the audit trail.
        $requests = [];
        foreach ($ids as $id) {
            $requests[$id] = DataDeletionRequest::create([
                'platform_user_id' => $id,
                'source' => DataDeletionRequest::SOURCE_MANUAL,
                'confirmation_code' => Str::random(40),
                'status' => DataDeletionRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        }

        $deleted = $deleter->deleteByPlatformUserIds($ids, DataDeletionRequest::SOURCE_MANUAL);

        // Mark every request completed with a copy of the audit summary.
        foreach ($requests as $request) {
            $request->fill([
                'status' => DataDeletionRequest::STATUS_COMPLETED,
                'matched_records' => $deleted,
                'completed_at' => now(),
            ])->save();
        }

        $this->newLine();
        $this->info('Deleted:');
        $this->line("  • connected_accounts: {$deleted['connected_accounts']}");
        $this->line("  • contact_platform:   {$deleted['contact_platform']}");
        $this->line("  • contacts:           {$deleted['contacts']}");

        $this->newLine();
        $this->info('Compliance audit rows written to data_deletion_requests table.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function parseIds(string $path): array
    {
        $raw = file_get_contents($path) ?: '';
        $lines = preg_split('/[\r\n,;]+/', $raw) ?: [];
        $ids = [];

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip header rows, commented rows, and empty lines.
            if ($line === '' || str_starts_with($line, '#') || ! ctype_digit($line)) {
                continue;
            }

            $ids[] = $line;
        }

        return array_values(array_unique($ids));
    }
}
