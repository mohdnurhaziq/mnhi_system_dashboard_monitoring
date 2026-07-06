<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::query()
            ->withCount([
                'findings as open_findings_count' => fn ($q) => $q->where('status', 'open'),
            ]);

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($stack = $request->string('stack')->toString()) {
            $query->where('stack', $stack);
        }

        $sort = $request->string('sort', 'name')->toString();
        $direction = $request->string('direction', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $sortable = ['name', 'last_commit_at', 'stack', 'open_findings_count'];
        if (in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        }

        $projects = $query->get();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => [
                'status' => $status ?: null,
                'stack' => $stack ?: null,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'stacks' => Project::whereNotNull('stack')->distinct()->orderBy('stack')->pluck('stack'),
        ]);
    }

    public function show(Project $project): Response
    {
        $project->load([
            'findings' => fn ($q) => $q->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
                ->orderBy('rule_key'),
            'generatedPrompts' => fn ($q) => $q->latest()->limit(10),
        ]);

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'staleThresholdDays' => (int) config('dashboard.stale_threshold_days', 90),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:included,excluded,archived'],
        ]);

        $project->update($validated);

        return back()->with('success', 'Project updated.');
    }
}
