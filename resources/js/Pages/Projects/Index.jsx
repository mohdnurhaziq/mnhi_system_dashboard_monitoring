import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StackBadge from '@/Components/StackBadge';
import StatusBadge from '@/Components/StatusBadge';
import StaleIndicator from '@/Components/StaleIndicator';

export default function ProjectsIndex({ projects, filters, stacks }) {
    const applyFilter = (key, value) => {
        router.get('/projects', { ...filters, [key]: value || undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const setStatus = (project, status) => {
        router.patch(`/projects/${project.id}`, { status }, { preserveScroll: true });
    };

    const rescan = (project) => {
        router.post(`/projects/${project.id}/scan`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout title="Projects">
            <Head title="Projects" />

            <div className="mb-4 flex flex-wrap gap-3">
                <select
                    value={filters.status ?? ''}
                    onChange={(e) => applyFilter('status', e.target.value)}
                    className="rounded-md border-gray-300 text-sm"
                >
                    <option value="">All statuses</option>
                    <option value="included">Included</option>
                    <option value="excluded">Excluded</option>
                    <option value="archived">Archived</option>
                </select>

                <select
                    value={filters.stack ?? ''}
                    onChange={(e) => applyFilter('stack', e.target.value)}
                    className="rounded-md border-gray-300 text-sm"
                >
                    <option value="">All stacks</option>
                    {stacks.map((s) => (
                        <option key={s} value={s}>
                            {s}
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th className="px-4 py-3">Project</th>
                            <th className="px-4 py-3">Stack</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3">Last commit</th>
                            <th className="px-4 py-3 text-right">Findings</th>
                            <th className="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {projects.map((p) => (
                            <tr key={p.id} className="hover:bg-gray-50">
                                <td className="px-4 py-3">
                                    <Link
                                        href={`/projects/${p.id}`}
                                        className="font-medium text-indigo-600 hover:underline"
                                    >
                                        {p.name}
                                    </Link>
                                    <div className="text-xs text-gray-400">{p.root_path}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <StackBadge stack={p.stack} />
                                </td>
                                <td className="px-4 py-3">
                                    <StatusBadge status={p.status} />
                                </td>
                                <td className="px-4 py-3">
                                    <StaleIndicator
                                        lastCommitAt={p.last_commit_at}
                                        hasCommits={p.has_commits}
                                    />
                                </td>
                                <td className="px-4 py-3 text-right">{p.open_findings_count || 0}</td>
                                <td className="px-4 py-3 text-right">
                                    <div className="flex justify-end gap-2">
                                        <button
                                            onClick={() => rescan(p)}
                                            className="text-xs text-gray-600 hover:text-gray-900"
                                        >
                                            Rescan
                                        </button>
                                        {p.status !== 'included' && (
                                            <button
                                                onClick={() => setStatus(p, 'included')}
                                                className="text-xs text-emerald-600 hover:underline"
                                            >
                                                Include
                                            </button>
                                        )}
                                        {p.status === 'included' && (
                                            <button
                                                onClick={() => setStatus(p, 'excluded')}
                                                className="text-xs text-gray-500 hover:underline"
                                            >
                                                Exclude
                                            </button>
                                        )}
                                        {p.status !== 'archived' && (
                                            <button
                                                onClick={() => setStatus(p, 'archived')}
                                                className="text-xs text-gray-500 hover:underline"
                                            >
                                                Archive
                                            </button>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {projects.length === 0 && (
                            <tr>
                                <td colSpan={6} className="px-4 py-6 text-center text-gray-400">
                                    No projects match these filters.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </AppLayout>
    );
}
