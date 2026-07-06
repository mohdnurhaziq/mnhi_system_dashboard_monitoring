import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StackBadge from '@/Components/StackBadge';
import StaleIndicator from '@/Components/StaleIndicator';

function StatCard({ label, value, tone = 'default' }) {
    const tones = {
        default: 'text-gray-900',
        critical: 'text-red-600',
        warning: 'text-amber-600',
        info: 'text-sky-600',
    };
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-5">
            <div className="text-sm text-gray-500">{label}</div>
            <div className={`mt-1 text-3xl font-semibold ${tones[tone]}`}>{value}</div>
        </div>
    );
}

export default function DashboardIndex({ stats, needsAttention, staleThresholdDays, aiDigest }) {
    const [digesting, setDigesting] = useState(false);
    const post = (url) => router.post(url, {}, { preserveScroll: true });
    const generateDigest = () =>
        router.post('/ai/digest', {}, {
            preserveScroll: true,
            onStart: () => setDigesting(true),
            onFinish: () => setDigesting(false),
        });

    return (
        <AppLayout
            title="Dashboard"
            actions={
                <div className="flex gap-2">
                    <button
                        onClick={() => post('/projects/discover')}
                        className="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Discover New
                    </button>
                    <button
                        onClick={() => post('/projects/scan')}
                        className="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    >
                        Scan All
                    </button>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                <StatCard label="Total projects" value={stats.total} />
                <StatCard label="Included" value={stats.included} />
                <StatCard label="Stale (>90d)" value={stats.stale} tone="warning" />
                <StatCard label="Zero commits" value={stats.zeroCommits} tone="critical" />
            </div>

            <div className="mt-4 grid grid-cols-3 gap-4">
                <StatCard label="Critical findings" value={stats.findings.critical} tone="critical" />
                <StatCard label="Warning findings" value={stats.findings.warning} tone="warning" />
                <StatCard label="Info findings" value={stats.findings.info} tone="info" />
            </div>

            <div className="mt-8 rounded-lg border border-indigo-200 bg-indigo-50/40 p-5">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-lg font-semibold">✨ AI priorities — what to work on next</h2>
                        <p className="text-xs text-gray-500">
                            Your local LLM reviews findings across all projects and suggests where to focus.
                        </p>
                    </div>
                    <button
                        onClick={generateDigest}
                        disabled={digesting}
                        className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        {digesting ? 'Analyzing…' : aiDigest ? 'Regenerate' : 'Generate'}
                    </button>
                </div>
                {aiDigest ? (
                    <div className="mt-4">
                        <pre className="whitespace-pre-wrap font-sans text-sm leading-relaxed text-gray-800">
                            {aiDigest.content}
                        </pre>
                        <p className="mt-3 text-xs text-gray-400">
                            Generated {aiDigest.generated_at?.slice(0, 16).replace('T', ' ')} · {aiDigest.model}
                        </p>
                    </div>
                ) : (
                    <p className="mt-4 text-sm text-gray-400">
                        No digest yet — click Generate (takes a few seconds via your local LLM).
                    </p>
                )}
            </div>

            <div className="mt-8">
                <h2 className="mb-3 text-lg font-semibold">Needs Attention</h2>
                <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Project</th>
                                <th className="px-4 py-3">Stack</th>
                                <th className="px-4 py-3">Last commit</th>
                                <th className="px-4 py-3 text-right">Critical</th>
                                <th className="px-4 py-3 text-right">Open findings</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {needsAttention.map((p) => (
                                <tr key={p.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3">
                                        <Link
                                            href={`/projects/${p.id}`}
                                            className="font-medium text-indigo-600 hover:underline"
                                        >
                                            {p.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <StackBadge stack={p.stack} />
                                    </td>
                                    <td className="px-4 py-3">
                                        <StaleIndicator
                                            lastCommitAt={p.last_commit_at}
                                            hasCommits={p.has_commits}
                                            thresholdDays={staleThresholdDays}
                                        />
                                    </td>
                                    <td className="px-4 py-3 text-right font-semibold text-red-600">
                                        {p.critical_count || 0}
                                    </td>
                                    <td className="px-4 py-3 text-right">{p.open_findings_count || 0}</td>
                                </tr>
                            ))}
                            {needsAttention.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-6 text-center text-gray-400">
                                        No projects yet — click “Discover New”.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
