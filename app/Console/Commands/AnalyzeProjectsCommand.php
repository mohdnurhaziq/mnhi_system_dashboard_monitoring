<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Llm\LlmAnalysisService;
use Illuminate\Console\Command;

class AnalyzeProjectsCommand extends Command
{
    protected $signature = 'projects:analyze {project? : Project id or name; omit to analyse all included projects}';

    protected $description = 'Run local-LLM analysis (ideas / UI / errors) on projects via Ollama.';

    public function handle(LlmAnalysisService $service): int
    {
        $projects = $this->resolveProjects();

        if ($projects->isEmpty()) {
            $this->warn('No matching projects.');

            return self::SUCCESS;
        }

        foreach ($projects as $project) {
            $this->line("Analysing {$project->name}...");
            $result = $service->analyze($project);
            $result['ok']
                ? $this->info('  '.$result['message'])
                : $this->error('  '.$result['message']);

            if (! $result['ok'] && str_contains($result['message'], 'not reachable')) {
                // No point continuing if the server is down.
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Project> */
    private function resolveProjects()
    {
        $arg = $this->argument('project');

        if ($arg !== null) {
            return Project::query()
                ->when(is_numeric($arg), fn ($q) => $q->where('id', (int) $arg))
                ->when(! is_numeric($arg), fn ($q) => $q->where('name', $arg))
                ->get();
        }

        return Project::query()->included()->get();
    }
}
