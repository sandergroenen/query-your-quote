import { useState, useEffect } from 'react';

function FilteredQuoteSimpleChannel() {
    const [receivedQuote, setReceivedQuote] = useState({ quote: null });


    useEffect(() => {

        // Listen for the quotes channel
        const channel = window.Echo.channel('quotes');

        // Listen for the QuoteRetrieved event
        channel.listen('.FilteredQuoteRetrieved', (event) => {
            console.log('Received filtered quote event in filtered simple channel ', event);
            setReceivedQuote( event.quote.jsonResponseQuote );  
            console.log(receivedQuote);
          
        });

        // Cleanup function
        return () => {
            channel.stopListening('.FilteredQuoteRetrieved');
        };
    });

    const [filter, setFilter] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post('/api/set-quote-filter', { filter });
            console.log(response.data.message);
        } catch (error) {
            console.error('Error setting quote filter:', error);
        }
    };

    // Render the component
    return (
        <div className="p-4 bg-white rounded shadow">
            <h2 className="text-xl font-bold mb-4">Simple quote channel filted on word : </h2>
            
            <form onSubmit={handleSubmit}>
                <input
                    type="text"
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    placeholder="Enter quote filter"
                />
                <button className='bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded' type="submit">Set Filter</button>
            </form>

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

export default FilteredQuoteSimpleChannel;