<?php

namespace App\Providers;

use App\Contracts\AiProviderInterface;
use App\Models\Post;
use App\Observers\PostObserver;
use App\Services\Ai\GeminiProvider;
use App\Services\Ai\NaraRouterProvider;
use App\Services\Ai\OllamaProvider;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiProviderInterface::class, function () {
            return match (config('services.ai.provider')) {
                'gemini'      => new GeminiProvider(),
                'ollama'      => new OllamaProvider(),
                'nararouter'  => new NaraRouterProvider(),
                // 'claude' => new ClaudeProvider(),
                // 'openai' => new OpenAiProvider(),
                default       => new GeminiProvider(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceRootUrl(config('app.url'));

        Cashier::useCustomerModel(\App\Models\Team::class);

        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->registerSlowQueryLogger();

        Event::listen(Registered::class, function (Registered $event): void {
            Session::flash('track_signup_conversion', $event->user->getKey());
        });

        Post::observe(PostObserver::class);
    }

    /**
     * Log any single query slower than SLOW_QUERY_MS (default 200ms) to
     * storage/logs/slow-queries.log. Opt-in via env so prod stays quiet
     * unless we're actively hunting a bottleneck.
     */
    protected function registerSlowQueryLogger(): void
    {
        if (! env('SLOW_QUERY_LOG', false)) {
            return;
        }

        $threshold = (int) env('SLOW_QUERY_MS', 200);

        DB::listen(function ($query) use ($threshold) {
            if ($query->time < $threshold) {
                return;
            }

            $path = request()?->path() ?? 'cli';
            $sql  = $query->sql;

            Log::channel('slow_queries')->info(
                sprintf('[%s ms] %s  ::  %s', number_format($query->time, 1), $path, $sql),
                ['bindings' => $query->bindings]
            );
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('chat', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // SQLite WAL mode: allows concurrent reads + queue worker writes without "database is locked"
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA journal_mode=WAL;');
            DB::statement('PRAGMA busy_timeout=5000;'); // wait up to 5s instead of failing instantly
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => Password::min(8));
    }
}
