<?php

namespace App\Domain\Dto;

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
    public function __get($name)
    {
        return $this->{$name};
    }


    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }


    public function __isset($name)
    {
        return isset($this->{$name});
    }
}
