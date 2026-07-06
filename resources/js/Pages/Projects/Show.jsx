import { useEffect, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StackBadge from '@/Components/StackBadge';
import StatusBadge from '@/Components/StatusBadge';
import SeverityChip from '@/Components/SeverityChip';
import CopyButton from '@/Components/CopyButton';
import { formatDate, formatDateTime } from '@/lib/datetime';

const CATEGORY_META = {
    security: { label: 'Security', hint: 'Potential security risks', accent: 'text-rose-700' },
    error: { label: 'Errors', hint: 'Broken or risky — will cause failures', accent: 'text-red-700' },
    performance: { label: 'Performance', hint: 'Efficiency / speed problems', accent: 'text-cyan-700' },
    gap: { label: 'Gaps', hint: 'Missing hygiene / documentation / tests', accent: 'text-amber-700' },
    idea: { label: 'Ideas', hint: 'AI-suggested features & improvements', accent: 'text-indigo-700' },
    ui: { label: 'UI / UX', hint: 'AI-suggested interface improvements', accent: 'text-fuchsia-700' },
};
const CATEGORY_ORDER = ['security', 'error', 'performance', 'gap', 'idea', 'ui'];

function MetricRow({ label, value }) {
    return (
        <div className="flex justify-between border-b border-gray-100 py-1.5 text-sm last:border-0">
            <span className="text-gray-500">{label}</span>
            <span className="font-medium text-gray-900">{value}</span>
        </div>
    );
}

function DependencyBlock({ label, info, lockName }) {
    const total = (info.declared ?? 0) + (info.dev ?? 0);
    return (
        <div className="border-b border-gray-100 py-2 last:border-0">
            <div className="flex items-center justify-between text-sm">
                <span className="font-medium text-gray-800">{label}</span>
                <span className="text-gray-500">
                    {info.declared ?? 0} deps{info.dev ? ` + ${info.dev} dev` : ''}
                </span>
            </div>
            <div className="mt-1 flex flex-wrap gap-1.5">
                <DepTag
                    ok={total === 0 ? null : info.has_lock}
                    okText={`${lockName} ✓`}
                    badText={`no ${lockName}`}
                />
                <DepTag ok={info.installed} okText="installed" badText="not installed" />
            </div>
        </div>
    );
}

function DepTag({ ok, okText, badText }) {
    if (ok === null) return null;
    return (
        <span
            className={`rounded px-1.5 py-0.5 text-[11px] font-medium ${
                ok ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'
            }`}
        >
            {ok ? okText : badText}
        </span>
    );
}

export default function ProjectShow({ project }) {
    const [analyzing, setAnalyzing] = useState(false);
    const [openLatestPrompt, setOpenLatestPrompt] = useState(false);
    const [aiOptimize, setAiOptimize] = useState(false);
    const [generating, setGenerating] = useState(false);
    const [showPromptModal, setShowPromptModal] = useState(false);
    const [summarizing, setSummarizing] = useState(false);
    const [draftingReadme, setDraftingReadme] = useState(false);
    const promptsRef = useRef(null);

    // Close the prompt modal on Escape.
    useEffect(() => {
        if (!showPromptModal) return;
        const onKey = (e) => e.key === 'Escape' && setShowPromptModal(false);
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [showPromptModal]);
    const metrics = project.metrics ?? {};
    const git = metrics.git ?? {};
    const files = metrics.files ?? {};
    const todos = metrics.todos ?? {};
    const deps = metrics.dependencies ?? {};

    const findings = project.findings ?? [];
    // Resolved findings (auto-detected fixes or manually resolved) are shown in
    // their own section, not mixed into the active category groups.
    const activeFindings = findings.filter((f) => f.status !== 'resolved');
    const resolvedFindings = findings.filter((f) => f.status === 'resolved');
    const openCount = findings.filter((f) => f.status === 'open').length;
    const byCategory = CATEGORY_ORDER.map((cat) => ({
        cat,
        items: activeFindings.filter((f) => (f.category ?? 'gap') === cat),
    })).filter((group) => group.items.length > 0);

    const generatePrompt = (findingId = null) => {
        router.post(
            `/projects/${project.id}/prompts`,
            {
                ...(findingId ? { finding_id: findingId } : {}),
                optimize: aiOptimize,
            },
            {
                preserveScroll: true,
                onStart: () => setGenerating(true),
                onFinish: () => setGenerating(false),
                // Reveal the result in a modal so it's unmistakably the ONE prompt
                // just created — not lost in the stacked history list below.
                onSuccess: () => {
                    setOpenLatestPrompt(true);
                    setShowPromptModal(true);
                },
            },
        );
    };

    const dismiss = (finding, status) => {
        router.patch(`/findings/${finding.id}`, { status }, { preserveScroll: true });
    };

    const summarize = () =>
        router.post(`/projects/${project.id}/ai/summarize`, {}, {
            preserveScroll: true,
            onStart: () => setSummarizing(true),
            onFinish: () => setSummarizing(false),
        });

    // README draft is persisted as a generated prompt, so reuse the prompt modal.
    const draftReadme = () =>
        router.post(`/projects/${project.id}/ai/readme`, {}, {
            preserveScroll: true,
            onStart: () => setDraftingReadme(true),
            onFinish: () => setDraftingReadme(false),
            onSuccess: () => {
                setOpenLatestPrompt(true);
                setShowPromptModal(true);
            },
        });

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
                        onClick={draftReadme}
                        disabled={draftingReadme}
                        title="Draft a starter README.md for this project with your local LLM. Opens as a copy-paste prompt."
                        className="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
                    >
                        {draftingReadme ? 'Drafting…' : '✨ Draft README'}
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

            {/* Prompt modal — shows the single prompt just generated, front and centre. */}
            {showPromptModal && (project.generated_prompts ?? [])[0] && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                    onClick={() => setShowPromptModal(false)}
                >
                    <div
                        className="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-lg bg-white shadow-xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <div className="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-3">
                            <div className="min-w-0">
                                <p className="text-[11px] font-semibold uppercase tracking-wide text-indigo-600">
                                    Prompt ready
                                </p>
                                <h3 className="truncate text-sm font-semibold text-gray-900">
                                    {project.generated_prompts[0].title}
                                </h3>
                            </div>
                            <div className="flex shrink-0 items-center gap-2">
                                <CopyButton text={project.generated_prompts[0].body} />
                                <button
                                    onClick={() => setShowPromptModal(false)}
                                    aria-label="Close"
                                    className="rounded-md px-2 py-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                        <p className="px-5 pt-3 text-xs text-gray-500">
                            Copy this and paste it into Claude Code in that project to carry out the work.
                        </p>
                        <pre className="m-5 mt-2 flex-1 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-4 text-xs text-gray-700">
                            {project.generated_prompts[0].body}
                        </pre>
                    </div>
                </div>
            )}

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

            {/* AI summary — what this project is, inferred from its code. */}
            <div className="mb-6 rounded-lg border border-indigo-200 bg-indigo-50/40 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h3 className="text-sm font-semibold text-indigo-700">✨ AI summary</h3>
                        {project.ai_summary ? (
                            <>
                                <p className="mt-1 text-sm text-gray-700">{project.ai_summary}</p>
                                <p className="mt-1 text-xs text-gray-400">
                                    Generated {formatDateTime(project.ai_summary_at)}
                                </p>
                            </>
                        ) : (
                            <p className="mt-1 text-sm text-gray-500">
                                No summary yet — generate a plain-English description of what this project is.
                            </p>
                        )}
                    </div>
                    <button
                        onClick={summarize}
                        disabled={summarizing}
                        className="shrink-0 rounded-md bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-300 hover:bg-indigo-50 disabled:opacity-50"
                    >
                        {summarizing ? 'Summarizing…' : project.ai_summary ? 'Regenerate' : 'Summarize'}
                    </button>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {/* Left: metrics */}
                <div className="space-y-6 lg:col-span-1">
                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <h3 className="mb-3 text-sm font-semibold text-gray-700">Metadata</h3>
                        <MetricRow label="Path" value={<span className="text-xs">{project.resolved_path}</span>} />
                        <MetricRow label="Last scanned" value={formatDateTime(project.last_scanned_at)} />
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <h3 className="mb-3 text-sm font-semibold text-gray-700">Git</h3>
                        <MetricRow label="Under git" value={git.has_git ? 'yes' : 'no'} />
                        <MetricRow label="Commits" value={git.has_commits ? git.commit_count : '0'} />
                        <MetricRow label="Last commit" value={formatDate(git.last_commit_at)} />
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

                    {deps.has_manifest && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5">
                            <h3 className="mb-3 text-sm font-semibold text-gray-700">Dependencies</h3>
                            {deps.composer && (
                                <DependencyBlock label="Composer" info={deps.composer} lockName="composer.lock" />
                            )}
                            {deps.npm && (
                                <DependencyBlock
                                    label="npm"
                                    info={deps.npm}
                                    lockName={deps.npm.lockfile ?? 'lock file'}
                                />
                            )}
                        </div>
                    )}
                </div>

                {/* Right: findings + prompts */}
                <div className="space-y-6 lg:col-span-2">
                    {/* How this works — the tool prepares instructions, it doesn't edit your projects. */}
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <p className="font-semibold">How this works</p>
                        <p className="mt-1 text-blue-800">
                            This dashboard <span className="font-medium">finds issues but never changes your projects</span>.
                            To act on one, generate a <span className="font-medium">prompt</span> — ready-to-use
                            instructions you copy into an AI coding assistant that does the actual work.
                        </p>
                        <ol className="mt-2 ml-4 list-decimal space-y-0.5 text-blue-800">
                            <li>Click <span className="font-medium">“Get fix prompt”</span> on a finding (or “Generate project prompt” for everything).</li>
                            <li>The prompt appears below — click <span className="font-medium">Copy</span>.</li>
                            <li>Paste it into <span className="font-medium">Claude Code</span> (or ChatGPT/Copilot) in that project to carry out the fix.</li>
                        </ol>
                    </div>

                    <div className="rounded-lg border border-gray-200 bg-white p-5">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <h3 className="text-sm font-semibold text-gray-700">
                                Findings ({openCount} open)
                            </h3>
                            <div className="flex items-center gap-3">
                                <label
                                    title="Refine the prompt with your local LLM (Ollama) into a focused brief with prioritized steps & acceptance criteria. Slower; falls back to the standard prompt if Ollama is offline."
                                    className="flex cursor-pointer items-center gap-1.5 text-xs text-gray-600"
                                >
                                    <input
                                        type="checkbox"
                                        checked={aiOptimize}
                                        onChange={(e) => setAiOptimize(e.target.checked)}
                                        className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    ✨ AI-optimize
                                </label>
                                <button
                                    onClick={() => generatePrompt()}
                                    disabled={generating}
                                    title="Creates copy-paste instructions covering this whole project. Doesn't modify any files."
                                    className="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {generating ? 'Generating…' : 'Generate project prompt'}
                                </button>
                            </div>
                        </div>
                        {aiOptimize && (
                            <p className="-mt-2 mb-3 text-xs text-indigo-600">
                                ✨ AI-optimize on — prompts are refined by your local LLM (Ollama). This takes a few
                                seconds; if Ollama is offline you'll get the standard prompt.
                            </p>
                        )}

                        {byCategory.length === 0 && (
                            resolvedFindings.length > 0 ? (
                                <p className="py-4 text-center text-sm text-emerald-600">
                                    ✓ No open findings — everything detected here has been resolved.
                                </p>
                            ) : (
                                <p className="py-4 text-center text-sm text-gray-400">
                                    No findings yet. Run a Rescan for heuristics, or “AI Analysis”
                                    for ideas &amp; UI suggestions.
                                </p>
                            )
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
                                                            disabled={generating}
                                                            title="Creates copy-paste instructions to fix this finding. Doesn't modify any files — you paste it into Claude Code."
                                                            className="text-xs font-medium text-indigo-600 hover:underline disabled:opacity-50"
                                                        >
                                                            {generating ? 'Generating…' : 'Get fix prompt →'}
                                                        </button>
                                                        {f.status === 'open' && (
                                                            <button
                                                                onClick={() => dismiss(f, 'resolved')}
                                                                title="I've fixed this — move it to Resolved. Use this for AI findings, which a rescan can't auto-detect."
                                                                className="text-xs font-medium text-emerald-600 hover:underline"
                                                            >
                                                                ✓ Mark fixed
                                                            </button>
                                                        )}
                                                        <button
                                                            onClick={() =>
                                                                dismiss(f, f.status === 'open' ? 'dismissed' : 'open')
                                                            }
                                                            title={
                                                                f.status === 'open'
                                                                    ? "Not relevant — hide it without marking it fixed."
                                                                    : 'Move back to open findings.'
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

                    {resolvedFindings.length > 0 && (
                        <details className="rounded-lg border border-emerald-200 bg-emerald-50/40 p-5">
                            <summary className="cursor-pointer text-sm font-semibold text-emerald-700">
                                Resolved ✓ ({resolvedFindings.length})
                                <span className="ml-2 font-normal text-emerald-600/80">
                                    fixed and no longer detected — click to review
                                </span>
                            </summary>
                            <div className="mt-3 space-y-2">
                                {resolvedFindings.map((f) => (
                                    <div
                                        key={f.id}
                                        className="flex items-start justify-between gap-3 rounded-md border border-emerald-100 bg-white p-3"
                                    >
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium text-gray-500 line-through">
                                                {f.message}
                                            </p>
                                            <p className="mt-0.5 text-xs text-emerald-600">
                                                Resolved{f.resolved_at ? ` ${formatDate(f.resolved_at)}` : ''}
                                                {f.source === 'llm' ? ' · AI finding' : ''}
                                            </p>
                                        </div>
                                        <button
                                            onClick={() => dismiss(f, 'open')}
                                            title="Mark this as an open finding again"
                                            className="shrink-0 text-xs text-gray-400 hover:text-gray-600"
                                        >
                                            Reopen
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </details>
                    )}

                    {files.readme_excerpt && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5">
                            <h3 className="mb-2 text-sm font-semibold text-gray-700">README excerpt</h3>
                            <pre className="max-h-48 overflow-auto whitespace-pre-wrap rounded bg-gray-50 p-3 text-xs text-gray-700">
                                {files.readme_excerpt}
                            </pre>
                        </div>
                    )}

                    {(project.generated_prompts ?? []).length > 0 && (
                        <div ref={promptsRef} className="rounded-lg border border-gray-200 bg-white p-5">
                            <h3 className="text-sm font-semibold text-gray-700">Generated prompts</h3>
                            <p className="mb-3 mt-0.5 text-xs text-gray-500">
                                Open one, click <span className="font-medium">Copy</span>, then paste it into
                                Claude Code in that project to carry out the work.
                            </p>
                            <div className="space-y-3">
                                {project.generated_prompts.map((p, i) => (
                                    <details
                                        key={p.id}
                                        open={i === 0 && openLatestPrompt}
                                        className={`rounded-md border ${
                                            i === 0 && openLatestPrompt
                                                ? 'border-indigo-300 ring-1 ring-indigo-200'
                                                : 'border-gray-200'
                                        }`}
                                    >
                                        <summary className="flex cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm">
                                            <span className="min-w-0 truncate font-medium">
                                                {i === 0 && openLatestPrompt && (
                                                    <span className="mr-1.5 rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">
                                                        NEW
                                                    </span>
                                                )}
                                                {p.title}
                                            </span>
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
