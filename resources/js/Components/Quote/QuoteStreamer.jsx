import { useState, useEffect, useRef } from 'react';

function QuoteStreamer() {
    const [quoteHistory, setQuoteHistory] = useState([]);
    const quoteListRef = useRef(null);

    // Auto-scroll to the bottom when new quotes are added
    useEffect(() => {
        if (quoteListRef.current) {
            quoteListRef.current.scrollTop = quoteListRef.current.scrollHeight;
        }
    }, [quoteHistory]);

    useEffect(() => {
        console.log('Quote history updated:', quoteHistory);

        // Listen for the quotes channel
        const channel = window.Echo.channel('quotes');

        // Listen for the QuoteRetrieved event
        channel.listen('.QuoteRetrieved', (event) => {
            console.log('Received quote event in streamer', event);
            
            // Add new quote to history with timestamp
            setQuoteHistory(prevHistory => [
                ...prevHistory,
                {
                    ...event.quote.jsonResponseQuote,
                    timestamp: new Date()
                }
            ]);
        });

        // Cleanup function
        return () => {
            channel.stopListening('.QuoteRetrieved');
        };
    }, []);

    // Format date for display
    const formatDate = (date) => {
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    };

    // Render the component
    return (
        <div className="p-4 bg-white rounded shadow">
            <h2 className="text-xl font-bold mb-4">Live Quote Updates</h2>
            
            <div 
                ref={quoteListRef}
                className="max-h-96 overflow-y-auto pr-2 space-y-6 border border-gray-100 rounded p-4"
            >
                {quoteHistory.length > 0 ? (
                    quoteHistory.map((item, historyIndex) => (
                        <div key={historyIndex} className="border-b border-gray-200 pb-4 mb-4 last:border-0">
                            <div className="text-sm text-gray-500 mb-2">
                                Received at: {formatDate(item.timestamp)}
                            </div>
                            
                            <ul className="space-y-4">
                                <li className="p-3 bg-gray-50 rounded border border-gray-200">
                                    <p className="italic text-orange-400">{item.apiName}</p>
                                    <p className="italic text-gray-800">"{item.quote}"</p>
                                    <p className="text-right text-sm text-gray-600 mt-2">— {item.author}</p>
                                    <p className="text-right text-sm text-gray-400 mt-2">Time taken: {item.timeTaken} ms</p>
                                </li>                               
                            </ul>
                        </div>
                    ))
                ) : (
                    <p className="text-gray-500">Waiting for quotes...</p>
                )}
            </div>
        </div>
    );
}

export default QuoteStreamer;