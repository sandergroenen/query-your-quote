<?php

namespace App\Providers;

use App\Domain\Events\FilteredQuoteRetrievedEvent;
use App\Domain\Events\QuoteRetrievedEvent;
use App\Domain\Listeners\NotifyQuoteRetrieval;
use DeepCopy\Filter\Filter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        //register event handler for all quote events
        Event::listen(
            QuoteRetrievedEvent::class,
            NotifyQuoteRetrieval::class
        );
    }
}
