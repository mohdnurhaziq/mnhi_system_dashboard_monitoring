<?php

namespace App\Services\ProjectScanner;

use App\Models\Finding;
use App\Models\Project;
use App\Services\ProjectDiscovery\ProjectRootResolver;
use App\Services\ProjectScanner\Metrics\DiagnosticsGatherer;
use App\Services\ProjectScanner\Metrics\FileMarkerGatherer;
use App\Services\ProjectScanner\Metrics\GitMetricsGatherer;
use App\Services\ProjectScanner\Metrics\LaravelStructureGatherer;
use App\Services\ProjectScanner\Metrics\TodoScanner;
use App\Services\RuleEngine\FindingResult;
use App\Services\RuleEngine\RuleEngineRunner;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectScanService
{
    public function __construct(
        private ProjectRootResolver $resolver,
        private StackDetector $stackDetector,
        private GitMetricsGatherer $git,
        private FileMarkerGatherer $files,
        private LaravelStructureGatherer $laravel,
        private TodoScanner $todos,
        private DiagnosticsGatherer $diagnostics,
        private RuleEngineRunner $ruleEngine,
    ) {}

    /**
     * Scan a single project: gather metrics, run rules, persist findings + snapshot.
     */
    public function scan(Project $project): Project
    {
        $start = microtime(true);

        $resolution = $this->resolver->resolve($project->root_path);
        $resolvedPath = $resolution['path'];

        $metrics = $this->gatherMetrics($resolvedPath, $project->root_path, $resolution['ambiguous']);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // Persist denormalised columns + full metrics blob.
        $project->resolved_path = $resolvedPath;
        $project->stack = $metrics['stack'];
        $project->stack_version = $metrics['stack_version'];
        $project->has_commits = $metrics['git']['has_commits'] ?? false;
        $project->last_commit_at = ! empty($metrics['git']['last_commit_at'])
            ? Carbon::parse($metrics['git']['last_commit_at'])
            : null;
        $project->last_scanned_at = now();
        $project->metrics = $metrics;
        $project->save();

        // Snapshot history.
        $project->scanSnapshots()->create([
            'metrics' => $metrics,
            'duration_ms' => $durationMs,
            'scanned_at' => now(),
        ]);
        $this->pruneSnapshots($project);

        // Run rules and reconcile findings.
        $results = $this->ruleEngine->run($project, $metrics);
        $this->reconcileFindings($project, $results);

        return $project;
    }

    private function gatherMetrics(string $resolvedPath, string $configuredPath, bool $ambiguous): array
    {
        $stack = $this->stackDetector->detect($resolvedPath);
        $git = $this->git->gather($resolvedPath);
        $files = $this->files->gather($resolvedPath);
        $todos = $this->todos->gather($resolvedPath);
        $diagnostics = $this->diagnostics->gather($resolvedPath, $stack['stack']);

        $metrics = [
            'stack' => $stack['stack'],
            'stack_version' => $stack['stack_version'],
            'stack_detection_failed' => $stack['detection_failed'],
            'git' => $git,
            'files' => $files,
            'todos' => $todos,
            'diagnostics' => $diagnostics,
            'root_resolution' => [
                'configured_path' => $configuredPath,
                'resolved_path' => $resolvedPath,
                'ambiguous' => $ambiguous,
            ],
            'scanned_at' => now()->toIso8601String(),
        ];

        if (str_starts_with($stack['stack'], 'laravel')) {
            $metrics['laravel'] = $this->laravel->gather($resolvedPath);
        }

        return $metrics;
    }

    /**
     * @param  Collection<int, FindingResult>  $results
     */
    private function reconcileFindings(Project $project, $results): void
    {
        $seenKeys = [];

        foreach ($results as $result) {
            /** @var FindingResult $result */
            $seenKeys[] = $result->ruleKey;

            $finding = Finding::firstOrNew([
                'project_id' => $project->id,
                'rule_key' => $result->ruleKey,
            ]);

            if (! $finding->exists) {
                $finding->status = 'open';
                $finding->first_detected_at = now();
            } elseif ($finding->status === 'resolved') {
                // Regression: an issue we'd marked fixed is back — reopen it.
                $finding->status = 'open';
                $finding->resolved_at = null;
            }

            // Refresh message/severity/details even for dismissed findings,
            // but leave the dismissed status intact.
            $finding->category = $result->category;
            $finding->source = 'heuristic';
            $finding->severity = $result->severity;
            $finding->message = $result->message;
            $finding->details = $result->details;
            $finding->last_seen_at = now();
            $finding->save();
        }

        // Heuristic findings not re-detected this scan are no longer present.
        // LLM-sourced findings are managed separately and must survive rescans.
        $gone = $project->findings()
            ->where('source', 'heuristic')
            ->when(! empty($seenKeys), fn ($q) => $q->whereNotIn('rule_key', $seenKeys));

        // An open finding that disappeared was fixed — mark it resolved (keep it
        // as a record) rather than deleting it silently.
        (clone $gone)->where('status', 'open')->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        // A dismissed finding that's also gone has no value to keep around.
        (clone $gone)->where('status', 'dismissed')->delete();
    }

    private function pruneSnapshots(Project $project): void
    {
        $keep = (int) config('dashboard.snapshots_to_keep', 20);

        $idsToKeep = $project->scanSnapshots()
            ->latest('scanned_at')
            ->limit($keep)
            ->pluck('id');

        $project->scanSnapshots()
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
