<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Health check route for AWS load balancer
Route::get('/health', function () {
    return response('OK', 200);
});



Route::get('/', function () {
    return Inertia::render('Quotes', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware(['auth', 'verified'])->name('quotes');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-broadcast', function () {
    $dummyJsonQuote = [
        'quote' => 'Test quote from DummyJSON',
        'author' => 'Test Author',
        'timeTaken' => 0,
        'isFastest' => true
    ];
    
    $zenQuotesQuote = [
        'quote' => 'Test quote from ZenQuotes',
        'author' => 'Test Author',
        'timeTaken' => 0,
        'isFastest' => false
    ];
    
    event(new \App\Domain\Events\QuoteRetrieved(new \App\Domain\Dto\QuotesDto($dummyJsonQuote, $zenQuotesQuote)));
    
    return 'Event dispatched!';
});

require __DIR__.'/auth.php';
