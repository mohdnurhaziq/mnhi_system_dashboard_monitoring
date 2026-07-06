<?php

namespace App\Services\PromptGeneration;

use App\Models\Finding;
use App\Models\GeneratedPrompt;
use App\Models\Project;
use App\Services\Llm\PromptOptimizer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PromptBuilder
{
    public function __construct(
        private ProjectContextGatherer $context,
        private PromptOptimizer $optimizer,
    ) {}

    /**
     * Build (and persist) a ready-to-paste prompt for a project, optionally
     * scoped to a single finding.
     *
     * Base assembly is pure local templating. When $optimize is true and the
     * local LLM is reachable, the assembled context is additionally run through
     * Ollama to produce a focused task brief prepended above the raw context.
     */
    public function build(Project $project, ?Finding $finding = null, bool $optimize = false): GeneratedPrompt
    {
        $metrics = $project->metrics ?? [];
        $path = $project->resolved_path ?? $project->root_path;

        $openFindings = $finding
            ? [$finding]
            : $project->findings()->open()->orderByRaw($this->severityOrder())->get()->all();

        $context = [
            'project' => $project,
            'metrics' => $metrics,
            'path' => $path,
            'readme' => $this->fullReadme($path),
            'gitLog' => $this->recentGitLog($path),
            'fileTree' => $this->context->fileTree($path),
            'keyFiles' => $this->context->keyFileExcerpts($path, $metrics['stack'] ?? null),
            'findings' => $openFindings,
            'scopedFinding' => $finding,
        ];

        $view = $finding ? 'prompts.finding-context' : 'prompts.project-context';
        $body = trim(View::make($view, $context)->render());

        // Optionally refine the assembled context into a focused brief via the
        // local LLM. Falls back to the plain template if unavailable/failed.
        $optimized = false;
        if ($optimize && $this->optimizer->available()) {
            $brief = $this->optimizer->optimize($body);
            if ($brief !== null) {
                $body = $brief."\n\n---\n\n## Detailed context (auto-generated)\n\n".$body;
                $optimized = true;
            }
        }

        $prefix = $optimized ? '✨ ' : '';
        $title = $finding
            ? "{$prefix}Fix: {$finding->message} — {$project->name}"
            : "{$prefix}Improve {$project->name}";

        return $project->generatedPrompts()->create([
            'finding_id' => $finding?->id,
            'title' => Str::limit($title, 250),
            'body' => $body,
            'context_snapshot' => [
                'optimized' => $optimized,
                'optimize_requested' => $optimize,
                'metrics' => $metrics,
                'findings' => collect($openFindings)->map(fn ($f) => [
                    'rule_key' => $f->rule_key,
                    'severity' => $f->severity,
                    'message' => $f->message,
                ])->all(),
            ],
        ]);
    }

    private function severityOrder(): string
    {
        return "CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END";
    }

    private function fullReadme(string $path): ?string
    {
        foreach (['README.md', 'README.MD', 'readme.md', 'README', 'README.txt'] as $name) {
            $file = $path.'/'.$name;
            if (is_file($file)) {
                try {
                    $contents = file_get_contents($file, false, null, 0, 4000);

                    return $contents !== false ? trim($contents) : null;
                } catch (\Throwable) {
                    return null;
                }
            }
        }

        return null;
    }

    private function recentGitLog(string $path): ?string
    {
        if (! is_dir($path.'/.git')) {
            return null;
        }

        try {
            $process = new Process(
                ['git', '-C', $path, 'log', '--oneline', '-10'],
                $path, null, null, 15.0
            );
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $out = trim($process->getOutput());

            return $out === '' ? null : $out;
        } catch (\Throwable) {
            return null;
        }
    }
}
