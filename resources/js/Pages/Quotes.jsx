import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import RandomQuote from '@/Components/Quote/RandomQuote';
import RateLimitDemo from '@/Components/Quote/RateLimitDemo';
import FastestQuote from '@/Components/Quote/FastestQuote';

export default function Quotes({ auth }) {
    return (
        <>
            <Head title="Query Your Quote" />
            <div className="min-h-screen bg-gray-100 dark:bg-gray-900">
                <nav className="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex">
                                <div className="shrink-0 flex items-center">
                                    <Link href="/">
                                        <h1 className="font-bold text-xl text-gray-800 dark:text-gray-200">
                                            Query Your Quote
                                        </h1>
                                    </Link>
                                </div>
                            </div>
                            <div className="hidden sm:flex sm:items-center sm:ml-6">
                                {auth.user ? (
                                    <div className="ml-3 relative flex space-x-4">
                                        <Link
                                            href={route('profile.edit')}
                                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            Profile
                                        </Link>
                                        
                                        <Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            Log Out
                                        </Link>
                                    </div>
                                ) : (
                                    <div className="space-x-4">
                                        <Link
                                            href={route('login')}
                                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            Log in
                                        </Link>

                                        <Link
                                            href={route('register')}
                                            className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            Register
                                        </Link>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-gray-900 dark:text-gray-100">
                                <h1 className="text-2xl font-bold mb-6">Query Your Quote</h1>
                                <p className="mb-6">
                                    Welcome to Query Your Quote, a simple application that fetches random quotes from both DummyJSON and ZenQuotes APIs and compares their response times.
                                </p>
                                <p className="mb-6">
                                    The quote that loads faster will be highlighted with a green background and marked as "FASTEST".
                                </p>
                                <p className="mb-6">
                                    This application also demonstrates rate limiting with 5 requests per 10 seconds by default. Try the rate limit demo below!
                                </p>
                            </div>
                        </div>

                        <div className="mt-6">
                            <RandomQuote />
                        </div>
                        <div className="mt-6">
                            <FastestQuote />
                        </div>
                        <RateLimitDemo />
                    </div>
                </div>
            </div>
        </>
    );
}
