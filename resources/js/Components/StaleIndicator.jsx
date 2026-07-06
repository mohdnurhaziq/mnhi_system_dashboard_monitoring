function daysSince(dateString) {
    if (!dateString) return null;
    const then = new Date(dateString).getTime();
    if (Number.isNaN(then)) return null;
    return Math.floor((Date.now() - then) / (1000 * 60 * 60 * 24));
}

export default function StaleIndicator({ lastCommitAt, hasCommits, thresholdDays = 90 }) {
    if (!hasCommits) {
        return <span className="text-xs font-medium text-red-600">no commits</span>;
    }

    const days = daysSince(lastCommitAt);
    if (days === null) {
        return <span className="text-xs text-gray-400">unknown</span>;
    }

    let color = 'text-emerald-600';
    if (days > thresholdDays) color = 'text-amber-600';
    if (days > thresholdDays * 3) color = 'text-red-600';

    const label =
        days === 0 ? 'today' : days === 1 ? '1 day ago' : `${days} days ago`;

    return <span className={`text-xs font-medium ${color}`}>{label}</span>;
}
