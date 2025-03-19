<?php

namespace App\Providers;

use App\Services\ZenQuotesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Event\Runtime\PHPUnit;

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

    public function test_my_command(){
        p.__
        PHPUnit::assertTrue(true);
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
