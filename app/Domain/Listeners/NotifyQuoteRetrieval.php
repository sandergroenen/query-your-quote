<?php

namespace App\Domain\Listeners;

use App\Domain\Events\QuoteRetrieved;
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
    public function handle(QuoteRetrieved $event): void
    {
        // For now do nothing since frontend is also listening to same event
       
    }
}
