<?php

namespace App\Domain\Dto;

class QuotesDto
{
    public function __construct(
        public array $dummyJson,
        public array $zenQuotes
    ) {
    }

    //magic getters so the dto properties can be accessed directly like an array
    public function __get($name)
    {
        return $this->{$name};
    }

    public function __isset($name)
    {
        return isset($this->{$name});
    }
}
