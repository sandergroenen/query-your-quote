import { useState, useEffect, useRef } from 'react';

function QuoteStreamer() {
    const [quoteHistory, setQuoteHistory] = useState([]);
    const [connectionStatus, setConnectionStatus] = useState('Connecting...');
    const quoteListRef = useRef(null);

    // Auto-scroll to the bottom when new quotes are added
    useEffect(() => {
        if (quoteListRef.current) {
            quoteListRef.current.scrollTop = quoteListRef.current.scrollHeight;
        }
    }, [quoteHistory]);

    useEffect(() => {
        console.log('Quote history updated:', quoteHistory);
        console.log('Echo configuration:', {
            broadcaster: 'reverb',
            key: window.Echo?.options?.key,
            wsHost: window.Echo?.options?.wsHost,
            wsPort: window.Echo?.options?.wsPort,
            scheme: window.Echo?.options?.forceTLS ? 'https' : 'http'
        });

        // Listen for the quotes channel
        const channel = window.Echo.channel('quotes');

        // Debug connection status
        if (window.Echo?.connector?.socket) {
            window.Echo.connector.socket.on('connecting', () => {
                console.log('Socket connecting...');
                setConnectionStatus('Connecting...');
            });

            window.Echo.connector.socket.on('connected', () => {
                console.log('Socket connected!');
                setConnectionStatus('Connected!');
            });

            window.Echo.connector.socket.on('error', (error) => {
                console.error('Socket error:', error);
                setConnectionStatus(`Error: ${error}`);
            });

            window.Echo.connector.socket.on('disconnected', () => {
                console.log('Socket disconnected');
                setConnectionStatus('Disconnected');
            });
        }

        // Check connection status manually
        const checkConnectionInterval = setInterval(() => {
            if (window.Echo?.connector?.socket?.connection?.state === 'connected') {
                setConnectionStatus('Connected!');
            }
        }, 2000);

        // Listen for the QuoteRetrieved event
        channel.listen('.QuoteRetrieved', (event) => {
            console.log('Received quote event with dot prefix:', event);
            
            // Add new quote to history with timestamp
            setQuoteHistory(prevHistory => [
                ...prevHistory,
                {
                    ...event,
                    timestamp: new Date()
                }
            ]);
        });

        // Cleanup function
        return () => {
            clearInterval(checkConnectionInterval);
            channel.stopListening('.QuoteRetrieved');
            if (window.Echo?.connector?.socket) {
                window.Echo.connector.socket.off('connecting');
                window.Echo.connector.socket.off('connected');
                window.Echo.connector.socket.off('error');
                window.Echo.connector.socket.off('disconnected');
            }
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
            <div className="mb-4 text-sm">
                <p>Connection status: <span className={connectionStatus.includes('Error') ? 'text-red-500' : connectionStatus === 'Connected!' ? 'text-green-500' : 'text-yellow-500'}>{connectionStatus}</span></p>
            </div>
            
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
                                {item.quotes.dummyJson && (
                                    <li className="p-3 bg-gray-50 rounded border border-gray-200">
                                        <div className="flex justify-between items-center mb-2">
                                            <h4 className="font-medium text-blue-600">DummyJSON API</h4>
                                            {item.quotes.dummyJson.isFastest && (
                                                <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                                    Fastest ({item.quotes.dummyJson.timeTaken.toFixed(2)}ms)
                                                </span>
                                            )}
                                        </div>
                                        <p className="italic text-gray-800">"{item.quotes.dummyJson.quote}"</p>
                                        <p className="text-right text-sm text-gray-600 mt-2">— {item.quotes.dummyJson.author}</p>
                                    </li>
                                )}
                                {item.quotes.zenQuotes && (
                                    <li className="p-3 bg-gray-50 rounded border border-gray-200">
                                        <div className="flex justify-between items-center mb-2">
                                            <h4 className="font-medium text-purple-600">ZenQuotes API</h4>
                                            {item.quotes.zenQuotes.isFastest && (
                                                <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                                    Fastest ({item.quotes.zenQuotes.timeTaken.toFixed(2)}ms)
                                                </span>
                                            )}
                                        </div>
                                        <p className="italic text-gray-800">"{item.quotes.zenQuotes.quote}"</p>
                                        <p className="text-right text-sm text-gray-600 mt-2">— {item.quotes.zenQuotes.author}</p>
                                    </li>
                                )}
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