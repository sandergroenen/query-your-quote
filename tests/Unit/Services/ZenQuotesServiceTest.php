<?php

namespace Tests\Unit\Services;

use App\Services\ZenQuotesService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ZenQuotesServiceTest extends TestCase
{
    protected ZenQuotesService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ZenQuotesService();
        
        // Prevent actual HTTP requests
        Http::preventStrayRequests();
    }

    #[Test]
    public function it_can_get_random_quote()
    {
        // Mock the quote API response
        Http::fake([
            'https://zenquotes.io/api/random' => Http::response([
                [
                    'q' => 'This is a test quote from ZenQuotes',
                    'a' => 'ZenQuotes Author',
                    'h' => '<blockquote>This is a test quote from ZenQuotes</blockquote>'
                ]
            ], 200)
        ]);

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert the quote data is returned
        $this->assertEquals('This is a test quote from ZenQuotes', $quote['quote']);
        $this->assertEquals('ZenQuotes Author', $quote['author']);
        $this->assertArrayHasKey('timeTaken', $quote);

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://zenquotes.io/api/random';
        });
    }

    #[Test]
    public function it_handles_api_error()
    {
        // Mock the quote API response with an error
        Http::fake([
            'https://zenquotes.io/api/random' => Http::response([
                'message' => 'API rate limit exceeded'
            ], 429)
        ]);

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert error data is returned
        $this->assertStringContainsString('Unable to fetch quote from ZenQuotes', $quote['quote']);
        $this->assertEquals('Error', $quote['author']);
        $this->assertEquals(0, $quote['timeTaken']);
        $this->assertTrue($quote['error']);
        $this->assertArrayHasKey('errorMessage', $quote);
    }

    #[Test]
    public function it_handles_invalid_response()
    {
        // Mock the quote API response with invalid data
        Http::fake([
            'https://zenquotes.io/api/random' => Http::response([
                ['someOtherField' => 'value']
            ], 200)
        ]);

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert error data is returned
        $this->assertStringContainsString('Unable to fetch quote from ZenQuotes', $quote['quote']);
        $this->assertEquals('Error', $quote['author']);
        $this->assertEquals(0, $quote['timeTaken']);
        $this->assertTrue($quote['error']);
        $this->assertArrayHasKey('errorMessage', $quote);
    }

    #[Test]
    public function it_handles_empty_response()
    {
        // Mock the quote API response with empty array
        Http::fake([
            'https://zenquotes.io/api/random' => Http::response([], 200)
        ]);

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert error data is returned
        $this->assertStringContainsString('Unable to fetch quote from ZenQuotes', $quote['quote']);
        $this->assertEquals('Error', $quote['author']);
        $this->assertEquals(0, $quote['timeTaken']);
        $this->assertTrue($quote['error']);
        $this->assertArrayHasKey('errorMessage', $quote);
    }

    #[Test]
    public function it_handles_timeout()
    {
        // Mock a timeout exception
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert error data is returned
        $this->assertStringContainsString('Unable to fetch quote from ZenQuotes', $quote['quote']);
        $this->assertEquals('Error', $quote['author']);
        $this->assertEquals(0, $quote['timeTaken']);
        $this->assertTrue($quote['error']);
        $this->assertArrayHasKey('errorMessage', $quote);
    }
}
