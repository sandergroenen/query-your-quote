<?php

namespace App\Domain\Dto;

/**
 * @property-read string|null $apiName
 * @property-read string|null $quote
 * @property-read string $author
 * @property-read float $timeTaken
 * @property-read ?string $user
 * @property-read ?bool $error
 * @property-read ?string $errorMessage
 * @property-read ?bool $isFastest
 */
class QuoteDto
{

    public QuoteJsonResponse $jsonResponseQuote;

    public function __construct(
        QuoteJsonResponse $quoteObject
    ) {
        $this->jsonResponseQuote = $quoteObject;
    }

    //magic getters so the dto properties can be accessed directly like an object
    public function __get(String $name): mixed
    {
        return $this->jsonResponseQuote->{$name} ?? null;
    }

    public function __isset(String $name): bool
    {
        return isset($this->jsonResponseQuote->{$name});
    }

    // Custom serialization method for PHP 8+
    public function __serialize(): array
    {
        // Extract all properties from QuoteJsonResponse for serialization
        $quoteData = [];
        $quoteData = [
            'apiName' => $this->jsonResponseQuote->apiName ?? '',
            'quote' => $this->jsonResponseQuote->quote ?? '',
            'author' => $this->jsonResponseQuote->author ?? '',
            'timeTaken' => $this->jsonResponseQuote->timeTaken ?? 0.0,
            'user' => $this->jsonResponseQuote->user ?? '',
            'error' => $this->jsonResponseQuote->error ?? false,
            'errorMessage' => $this->jsonResponseQuote->errorMessage ?? null,
            'isFastest' => $this->jsonResponseQuote->isFastest ?? null,
        ];

        // Return with a structure that maintains the relationship
        return [
            'quote' => $quoteData
        ];
    }

    // Custom deserialization method for PHP 8+
    /** @phpstan-ignore-next-line */
    public function __unserialize(array $data): void
    {
        // Reconstruct QuoteJsonResponse from the serialized data
        if (isset($data['quote']) && is_array($data['quote'])) {
            $quote = $data['quote'];
            $this->jsonResponseQuote = new QuoteJsonResponse(
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
