<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Providers\DummyJsonServiceProvider;
use App\Http\Middleware\QuoteRateLimiter;
use App\Providers\ZenQuotesServiceProvider;
use App\Services\ZenQuotesService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',  
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'quote.throttle' => QuoteRateLimiter::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        DummyJsonServiceProvider::class,
        ZenQuotesServiceProvider::class,
    ])
    ->withEvents(discover: [
        __DIR__.'/../app/Domain/Listeners',
    ])
    ->create();
