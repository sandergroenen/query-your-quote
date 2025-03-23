<?php

namespace App\Domain\Dto;

/**
 * @property-read QuoteDto $dummyJson
 * @property-read QuoteDto $zenQuotes
 */
class AllQuotesDto
{
    /** @var QuoteDto[] */
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
    public function __get(String $name): ?QuoteDto
    {
        return $this->quotes[$name] ?? null;
    }

    public function __isset(String $name): bool
    {
        return isset($this->quotes[$name]);
    }
    
}
