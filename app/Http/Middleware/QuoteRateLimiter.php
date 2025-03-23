<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class QuoteRateLimiter
{
    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new rate limiter middleware.
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, $maxAttempts = 5, $decaySeconds = 60)
    {
        // Ensure parameters are integers
        $maxAttempts = (int) $maxAttempts;
        $decaySeconds = (int) $decaySeconds;
        
        // Log the parameters for debugging
        Log::debug("QuoteRateLimiter: Checking rate limit", [
            'maxAttempts' => $maxAttempts,
            'decaySeconds' => $decaySeconds,
            'ip' => $request->ip(),
        ]);
        
        $key = 'quotes:' . $request->ip();

        // Check if the request exceeds the rate limit
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            Log::debug("QuoteRateLimiter: Rate limit exceeded", [
                'key' => $key,
                'maxAttempts' => $maxAttempts,
                'attempts' => $this->limiter->attempts($key),
                'remaining' => $this->limiter->remaining($key, $maxAttempts),
                'retryAfter' => $this->limiter->availableIn($key),
            ]);
            
            return $this->buildResponse($key, $maxAttempts);
        }

        // Increment the counter
        $this->limiter->hit($key, $decaySeconds);
        
        Log::debug("QuoteRateLimiter: Request allowed", [
            'key' => $key,
            'maxAttempts' => $maxAttempts,
            'attempts' => $this->limiter->attempts($key),
            'remaining' => $this->limiter->remaining($key, $maxAttempts),
        ]);

        $response = $next($request);

        // Add rate limit headers to the response
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->limiter->remaining($key, $maxAttempts),
            $this->limiter->availableIn($key)
        );
    }

    /**
     * Create a response for when the rate limit is exceeded.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function buildResponse($key, $maxAttempts)
    {
        $retryAfter = $this->limiter->availableIn($key);

        $response = response()->json([
            'message' => 'Too many requests. Please try again later.',
            'retryAfter' => $retryAfter,
        ], 429);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            0,
            $retryAfter
        );
    }

    /**
     * Add the rate limit headers to the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function addHeaders(Response $response, $maxAttempts, $remainingAttempts, $retryAfter = null)
    {
        $response->headers->add([
            'X-RateLimit-Remaining' => $remainingAttempts,
            'X-RateLimit-Limit' => $maxAttempts,
        ]);

        if (! is_null($retryAfter)) {
            $response->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Reset' => $retryAfter,
            ]);
        }

        return $response;
    }

    public function resetAttempts(String $ip): void
    {
        $key = 'quotes:' . $ip;
        $this->limiter->clear($key);
    }

    public function getRemainingAttempts(String $ip, int $maxAttempts): int
    {
        $key = 'quotes:' . $ip;
        return $this->limiter->remaining($key, $maxAttempts);
    }
}
