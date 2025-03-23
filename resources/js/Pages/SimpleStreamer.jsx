import { Head } from '@inertiajs/react';
import QuoteSimpleChannel from '@/Components/Quote/QuoteSimpleChannel';
import FilteredQuoteSimpleChannel from '@/Components/Quote/FilteredQuoteSimpleChannel';

export default function Quotes({ auth }) {
    return (
        <>
            <Head title="Query Your Quote" />
            <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
        

                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">                    

                        <div className="mt-6">
                            <QuoteSimpleChannel />
                        </div>  
                        
                        <div className="mt-6">
                            <FilteredQuoteSimpleChannel />
                        </div>        
                    </div>
                </div>
            </div>
        </>
    );
}
