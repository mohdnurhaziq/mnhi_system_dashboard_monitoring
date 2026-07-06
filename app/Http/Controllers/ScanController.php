<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectDiscovery\ProjectDiscoveryService;
use App\Services\ProjectScanner\ProjectScanService;
use Illuminate\Http\RedirectResponse;

class ScanController extends Controller
{
    public function __construct(
        private ProjectDiscoveryService $discovery,
        private ProjectScanService $scanner,
    ) {}

    public function scanAll(): RedirectResponse
    {
        $projects = Project::query()->included()->get();
        $scanned = 0;
        $failed = [];

        // Keep going if a single project fails (e.g. its directory was deleted
        // or a git command errors) so one bad project can't abort the batch.
        foreach ($projects as $project) {
            try {
                $this->scanner->scan($project);
                $scanned++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $project->name;
            }
        }

        if ($failed !== []) {
            return back()->with('error', "Scanned {$scanned} project(s); ".count($failed)
                .' failed ('.implode(', ', $failed).'). See the log for details.');
        }

        return back()->with('success', "Scanned {$scanned} project(s).");
    }

    public function scanOne(Project $project): RedirectResponse
    {
        try {
            $this->scanner->scan($project);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Could not scan {$project->name}: {$e->getMessage()}");
        }

        return back()->with('success', "Rescanned {$project->name}.");
    }

    public function discover(): RedirectResponse
    {
        try {
            $result = $this->discovery->discover();
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Discovery failed: '.$e->getMessage());
        }

        // Scan any freshly discovered projects so they show metrics immediately.
        // A scan failure here shouldn't hide the fact that discovery succeeded;
        // the project is still created and can be rescanned individually.
        Project::query()->included()->whereNull('last_scanned_at')->get()
            ->each(function (Project $p) {
                try {
                    $this->scanner->scan($p);
                } catch (\Throwable $e) {
                    report($e);
                }
            });

        return back()->with('success', "Discovered {$result['created']} new project(s).");
    }
}
