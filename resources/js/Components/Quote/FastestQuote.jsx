// FastestQuote.jsx
import axios from 'axios';
import React from 'react';
import { useState, useEffect } from 'react';

export default function FastestQuote() {
    const [quoteData, setQuote] = React.useState({ quote: {}, whoIsFastest: '' });
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(null);

    const fetchFastestQuote = async () => {
        try {
            setLoading(true);
            setError(null);
            const response = await axios.get('/api/quotes/fastest');
            console.log('Fastest quote response:', response.data);
            setQuote(response.data);
        } catch (err) {
            setError('Failed to fetch fastest quote: ' + (err.response?.data?.error || err.message));
            console.error('Error fetching fastest quote:', err);
        } finally {
            setLoading(false);
        }
    }
    useEffect(() => {
        fetchFastestQuote();
    }, []);

    if (loading) return <p>Loading...</p>;
    if (error) return <p>{error}</p>;

    return (
        <div className="overflow-hidden shadow-sm sm:rounded-lg p-6 h-full flex flex-col bg-white dark:bg-gray-800">
            <div className="text-gray-900 dark:text-gray-100 flex-1 flex flex-col">
                <h2 className="text-xl font-semibold mb-4">Fastest Quote</h2>
                <h3 className="text-xl font-semibold mb-4">Api service which was fastest: {quoteData.whoIsFastest}</h3>
                <h3 className="text-xl font-semibold mb-4">Time taken: {quoteData.quote.timeTaken}</h3>
                <blockquote className="italic text-lg border-l-4 border-gray-300 dark:border-gray-600 pl-4 py-2">
                    "{quoteData.quote.quote}"
                </blockquote>
                <p className="text-right font-semibold">— {quoteData.quote.author}</p>
            </div>
        </div>
    );
}