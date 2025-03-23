import { useState, useEffect } from 'react';
import axios from 'axios';
import ReactSpeedometer from 'react-d3-speedometer';

export default function RandomQuote() {
    const [quotes, setQuotes] = useState({
        dummyJson: null,
        zenQuotes: null
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [speedometerKey, setSpeedometerKey] = useState(0);

    const fetchRandomQuotes = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axios.get('/api/quotes/random');
            console.log('random quotes response:', response.data.quotes);
            // Set the quotes data
            setQuotes(response.data.quotes);
            // Increment the key to force remounting of the speedometer
            setSpeedometerKey(prev => prev + 1);
        } catch (err) {
            setError('Failed to fetch quotes: ' + (err.response?.data?.error || err.message));
            console.error('Error fetching quotes:', err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchRandomQuotes();
    }, []);


    // Function to render a quote card
    const renderQuoteCard = (quote, source, requestTime, isFastest) => {

        if (!quote) return null;
        
        return (
            <div className={`overflow-hidden shadow-sm sm:rounded-lg p-6 h-full flex flex-col ${isFastest ? 'bg-green-100 dark:bg-green-800/30 border-2 border-green-500' : 'bg-white dark:bg-gray-800'}`}>
                <div className="text-gray-900 dark:text-gray-100 flex-1 flex flex-col">
                    <h2 className="text-xl font-semibold mb-4">{source} Quote</h2>
                    
                    <div className="space-y-6 flex-1 flex flex-col justify-between">
                        <div className="space-y-4">
                            <blockquote className="italic text-lg border-l-4 border-gray-300 dark:border-gray-600 pl-4 py-2">
                                "{quote.quote}"
                            </blockquote>
                            <p className="text-right font-semibold">— {quote.author}</p>
                        </div>
                        
                        <div className="mt-auto p-4 bg-gray-100 dark:bg-gray-700 rounded-lg min-h-[350px]">
                            <h3 className="text-lg font-medium mb-2">Quote Details:</h3>
                            <div className="grid grid-cols-1 gap-4">
                                {source === 'DummyJSON' && quote.user && (
                                    <div>
                                        <p><span className="font-semibold">Username:</span> {quote.user}</p>
                                    </div>
                                )}
                                {source === 'ZenQuotes' && (
                                    <div className="h-[72px]">
                                        <p><span className="font-semibold">Source:</span> ZenQuotes.io</p>
                                    </div>
                                )}
                                <div className="flex justify-center items-center">
                                    <div className="text-center w-full">
                                        <p className="text-sm mb-2">Request Time: {requestTime} ms</p>
                                        
                                        {!loading && requestTime > 0 && (
                                            <div key={`${source}-${speedometerKey}`}>
                                                <ReactSpeedometer
                                                    forceRender={true}
                                                    maxValue={2000}
                                                    value={requestTime}
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
                                                    currentValueText={`${Math.round(requestTime)} ms`}
                                                />
                                                {isFastest && (
                                                    <p className="text-red-600 dark:text-red-400 font-bold mt-2 text-lg">FASTEST</p>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <div className="space-y-6">
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
            
            {!loading && !error && quotes.dummyJson && quotes.zenQuotes && (
                <>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {renderQuoteCard(quotes.dummyJson.jsonResponseQuote, 'DummyJSON', quotes.dummyJson.jsonResponseQuote.timeTaken, quotes.dummyJson.jsonResponseQuote.isFastest)}
                        {renderQuoteCard(quotes.zenQuotes.jsonResponseQuote, 'ZenQuotes', quotes.zenQuotes.jsonResponseQuote.timeTaken, quotes.zenQuotes.jsonResponseQuote.isFastest)}
                    </div>
                    
                    <div className="flex justify-center mt-6">
                        <button
                            onClick={fetchRandomQuotes}
                            className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            disabled={loading}
                        >
                            {loading ? 'Loading...' : 'Get Another Quote'}
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}
