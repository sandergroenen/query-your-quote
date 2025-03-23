<?php

namespace App\Http\Controllers\Api;

use App\Domain\Quotes\QuoteHandler;
use App\Http\Controllers\Controller;
use App\Domain\Quotes\DummyJsonService;
use App\Domain\Quotes\ZenQuotesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    protected DummyJsonService $dummyJsonService;
    protected ZenQuotesService $zenQuotesService;


    public function __construct(DummyJsonService $dummyJsonService, ZenQuotesService $zenQuotesService)
    {
        $this->dummyJsonService = $dummyJsonService;
        $this->zenQuotesService = $zenQuotesService;
    }


    /**
     * Get random quotes from all availble quote endpoints      *
     * @param \QuoteHandler|null $request
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function handle(?Request $request = null, QuoteHandler $quoteHandler): JsonResponse
    {
        try {
            
            if ($request->path() == 'api/quotes/random'){
                $allQuotesDto = $quoteHandler->getRandomQuote();
                // Return both quotes
                return response()->json(
                    $allQuotesDto
                );
            }elseif($request->path() == 'api/quotes/fastest'){
                $fastestQuotesDto = $quoteHandler->getFastestQuote();
                // Return both quotes
                return response()->json(
                    $fastestQuotesDto
                );
            }elseif($request->path() == 'api/quotes/streaming'){
               //
            }else{
                return response()->json([
                    'error' => 'Unknown endpoint: ' . $request->path()
                ], 500);
            }


        } catch (\Exception $e) {
            // Log the error with stack trace
            Log::error('Error fetching quotes: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to fetch quotes: ' . $e->getMessage()
            ], 500);
        }
    }
}
