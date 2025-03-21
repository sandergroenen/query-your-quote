<?php

namespace App\Domain\Dto;

class FastestQuotesDto extends QuoteDto
{
    public string $whoIsFastest;
    public function __construct(
        string $whoIsFastest,
        QuoteJsonResponse $quote
    ) {
        $this->whoIsFastest = $whoIsFastest;
        parent::__construct($quote);
    }


    //setup fastest quote accessor
    public function __get($name)
    {
        if ($name == 'fastestQuote') {
            return $this->quote;
        }

        // Delegate to parent for other properties
        return parent::__get($name);
    }

    // Custom serialization method for PHP 8+
    public function __serialize(): array
    {
        // Get the parent serialization data
        $parentData = parent::__serialize();

        // Add the FastestQuotesDto specific data
        $parentData['whoIsFastest'] = $this->whoIsFastest;

        return $parentData;
    }

    // Custom deserialization method for PHP 8+
    public function __unserialize(array $data): void
    {
        // Call parent unserialize first
        parent::__unserialize($data);

        // Set FastestQuotesDto specific properties
        $this->whoIsFastest = $data['whoIsFastest'] ?? '';
    }
}
