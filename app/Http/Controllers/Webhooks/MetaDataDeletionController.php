<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMetaDataDeletion;
use App\Models\DataDeletionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Implements Meta's User Data Deletion Callback.
 *
 * Configure the URL in Facebook App Dashboard → Settings → Advanced →
 * Data Deletion Request URL:
 *
 *     https://ot1-pro.com/api/webhooks/meta/data-deletion
 *
 * Spec: https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback
 */
final class MetaDataDeletionController extends Controller
{
    public function callback(Request $request): JsonResponse
    {
        $signedRequest = (string) $request->input('signed_request', '');
        if ($signedRequest === '') {
            return response()->json(['error' => 'missing signed_request'], 400);
        }

        $appSecret = (string) config('services.meta.app_secret', '');
        if ($appSecret === '') {
            Log::error('Meta data deletion callback received but META_APP_SECRET is not configured');

            return response()->json(['error' => 'app misconfigured'], 500);
        }

        $payload = $this->parseSignedRequest($signedRequest, $appSecret);
        if ($payload === null) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $userId = (string) ($payload['user_id'] ?? '');
        if ($userId === '') {
            return response()->json(['error' => 'missing user_id'], 400);
        }

        $deletion = DataDeletionRequest::create([
            'platform_user_id' => $userId,
            'source' => DataDeletionRequest::SOURCE_META,
            'confirmation_code' => Str::random(40),
            'status' => DataDeletionRequest::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        ProcessMetaDataDeletion::dispatch($deletion->id);

        return response()->json([
            'url' => route('data-deletion.status', ['code' => $deletion->confirmation_code]),
            'confirmation_code' => $deletion->confirmation_code,
        ]);
    }

    public function status(string $code)
    {
        $deletion = DataDeletionRequest::query()
            ->where('confirmation_code', $code)
            ->firstOrFail();

        return view('pages.data-deletion-status', ['deletion' => $deletion]);
    }

    /**
     * Validate Meta's signed_request and return the decoded payload, or null
     * if the signature is invalid.
     *
     * @return array<string, mixed>|null
     */
    private function parseSignedRequest(string $signedRequest, string $appSecret): ?array
    {
        if (! str_contains($signedRequest, '.')) {
            return null;
        }

        [$encodedSig, $encodedPayload] = explode('.', $signedRequest, 2);

        $sig = $this->base64UrlDecode($encodedSig);
        $payload = $this->base64UrlDecode($encodedPayload);

        if ($sig === null || $payload === null) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $encodedPayload, $appSecret, true);
        if (! hash_equals($expectedSig, $sig)) {
            return null;
        }

        $decoded = json_decode($payload, true);
        if (! is_array($decoded)) {
            return null;
        }

        $algorithm = strtoupper((string) ($decoded['algorithm'] ?? ''));
        if ($algorithm !== 'HMAC-SHA256') {
            return null;
        }

        return $decoded;
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}
