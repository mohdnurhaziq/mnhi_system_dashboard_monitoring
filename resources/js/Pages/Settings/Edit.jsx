import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

function Section({ title, children }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-5">
            <h3 className="mb-3 text-sm font-semibold text-gray-700">{title}</h3>
            {children}
        </div>
    );
}

export default function SettingsEdit({ config, configPath }) {
    return (
        <AppLayout title="Settings">
            <Head title="Settings" />

            <p className="mb-4 rounded-md bg-sky-50 px-4 py-2 text-sm text-sky-800 ring-1 ring-sky-600/20">
                These settings are read-only here. Edit them in{' '}
                <code className="font-mono">{configPath}</code> and rescan.
            </p>

            <div className="grid gap-4 md:grid-cols-2">
                <Section title="Scan roots">
                    <ul className="space-y-1 text-sm">
                        {config.scan_roots.map((r, i) => (
                            <li key={i} className="font-mono text-xs text-gray-700">
                                {r.path} {r.shallow_only && <span className="text-gray-400">(shallow)</span>}
                            </li>
                        ))}
                    </ul>
                </Section>

                <Section title="Thresholds">
                    <dl className="space-y-1 text-sm">
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Stale threshold (days)</dt>
                            <dd className="font-medium">{config.stale_threshold_days}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">TODO density threshold</dt>
                            <dd className="font-medium">{config.todo_density_threshold}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Max files for TODO scan</dt>
                            <dd className="font-medium">{config.max_files_for_todo_scan}</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Snapshots kept</dt>
                            <dd className="font-medium">{config.snapshots_to_keep}</dd>
                        </div>
                    </dl>
                </Section>

                <Section title="Excluded path patterns">
                    <div className="flex flex-wrap gap-2">
                        {config.excluded_path_patterns.map((p) => (
                            <span key={p} className="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600">
                                {p}
                            </span>
                        ))}
                    </div>
                </Section>

                <Section title="Rules enabled">
                    <ul className="space-y-1 text-sm">
                        {Object.entries(config.rules_enabled).map(([key, enabled]) => (
                            <li key={key} className="flex justify-between">
                                <span className="font-mono text-xs text-gray-700">{key}</span>
                                <span className={enabled ? 'text-emerald-600' : 'text-gray-400'}>
                                    {enabled ? 'on' : 'off'}
                                </span>
                            </li>
                        ))}
                    </ul>
                </Section>
            </div>
        </AppLayout>
    );
}
