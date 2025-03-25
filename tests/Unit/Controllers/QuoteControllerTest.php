<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\QuoteController;
use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\ZenQuotesService;
use Exception;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QuoteControllerTest extends TestCase
{
    protected $dummyJsonService;
    protected $zenQuotesService;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock services
        $this->dummyJsonService = Mockery::mock(DummyJsonService::class);
        $this->zenQuotesService = Mockery::mock(ZenQuotesService::class);
        
        // Create controller with mock services
        /** @disregard p1006 */
        $this->controller = new QuoteController(
            $this->dummyJsonService,
            $this->zenQuotesService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_gets_quotes_from_both_services()
    {
        // Set up mock responses
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'DummyJSON quote',
                'author' => 'DummyJSON Author',
                'timeTaken' => 100,
                'user' => ['id' => 1, 'username' => 'testuser']
            ]);
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'ZenQuotes quote',
                'author' => 'ZenQuotes Author',
                'timeTaken' => 150
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();

        // Assert response is a JsonResponse
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Get the response content
        $content = json_decode($response->getContent(), true);
        
        // Assert the response contains both quotes
        $this->assertArrayHasKey('dummyJson', $content);
        $this->assertArrayHasKey('zenQuotes', $content);
        
        // Assert DummyJSON quote data
        $this->assertEquals('DummyJSON quote', $content['dummyJson']['quote']);
        $this->assertEquals('DummyJSON Author', $content['dummyJson']['author']);
        $this->assertEquals(100, $content['dummyJson']['timeTaken']);
        $this->assertTrue($content['dummyJshttps://zenquotes.io/apion']['isFastest']);
        
        // Assert ZenQuotes quote data
        $this->assertEquals('ZenQuotes quote', $content['zenQuotes']['quote']);
        $this->assertEquals('ZenQuotes Author', $content['zenQuotes']['author']);
        $this->assertEquals(150, $content['zenQuotes']['timeTaken']);
        $this->assertFalse($content['zenQuotes']['isFastest']);
    }

    #[Test]
    public function it_marks_zenquotes_as_fastest_when_appropriate()
    {
        // Set up mock responses with ZenQuotes being faster
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'DummyJSON quote',
                'author' => 'DummyJSON Author',
                'timeTaken' => 200,
                'user' => ['id' => 1, 'username' => 'testuser']
            ]);
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'ZenQuotes quote',
                'author' => 'ZenQuotes Author',
                'timeTaken' => 100
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();
        $content = json_decode($response->getContent(), true);
        
        // Assert ZenQuotes is marked as fastest
        $this->assertFalse($content['dummyJson']['isFastest']);
        $this->assertTrue($content['zenQuotes']['isFastest']);
    }

    #[Test]
    public function it_handles_dummyjson_service_error()
    {
        // Set up mock responses with DummyJSON error
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'Unable to fetch quote from DummyJSON: Error message',
                'author' => 'Error',
                'timeTaken' => 0,
                'error' => true,
                'errorMessage' => 'Error message'
            ]);
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'ZenQuotes quote',
                'author' => 'ZenQuotes Author',
                'timeTaken' => 100
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();
        $content = json_decode($response->getContent(), true);
        
        // Assert DummyJSON has error and ZenQuotes is marked as fastest
        $this->assertTrue($content['dummyJson']['error']);
        $this->assertFalse($content['dummyJson']['isFastest'] ?? false);
        $this->assertTrue($content['zenQuotes']['isFastest']);
    }

    #[Test]
    public function it_handles_zenquotes_service_error()
    {
        // Set up mock responses with ZenQuotes error
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'DummyJSON quote',
                'author' => 'DummyJSON Author',
                'timeTaken' => 100,
                'user' => ['id' => 1, 'username' => 'testuser']
            ]);
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'Unable to fetch quote from ZenQuotes: Error message',
                'author' => 'Error',
                'timeTaken' => 0,
                'error' => true,
                'errorMessage' => 'Error message'
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();
        $content = json_decode($response->getContent(), true);
        
        // Assert ZenQuotes has error and DummyJSON is marked as fastest
        $this->assertTrue($content['zenQuotes']['error']);
        $this->assertFalse($content['zenQuotes']['isFastest'] ?? false);
        $this->assertTrue($content['dummyJson']['isFastest']);
    }

    #[Test]
    public function it_handles_both_services_failing()
    {
        // Set up mock responses with both services failing
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'Unable to fetch quote from DummyJSON: Error message',
                'author' => 'Error',
                'timeTaken' => 0,
                'error' => true,
                'errorMessage' => 'Error message'
            ]);
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'Unable to fetch quote from ZenQuotes: Error message',
                'author' => 'Error',
                'timeTaken' => 0,
                'error' => true,
                'errorMessage' => 'Error message'
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();
        $content = json_decode($response->getContent(), true);
        
        // Assert both services have errors and neither is marked as fastest
        $this->assertTrue($content['dummyJson']['error']);
        $this->assertTrue($content['zenQuotes']['error']);
        $this->assertFalse($content['dummyJson']['isFastest'] ?? false);
        $this->assertFalse($content['zenQuotes']['isFastest'] ?? false);
    }

    #[Test]
    public function it_handles_unexpected_exceptions()
    {
        // Set up the DummyJSON service to throw an exception
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andThrow(new Exception('Unexpected error'));
            
        // ZenQuotes service is still called because the exception is caught
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn([
                'quote' => 'ZenQuotes quote',
                'author' => 'ZenQuotes Author',
                'timeTaken' => 100
            ]);

        // Call the controller method
        $response = $this->controller->getRandomQuote();
        
        // Get the response content
        $content = json_decode($response->getContent(), true);
        
        // Assert the response contains the expected structure
        $this->assertArrayHasKey('dummyJson', $content);
        $this->assertArrayHasKey('zenQuotes', $content);
        
        // Check DummyJson error details
        $this->assertTrue($content['dummyJson']['error']);
        $this->assertStringContainsString('Unexpected error', $content['dummyJson']['errorMessage']);
        $this->assertEquals('Error', $content['dummyJson']['author']);
        $this->assertFalse($content['dummyJson']['isFastest']);
        
        // Check ZenQuotes is marked as fastest by default
        $this->assertTrue($content['zenQuotes']['isFastest']);
        
        // Assert the response status code is 200 (not 500 as we expected)
        $this->assertEquals(200, $response->getStatusCode());
    }
}
