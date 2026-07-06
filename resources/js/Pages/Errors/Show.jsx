import { Head, Link } from '@inertiajs/react';

// Human-friendly copy per status. Kept self-contained (no AppLayout / shared
// props) since those aren't guaranteed to be resolved during error rendering.
const MESSAGES = {
    403: {
        title: 'Access denied',
        body: "You don't have permission to view this page.",
    },
    404: {
        title: 'Page not found',
        body: "This page doesn't exist. The project may have been removed, or the link is out of date.",
    },
    500: {
        title: 'Something went wrong',
        body: 'An unexpected error occurred. Please try again — if it keeps happening, check the application log (storage/logs).',
    },
    503: {
        title: 'Temporarily unavailable',
        body: 'The app is briefly unavailable. Please try again in a moment.',
    },
};

export default function ErrorShow({ status }) {
    const meta = MESSAGES[status] ?? {
        title: 'Error',
        body: 'An unexpected error occurred.',
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <Head title={`${status} — ${meta.title}`} />
            <div className="w-full max-w-md rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p className="text-6xl font-bold text-gray-200">{status}</p>
                <h1 className="mt-4 text-xl font-semibold text-gray-900">{meta.title}</h1>
                <p className="mt-2 text-sm text-gray-500">{meta.body}</p>
                <Link
                    href="/"
                    className="mt-6 inline-block rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
                >
                    ← Back to dashboard
                </Link>
            </div>
        </div>
    );
}
