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

export default function SettingsEdit({ config, configPath, aiModel }) {
    const specs = aiModel.specs;

    return (
        <AppLayout title="Settings">
            <Head title="Settings" />

            <p className="mb-4 rounded-md bg-sky-50 px-4 py-2 text-sm text-sky-800 ring-1 ring-sky-600/20">
                These settings are read-only here. Edit them in{' '}
                <code className="font-mono">{configPath}</code> and rescan.
            </p>

            <div className="grid gap-4 md:grid-cols-2">
                <Section title="AI model">
                    <div className="mb-3 flex items-center justify-between">
                        <span className="font-mono text-sm font-medium">{aiModel.configured_name}</span>
                        <span
                            className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                aiModel.available
                                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20'
                                    : 'bg-red-50 text-red-700 ring-1 ring-red-600/20'
                            }`}
                        >
                            {aiModel.available ? 'reachable' : 'not reachable'}
                        </span>
                    </div>
                    <dl className="space-y-1 text-sm">
                        {specs ? (
                            <>
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Size on disk</dt>
                                    <dd className="font-medium">{specs.size_gb} GB</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Parameters</dt>
                                    <dd className="font-medium">{specs.parameter_size}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Quantization</dt>
                                    <dd className="font-medium">{specs.quantization}</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Context length</dt>
                                    <dd className="font-medium">{specs.context_length.toLocaleString()} tokens</dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="text-gray-500">Family</dt>
                                    <dd className="font-medium">{specs.family}</dd>
                                </div>
                            </>
                        ) : (
                            <p className="text-gray-400">
                                Model not pulled yet — run <code className="font-mono">ollama pull {aiModel.configured_name}</code>.
                            </p>
                        )}
                        <div className="flex justify-between border-t border-gray-100 pt-1">
                            <dt className="text-gray-500">Request timeout</dt>
                            <dd className="font-medium">{aiModel.timeout}s</dd>
                        </div>
                        <div className="flex justify-between">
                            <dt className="text-gray-500">Server</dt>
                            <dd className="font-mono text-xs">{aiModel.base_url}</dd>
                        </div>
                    </dl>
                </Section>

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
