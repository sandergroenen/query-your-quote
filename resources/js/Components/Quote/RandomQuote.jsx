import { useState, useEffect } from 'react';
import axios from 'axios';
import ReactSpeedometer from 'react-d3-speedometer';

export default function RandomQuote() {
    const [quote, setQuote] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [requestTime, setRequestTime] = useState(0);
    const [speedometerKey, setSpeedometerKey] = useState(0);

    const fetchRandomQuote = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axios.get('/api/quotes/random');
            
            // Parse the time taken as a number and ensure it's valid
            let timeTaken = 0;
            try {
                timeTaken = parseFloat(response.data.timeTaken);
                if (isNaN(timeTaken) || !isFinite(timeTaken)) {
                    timeTaken = 0;
                }
            } catch (e) {
                console.error('Error parsing time taken:', e);
                timeTaken = 0;
            }
            
            // Update the request time state with a valid number
            setRequestTime(timeTaken);
            
            // Set the quote data
            setQuote(response.data);
            
            // Increment the key to force remounting of the speedometer
            setSpeedometerKey(prev => prev + 1);
        } catch (err) {
            setError('Failed to fetch quote: ' + (err.response?.data?.error || err.message));
            console.error('Error fetching quote:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchRandomQuote();
    }, []);

    // Ensure the value passed to the speedometer is always a valid number
    const safeRequestTime = typeof requestTime === 'number' && isFinite(requestTime) ? requestTime : 0;

    return (
        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
            <div className="text-gray-900 dark:text-gray-100">
                <h2 className="text-xl font-semibold mb-4">Random Quote</h2>
                
                {loading && (
                    <div className="flex justify-center items-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white"></div>
                    </div>
                )}
                
                {error && (
                    <div className="bg-red-100 dark:bg-red-900 p-4 rounded-md text-red-800 dark:text-red-200">
                        {error}
                    </div>
                )}
                
                {!loading && !error && quote && (
                    <div className="space-y-6">
                        <div className="space-y-4">
                            <blockquote className="italic text-lg border-l-4 border-gray-300 dark:border-gray-600 pl-4 py-2">
                                "{quote.quote}"
                            </blockquote>
                            <p className="text-right font-semibold">— {quote.author}</p>
                        </div>
                        
                        {quote.user && (
                            <div className="mt-6 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                <h3 className="text-lg font-medium mb-2">Quote fetched using:</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p><span className="font-semibold">Username:</span> {quote.user.username}</p>
                                        <p><span className="font-semibold">Name:</span> {quote.user.firstName} {quote.user.lastName}</p>
                                        <p><span className="font-semibold">Email:</span> {quote.user.email}</p>
                                        <p><span className="font-semibold">Password:</span> ********</p>
                                    </div>
                                    <div className="flex justify-center items-center">
                                        <div className="text-center w-full">
                                            <p className="text-sm mb-2">Request Time: {safeRequestTime} ms</p>
                                            
                                            {!loading && safeRequestTime > 0 && (
                                                <div key={speedometerKey}>
                                                    <ReactSpeedometer
                                                        forceRender={true}
                                                        maxValue={2000}
                                                        value={safeRequestTime}
                                                        needleColor="red"
                                                        startColor="green"
                                                        endColor="red"
                                                        segments={5}
                                                        width={200}
                                                        height={130}
                                                        ringWidth={20}
                                                        needleHeightRatio={0.7}
                                                        textColor="currentColor"
                                                        valueFormat="d"
                                                        currentValueText={`${Math.round(safeRequestTime)} ms`}
                                                    />
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                        
                        <div className="flex justify-center mt-6">
                            <button
                                onClick={fetchRandomQuote}
                                className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                disabled={loading}
                            >
                                {loading ? 'Loading...' : 'Get Another Quote'}
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
