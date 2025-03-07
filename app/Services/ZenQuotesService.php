<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenQuotesService
{
    protected $apiUrl = 'https://zenquotes.io/api/random';

    /**
     * Get a random quote from ZenQuotes API
     *
     * @return array
     */
    public function getRandomQuote()
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
            
            // Return formatted quote
            return [
                'quote' => $data[0]['q'],
                'author' => $data[0]['a'],
                'timeTaken' => round($timeTaken, 2)
            ];
        } catch (\Exception $e) {
            // Log the error
            Log::warning('Error fetching quote from ZenQuotes: ' . $e->getMessage());
            
            // Return error information
            return [
                'quote' => 'Unable to fetch quote from ZenQuotes: ' . $e->getMessage(),
                'author' => 'Error',
                'timeTaken' => 0,
                'error' => true,
                'errorMessage' => $e->getMessage()
            ];
        }
    }
}
