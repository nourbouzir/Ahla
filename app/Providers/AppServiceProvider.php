<?php

namespace App\Providers;

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

            $this->app->bind($contract, $implementation);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
