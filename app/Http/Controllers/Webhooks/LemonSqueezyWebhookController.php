<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class LemonSqueezyWebhookController extends Controller
{
    private const PLAN_MAP = [
        'OT Starter' => 'starter',
        'OT Pro'     => 'pro',
    ];

    public function handle(Request $request): Response
    {
        if (! $this->validSignature($request)) {
            Log::warning('LemonSqueezy webhook: invalid signature');
            abort(403, 'Invalid signature');
        }

        $payload   = $request->json()->all();
        $eventName = data_get($payload, 'meta.event_name');
        $attrs     = data_get($payload, 'data.attributes', []);

        match ($eventName) {
            'subscription_created', 'subscription_updated' => $this->handleActivated($payload, $attrs),
            'subscription_cancelled'                        => $this->handleCancelled($payload, $attrs),
            'subscription_expired'                          => $this->handleExpired($payload, $attrs),
            default                                         => null,
        };

        return response('OK', 200);
    }

    private function handleActivated(array $payload, array $attrs): void
    {
        $team = $this->resolveTeam($payload, $attrs);
        if (! $team) {
            return;
        }

        $productName = data_get($attrs, 'product_name', '');
        $plan        = self::PLAN_MAP[$productName] ?? null;

        if (! $plan) {
            Log::warning('LemonSqueezy webhook: unknown product', ['product_name' => $productName]);
            return;
        }

        $team->update([
            'subscription_plan'          => $plan,
            'subscription_status'        => 'active',
            'lemon_squeezy_id'           => data_get($payload, 'data.id'),
            'lemon_squeezy_customer_id'  => (string) data_get($attrs, 'customer_id'),
        ]);

        Log::info('LemonSqueezy: team upgraded', ['team_id' => $team->id, 'plan' => $plan]);
    }

    private function handleCancelled(array $payload, array $attrs): void
    {
        $team = $this->resolveTeam($payload, $attrs);
        if (! $team) {
            return;
        }

        // Cancelled but still active until period ends — keep plan, mark status
        $team->update(['subscription_status' => 'cancelled']);

        Log::info('LemonSqueezy: subscription cancelled', ['team_id' => $team->id]);
    }

    private function handleExpired(array $payload, array $attrs): void
    {
        $team = $this->resolveTeam($payload, $attrs);
        if (! $team) {
            return;
        }

        $team->update([
            'subscription_plan'   => 'free',
            'subscription_status' => 'active',
            'lemon_squeezy_id'    => null,
        ]);

        Log::info('LemonSqueezy: subscription expired, downgraded to free', ['team_id' => $team->id]);
    }

    private function resolveTeam(array $payload, array $attrs): ?Team
    {
        // Primary: team_id passed as custom checkout data
        $teamId = data_get($payload, 'meta.custom_data.team_id');
        if ($teamId) {
            return Team::find((int) $teamId);
        }

        // Fallback: match by customer email via team owner
        $email = data_get($attrs, 'user_email');
        if ($email) {
            return Team::whereHas('owner', fn ($q) => $q->where('email', $email))->first();
        }

        Log::warning('LemonSqueezy webhook: could not resolve team', ['payload' => $payload]);
        return null;
    }

    private function validSignature(Request $request): bool
    {
        $secret = config('services.lemonsqueezy.webhook_secret');
        if (empty($secret)) {
            return true; // Skip validation in local dev if secret not set
        }

        $signature = $request->header('X-Signature');
        if (! $signature) {
            return false;
        }

        $hash = hash_hmac('sha256', $request->getContent(), $secret);
        return hash_equals($hash, $signature);
    }
}
