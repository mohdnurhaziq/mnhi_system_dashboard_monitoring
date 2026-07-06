import { useState } from 'react';

export default function CopyButton({ text, className = '' }) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        try {
            await navigator.clipboard.writeText(text);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            // Fallback: select-none no-op; clipboard may be blocked.
        }
    };

    return (
        <button
            type="button"
            onClick={copy}
            className={`inline-flex items-center gap-1 rounded-md bg-gray-800 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-gray-700 ${className}`}
        >
            {copied ? 'Copied!' : 'Copy'}
        </button>
    );
}
