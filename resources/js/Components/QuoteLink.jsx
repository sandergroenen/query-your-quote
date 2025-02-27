import { Link } from '@inertiajs/react';

export default function QuoteLink() {
    return (
        <Link
            href={route('quotes')}
            className="flex items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] transition duration-300 hover:text-black/70 hover:ring-black/20 focus:outline-none focus:ring-black/20 dark:bg-zinc-900 dark:ring-zinc-800 dark:hover:text-white/70 dark:hover:ring-zinc-700 dark:focus:ring-zinc-700 lg:pb-10"
        >
            <div className="flex size-12 shrink-0 items-center justify-center rounded-full bg-[#FF2D20]/10 sm:size-16">
                <svg
                    className="size-6 text-[#FF2D20] sm:size-8"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    strokeWidth="1.5"
                    stroke="currentColor"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"
                    />
                </svg>
            </div>

            <div className="pt-3 sm:pt-5">
                <h2 className="text-xl font-semibold text-black dark:text-white">
                    Query Your Quote
                </h2>

                <p className="mt-4 text-sm/relaxed">
                    Try our quote generator! Get random quotes from famous authors and thinkers.
                    Click here to explore the world of wisdom.
                </p>

                <p className="mt-4 text-xs font-semibold text-black/70 dark:text-white/70">
                    Explore Quotes →
                </p>
            </div>
        </Link>
    );
}
