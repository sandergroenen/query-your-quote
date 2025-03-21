<?php

namespace App\Domain\Quotes;

use App\Domain\Dto\AllQuotesDto;
use App\Domain\Dto\FastestQuotesDto;
use App\Domain\Dto\QuoteDto;
use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\ZenQuotesService;

class QuoteHandler
{
    /**
     * @var DummyJsonService
     */
    private $dummyJsonService;

    /**
     * @var ZenQuotesService
     */
    private $zenQuotesService;

    /**
     * QuoteHandler constructor.
     * @param DummyJsonService $dummyJsonService
     * @param ZenQuotesService $zenQuotesService
     */
    public function __construct(DummyJsonService $dummyJsonService, ZenQuotesService $zenQuotesService)
    {
        $this->dummyJsonService = $dummyJsonService;
        $this->zenQuotesService = $zenQuotesService;
    }

    /**
     * @return AllQuotesDto
     */
    public function getRandomQuote(): AllQuotesDto
    {
        $dummyJsonQuote = $this->dummyJsonService->getRandomQuote();
        $zenQuotesQuote = $this->zenQuotesService->getRandomQuote();

        // Determine which API was faster (only if both were successful)
        $dummyJsonTime = $dummyJsonQuote->timeTaken ?? 0;
        $zenQuotesTime = $zenQuotesQuote->timeTaken ?? 0;

        // Only mark as fastest if there was no error
        $dummyJsonQuote->isFastest = empty($dummyJsonQuote->error) &&
            ($dummyJsonTime <= $zenQuotesTime || !empty($zenQuotesQuote->error));

        $zenQuotesQuote->isFastest = empty($zenQuotesQuote->error) &&
            ($zenQuotesTime < $dummyJsonTime || !empty($dummyJsonQuote->error));


        return new AllQuotesDto(new QuoteDto($dummyJsonQuote), new QuoteDto($zenQuotesQuote));
    }


    function getFastestQuote()
    {
        $randomQuotes = $this->getRandomQuote();
        $whoIsFastest = $randomQuotes->dummyJson->isFastest ? 'dummyJson' : ($randomQuotes->zenQuotes->isFastest ? 'zenQuotes' : 'dummyJson');
        return new FastestQuotesDto($whoIsFastest, $randomQuotes->{$whoIsFastest}->quote);
    }
}
