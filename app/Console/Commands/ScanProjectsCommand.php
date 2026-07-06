<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\ProjectDiscovery\ProjectDiscoveryService;
use App\Services\ProjectScanner\ProjectScanService;
use Illuminate\Console\Command;

class ScanProjectsCommand extends Command
{
    protected $signature = 'projects:scan {project? : Project id or root_path to scan a single one} {--discover : Run discovery for new projects first}';

    protected $description = 'Discover and scan local projects, detecting gaps via the heuristic rule engine.';

    public function handle(
        ProjectDiscoveryService $discovery,
        ProjectScanService $scanner,
    ): int {
        if ($this->option('discover') || Project::count() === 0) {
            $this->info('Discovering projects...');
            $result = $discovery->discover();
            $this->line("  Created {$result['created']} new, {$result['existing']} already known.");
        }

        $projects = $this->resolveProjects();

        if ($projects->isEmpty()) {
            $this->warn('No projects to scan.');

            return self::SUCCESS;
        }

        $this->info("Scanning {$projects->count()} project(s)...");

        $rows = [];
        foreach ($projects as $project) {
            $scanner->scan($project);
            $project->refresh();

            $rows[] = [
                $project->name,
                $project->stack ?? 'unknown',
                $project->has_commits ? 'yes' : 'no',
                $project->findings()->open()->count(),
            ];
        }

        $this->table(['Project', 'Stack', 'Commits', 'Open findings'], $rows);
        $this->info('Done.');

        return self::SUCCESS;
    }

    /** @return \Illuminate\Support\Collection<int, Project> */
    private function resolveProjects()
    {
        $arg = $this->argument('project');

        if ($arg !== null) {
            $query = Project::query();
            if (is_numeric($arg)) {
                $query->where('id', (int) $arg);
            } else {
                $query->where('root_path', $arg)->orWhere('name', $arg);
            }

            return $query->get();
        }

        return Project::query()->included()->get();
    }
}
