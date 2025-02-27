<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DummyJsonService;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    protected $dummyJsonService;

    public function __construct(DummyJsonService $dummyJsonService)
    {
        $this->dummyJsonService = $dummyJsonService;
    }

    /**
     * Get a random quote from DummyJSON API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRandomQuote()
    {
        try {
            $quote = $this->dummyJsonService->getRandomQuote();
            return response()->json($quote);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
