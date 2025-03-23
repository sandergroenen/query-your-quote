<?php

namespace Tests\Unit\Services;

use App\Domain\Quotes\ZenQuotesService;
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
        $this->assertEquals('This is a test quote from ZenQuotes', $quote->quote);
        $this->assertEquals('ZenQuotes Author', $quote->author);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertNull($quote->user);

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
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('API rate limit exceeded', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertNull($quote->user);

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://zenquotes.io/api/random';
        });
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
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('Invalid response', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertNull($quote->user);

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://zenquotes.io/api/random';
        });
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
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('Invalid response', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertNull($quote->user);

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://zenquotes.io/api/random';
        });
    }

    #[Test]
    public function it_handles_timeout()
    {
        // Mock a timeout exception
        Http::fake([
            'https://zenquotes.io/api/random' => Http::response(null, 500, [], 0.001)
        ]);

        // Call the getRandomQuote method
        $quote = $this->service->getRandomQuote();

        // Assert error data is returned
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('HTTP 500', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertNull($quote->user);

        // Assert HTTP request was made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://zenquotes.io/api/random';
        });
    }
}
