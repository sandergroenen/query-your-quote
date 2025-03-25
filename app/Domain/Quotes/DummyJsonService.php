<?php

namespace App\Domain\Quotes;

use App\Domain\Dto\QuoteDto;
use App\Domain\Dto\QuoteJsonResponse;
use App\Domain\Events\PrivateQuoteEvent;
use App\Domain\Events\QuoteRetrievedEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DummyJsonService
{
    protected string $baseUrl = 'https://dummyjson.com';
    protected ?string $accessToken = null;
    /** @var ?array<string, mixed> */
    protected ?array $currentUser = null;
    protected string $apiUrl = 'https://dummyjson.com/quotes/random';

    /**
     * Login to DummyJSON API and get access token using a random user
     *
     * @return string
     * @throws \Exception
     */
    public function login()
    {
        // If we already have a token, return it
        if ($this->accessToken) {
            return $this->accessToken;
        }

        // Check if token is in cache
        if (Cache::has('dummyjson_token')) {
            $this->accessToken = Cache::get('dummyjson_token');
            $this->currentUser = Cache::get('dummyjson_user');
            return $this->accessToken;
        }

        try {
            // Fetch users from the API
            $usersResponse = Http::get($this->baseUrl . '/users');

            if (!$usersResponse->successful()) {
                throw new \Exception('Failed to fetch users from DummyJSON API: ' . $usersResponse->body());
            }

            $users = $usersResponse->json('users');

            if (empty($users)) {
                throw new \Exception('No users found in the DummyJSON API response');
            }

            // Select a random user
            $randomUser = $users[array_rand($users)];

            // Store the current user for later use
            $this->currentUser = [
                'id' => $randomUser['id'],
                'username' => $randomUser['username'],
                'firstName' => $randomUser['firstName'] ?? '',
                'lastName' => $randomUser['lastName'] ?? '',
                'email' => $randomUser['email'] ?? '',
            ];

            // Use the random user's credentials to authenticate
            $response = Http::post($this->baseUrl . '/auth/login', [
                'username' => $randomUser['username'],
                'password' => $randomUser['password'] ?? $randomUser['username'] . '123', // Fallback if password not provided
                'expiresInMins' => 30,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Debug the response
                Log::debug('Auth response: ' . json_encode($data));

                // Check for token in different possible fields
                if (isset($data['token'])) {
                    $this->accessToken = $data['token'];
                } elseif (isset($data['accessToken'])) {
                    $this->accessToken = $data['accessToken'];
                } else {
                    // If no token is found, use a dummy token for testing
                    $this->accessToken = 'dummy-token';
                    Log::warning('No token found in response, using dummy token');
                }

                // Cache the token and user
                Cache::put('dummyjson_token', $this->accessToken, now()->addMinutes(30));
                Cache::put('dummyjson_user', $this->currentUser, now()->addMinutes(30));

                return $this->accessToken;
            }

            throw new \Exception('Failed to authenticate with DummyJSON API: ' . $response->body());
        } catch (\Exception $e) {
            // Log the error
            Log::error('DummyJSON authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a random quote from DummyJSON API
     *
     * @return QuoteJsonResponse
     */
    public function getRandomQuote(): QuoteJsonResponse
    {
        $startTime = microtime(true);

        try {
            // Authenticate first
            $this->login();

            // Make API request
            $response = Http::withToken($this->accessToken ?? '')->timeout(5)->get($this->apiUrl);

            // Check if the request was successful
            if (!$response->successful()) {
                $statusCode = $response->status();
                $errorMessage = $response->body();

                Log::warning("DummyJSON API error: {$statusCode}", [
                    'error' => $errorMessage,
                    'status' => $statusCode
                ]);

                throw new \Exception("DummyJSON API error: HTTP {$statusCode} - {$errorMessage}");
            }

            // Parse response
            $data = $response->json();

            // Check if we got valid data
            if (empty($data) || !isset($data['quote']) || !isset($data['author'])) {
                $responseBody = $response->body();
                Log::warning("Invalid response from DummyJSON API", ['response' => $responseBody]);
                throw new \Exception("Invalid response from DummyJSON API: {$responseBody}");
            }

            // Calculate time taken
            $timeTaken = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            //dispatch the quoteRetrieved event so listeners can act on it
            $jsonResponse = new QuoteJsonResponse(
                'dummyJson',
                $data['quote'],
                $data['author'],
                round($timeTaken, 2),
                $this->currentUser['username'],
                false
            );
            QuoteRetrievedEvent::dispatch(new QuoteDto($jsonResponse));
            PrivateQuoteEvent::dispatch(new QuoteDto($jsonResponse));

            // Return formatted quote with user info
            return $jsonResponse;
            
        } catch (\Exception $e) {
            // Log the error
            Log::warning('Error fetching quote from DummyJSON: ' . $e->getMessage());

            // Return error information
            return new QuoteJsonResponse(
                'dummyJson',
                'Unable to fetch quote from DummyJSON: ' . $e->getMessage(),
                'Error',
                0,
                $this->currentUser['username'],
                true,
                $e->getMessage(),
            );
        }
    }
}
