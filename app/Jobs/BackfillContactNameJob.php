<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\ContactPlatform;
use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lazy retry of /me profile fetch for a single contact.
 * Used both in-band (after a webhook arrives with no usable name) and via
 * the contacts:backfill-names maintenance command for legacy "Unknown" rows.
 *
 * Single attempt — fail silent. We never want to retry-loop on permission errors.
 */
class BackfillContactNameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(public int $contactId) {}

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);
        if (! $contact) {
            return;
        }

        // Don't overwrite a real name that someone has already filled in.
        if (! $this->isUnknown($contact->name)) {
            return;
        }

        $cp = ContactPlatform::where('contact_id', $contact->id)
            ->whereIn('platform', ['instagram', 'facebook'])
            ->first();

        if (! $cp) {
            return;
        }

        $page = Page::where('team_id', $contact->team_id)
            ->where('platform', $cp->platform)
            ->where('is_active', true)
            ->whereHas('connectedAccount', fn ($q) => $q->where('is_active', true))
            ->first();

        if (! $page) {
            return;
        }

        $profile = $this->fetchProfile($cp->platform_contact_id, $page);
        $name = $profile['name'] ?? null;

        if (! $name || $this->isUnknown($name)) {
            return;
        }

        $updates = ['name' => $name];
        if (! empty($profile['avatar']) && empty($contact->avatar)) {
            $updates['avatar'] = $profile['avatar'];
        }
        $contact->update($updates);

        if ($cp->platform_name === null || $this->isUnknown($cp->platform_name)) {
            $cp->update(['platform_name' => $name]);
        }

        Log::info('BackfillContactNameJob: filled name', [
            'contact_id' => $contact->id,
            'name'       => $name,
        ]);
    }

    protected function isUnknown(?string $value): bool
    {
        $v = trim((string) $value);
        return $v === '' || strcasecmp($v, 'Unknown') === 0;
    }

    /**
     * Fetch /me profile. Mirrors the IG/FB switch in
     * ProcessIncomingMessage::fetchMetaSenderProfile.
     */
    protected function fetchProfile(string $senderId, Page $page): array
    {
        try {
            $version = config('services.meta.graph_api_version', 'v21.0');
            $isInstagramBusiness = ($page->metadata['auth_type'] ?? null) === 'instagram_business';

            if ($isInstagramBusiness) {
                $resp = Http::get("https://graph.instagram.com/{$version}/{$senderId}", [
                    'fields'       => 'name,username,profile_picture_url',
                    'access_token' => $page->page_access_token,
                ]);
                if ($resp->successful()) {
                    $d = $resp->json();
                    return [
                        'name'   => $d['name'] ?? $d['username'] ?? null,
                        'avatar' => $d['profile_picture_url'] ?? null,
                    ];
                }
            } else {
                $resp = Http::get("https://graph.facebook.com/{$version}/{$senderId}", [
                    'fields'       => 'name,first_name,last_name,profile_pic',
                    'access_token' => $page->page_access_token,
                ]);
                if ($resp->successful()) {
                    $d = $resp->json();
                    $name = $d['name'] ?? trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                    return [
                        'name'   => $name ?: null,
                        'avatar' => $d['profile_pic'] ?? null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('BackfillContactNameJob: profile fetch failed', [
                'contact_id' => $this->contactId,
                'error'      => $e->getMessage(),
            ]);
        }

        return [];
    }
}
