<?php

namespace App\Providers;

use App\Services\ZenQuotesService;
use Illuminate\Support\ServiceProvider;

class ZenQuotesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ZenQuotesService::class, function ($app) {
            return new ZenQuotesService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
