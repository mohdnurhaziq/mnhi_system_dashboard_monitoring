<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Llm\ProjectAiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class AiController extends Controller
{
    public function __construct(private ProjectAiService $ai) {}

    /**
     * Generate & store a short AI summary of what a project is and its state.
     */
    public function summarize(Project $project): RedirectResponse
    {
        if (! $this->ai->available()) {
            return back()->with('error', $this->offline());
        }

        $summary = $this->ai->summarize($project);
        if ($summary === null) {
            return back()->with('error', 'The model did not return a summary.');
        }

        $project->update(['ai_summary' => $summary, 'ai_summary_at' => now()]);

        return back()->with('success', 'AI summary generated.');
    }

    /**
     * Draft a starter README (persisted as a generated prompt so it shows in the
     * same copy-paste modal / history).
     */
    public function readme(Project $project): RedirectResponse
    {
        if (! $this->ai->available()) {
            return back()->with('error', $this->offline());
        }

        $readme = $this->ai->draftReadme($project);
        if ($readme === null) {
            return back()->with('error', 'The model did not return a README.');
        }

        $project->generatedPrompts()->create([
            'title' => "README draft — {$project->name}",
            'body' => $readme,
            'context_snapshot' => ['kind' => 'readme_draft'],
        ]);

        return back()->with('success', 'AI README draft generated.');
    }

    /**
     * Generate a prioritized cross-project "what to work on next" digest and
     * cache it so it persists between visits until regenerated.
     */
    public function digest(): RedirectResponse
    {
        if (! $this->ai->available()) {
            return back()->with('error', $this->offline());
        }

        $projects = Project::query()->included()
            ->with(['findings' => fn ($q) => $q->where('status', 'open')])
            ->get();

        $digest = $this->ai->digest($projects);
        if ($digest === null) {
            return back()->with('error', 'The model did not return a digest.');
        }

        Cache::forever('dashboard.ai_digest', [
            'content' => $digest,
            'generated_at' => now()->toIso8601String(),
            'model' => $this->ai->model(),
        ]);

        return back()->with('success', 'AI priorities digest generated.');
    }

    /**
     * The "Ask AI about your projects" page (shows the last answer).
     */
    public function askPage(Request $request): Response
    {
        return Inertia::render('Ask/Index', [
            'available' => $this->ai->available(),
            'question' => $request->session()->get('ai_question'),
            'answer' => $request->session()->get('ai_answer'),
        ]);
    }

    /**
     * Answer a free-form question over the whole portfolio.
     */
    public function ask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        if (! $this->ai->available()) {
            return back()->with('error', $this->offline());
        }

        $projects = Project::query()->included()
            ->with(['findings' => fn ($q) => $q->where('status', 'open')])
            ->get();

        $answer = $this->ai->ask($validated['question'], $projects);

        return back()->with([
            'ai_question' => $validated['question'],
            'ai_answer' => $answer ?? 'The model did not return an answer.',
        ]);
    }

    private function offline(): string
    {
        return 'Local LLM (Ollama) is not reachable — start Ollama and pull the model, then retry.';
    }
}
