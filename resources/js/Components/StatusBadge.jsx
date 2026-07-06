const STYLES = {
    included: 'bg-emerald-100 text-emerald-800',
    excluded: 'bg-gray-100 text-gray-600',
    archived: 'bg-zinc-200 text-zinc-700',
};

export default function StatusBadge({ status }) {
    const style = STYLES[status] ?? 'bg-gray-100 text-gray-600';

    return (
        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${style}`}>
            {status}
        </span>
    );
}
