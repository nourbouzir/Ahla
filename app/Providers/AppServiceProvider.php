<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use LogicException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (config('ahla.bindings', []) as $contract => $implementation) {
            if (! interface_exists($contract)) {
                throw new LogicException("Configured Ahla contract [{$contract}] does not exist.");
            }

            if (! is_string($implementation) || $implementation === '') {
                throw new LogicException("Configure an implementation for Ahla contract [{$contract}].");
            }

            if (! class_exists($implementation) || ! is_a($implementation, $contract, true)) {
                throw new LogicException(
                    "Configured Ahla implementation [{$implementation}] must implement [{$contract}].",
                );
            }

            // Singletons: the fakes keep state between calls.
            $this->app->singleton($contract, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute((int) config('ahla.search_rate_limit'))->by($request->ip()));

        // HTTPS only in production: every generated link is https and cookies are never sent over http.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
            config(['session.secure' => true, 'session.same_site' => 'lax', 'session.http_only' => true]);
        }
    }
}
