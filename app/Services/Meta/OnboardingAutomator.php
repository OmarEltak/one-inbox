<?php

declare(strict_types=1);

namespace App\Services\Meta;

use App\Contracts\AiProviderInterface;
use App\Mail\OnboardingAutomationFailed;
use App\Models\ConnectedAccount;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\OnboardingRequest;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use App\Services\Platforms\FacebookPlatform;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Fully automates managed FB/IG onboarding: parses the customer's page URL,
 * refreshes super-admin's page list, asks the AI whether the submitted
 * business name matches the real page, and either assigns the Page row to
 * the customer team or auto-rejects with an AI-drafted reason.
 *
 * Reads super-admin's long-lived FB user access token from the super-admin
 * user's existing ConnectedAccount (created when they OAuth'd for their own
 * team) — no separate token store needed.
 *
 * Kill switch: config('services.meta.managed_onboarding_auto').
 */
class OnboardingAutomator
{
    private const CONFIDENCE_THRESHOLD = 0.85;

    public function __construct(
        private AiProviderInterface $ai,
        private FacebookPlatform $facebook,
    ) {}

    public function handle(OnboardingRequest $req): void
    {
        try {
            $account = $this->superAdminFacebookAccount();
            if (! $account) {
                $this->autoReject($req, 'Automation offline: no super-admin Facebook connection on file. A human will review shortly.');
                $this->notifyOwner($req, 'Super-admin has no active Facebook ConnectedAccount. OAuth /connections as super-admin first.');
                return;
            }

            $this->facebook->fetchPages($account);

            $candidate = $this->findCandidatePage($account, $req);
            if (! $candidate) {
                $this->autoReject(
                    $req,
                    "We could not find a page matching \"{$req->business_name}\" among the pages you gave us admin access to. Please confirm you added our admin account (facebook.com/omarEltak88) as a Page admin, then resubmit."
                );
                $this->notifyOwner($req, 'No candidate page found in super-admin /me/accounts list.');
                return;
            }

            $decision = $this->askAiForMatch($req, $candidate);

            if (! $decision['matches'] || $decision['confidence'] < self::CONFIDENCE_THRESHOLD) {
                $reason = trim((string) ($decision['reason'] ?? '')) ?: 'The page we found does not appear to match your submission.';
                $this->autoReject($req, $reason . "\n\n— Reviewed automatically by AI. If this is a mistake, email omareltak7@gmail.com.");
                return;
            }

            $this->assignPage($req, $candidate);
        } catch (\Throwable $e) {
            Log::error('OnboardingAutomator: unexpected failure', [
                'request_id' => $req->id,
                'error'      => $e->getMessage(),
            ]);
            $this->notifyOwner($req, 'Automation crashed: ' . $e->getMessage());
        }
    }

    private function superAdminFacebookAccount(): ?ConnectedAccount
    {
        $superAdmin = User::where('is_super_admin', true)->orderBy('id')->first();
        if (! $superAdmin || ! $superAdmin->current_team_id) {
            return null;
        }

        return ConnectedAccount::where('team_id', $superAdmin->current_team_id)
            ->where('platform', 'facebook')
            ->where('is_active', true)
            ->latest('id')
            ->first();
    }

    private function findCandidatePage(ConnectedAccount $account, OnboardingRequest $req): ?Page
    {
        $handle = $this->extractHandle($req->page_url);

        $query = Page::where('team_id', $account->team_id)
            ->where('platform', $req->platform)
            ->where('is_active', true);

        if ($handle !== null) {
            $byHandle = (clone $query)
                ->where(function ($q) use ($handle) {
                    $q->where('platform_page_id', $handle)
                      ->orWhere('name', 'like', '%' . $handle . '%');
                })
                ->first();
            if ($byHandle) {
                return $byHandle;
            }
        }

        return (clone $query)
            ->where('name', 'like', '%' . $req->business_name . '%')
            ->first();
    }

    private function extractHandle(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        if (! preg_match('#facebook\.com/(?:pg/|profile\.php\?id=)?([A-Za-z0-9._\-]+)#i', $url, $m)) {
            return null;
        }
        $handle = $m[1];
        return in_array(strtolower($handle), ['pages', 'people', 'watch'], true) ? null : $handle;
    }

    private function askAiForMatch(OnboardingRequest $req, Page $candidate): array
    {
        $systemPrompt = <<<'SYS'
You validate whether two Facebook/Instagram page references refer to the same business.
Consider small typos, capitalization, translation, and short-name/long-name pairs as matches.
Consider clearly different brands as NOT matches.

Reply ONLY with a single JSON object on one line, no prose before or after:
{"matches": true|false, "confidence": 0.0-1.0, "reason": "one-sentence explanation the customer can read"}
SYS;

        $category = is_array($candidate->metadata ?? null)
            ? ($candidate->metadata['category'] ?? 'unknown')
            : 'unknown';

        $userMessage = "CUSTOMER SUBMISSION:\n"
            . "- Business/Page name: {$req->business_name}\n"
            . "- Page URL: {$req->page_url}\n\n"
            . "PAGE WE FOUND (super-admin has admin access):\n"
            . "- Name: {$candidate->name}\n"
            . "- Platform page ID: {$candidate->platform_page_id}\n"
            . "- Category: {$category}";

        $raw = $this->ai->generateText($systemPrompt, $userMessage);
        $json = $this->parseJson($raw);

        return [
            'matches'    => (bool) ($json['matches'] ?? false),
            'confidence' => (float) ($json['confidence'] ?? 0),
            'reason'     => (string) ($json['reason'] ?? ''),
        ];
    }

    private function parseJson(string $raw): array
    {
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function assignPage(OnboardingRequest $req, Page $page): void
    {
        $targetTeam = Team::find($req->team_id);
        if (! $targetTeam) {
            $this->notifyOwner($req, 'Customer team vanished mid-automation.');
            return;
        }

        $sourceTeamId = $page->team_id;

        DB::transaction(function () use ($page, $req, $targetTeam, $sourceTeamId) {
            $page->update(['team_id' => $targetTeam->id]);
            Conversation::where('page_id', $page->id)->update(['team_id' => $targetTeam->id]);

            $contactIds = Conversation::where('page_id', $page->id)
                ->pluck('contact_id')->unique()->filter()->all();
            if (! empty($contactIds)) {
                Contact::whereIn('id', $contactIds)
                    ->where('team_id', $sourceTeamId)
                    ->update(['team_id' => $targetTeam->id]);
            }

            $req->update([
                'status'            => OnboardingRequest::STATUS_COMPLETED,
                'resulting_page_id' => $page->id,
                'admin_notes'       => 'Auto-completed by AI-matched onboarding pipeline.',
                'completed_at'      => now(),
            ]);
        });

        Team::find($sourceTeamId)?->clearActivePagesCache();
        $targetTeam->clearActivePagesCache();
    }

    private function autoReject(OnboardingRequest $req, string $reason): void
    {
        $req->update([
            'status'       => OnboardingRequest::STATUS_REJECTED,
            'admin_notes'  => $reason,
            'completed_at' => now(),
        ]);
    }

    private function notifyOwner(OnboardingRequest $req, string $note): void
    {
        $to = config('services.meta.managed_onboarding_notify', 'omareltak7@gmail.com');
        try {
            Mail::to($to)->send(new OnboardingAutomationFailed($req, $note));
        } catch (\Throwable $e) {
            Log::warning('OnboardingAutomator: notify email failed', [
                'error' => $e->getMessage(),
                'note'  => $note,
            ]);
        }
    }
}
