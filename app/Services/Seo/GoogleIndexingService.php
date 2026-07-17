<?php

declare(strict_types=1);

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleIndexingService
{
    private const SCOPE          = 'https://www.googleapis.com/auth/indexing';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
    private const PUBLISH_URL    = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    /**
     * Notify Google of a URL update. Type: URL_UPDATED (new/changed) or URL_DELETED.
     */
    public function notify(string $url, string $type = 'URL_UPDATED'): bool
    {
        $token = $this->accessToken();
        if ($token === null) {
            return false;
        }

        $response = Http::timeout(10)
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post(self::PUBLISH_URL, ['url' => $url, 'type' => $type]);

        if ($response->successful()) {
            Log::info('Google Indexing API notified', ['url' => $url, 'type' => $type]);
            return true;
        }

        Log::warning('Google Indexing API failed', [
            'url'    => $url,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return false;
    }

    private function accessToken(): ?string
    {
        $credentialsPath = config('services.google_indexing.credentials');
        if (! $credentialsPath || ! is_readable($credentialsPath)) {
            return null;
        }

        return Cache::remember('google_indexing_token', 3300, function () use ($credentialsPath) {
            $creds = json_decode((string) file_get_contents($credentialsPath), true);
            if (! is_array($creds) || empty($creds['client_email']) || empty($creds['private_key'])) {
                throw new RuntimeException('Invalid Google service account credentials.');
            }

            $jwt = $this->buildJwt($creds['client_email'], $creds['private_key']);

            $response = Http::timeout(10)->asForm()->post(self::TOKEN_ENDPOINT, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (! $response->successful()) {
                Log::error('Google OAuth token exchange failed', ['body' => $response->body()]);
                throw new RuntimeException('Failed to exchange JWT for access token.');
            }

            return (string) $response->json('access_token');
        });
    }

    private function buildJwt(string $clientEmail, string $privateKey): string
    {
        $now = time();

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $clientEmail,
            'scope' => self::SCOPE,
            'aud'   => self::TOKEN_ENDPOINT,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $segments = [
            $this->b64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64UrlEncode(json_encode($claims, JSON_UNESCAPED_SLASHES)),
        ];

        $signingInput = implode('.', $segments);
        $signature    = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign JWT with service account private key.');
        }

        $segments[] = $this->b64UrlEncode($signature);
        return implode('.', $segments);
    }

    private function b64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
