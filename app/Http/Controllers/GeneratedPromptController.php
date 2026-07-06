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
            'optimize' => ['sometimes', 'boolean'],
        ]);

        $finding = null;
        if (! empty($validated['finding_id'])) {
            $finding = Finding::where('id', $validated['finding_id'])
                ->where('project_id', $project->id)
                ->firstOrFail();
        }

        $optimize = (bool) ($validated['optimize'] ?? false);

        try {
            $prompt = $builder->build($project, $finding, $optimize);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Could not generate prompt: '.$e->getMessage());
        }

        // Tell the user what they actually got, including the fallback case where
        // AI optimization was requested but the local LLM was unreachable.
        if ($optimize && ! ($prompt->context_snapshot['optimized'] ?? false)) {
            return back()->with('error', 'AI optimization unavailable (Ollama offline) — generated a standard prompt instead.');
        }

        return back()->with('success', $optimize ? 'AI-optimized prompt generated.' : 'Prompt generated.');
    }
}
