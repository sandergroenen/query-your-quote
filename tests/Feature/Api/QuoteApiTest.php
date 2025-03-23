<?php

namespace Tests\Feature\Api;

use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\ZenQuotesService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;

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
                             'jsonResponseQuote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'user',
                                 'isFastest'
                             ]
                         ],
                         'zenQuotes' => [
                             'jsonResponseQuote' => [
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
                    'jsonResponseQuote' => [
                        'quote' => 'DummyJSON quote',
                        'author' => 'DummyJSON Author',
                        'timeTaken' => 100,
                        'user' => 'testuser',
                        'isFastest' => true
                    ]
                ],
                'zenQuotes' => [
                    'jsonResponseQuote' => [
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
                             'jsonResponseQuote' => [
                                 'quote',
                                 'author',
                                 'timeTaken',
                                 'user',
                                 'isFastest'
                             ]
                         ],
                         'zenQuotes' => [
                             'jsonResponseQuote' => [
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
                    'jsonResponseQuote' => [
                        'quote' => 'Unable to fetch quote from DummyJSON: API error',
                        'author' => 'Error',
                        'timeTaken' => 0,
                        'user' => 'testuser',
                        'isFastest' => false // False because of both equal time 
                    ]
                ],
                'zenQuotes' => [
                    'jsonResponseQuote' => [
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
                    'jsonResponseQuote' => [
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
                    'jsonResponseQuote' => [
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
 
        Http::allowStrayRequests();

        $rateLimiter = new \App\Http\Middleware\QuoteRateLimiter(app('Illuminate\Cache\RateLimiter'));

        // Create a mock request to get the IP
        $mockRequest = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '172.20.0.1']);
        $ipAddress = $mockRequest->getClientIp();
        // Reset rate limit cache
        $rateLimiter->resetAttempts($ipAddress);
        $simultaneousRequests = 5;

        for ($ratelimit = 1; $ratelimit <= 5; $ratelimit++) {
            $rateLimithit = 0;

            // Log initial state
            $initialRemaining = $rateLimiter->getRemainingAttempts($ipAddress, $ratelimit);
            echo "Initial remaining attempts: $initialRemaining\n";

            $this->assertEquals($ratelimit, $initialRemaining);

            // Send requests sequentially
            for ($i = 0; $i < $simultaneousRequests; $i++) {
                $response = Http::get("http://host.docker.internal/api/quotes/random?rateLimit=$ratelimit");

                if ($response->status() !== 200) {
                    $rateLimithit++;
                    $this->assertEquals(429, $response->status());
                } else {
                    $this->assertEquals(200, $response->status());
                }
            }

            // Log after requests
            $afterRequestsRemaining = $rateLimiter->getRemainingAttempts($ipAddress, $ratelimit);
            echo "Remaining attempts after requests: $afterRequestsRemaining\n";

            $this->assertEquals($simultaneousRequests - $ratelimit, $rateLimithit,"Current rate limit: $ratelimit, current remaining attempts: $afterRequestsRemaining");

            // Reset rate limit cache
            $rateLimiter->resetAttempts($ipAddress);
            
            // Log after reset
            $afterResetRemaining = $rateLimiter->getRemainingAttempts($ipAddress, $ratelimit);
            echo "Remaining attempts after reset: $afterResetRemaining\n";
            
            // Verify remaining attempts
            $this->assertEquals($ratelimit, $afterResetRemaining);
        }
    }

}
