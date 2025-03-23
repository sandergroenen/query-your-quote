<?php

use App\Http\Controllers\Api\QuoteController;
use App\Http\Middleware\QuoteRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Use the rate limit specified in the request query parameter or default to 1
Route::get('/quotes/random', function (Request $request) {
    // Get the rate limit from the query parameter or default to 1
    $rateLimit = (int) $request->query('rateLimit', 1);
    $decaySeconds = 10;
    
    // Create a new instance of the rate limiter middleware with the parameters
    $rateLimiter = new QuoteRateLimiter(app('Illuminate\Cache\RateLimiter'));
    
    // Call the handle method directly with the parameters
    return $rateLimiter->handle($request, function ($request) {
        return app()->make(QuoteController::class)->handle(app('App\Domain\Quotes\QuoteHandler'),$request);
    }, $rateLimit, $decaySeconds);
});

Route::get('/quotes/fastest', function(Request $request){
    return app()->make(QuoteController::class)->handle(app('App\Domain\Quotes\QuoteHandler'),$request);
}); 
