<?php

namespace App\Http\Controllers;

use App\Models\Finding;
use App\Models\GeneratedPrompt;
use App\Models\Project;
use App\Services\PromptGeneration\PromptBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeneratedPromptController extends Controller
{
    public function index(Request $request): Response
    {
        $query = GeneratedPrompt::query()
            ->with('project:id,name')
            ->latest();

        if ($projectId = $request->integer('project')) {
            $query->where('project_id', $projectId);
        }

        return Inertia::render('Prompts/Index', [
            'prompts' => $query->limit(100)->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'filters' => ['project' => $projectId ?: null],
        ]);
    }

    public function store(Request $request, Project $project, PromptBuilder $builder): RedirectResponse
    {
        $validated = $request->validate([
            'finding_id' => ['nullable', 'integer', 'exists:findings,id'],
        ]);

        $finding = null;
        if (! empty($validated['finding_id'])) {
            $finding = Finding::where('id', $validated['finding_id'])
                ->where('project_id', $project->id)
                ->firstOrFail();
        }

        $builder->build($project, $finding);

        return back()->with('success', 'Prompt generated.');
    }
}
