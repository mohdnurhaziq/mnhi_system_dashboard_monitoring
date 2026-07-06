<?php

namespace App\Services\PromptGeneration;

use App\Models\Finding;
use App\Models\GeneratedPrompt;
use App\Models\Project;
use Illuminate\Support\Facades\View;
use Symfony\Component\Process\Process;

class PromptBuilder
{
    public function __construct(private ProjectContextGatherer $context) {}

    /**
     * Build (and persist) a ready-to-paste prompt for a project, optionally
     * scoped to a single finding. Pure local template assembly — no AI calls.
     */
    public function build(Project $project, ?Finding $finding = null): GeneratedPrompt
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
        $body = View::make($view, $context)->render();
        $body = trim($body);

        $title = $finding
            ? "Fix: {$finding->message} — {$project->name}"
            : "Improve {$project->name}";

        return $project->generatedPrompts()->create([
            'finding_id' => $finding?->id,
            'title' => \Illuminate\Support\Str::limit($title, 250),
            'body' => $body,
            'context_snapshot' => [
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
