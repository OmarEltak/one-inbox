<?php

declare(strict_types=1);

namespace App\Services\Compliance;

use App\Models\Contact;
use App\Models\ConnectedAccount;
use App\Models\ContactPlatform;
use App\Models\DataDeletionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single source of truth for Meta-driven user data deletion.
 *
 * Called by:
 *   - {@see \App\Jobs\ProcessMetaDataDeletion} — async path from the
 *     POST /api/webhooks/meta/data-deletion endpoint.
 *   - {@see \App\Console\Commands\MetaDeleteUsersCommand} — manual path
 *     for processing the CSV Meta emails when no callback is configured.
 */
final class MetaUserDataDeleter
{
    /**
     * @param  list<string>  $platformUserIds  Facebook/Instagram user IDs to wipe.
     * @return array{
     *     connected_accounts: int,
     *     contact_platform: int,
     *     contacts: int,
     *     matched_account_ids: list<int>,
     *     matched_platform_ids: list<int>
     * }
     */
    public function deleteByPlatformUserIds(array $platformUserIds, string $source = DataDeletionRequest::SOURCE_MANUAL): array
    {
        if ($platformUserIds === []) {
            return $this->emptyResult();
        }

        $accounts = ConnectedAccount::whereIn('platform_user_id', $platformUserIds)->get();
        $platforms = ContactPlatform::whereIn('platform_contact_id', $platformUserIds)->get();

        $deleted = [
            'connected_accounts' => 0,
            'contact_platform' => 0,
            'contacts' => 0,
            'matched_account_ids' => $accounts->pluck('id')->all(),
            'matched_platform_ids' => $platforms->pluck('id')->all(),
        ];

        DB::transaction(function () use ($accounts, $platforms, &$deleted): void {
            foreach ($accounts as $a) {
                $a->delete();
                $deleted['connected_accounts']++;
            }

            $contactIds = $platforms->pluck('contact_id')->unique()->all();
            foreach ($contactIds as $contactId) {
                $contact = Contact::find($contactId);
                if ($contact !== null) {
                    $contact->delete();
                    $deleted['contacts']++;
                }
            }

            $deleted['contact_platform'] = ContactPlatform::whereIn('id', $platforms->pluck('id'))->delete();
        });

        Log::channel('stack')->info('meta_data_deletion', [
            'source' => $source,
            'request_id_count' => count($platformUserIds),
            'deleted' => $deleted,
        ]);

        return $deleted;
    }

    /**
     * @return array{connected_accounts: int, contact_platform: int, contacts: int, matched_account_ids: list<int>, matched_platform_ids: list<int>}
     */
    private function emptyResult(): array
    {
        return [
            'connected_accounts' => 0,
            'contact_platform' => 0,
            'contacts' => 0,
            'matched_account_ids' => [],
            'matched_platform_ids' => [],
        ];
    }
}
