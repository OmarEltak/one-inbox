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
        // Team-scope check: only members of the owning team may test-send.
        $user = Auth::user();
        abort_unless($user && $campaign->team_id === (int) $user->current_team_id, 403);
        abort_unless($campaign->platform === 'whatsapp', 400,
            'Test send only implemented for WhatsApp in phase A.');

        $key = 'campaigns:test-send:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json(
                ['error' => 'Too many test sends; try again in an hour.'],
                429,
            );
        }
        RateLimiter::hit($key, 3600);

        $data = $request->validate([
            'phone' => 'required|string',
            'name' => 'nullable|string|max:80',
        ]);

        $body = str_replace(
            ['{{name}}', '{{phone}}'],
            [$data['name'] ?? 'there', $data['phone']],
            (string) $campaign->message_template,
        );

        $page = $campaign->senderPage;
        abort_unless($page && $page->is_active, 422, 'Sender WhatsApp page is not connected.');

        $result = $sender->send($page, $data['phone'], $body);

        return response()->json(
            ['sent' => $result->sent, 'error' => $result->error],
            $result->sent ? 200 : 502,
        );
    }
}
