<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $staleDays = (int) config('dashboard.stale_threshold_days', 90);

        $statusCounts = Project::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $severityCounts = Finding::open()
            ->select('severity', DB::raw('count(*) as total'))
            ->groupBy('severity')
            ->pluck('total', 'severity');

        $needsAttention = Project::query()
            ->included()
            ->withCount([
                'findings as open_findings_count' => fn ($q) => $q->where('status', 'open'),
                'findings as critical_count' => fn ($q) => $q->where('status', 'open')->where('severity', 'critical'),
            ])
            ->orderByDesc('critical_count')
            ->orderByDesc('open_findings_count')
            ->limit(10)
            ->get(['id', 'name', 'stack', 'last_commit_at', 'has_commits']);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total' => Project::count(),
                'included' => (int) ($statusCounts['included'] ?? 0),
                'excluded' => (int) ($statusCounts['excluded'] ?? 0),
                'archived' => (int) ($statusCounts['archived'] ?? 0),
                'stale' => Project::included()->stale($staleDays)->count(),
                'zeroCommits' => Project::included()->where('has_commits', false)->count(),
                'findings' => [
                    'critical' => (int) ($severityCounts['critical'] ?? 0),
                    'warning' => (int) ($severityCounts['warning'] ?? 0),
                    'info' => (int) ($severityCounts['info'] ?? 0),
                ],
            ],
            'needsAttention' => $needsAttention,
            'staleThresholdDays' => $staleDays,
        ]);
    }
}
