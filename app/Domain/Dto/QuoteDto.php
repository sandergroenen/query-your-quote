<?php

namespace App\Domain\Dto;

class QuoteDto
{
    public $quote;

    public function __construct(
        QuoteJsonResponse $quote
    ) {
        $this->quote = $quote;
    }

    //magic getters so the dto properties can be accessed directly like an array
    public function __get($name)
    {
        return $this->quote->{$name} ?? null;
    }

    public function __isset($name)
    {
        return isset($this->quote->{$name});
    }

    // Custom serialization method for PHP 8+
    public function __serialize(): array
    {
        // Extract all properties from QuoteJsonResponse for serialization
        $quoteData = [];
        if ($this->quote) {
            $quoteData = [
                'apiName' => $this->quote->apiName ?? '',
                'quote' => $this->quote->quote ?? '',
                'author' => $this->quote->author ?? '',
                'timeTaken' => $this->quote->timeTaken ?? 0.0,
                'user' => $this->quote->user ?? '',
                'error' => $this->quote->error ?? false,
                'errorMessage' => $this->quote->errorMessage ?? null,
                'isFastest' => $this->quote->isFastest ?? null,
            ];
        }

        // Return with a structure that maintains the relationship
        return [
            'quote' => $quoteData
        ];
    }

    // Custom deserialization method for PHP 8+
    public function __unserialize(array $data): void
    {
        // Reconstruct QuoteJsonResponse from the serialized data
        if (isset($data['quote']) && is_array($data['quote'])) {
            $quote = $data['quote'];
            $this->quote = new QuoteJsonResponse(
                $quote['apiName'] ?? '',
                $quote['quote'] ?? '',
                $quote['author'] ?? '',
                $quote['timeTaken'] ?? 0.0,
                $quote['user'] ?? '',
                $quote['error'] ?? false,
                $quote['errorMessage'] ?? null,
                $quote['isFastest'] ?? null
            );
        }
    }
}
