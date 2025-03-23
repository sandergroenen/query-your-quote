<?php

namespace Tests\Feature\Api;

use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\ZenQuotesService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QuoteApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Prevent actual HTTP requests
        Http::preventStrayRequests();
    }

    #[Test]
    public function it_returns_quotes_from_both_services()
    {
        // Mock both services
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'dummyJson',
                     'DummyJSON quote',
                     'DummyJSON Author',
                     100,
                     'testuser',
                     false
                 ));
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'zenQuotes',
                     'ZenQuotes quote',
                     'ZenQuotes Author',
                     150,
                     null,
                     true
                 ));
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status and structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'quotes' => [
                         'dummyJson' => [
                             'quote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'user',
                                 'isFastest'
                             ]
                         ],
                         'zenQuotes' => [
                             'quote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'isFastest'
                             ]
                         ]
                     ]
                 ]);

        // Assert specific values
        $response->assertJson([
            'quotes' => [
                'dummyJson' => [
                    'quote' => [
                        'quote' => 'DummyJSON quote',
                        'author' => 'DummyJSON Author',
                        'timeTaken' => 100,
                        'user' => 'testuser',
                        'isFastest' => true
                    ]
                ],
                'zenQuotes' => [
                    'quote' => [
                        'quote' => 'ZenQuotes quote',
                        'author' => 'ZenQuotes Author',
                        'timeTaken' => 150,
                        'isFastest' => false
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function it_handles_service_errors_gracefully()
    {
        // Mock both services with errors
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'dummyJson',
                     'Unable to fetch quote from DummyJSON: API error',
                     'Error',
                     0,
                     'testuser',
                     false,
                     'API error'
                 ));
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'zenQuotes',
                     'Unable to fetch quote from ZenQuotes: API error',
                     'Error',
                     0,
                     null,
                     false,
                     'API error'
                 ));
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status and structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'quotes' => [
                         'dummyJson' => [
                             'quote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'user',
                                 'isFastest'
                             ]
                         ],
                         'zenQuotes' => [
                             'quote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'isFastest'
                             ]
                         ]
                     ]
                 ]);

        // Assert specific error values
        $response->assertJson([
            'quotes' => [
                'dummyJson' => [
                    'quote' => [
                        'quote' => 'Unable to fetch quote from DummyJSON: API error',
                        'author' => 'Error',
                        'timeTaken' => 0,
                        'user' => 'testuser',
                        'isFastest' => false // False because of both equal time 
                    ]
                ],
                'zenQuotes' => [
                    'quote' => [
                        'quote' => 'Unable to fetch quote from ZenQuotes: API error',
                        'author' => 'Error',
                        'timeTaken' => 0,
                        'isFastest' => false // False because of both equal time
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function it_handles_one_service_failing()
    {
        // Mock DummyJsonService to succeed
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'dummyJson',
                     'DummyJSON quote',
                     'DummyJSON Author',
                     100,
                     'testuser',
                     false
                 ));
        });
        
        // Mock ZenQuotesService to fail
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'zenQuotes',
                     'Unable to fetch quote from ZenQuotes: API error',
                     'Error',
                     0,
                     null,
                     true,
                     'API error'
                 ));
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status
        $response->assertStatus(200);
        
        // Assert DummyJSON is successful and marked as fastest
        $response->assertJson([
            'quotes' => [
                'dummyJson' => [
                    'quote' => [
                        'quote' => 'DummyJSON quote',
                        'author' => 'DummyJSON Author',
                        'isFastest' => true // Fastest because ZenQuotes failed
                    ]
                ]
            ]
        ]);
        
        // Assert ZenQuotes has error
        $response->assertJson([
            'quotes' => [
                'zenQuotes' => [
                    'quote' => [
                        'error' => true,
                        'errorMessage' => 'API error'
                    ]
                ]
            ]
        ]);
    }

    #[Test]
    public function it_can_be_accessed_without_authentication()
    {
        // Mock both services
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'dummyJson',
                     'DummyJSON quote',
                     'DummyJSON Author',
                     100,
                     null,
                     true
                 ));
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn(new \App\Domain\Dto\QuoteJsonResponse(
                     'zenQuotes',
                     'ZenQuotes quote',
                     'ZenQuotes Author',
                     150,
                     null,
                     false
                 ));
        });

        // Make request without authentication
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response is successful
        $response->assertStatus(200);
    }


    
    #[Test]
    public function it_will_hit_ratelimit_according_to_parameters(){
 
        //allow stray requests otherwise the pool method will throw an exception
        Http::allowStrayRequests();

        $simultaneousRequests = 5;
        $ratelimit = 3;
        $rateLimithit = 0;

        // Log initial cache state
        Log::info('Initial rate limit cache state: ', Cache::get('rate_limit_key'));

        // Simulate simultaneous requests
        for ($i = 0; $i < $simultaneousRequests; $i++) {
            $response = $this->getJson('/api/quotes/random?rateLimit=100');

            if ($response->status() !== 200) {
                $rateLimithit++;
                // Log cache hit
                Log::info('Rate limit hit for request ' . $i);
            } else {
                // Log cache miss
                Log::info('Rate limit not hit for request ' . $i);
            }

            $this->assertEquals(200, $response->status());
        }

        // Log final cache state
        Log::info('Final rate limit cache state: ', Cache::get('rate_limit_key'));

        $this->assertEquals($simultaneousRequests - $ratelimit, $rateLimithit);
    }

}
