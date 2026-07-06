import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import CopyButton from '@/Components/CopyButton';
import { formatDateTime } from '@/lib/datetime';

export default function PromptsIndex({ prompts, projects, filters }) {
    const filterByProject = (id) => {
        router.get('/prompts', { project: id || undefined }, {
            preserveState: true,
            replace: true,
        });
    };

    return (
        <AppLayout title="Generated Prompts">
            <Head title="Prompts" />

            <div className="mb-4">
                <select
                    value={filters.project ?? ''}
                    onChange={(e) => filterByProject(e.target.value)}
                    className="rounded-md border-gray-300 text-sm"
                >
                    <option value="">All projects</option>
                    {projects.map((p) => (
                        <option key={p.id} value={p.id}>
                            {p.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="space-y-3">
                {prompts.map((p) => (
                    <details key={p.id} className="rounded-lg border border-gray-200 bg-white">
                        <summary className="flex cursor-pointer items-center justify-between px-4 py-3">
                            <div>
                                <span className="font-medium">{p.title}</span>
                                <div className="text-xs text-gray-400">
                                    {p.project?.name} · {formatDateTime(p.created_at)}
                                </div>
                            </div>
                            <CopyButton text={p.body} />
                        </summary>
                        <pre className="max-h-96 overflow-auto whitespace-pre-wrap border-t border-gray-100 bg-gray-50 p-4 text-xs text-gray-700">
                            {p.body}
                        </pre>
                    </details>
                ))}
                {prompts.length === 0 && (
                    <div className="rounded-lg border border-dashed border-gray-300 py-10 text-center text-sm text-gray-400">
                        No prompts generated yet. Open a project and click “Generate Prompt”.
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
