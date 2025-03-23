<?php

namespace Tests\Unit\Services;

use App\Domain\Quotes\DummyJsonService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DummyJsonServiceTest extends TestCase
{
    protected DummyJsonService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DummyJsonService();
        
        // Clear the cache before each test
        Cache::forget('dummyjson_token');
        Cache::forget('dummyjson_user');
        
        // Prevent actual HTTP requests
        Http::preventStrayRequests();
    }

    #[Test]
    public function it_can_login_with_random_user()
    {
        // Mock the users API response
        Http::fake([
            'https://dummyjson.com/users' => Http::response([
                'users' => [
                    [
                        'id' => 1,
                        'username' => 'testuser',
                        'firstName' => 'Test',
                        'lastName' => 'User',
                        'email' => 'test@example.com',
                        'password' => 'password123'
                    ]
                ]
            ], 200),
            'https://dummyjson.com/auth/login' => Http::response([
                'token' => 'fake-token-123'
            ], 200)
        ]);

        // Call the login method
        $token = $this->service->login();

        // Assert token is returned
        $this->assertEquals('fake-token-123', $token);

        // Assert HTTP requests were made
        Http::assertSent(function ($request) {
            return $request->url() == 'https://dummyjson.com/users';
        });
        
        Http::assertSent(function ($request) {
            return $request->url() == 'https://dummyjson.com/auth/login' &&
                   $request->data() == [
                       'username' => 'testuser',
                       'password' => 'password123',
                       'expiresInMins' => 30
                   ];
        });

        // Assert token was cached
        $this->assertEquals('fake-token-123', Cache::get('dummyjson_token'));
    }

    #[Test]
    public function it_returns_cached_token_if_available()
    {
        // Set up a cached token
        Cache::put('dummyjson_token', 'cached-token-456', now()->addMinutes(30));
        Cache::put('dummyjson_user', ['id' => 2, 'username' => 'cacheduser'], now()->addMinutes(30));

        // Call the login method
        $token = $this->service->login();

        // Assert cached token is returned
        $this->assertEquals('cached-token-456', $token);

        // Assert no HTTP requests were made
        Http::assertNothingSent();
    }

    #[Test]
    public function it_handles_login_failure()
    {
        // Mock the users API response
        Http::fake([
            'https://dummyjson.com/users' => Http::response([
                'users' => [
                    [
                        'id' => 1,
                        'username' => 'testuser',
                        'firstName' => 'Test',
                        'lastName' => 'User',
                        'email' => 'test@example.com'
                    ]
                ]
            ], 200),
            'https://dummyjson.com/auth/login' => Http::response([
                'message' => 'Invalid credentials'
            ], 401)
        ]);

        // Expect an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to authenticate with DummyJSON API');

        // Call the login method
        $this->service->login();
    }

    #[Test]
    public function it_handles_empty_users_response()
    {
        // Mock the users API response with empty users array
        Http::fake([
            'https://dummyjson.com/users' => Http::response([
                'users' => []
            ], 200)
        ]);

        // Expect an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No users found in the DummyJSON API response');

        // Call the login method
        $this->service->login();
    }

    #[Test]
    public function it_can_get_random_quote()
    {
        // Mock the login method to return a token
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                 ->makePartial()
                 ->shouldReceive('login')
                 ->once()
                 ->andReturn('test-token');
        })->makePartial();

        // Mock the quote API response
        Http::fake([
            'https://dummyjson.com/quotes/random' => Http::response([
                'quote' => 'This is a test quote',
                'author' => 'Test Author'
            ], 200)
        ]);

        // Get an instance with the mocked login method
        $service = app(DummyJsonService::class);
        
        // Set the current user
        $reflectionClass = new \ReflectionClass($service);
        $reflectionProperty = $reflectionClass->getProperty('currentUser');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, [
            'id' => 1,
            'username' => 'testuser'
        ]);
        
        // Set the access token
        $reflectionProperty = $reflectionClass->getProperty('accessToken');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, 'test-token');

        // Call the getRandomQuote method
        $quote = $service->getRandomQuote();

        // Assert the quote data is returned
        $this->assertEquals('This is a test quote', $quote->quote);
        $this->assertEquals('Test Author', $quote->author);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertEquals('testuser', $quote->user);

        // Assert HTTP request was made with the token
        Http::assertSent(function ($request) {
            return $request->url() == 'https://dummyjson.com/quotes/random' &&
                   $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    #[Test]
    public function it_handles_quote_api_error()
    {
        // Mock the login method to return a token
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                 ->makePartial()
                 ->shouldReceive('login')
                 ->once()
                 ->andReturn('test-token');
        })->makePartial();

        // Mock the quote API response with an error
        Http::fake([
            'https://dummyjson.com/quotes/random' => Http::response([
                'message' => 'Quote API error'
            ], 500)
        ]);

        // Get an instance with the mocked login method
        $service = app(DummyJsonService::class);
        
        // Set the current user
        $reflectionClass = new \ReflectionClass($service);
        $reflectionProperty = $reflectionClass->getProperty('currentUser');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, [
            'id' => 1,
            'username' => 'testuser'
        ]);
        
        // Set the access token
        $reflectionProperty = $reflectionClass->getProperty('accessToken');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, 'test-token');

        // Call the getRandomQuote method
        $quote = $service->getRandomQuote();

        // Assert the error response
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('Quote API error', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertEquals('testuser', $quote->user);

        // Assert HTTP request was made with the token
        Http::assertSent(function ($request) {
            return $request->url() == 'https://dummyjson.com/quotes/random' &&
                   $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }

    #[Test]
    public function it_handles_invalid_quote_response()
    {
        // Mock the login method to return a token
        $this->mock(DummyJsonService::class, function ($mock) {
            $mock->shouldAllowMockingProtectedMethods()
                 ->makePartial()
                 ->shouldReceive('login')
                 ->once()
                 ->andReturn('test-token');
        })->makePartial();

        // Mock the quote API response with invalid data
        Http::fake([
            'https://dummyjson.com/quotes/random' => Http::response([
                'invalid' => 'data'
            ], 200)
        ]);

        // Get an instance with the mocked login method
        $service = app(DummyJsonService::class);
        
        // Set the current user
        $reflectionClass = new \ReflectionClass($service);
        $reflectionProperty = $reflectionClass->getProperty('currentUser');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, [
            'id' => 1,
            'username' => 'testuser'
        ]);
        
        // Set the access token
        $reflectionProperty = $reflectionClass->getProperty('accessToken');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($service, 'test-token');

        // Call the getRandomQuote method
        $quote = $service->getRandomQuote();

        // Assert the error response
        $this->assertTrue($quote->error);
        $this->assertStringContainsString('Invalid response', $quote->errorMessage);
        $this->assertIsNumeric($quote->timeTaken);
        $this->assertEquals('testuser', $quote->user);

        // Assert HTTP request was made with the token
        Http::assertSent(function ($request) {
            return $request->url() == 'https://dummyjson.com/quotes/random' &&
                   $request->hasHeader('Authorization', 'Bearer test-token');
        });
    }
}
