<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DummyJsonService;
use App\Services\ZenQuotesService;
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

    function getFastestQuote(?Request $request = null) {
        $randomQuotes = $this->getRandomQuote($request)->getData(true);    
        $whoIsFastest = $randomQuotes['dummyJson']['isFastest'] ? 'dummyJson' : ($randomQuotes['zenQuotes']['isFastest'] ? 'zenQuotes' : 'dummyJson');
        return response()->json([
            'whoIsFastest' => $whoIsFastest,
            'quote' => $randomQuotes[$whoIsFastest]            
        ]);
    }

    /**
     * Get random quotes from both DummyJSON and ZenQuotes APIs
     *
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRandomQuote(?Request $request = null)
    {
        try {
            // Fetch quotes from both APIs with individual try/catch blocks
            try {
                $dummyJsonQuote = $this->dummyJsonService->getRandomQuote();
            } catch (\Exception $e) {
                Log::warning('Error fetching DummyJSON quote: ' . $e->getMessage());
                $dummyJsonQuote = [
                    'quote' => 'Unable to fetch quote from DummyJSON: ' . $e->getMessage(),
                    'author' => 'Error',
                    'timeTaken' => 0,
                    'error' => true,
                    'errorMessage' => $e->getMessage()
                ];
            }
            
            // ZenQuotes now handles its own errors and returns an error object
            $zenQuotesQuote = $this->zenQuotesService->getRandomQuote();
            
            // Determine which API was faster (only if both were successful)
            $dummyJsonTime = $dummyJsonQuote['timeTaken'] ?? 0;
            $zenQuotesTime = $zenQuotesQuote['timeTaken'] ?? 0;
            
            // Only mark as fastest if there was no error
            $dummyJsonQuote['isFastest'] = !isset($dummyJsonQuote['error']) && 
                ($dummyJsonTime <= $zenQuotesTime || isset($zenQuotesQuote['error']));
                
            $zenQuotesQuote['isFastest'] = !isset($zenQuotesQuote['error']) && 
                ($zenQuotesTime < $dummyJsonTime || isset($dummyJsonQuote['error']));
            
            // Return both quotes
            return response()->json([
                'dummyJson' => $dummyJsonQuote,
                'zenQuotes' => $zenQuotesQuote
            ]);
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
