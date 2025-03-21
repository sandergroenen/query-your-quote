<?php

namespace App\Providers;

use App\Domain\Quotes\ZenQuotesService;
use Illuminate\Support\ServiceProvider;

class ZenQuotesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ZenQuotesService::class, function () {
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
