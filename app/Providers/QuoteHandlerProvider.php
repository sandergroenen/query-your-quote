<?php

namespace App\Providers;

use App\Domain\Quotes\QuoteHandler;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class QuoteHandlerProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(QuoteHandler::class, function (Application $app) {
            return new QuoteHandler($app->make('App\Domain\Quotes\DummyJsonService'),$app->make('App\Domain\Quotes\ZenQuotesService'));
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
