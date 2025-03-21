<?php

namespace Tests\Unit\Controllers;

use App\Domain\Dto\AllQuotesDto;
use App\Domain\Dto\QuoteDto;
use App\Domain\Dto\QuoteJsonResponse;
use App\Http\Controllers\Api\QuoteController;
use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\QuoteHandler;
use App\Domain\Quotes\ZenQuotesService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QuoteControllerTest extends TestCase
{
    protected $dummyJsonService;
    protected $zenQuotesService;
    protected $handler;

    protected function setUp(): void
    {
        parent::setUp();
    
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse('dummyJson', 'DummyJSON quote', 'DummyJSON Author', 100, 'user'));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse('zenQuotes', 'ZenQuotes quote', 'ZenQuotes Author', 150, 'user'));
        });
    
        
        // Create the controller
        $this->handler = $this->app->make(QuoteHandler::class); 
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_gets_quotes_from_both_services()
    {

        // $request = Mockery::mock(Request::class, function ($mock) { 
        //     $mock->shouldReceive('path')->once()->andReturn('api/quotes/random');
        // });

        // Call the controller method
        $response = $this->handler->getRandomQuote();

        // Assert response is a JsonResponse
        $this->assertInstanceOf(AllQuotesDto::class, $response);
        
        // Get the response content
        $this->assertTrue(get_class($response) === AllQuotesDto::class,var_export($response,true));
        
        // Assert the dummyJson and zenQuotes properties can be directly accessed from the AllQuotesDto object
        $this->assertTrue(get_class($response->dummyJson) === QuoteDto::class,var_export($response,true));
        $this->assertTrue(get_class($response->zenQuotes) === QuoteDto::class,var_export($response,true));
        
        // Assert DummyJSON quote data
        $this->assertEquals('DummyJSON quote', $response->dummyJson->quote->quote);
        $this->assertEquals('DummyJSON Author', $response->dummyJson->quote->author);
        $this->assertEquals(100,$response->dummyJson->quote->timeTaken);
        $this->assertTrue($response->dummyJson->quote->isFastest);
        
        // Assert Zenquotes quote data
        $this->assertEquals('ZenQuotes quote', $response->zenQuotes->quote->quote);
        $this->assertEquals('ZenQuotes Author', $response->zenQuotes->quote->author);
        $this->assertEquals(150,$response->zenQuotes->quote->timeTaken);
        $this->assertFalse($response->zenQuotes->quote->isFastest);
    }

    #[Test]
    public function it_marks_zenquotes_as_fastest_when_appropriate()
    {
        // Set up mock responses with ZenQuotes faster
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'dummyJson',
                'DummyJSON quote',
                'DummyJSON Author',
                200,
                'testuser'
            ));
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                'ZenQuotes quote',
                'ZenQuotes Author',
                100
            ));

        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert ZenQuotes is marked as fastest
        $this->assertFalse($response->dummyJson->isFastest);
        $this->assertTrue($response->zenQuotes->isFastest);
    }

    #[Test]
    public function it_handles_dummyjson_service_error()
    {
        // Set up mock responses with DummyJSON error
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'dummyJson',
                '',
                '',
                0,
                '',
                true,
                'API Error'
            ));
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                'ZenQuotes quote',
                'ZenQuotes Author',
                150
            ));

        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert DummyJSON has error and ZenQuotes is marked as fastest
        $this->assertTrue($response->dummyJson->quote->error);
        $this->assertEquals('API Error', $response->dummyJson->quote->errorMessage);
        $this->assertFalse($response->dummyJson->quote->isFastest);
        $this->assertTrue($response->zenQuotes->quote->isFastest);
    }

    #[Test]
    public function it_handles_zenquotes_service_error()
    {
        // Set up mock responses with ZenQuotes error
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'dummyJson',
                'DummyJSON quote',
                'DummyJSON Author',
                150,
                'testuser'
            ));
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                '',
                '',
                0,
                '',
                true,
                'API Error'
            ));

        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert ZenQuotes has error and DummyJSON is marked as fastest
        $this->assertTrue($response->zenQuotes->quote->error);
        $this->assertEquals('API Error', $response->zenQuotes->quote->errorMessage);
        $this->assertFalse($response->zenQuotes->quote->isFastest);
        $this->assertTrue($response->dummyJson->quote->isFastest);
    }

    #[Test]
    public function it_handles_both_services_failing()
    {
        // Set up mock responses with both services failing
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'dummyJson',
                '',
                '',
                0,
                '',
                true,
                'DummyJSON API Error'
            ));
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                '',
                '',
                0,
                '',
                true,
                'ZenQuotes API Error'
            ));

        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert both services have errors and neither is marked as fastest
        $this->assertTrue($response->dummyJson->quote->error);
        $this->assertEquals('DummyJSON API Error', $response->dummyJson->quote->errorMessage);
        $this->assertFalse($response->dummyJson->quote->isFastest);
        
        $this->assertTrue($response->zenQuotes->quote->error);
        $this->assertEquals('ZenQuotes API Error', $response->zenQuotes->quote->errorMessage);
        $this->assertFalse($response->zenQuotes->quote->isFastest);
    }

    #[Test]
    public function it_handles_unexpected_exceptions()
    {
        // Set up DummyJSON service to throw an exception
        $this->dummyJsonService->shouldReceive('getRandomQuote')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));
            
        $this->zenQuotesService->shouldReceive('getRandomQuote')
            ->once()
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                'ZenQuotes quote',
                'ZenQuotes Author',
                150
            ));

        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Get the response content
        $this->assertInstanceOf(AllQuotesDto::class, $response);
        
        // Assert DummyJSON has error and ZenQuotes is marked as fastest
        $this->assertTrue($response->dummyJson->error);
        $this->assertStringContainsString('Unexpected error', $response->dummyJson->errorMessage);
        $this->assertFalse($response->dummyJson->isFastest);
        $this->assertTrue($response->zenQuotes->isFastest);
    }
}
