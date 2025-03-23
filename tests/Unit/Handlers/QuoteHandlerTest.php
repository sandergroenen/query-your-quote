<?php

namespace Tests\Unit\Controllers;

use App\Domain\Dto\AllQuotesDto;
use App\Domain\Dto\QuoteDto;
use App\Domain\Dto\QuoteJsonResponse;
use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\QuoteHandler;
use App\Domain\Quotes\ZenQuotesService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuoteHandlerTest extends TestCase
{
    /**
     * @var Mockery\MockInterface&DummyJsonService
     */
    protected $dummyJsonService;
    /**
     * @var Mockery\MockInterface&ZenQuotesService
     */
    protected $zenQuotesService;
    /**
     * @var QuoteHandler
     */
    protected $handler;

    protected function setUp(): void
    {
        parent::setUp();
        // No need to create mocks here since we're doing it in each test
    }

    #[Test]
    public function it_gets_quotes_from_both_services()
    {
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse('dummyJson', 'DummyJSON quote', 'DummyJSON Author', 100, 'user'));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse('zenQuotes', 'ZenQuotes quote', 'ZenQuotes Author', 150));
        });
        
        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        // Call the handler method
        $response = $this->handler->getRandomQuote();

        // Assert response is a JsonResponse
        $this->assertInstanceOf(AllQuotesDto::class, $response);
        
        // Get the response content
        $this->assertTrue(get_class($response) === AllQuotesDto::class,var_export($response,true));
        
        // Assert the dummyJson and zenQuotes properties can be directly accessed from the AllQuotesDto object
        $this->assertTrue(get_class($response->dummyJson) === QuoteDto::class,var_export($response,true));
        $this->assertTrue(get_class($response->zenQuotes) === QuoteDto::class,var_export($response,true));
        
        // Assert DummyJSON quote data
        $this->assertEquals('DummyJSON quote', $response->dummyJson->jsonResponseQuote->quote);
        $this->assertEquals('DummyJSON Author', $response->dummyJson->jsonResponseQuote->author);
        $this->assertEquals(100,$response->dummyJson->jsonResponseQuote->timeTaken);
        $this->assertTrue($response->dummyJson->jsonResponseQuote->isFastest);
        
        // Assert Zenquotes quote data
        $this->assertEquals('ZenQuotes quote', $response->zenQuotes->jsonResponseQuote->quote);
        $this->assertEquals('ZenQuotes Author', $response->zenQuotes->jsonResponseQuote->author);
        $this->assertEquals(150,$response->zenQuotes->jsonResponseQuote->timeTaken);
        $this->assertFalse($response->zenQuotes->jsonResponseQuote->isFastest);
    }

    #[Test]
    public function it_marks_zenquotes_as_fastest_when_appropriate()
    {
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'dummyJson',
                    'DummyJSON quote',
                    'DummyJSON Author',
                    200,
                    'testuser'
                ));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'zenQuotes',
                    'ZenQuotes quote',
                    'ZenQuotes Author',
                    100
                ));
        });

        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        
        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert ZenQuotes is marked as fastest
        $this->assertFalse($response->dummyJson->isFastest);
        $this->assertTrue($response->zenQuotes->isFastest);
    }

    #[Test]
    public function it_handles_dummyjson_service_error()
    {
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'dummyJson',
                    '',
                    '',
                    0,
                    '',
                    true,
                    'API Error'
                ));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'zenQuotes',
                    'ZenQuotes quote',
                    'ZenQuotes Author',
                    150
                ));
        });

        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        
        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert DummyJSON has error and ZenQuotes is marked as fastest
        $this->assertTrue($response->dummyJson->error);
        $this->assertEquals('API Error', $response->dummyJson->errorMessage);
        $this->assertFalse($response->dummyJson->isFastest);
        $this->assertTrue($response->zenQuotes->isFastest);
    }

    #[Test]
    public function it_handles_zenquotes_service_error()
    {
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'dummyJson',
                    'DummyJSON quote',
                    'DummyJSON Author',
                    150,
                    'testuser'
                ));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'zenQuotes',
                    '',
                    '',
                    0,
                    '',
                    true,
                    'API Error'
                ));
        });

        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        
        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert ZenQuotes has error and DummyJSON is marked as fastest
        $this->assertTrue($response->zenQuotes->error);
        $this->assertEquals('API Error', $response->zenQuotes->errorMessage);
        $this->assertFalse($response->zenQuotes->isFastest);
        $this->assertTrue($response->dummyJson->isFastest);
    }

    #[Test]
    public function it_handles_both_services_failing()
    {
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'dummyJson',
                    '',
                    '',
                    0,
                    '',
                    true,
                    'DummyJSON API Error'
                ));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
                ->andReturn(new QuoteJsonResponse(
                    'zenQuotes',
                    '',
                    '',
                    0,
                    '',
                    true,
                    'ZenQuotes API Error'
                ));
        });

        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        
        // Call the handler method
        $response = $this->handler->getRandomQuote();
        
        // Assert both services have errors and neither is marked as fastest
        $this->assertTrue($response->dummyJson->error);
        $this->assertEquals('DummyJSON API Error', $response->dummyJson->errorMessage);
        $this->assertFalse($response->dummyJson->isFastest);
        
        $this->assertTrue($response->zenQuotes->error);
        $this->assertEquals('ZenQuotes API Error', $response->zenQuotes->errorMessage);
        $this->assertFalse($response->zenQuotes->isFastest);
    }

    #[Test]
    public function it_handles_unexpected_exceptions()
    {
        
        // Create mocks for the services
        $this->dummyJsonService = $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
            ->andReturn(new QuoteJsonResponse(
                'dummyJson',
                'DummyJSON quote',
                'DummyJSON Author',
                100,
                '',
                true,
                'Unexpected error'
            ));
        });
        
        $this->zenQuotesService = $this->mock(ZenQuotesService::class, function ($mock) {
            $mock->shouldReceive('getRandomQuote')
            ->andReturn(new QuoteJsonResponse(
                'zenQuotes',
                'ZenQuotes quote',
                'ZenQuotes Author',
                150
            ));
        });

        // Create the handler with the mocked services
        /** @phpstan-ignore-next-line */
        $this->handler = new QuoteHandler($this->dummyJsonService, $this->zenQuotesService);
        
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
