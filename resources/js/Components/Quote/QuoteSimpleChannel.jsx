import { useState, useEffect } from 'react';

function QuoteSimpleChannel() {
    const [receivedQuote, setReceivedQuote] = useState({ quote: null });


    useEffect(() => {

        // Listen for the quotes channel
        const channel = window.Echo.channel('quotes');

        // Listen for the QuoteRetrieved event
        channel.listen('.QuoteRetrieved', (event) => {
            console.log('Received quote event in simple channel ', event);
            setReceivedQuote( event.quote.jsonResponseQuote );  
            console.log(receivedQuote);
          
        });

        // Cleanup function
        return () => {
            channel.stopListening('.QuoteRetrieved');
        };
    });


    // Render the component
    return (
        <div className="p-4 bg-white rounded shadow">
            <h2 className="text-xl font-bold mb-4">Simple quote channel</h2>
            
            <div             
                className="max-h-96 overflow-y-auto pr-2 space-y-6 border border-gray-100 rounded p-4"
            >
                                            
                            <ul className="space-y-4">
                                <li className="p-3 bg-gray-50 rounded border border-gray-200">
                                    <p className="italic text-orange-400">{receivedQuote.quote}</p>
                                    <p className="italic text-gray-800">"{receivedQuote.apiName}"</p>
                                </li>                               
                            </ul>
                        </div>
                    <p className="text-gray-500">Waiting for quotes...</p>
        </div>
    );  
}

export default QuoteSimpleChannel;