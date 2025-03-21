<?php

namespace App\Domain\Dto;

use ArrayAccess;

class AllQuotesDto
{
    public array $quotes = [];
    
    public function __construct(
        QuoteDto ...$quotes
    ) {
        foreach ($quotes as $quote) {
            // Access apiName directly through the magic __get method
            $this->quotes[$quote->apiName] = $quote;
        }
    }

    // Magic getters so the dto properties can be accessed directly
    public function __get($name)
    {
        return $this->quotes[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->quotes[$name]);
    }
    
}
