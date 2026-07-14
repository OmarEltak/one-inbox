<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\OnboardingRequest;
use App\Services\Meta\OnboardingAutomator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoProcessOnboardingRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 30;

    public function __construct(
        public int $onboardingRequestId,
        public int $attempt = 1,
    ) {}

    public function handle(OnboardingAutomator $automator): void
    {
        $req = OnboardingRequest::find($this->onboardingRequestId);
        if (! $req || ! $req->isOpen()) {
            return;
        }

        $automator->handle($req, $this->attempt);
    }
}
