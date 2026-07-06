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
        foreach ($projects as $project) {
            $this->scanner->scan($project);
        }

        return back()->with('success', "Scanned {$projects->count()} project(s).");
    }

    public function scanOne(Project $project): RedirectResponse
    {
        $this->scanner->scan($project);

        return back()->with('success', "Rescanned {$project->name}.");
    }

    public function discover(): RedirectResponse
    {
        $result = $this->discovery->discover();

        // Scan any freshly discovered projects so they show metrics immediately.
        Project::query()->included()->whereNull('last_scanned_at')->get()
            ->each(fn (Project $p) => $this->scanner->scan($p));

        return back()->with('success', "Discovered {$result['created']} new project(s).");
    }
}
