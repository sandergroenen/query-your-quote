<?php

namespace App\Domain\Listeners;

use App\Domain\Dto\QuoteDto;
use App\Domain\Events\FilteredQuoteRetrievedEvent;
use App\Domain\Events\QuoteRetrievedEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;

class NotifyQuoteRetrieval
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(QuoteRetrievedEvent $event): void
    {
        //filter the even on the global filter
        if (stripos($event->quote->jsonResponseQuote->quote, Cache::get('quote_filter')) !== false) {
            FilteredQuoteRetrievedEvent::dispatch(new QuoteDto($event->quote->jsonResponseQuote));
        }
       
    }
}
