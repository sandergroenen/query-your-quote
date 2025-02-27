<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DummyJsonService
{
    protected $baseUrl = 'https://dummyjson.com';
    protected $accessToken = null;
    protected $currentUser = null;

    /**
     * Login to DummyJSON API and get access token using a random user
     *
     * @return string
     */
    public function login()
    {
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
                \Log::debug('Auth response: ' . json_encode($data));
                
                // Check for token in different possible fields
                if (isset($data['token'])) {
                    $this->accessToken = $data['token'];
                } elseif (isset($data['accessToken'])) {
                    $this->accessToken = $data['accessToken'];
                } else {
                    // If no token is found, use a dummy token for testing
                    $this->accessToken = 'dummy-token';
                    \Log::warning('No token found in response, using dummy token');
                }
                
                return $this->accessToken;
            }
            
            throw new \Exception('Failed to authenticate with DummyJSON API: ' . $response->body());
        } catch (\Exception $e) {
            // Log the error
            \Log::error('DummyJSON authentication error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get a random quote from the DummyJSON API
     *
     * @return array
     */
    public function getRandomQuote()
    {
        try {
            // Authenticate first
            $this->login();
            
            // Start timing
            $startTime = microtime(true);
            
            // Make the request with the token
            $response = Http::withToken($this->accessToken)
                ->get($this->baseUrl . '/quotes/random');
            
            // End timing
            $endTime = microtime(true);
            $timeTaken = (int)round(($endTime - $startTime) * 1000); // Convert to milliseconds and ensure it's an integer
            
            if ($response->successful()) {
                $data = $response->json();
                
                // Add user info and timing to the response
                $data['user'] = $this->currentUser;
                $data['timeTaken'] = $timeTaken;
                
                return $data;
            }
            
            throw new \Exception('Failed to fetch random quote: ' . $response->body());
        } catch (\Exception $e) {
            // Log the error
            \Log::error('DummyJSON random quote error: ' . $e->getMessage());
            throw $e;
        }
    }
}
