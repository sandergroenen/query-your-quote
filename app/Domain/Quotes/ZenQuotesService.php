<?php

namespace App\Domain\Quotes;

use App\Domain\Dto\QuoteDto;
use App\Domain\Dto\QuoteJsonResponse;
use App\Domain\Events\QuoteRetrievedEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenQuotesService
{
    protected string $apiUrl = 'https://zenquotes.io/api/random';

    /**
     * Get a random quote from ZenQuotes API
     *
     * @return QuoteJsonResponse
     */
    public function getRandomQuote(): QuoteJsonResponse
    {
        $startTime = microtime(true);

        try {
            // Make API request
            $response = Http::timeout(5)->get($this->apiUrl);

            // Check if the request was successful
            if (!$response->successful()) {
                $statusCode = $response->status();
                $errorMessage = $response->body();

                Log::warning("ZenQuotes API error: {$statusCode}", [
                    'error' => $errorMessage,
                    'status' => $statusCode
                ]);

                throw new \Exception("ZenQuotes API error: HTTP {$statusCode} - {$errorMessage}");
            }

            // Parse response
            $data = $response->json();

            // Check if we got valid data
            if (empty($data) || !isset($data[0]['q']) || !isset($data[0]['a'])) {
                $responseBody = $response->body();
                Log::warning("Invalid response from ZenQuotes API", ['response' => $responseBody]);
                throw new \Exception("Invalid response from ZenQuotes API: {$responseBody}");
            }

            // Calculate time taken
            $timeTaken = (microtime(true) - $startTime) * 1000; // Convert to milliseconds


            $jsonResponse = new QuoteJsonResponse(
                'zenQuotes',
                $data[0]['q'],
                $data[0]['a'],
                round($timeTaken, 2),
                null,
                false
            );

            //dispatch the quoteRetrieved event so listeners can act on it
            QuoteRetrievedEvent::dispatch(new QuoteDto($jsonResponse));

            return $jsonResponse;

        } catch (\Exception $e) {
            // Log the error
            Log::warning('Error fetching quote from ZenQuotes: ' . $e->getMessage());

            // Return error information
            return new QuoteJsonResponse(
                'zenQuotes',
                'Unable to fetch quote from ZenQuotes: ' . $e->getMessage(),
                'Error',
                0,
                null,
                true,
                $e->getMessage(),
            );
        }
    }
}
