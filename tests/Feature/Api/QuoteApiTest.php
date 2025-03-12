<?php

namespace Tests\Feature\Api;

use App\Services\DummyJsonService;
use App\Services\ZenQuotesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
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
                 ->andReturn([
                     'quote' => 'DummyJSON quote',
                     'author' => 'DummyJSON Author',
                     'timeTaken' => 100,
                     'user' => ['id' => 1, 'username' => 'testuser']
                 ]);
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn([
                     'quote' => 'ZenQuotes quote',
                     'author' => 'ZenQuotes Author',
                     'timeTaken' => 150
                 ]);
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status and structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'dummyJson' => [
                         'quote',
                         'author',
                         'timeTaken',
                         'user',
                         'isFastest'
                     ],
                     'zenQuotes' => [
                         'quote',
                         'author',
                         'timeTaken',
                         'isFastest'
                     ]
                 ]);

        // Assert specific values
        $response->assertJson([
            'dummyJson' => [
                'quote' => 'DummyJSON quote',
                'author' => 'DummyJSON Author',
                'timeTaken' => 100,
                'isFastest' => true
            ],
            'zenQuotes' => [
                'quote' => 'ZenQuotes quote',
                'author' => 'ZenQuotes Author',
                'timeTaken' => 150,
                'isFastest' => false
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
                 ->andReturn([
                     'quote' => 'Unable to fetch quote from DummyJSON: API error',
                     'author' => 'Error',
                     'timeTaken' => 0,
                     'error' => true,
                     'errorMessage' => 'API error'
                 ]);
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn([
                     'quote' => 'Unable to fetch quote from ZenQuotes: API error',
                     'author' => 'Error',
                     'timeTaken' => 0,
                     'error' => true,
                     'errorMessage' => 'API error'
                 ]);
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status and structure
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'dummyJson' => [
                         'quote',
                         'author',
                         'timeTaken',
                         'error',
                         'errorMessage'
                     ],
                     'zenQuotes' => [
                         'quote',
                         'author',
                         'timeTaken',
                         'error',
                         'errorMessage'
                     ]
                 ]);

        // Assert error values
        $response->assertJson([
            'dummyJson' => [
                'error' => true,
                'errorMessage' => 'API error'
            ],
            'zenQuotes' => [
                'error' => true,
                'errorMessage' => 'API error'
            ]
        ]);
        
        // Assert neither service is marked as fastest
        $response->assertJsonMissing([
            'dummyJson' => ['isFastest' => true],
            'zenQuotes' => ['isFastest' => true]
        ]);
    }

    #[Test]
    public function it_handles_one_service_failing()
    {
        // Mock DummyJSON with success and ZenQuotes with error
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn([
                     'quote' => 'DummyJSON quote',
                     'author' => 'DummyJSON Author',
                     'timeTaken' => 100,
                     'user' => ['id' => 1, 'username' => 'testuser']
                 ]);
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn([
                     'quote' => 'Unable to fetch quote from ZenQuotes: API error',
                     'author' => 'Error',
                     'timeTaken' => 0,
                     'error' => true,
                     'errorMessage' => 'API error'
                 ]);
        });

        // Make request to the API endpoint
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response status
        $response->assertStatus(200);
        
        // Assert DummyJSON is successful and marked as fastest
        $response->assertJson([
            'dummyJson' => [
                'quote' => 'DummyJSON quote',
                'author' => 'DummyJSON Author',
                'isFastest' => true
            ]
        ]);
        
        // Assert ZenQuotes has error
        $response->assertJson([
            'zenQuotes' => [
                'error' => true,
                'errorMessage' => 'API error'
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
                 ->andReturn([
                     'quote' => 'DummyJSON quote',
                     'author' => 'DummyJSON Author',
                     'timeTaken' => 100
                 ]);
        });
        
        $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                 ->once()
                 ->andReturn([
                     'quote' => 'ZenQuotes quote',
                     'author' => 'ZenQuotes Author',
                     'timeTaken' => 150
                 ]);
        });

        // Make request without authentication
        $response = $this->getJson('/api/quotes/random?rateLimit=100');

        // Assert response is successful
        $response->assertStatus(200);
    }
}
