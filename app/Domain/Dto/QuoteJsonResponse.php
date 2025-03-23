<?php

namespace App\Domain\Dto;

use Dotenv\Util\Str;

class QuoteJsonResponse
{
    public function __construct(
        public string $apiName,
        public string $quote,
        public string $author,
        public float $timeTaken,
        public ?string $user = '',        
        public ?bool $error = false,
        public ?string $errorMessage = null,
        public ?bool $isFastest = null,
    ) {
    }

    //magic getters so the dto properties can be accessed directly like an array
    public function __get(String $name): mixed
    {
        return $this->{$name};
    }


    public function __set(String $name, mixed $value)
    {
        $this->{$name} = $value;
    }


    public function __isset(String $name): bool
    {
        return isset($this->{$name});
    }
}
