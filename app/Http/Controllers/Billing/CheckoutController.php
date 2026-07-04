<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function redirect(Request $request, string $plan): RedirectResponse
    {
        $baseUrl = match ($plan) {
            'starter' => config('services.lemonsqueezy.starter_checkout'),
            'pro'     => config('services.lemonsqueezy.pro_checkout'),
            default   => null,
        };

        if (! $baseUrl) {
            abort(404);
        }

        $team = $request->user()?->currentTeam;

        $params = [];

        if ($team) {
            $params['checkout[custom][team_id]'] = $team->id;
        }

        if ($request->user()?->email) {
            $params['checkout[email]'] = $request->user()->email;
        }

        $url = $baseUrl . (count($params) ? '?' . http_build_query($params) : '');

        return redirect()->away($url);
    }
}
