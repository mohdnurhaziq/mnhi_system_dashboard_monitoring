const STYLES = {
    laravel: 'bg-red-50 text-red-700 ring-red-600/10',
    'laravel-livewire': 'bg-pink-50 text-pink-700 ring-pink-600/10',
    'laravel-inertia-react': 'bg-indigo-50 text-indigo-700 ring-indigo-600/10',
    'node-react': 'bg-cyan-50 text-cyan-700 ring-cyan-600/10',
    'node-react-native': 'bg-teal-50 text-teal-700 ring-teal-600/10',
    'node-vue': 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
    'node-generic': 'bg-lime-50 text-lime-700 ring-lime-600/10',
    'php-composer': 'bg-violet-50 text-violet-700 ring-violet-600/10',
    go: 'bg-sky-50 text-sky-700 ring-sky-600/10',
    python: 'bg-yellow-50 text-yellow-700 ring-yellow-600/10',
    docker: 'bg-blue-50 text-blue-700 ring-blue-600/10',
    unknown: 'bg-gray-50 text-gray-500 ring-gray-500/10',
};

export default function StackBadge({ stack }) {
    const key = stack ?? 'unknown';
    const style = STYLES[key] ?? STYLES.unknown;

    return (
        <span className={`inline-flex items-center rounded px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${style}`}>
            {key}
        </span>
    );
}
