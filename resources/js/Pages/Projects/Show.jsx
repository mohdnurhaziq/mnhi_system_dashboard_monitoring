import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StackBadge from '@/Components/StackBadge';
import StatusBadge from '@/Components/StatusBadge';
import SeverityChip from '@/Components/SeverityChip';
import CopyButton from '@/Components/CopyButton';

const CATEGORY_META = {
    error: { label: 'Errors', hint: 'Broken or risky — will cause failures', accent: 'text-red-700' },
    gap: { label: 'Gaps', hint: 'Missing hygiene / documentation / tests', accent: 'text-amber-700' },
    idea: { label: 'Ideas', hint: 'AI-suggested features & improvements', accent: 'text-indigo-700' },
    ui: { label: 'UI / UX', hint: 'AI-suggested interface improvements', accent: 'text-fuchsia-700' },
};
const CATEGORY_ORDER = ['error', 'gap', 'idea', 'ui'];

function MetricRow({ label, value }) {
    return (
        <div className="flex justify-between border-b border-gray-100 py-1.5 text-sm last:border-0">
            <span className="text-gray-500">{label}</span>
            <span className="font-medium text-gray-900">{value}</span>
        </div>
    );
}

export default function ProjectShow({ project }) {
    const [analyzing, setAnalyzing] = useState(false);
    const metrics = project.metrics ?? {};
    const git = metrics.git ?? {};
    const files = metrics.files ?? {};
    const todos = metrics.todos ?? {};

    const findings = project.findings ?? [];
    const byCategory = CATEGORY_ORDER.map((cat) => ({
        cat,
        items: findings.filter((f) => (f.category ?? 'gap') === cat),
    })).filter((group) => group.items.length > 0);

    const generatePrompt = (findingId = null) => {
        router.post(
            `/projects/${project.id}/prompts`,
            findingId ? { finding_id: findingId } : {},
            { preserveScroll: true },
        );
    };

    const dismiss = (finding, status) => {
        router.patch(`/findings/${finding.id}`, { status }, { preserveScroll: true });
    };

    return (
        <AppLayout
            title={project.name}
            actions={
                <div className="flex gap-2">
                    <button
                        onClick={() => {
                            setAnalyzing(true);
                            router.post(`/projects/${project.id}/analyze`, {}, {
                                preserveScroll: true,
                                onFinish: () => setAnalyzing(false),
                            });
                        }}
                        disabled={analyzing}
                        className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                    >
                        {analyzing ? 'Analyzing…' : 'AI Analysis'}
                    </button>
                    <button
                        onClick={() => router.post(`/projects/${project.id}/scan`, {}, { preserveScroll: true })}
                        className="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
                    >
                        Rescan
                    </button>
                </div>
            }
        >
            <Head title={project.name} />

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <StackBadge stack={project.stack} />
                <StatusBadge status={project.status} />
                {project.stack_version && (
                    <span className="text-xs text-gray-500">{project.stack_version}</span>
                )}
                <Link href="/projects" className="ml-auto text-sm text-indigo-600 hover:underline">
                    ← All projects
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Left: metrics */}
                <div className="space-y-6 lg:col-span-1">
                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <h3 className="mb-3 text-sm font-semibold text-gray-700">Metadata</h3>
                        <MetricRow label="Path" value={<span className="text-xs">{project.resolved_path}</span>} />
                        <MetricRow label="Last scanned" value={project.last_scanned_at?.slice(0, 19).replace('T', ' ') ?? '—'} />
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <h3 className="mb-3 text-sm font-semibold text-gray-700">Git</h3>
                        <MetricRow label="Under git" value={git.has_git ? 'yes' : 'no'} />
                        <MetricRow label="Commits" value={git.has_commits ? git.commit_count : '0'} />
                        <MetricRow label="Last commit" value={git.last_commit_at?.slice(0, 10) ?? '—'} />
                        <MetricRow label="Uncommitted" value={git.uncommitted_files ?? 0} />
                        <MetricRow label="Branch" value={git.current_branch ?? '—'} />
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <h3 className="mb-3 text-sm font-semibold text-gray-700">Files</h3>
                        <MetricRow label="README" value={files.has_readme ? 'yes' : 'no'} />
                        <MetricRow label=".env.example" value={files.has_env_example ? 'yes' : 'no'} />
                        <MetricRow label="Tests" value={files.has_tests ? 'yes' : 'no'} />
                        <MetricRow label="CI" value={files.has_ci ? 'yes' : 'no'} />
                        <MetricRow label="TODOs" value={todos.count ?? 0} />
                    </div>
                </div>

                {/* Right: findings + prompts */}
                <div className="space-y-6 lg:col-span-2">
                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-gray-700">
                                Findings ({findings.length})
                            </h3>
                            <button
                                onClick={() => generatePrompt()}
                                className="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500"
                            >
                                Generate Prompt (whole project)
                            </button>
                        </div>

                        {byCategory.length === 0 && (
                            <p className="py-4 text-center text-sm text-gray-400">
                                No findings yet. Run a Rescan for heuristics, or “AI Analysis”
                                for ideas &amp; UI suggestions.
                            </p>
                        )}

                        <div className="space-y-5">
                            {byCategory.map(({ cat, items }) => {
                                const meta = CATEGORY_META[cat];
                                return (
                                    <div key={cat}>
                                        <div className="mb-2 flex items-baseline gap-2">
                                            <h4 className={`text-sm font-semibold ${meta.accent}`}>
                                                {meta.label} ({items.length})
                                            </h4>
                                            <span className="text-xs text-gray-400">{meta.hint}</span>
                                        </div>
                                        <div className="space-y-2">
                                            {items.map((f) => (
                                                <div
                                                    key={f.id}
                                                    className={`flex items-start justify-between rounded-md border p-3 ${
                                                        f.status === 'dismissed'
                                                            ? 'border-gray-100 bg-gray-50 opacity-60'
                                                            : 'border-gray-200'
                                                    }`}
                                                >
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <SeverityChip severity={f.severity} />
                                                            {f.source === 'llm' && (
                                                                <span className="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-600">
                                                                    AI
                                                                </span>
                                                            )}
                                                        </div>
                                                        <p className="mt-1 text-sm font-medium text-gray-800">
                                                            {f.message}
                                                        </p>
                                                        {f.details?.detail && (
                                                            <p className="mt-0.5 text-xs text-gray-500">
                                                                {f.details.detail}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <div className="ml-3 flex shrink-0 flex-col items-end gap-1">
                                                        <button
                                                            onClick={() => generatePrompt(f.id)}
                                                            className="text-xs text-indigo-600 hover:underline"
                                                        >
                                                            Fix prompt
                                                        </button>
                                                        <button
                                                            onClick={() =>
                                                                dismiss(f, f.status === 'open' ? 'dismissed' : 'open')
                                                            }
                                                            className="text-xs text-gray-400 hover:text-gray-600"
                                                        >
                                                            {f.status === 'open' ? 'Dismiss' : 'Reopen'}
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {files.readme_excerpt && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5">
                            <h3 className="mb-2 text-sm font-semibold text-gray-700">README excerpt</h3>
                            <pre className="max-h-48 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-700">
                                {files.readme_excerpt}
                            </pre>
                        </div>
                    )}

                    {(project.generated_prompts ?? []).length > 0 && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5">
                            <h3 className="mb-3 text-sm font-semibold text-gray-700">Recent prompts</h3>
                            <div className="space-y-3">
                                {project.generated_prompts.map((p) => (
                                    <details key={p.id} className="rounded-md border border-gray-200">
                                        <summary className="flex cursor-pointer items-center justify-between px-3 py-2 text-sm">
                                            <span className="font-medium">{p.title}</span>
                                            <CopyButton text={p.body} />
                                        </summary>
                                        <pre className="max-h-96 overflow-auto whitespace-pre-wrap border-t border-gray-100 bg-gray-50 p-3 text-xs text-gray-700">
                                            {p.body}
                                        </pre>
                                    </details>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
