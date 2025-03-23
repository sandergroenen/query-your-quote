import { useState, useRef } from 'react';

export default function RateLimitDemo() {
    const [quotes, setQuotes] = useState([]);
    const [loading, setLoading] = useState(false);
    const [rateLimit, setRateLimit] = useState(1);
    const [simultaneousRequests, setSimultaneousRequests] = useState(10);
    const abortControllerRef = useRef(null);
    const [loadingStates, setLoadingStates] = useState([]);

    const handleRateLimitChange = (e) => {
        const value = parseInt(e.target.value);
        if (value >= 1 && value <= 10) {
            setRateLimit(value);
        }
    };

    const handleSimultaneousRequestsChange = (e) => {
        const value = parseInt(e.target.value);
        if (value >= 1 && value <= 10) {
            setSimultaneousRequests(value);
        }
    };

    const fetchQuoteUntilRateLimit = async () => {
        // Clear previous quotes
        setQuotes([]);
        setLoading(true);

        // Initialize loading states for each request
        const initialLoadingStates = Array(simultaneousRequests).fill(true);
        setLoadingStates(initialLoadingStates);

        // Create a new AbortController to cancel previous requests if needed
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        abortControllerRef.current = new AbortController();

        // Create an array to collect all results
        const results = [];
        
        // Use fetch instead of axios for better parallel processing
        const promises = [];
        
        for (let i = 0; i < simultaneousRequests; i++) {
            // Add a unique timestamp to prevent caching and pass the rate limit
            const url = `/api/quotes/random?t=${Date.now()}-${i}&rateLimit=${rateLimit}`;
            
            // Use fetch API directly
            const promise = fetch(url, {
                signal: abortControllerRef.current.signal,
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(async response => {
                // Get headers
                const rateLimitLimit = response.headers.get('X-RateLimit-Limit');
                const rateLimitRemaining = response.headers.get('X-RateLimit-Remaining');
                console.log(data);
                // Handle rate limit error
                if (response.status === 429) {
                    const data = await response.json();
                    const retryAfter = data.retryAfter || 10000;
                    const retrySeconds = Math.ceil(retryAfter / 1000);
                    
                    results.push({
                        id: `${i}-rate-limited`,
                        content: `Request ${i+1}: Rate limit hit! Retry after ${retrySeconds} seconds`,
                        isRateLimited: true,
                        timestamp: Date.now()
                    });
                    
                    // Update loading state for this request
                    setLoadingStates(prev => {
                        const newStates = [...prev];
                        newStates[i] = false;
                        return newStates;
                    });
                    
                    return;
                }
                
                // Handle other errors
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(errorText || `HTTP error! status: ${response.status}`);
                }
                
                // Parse JSON response
                const data = await response.json();
                
                // Check for API errors
                const dummyJsonError = data.dummyJson.error;
                const zenQuotesError = data.zenQuotes.error;
                
                let content = `Request ${i+1}: `;
                
                // Format the content based on errors
                if (dummyJsonError && zenQuotesError) {
                    // Both APIs had errors
                    content += `Both APIs returned errors: \n`;
                    content += `DummyJSON: ${data.dummyJson.jsonResponseQuote}\n`;
                    content += `ZenQuotes: ${data.zenQuotes.jsonResponseQuote}`;
                } else if (dummyJsonError) {
                    // Only DummyJSON had an error
                    content += `DummyJSON Error: ${data.dummyJson.jsonResponseQuote}\n`;
                    content += `ZenQuotes: "${data.zenQuotes.jsonResponseQuote}" - ${data.zenQuotes.author}`;
                } else if (zenQuotesError) {
                    // Only ZenQuotes had an error
                    content += `DummyJSON: "${data.dummyJson.jsonResponseQuote}" - ${data.dummyJson.author}\n`;
                    content += `ZenQuotes Error: ${data.zenQuotes.quote}`;
                } else {
                    // No errors
                    content += `DummyJSON: "${data.dummyJson.jsonResponseQuote}" - ${data.dummyJson.author}\n`;
                    content += `ZenQuotes: "${data.zenQuotes.jsonResponseQuote}" - ${data.zenQuotes.author}`;
                }
                
                // Add user info if available
                if (data.dummyJson.user && !dummyJsonError) {
                    const user = data.dummyJson.user;
                    content += data.dummyJson.user;
                }
                
                // Add to results array
                results.push({
                    id: `${i}-success`,
                    content: content,
                    isRateLimited: false,
                    hasApiErrors: dummyJsonError || zenQuotesError,
                    rateLimitInfo: `Remaining: ${rateLimitRemaining}/${rateLimitLimit}`,
                    timestamp: Date.now()
                });
                
                // Update loading state for this request
                setLoadingStates(prev => {
                    const newStates = [...prev];
                    newStates[i] = false;
                    return newStates;
                });
            })
            .catch(error => {
                if (abortControllerRef.current?.signal.aborted) return;
                
                // Handle other errors
                results.push({
                    id: `${i}-error`,
                    content: `Request ${i+1}: Error - ${error.message}`,
                    isRateLimited: false,
                    isError: true,
                    timestamp: Date.now()
                });
                
                // Update loading state for this request
                setLoadingStates(prev => {
                    const newStates = [...prev];
                    newStates[i] = false;
                    return newStates;
                });
            });
            
            promises.push(promise);
        }

        try {
            // Wait for all requests to complete or fail
            await Promise.allSettled(promises);
            
            // Sort results by timestamp and update state once
            results.sort((a, b) => a.timestamp - b.timestamp);
            setQuotes(results);
        } finally {
            setLoading(false);
            setLoadingStates(Array(simultaneousRequests).fill(false));
            abortControllerRef.current = null;
        }
    };

    return (
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
            <h2 className="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Rate Limit Demonstration</h2>
            
            <div className="flex flex-wrap gap-4 mb-4">
                <div className="flex items-center">
                    <label htmlFor="rateLimit" className="mr-2 text-gray-700 dark:text-gray-300">Rate Limit (per 10 seconds):</label>
                    <input 
                        type="number" 
                        id="rateLimit" 
                        min="1" 
                        max="10" 
                        value={rateLimit} 
                        onChange={handleRateLimitChange}
                        className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-20"
                    />
                </div>
                
                <div className="flex items-center">
                    <label htmlFor="simultaneousRequests" className="mr-2 text-gray-700 dark:text-gray-300">Nr of simultaneous requests:</label>
                    <input 
                        type="number" 
                        id="simultaneousRequests" 
                        min="1" 
                        max="10" 
                        value={simultaneousRequests} 
                        onChange={handleSimultaneousRequestsChange}
                        className="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-20"
                    />
                </div>
                
                <button 
                    onClick={fetchQuoteUntilRateLimit}
                    disabled={loading}
                    className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {loading ? 'Fetching...' : 'Fetch until rate limit'}
                </button>
                
                {loading && (
                    <button 
                        onClick={() => {
                            if (abortControllerRef.current) {
                                abortControllerRef.current.abort();
                                setLoading(false);
                                setLoadingStates(Array(simultaneousRequests).fill(false));
                            }
                        }}
                        className="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        Stop
                    </button>
                )}
            </div>
            
            <div className="mt-4 border border-gray-200 dark:border-gray-700 rounded-md p-4 max-h-96 overflow-y-auto bg-gray-50 dark:bg-gray-900">
                {quotes.length === 0 && !loading ? (
                    <p className="text-gray-500 dark:text-gray-400 text-center">Click the button to start fetching quotes</p>
                ) : (
                    <div>
                        {/* Loading indicators */}
                        {loading && (
                            <div className="mb-4 grid grid-cols-5 gap-2">
                                {loadingStates.map((isLoading, index) => (
                                    <div key={`loading-${index}`} className="flex items-center">
                                        <span className="text-sm text-gray-500 dark:text-gray-400 mr-2">Request {index + 1}:</span>
                                        {isLoading ? (
                                            <svg className="animate-spin h-4 w-4 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        ) : (
                                            <svg className="h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                            </svg>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                        
                        {/* Results */}
                        <ul className="space-y-2">
                            {quotes.map((quote) => (
                                <li 
                                    key={quote.id} 
                                    className={`p-2 rounded ${quote.isRateLimited ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200' : quote.isError || quote.hasApiErrors ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200' : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200'}`}
                                >
                                    <div className="whitespace-pre-line">{quote.content}</div>
                                    {quote.rateLimitInfo && (
                                        <span className="text-sm text-gray-500 dark:text-gray-400 ml-2">[{quote.rateLimitInfo}]</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
}
