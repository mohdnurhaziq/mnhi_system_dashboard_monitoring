const STYLES = {
    critical: 'bg-red-100 text-red-800 ring-red-600/20',
    warning: 'bg-amber-100 text-amber-800 ring-amber-600/20',
    info: 'bg-sky-100 text-sky-800 ring-sky-600/20',
};

export default function SeverityChip({ severity, count = null }) {
    const style = STYLES[severity] ?? 'bg-gray-100 text-gray-800 ring-gray-600/20';

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset ${style}`}
        >
            {severity}
            {count !== null && <span className="font-semibold">({count})</span>}
        </span>
    );
}
