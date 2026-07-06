<?php

namespace App\Services\Llm;

use App\Models\Project;
use App\Services\PromptGeneration\ProjectContextGatherer;
use Illuminate\Support\Collection;

class ProjectAiService
{
    public function __construct(
        private OllamaClient $client,
        private ProjectContextGatherer $context,
    ) {}

    public function available(): bool
    {
        return $this->client->isAvailable();
    }

    public function model(): string
    {
        return $this->client->model();
    }

    /**
     * A 2–3 sentence plain-English summary of what a project is and its state.
     */
    public function summarize(Project $project): ?string
    {
        $system = <<<'SYS'
        You are a senior engineer. Given a project's structure and key files, write
        a concise 2-3 sentence summary of what the project is, its apparent purpose,
        and its current state. Be specific and factual — no filler, no marketing.
        Output plain text only (no headings, no markdown).
        SYS;

        return $this->clean($this->client->generateText($this->projectContext($project), $system));
    }

    /**
     * A starter README.md (Markdown) for a project, inferred from its code.
     */
    public function draftReadme(Project $project): ?string
    {
        $system = <<<'SYS'
        You are a senior engineer writing a starter README.md for a project, based
        on its structure and key files. Include: a title, a one-paragraph
        description, tech stack, setup/installation steps (inferred from the
        manifests you see), and basic usage. Be specific to THIS project; do not
        invent features. Output ONLY the README in Markdown, nothing else.
        SYS;

        return $this->clean($this->client->generateText($this->projectContext($project), $system));
    }

    /**
     * Answer a free-form question about the whole portfolio of projects.
     */
    public function ask(string $question, Collection $projects): ?string
    {
        $system = <<<'SYS'
        You are an assistant helping a developer understand and prioritize work
        across their local projects. You are given a compact table of every project
        with its stack, git state, and open findings, followed by a question.
        Answer the question directly and concretely using ONLY the data provided.
        Reference specific project names. If the data doesn't contain the answer,
        say so. Keep it tight; use short Markdown lists where helpful.
        SYS;

        $prompt = $this->portfolioTable($projects)."\n\n## Question\n".$question;

        return $this->clean($this->client->generateText($prompt, $system));
    }

    /**
     * A prioritized "what to work on next" digest across all projects.
     */
    public function digest(Collection $projects): ?string
    {
        $system = <<<'SYS'
        You are a senior engineering lead reviewing a developer's portfolio of local
        projects. You are given a compact table of every project with its stack, git
        state, and open findings. Produce a prioritized action plan:
        - Start with a one-sentence overall assessment.
        - Then a numbered "Top priorities" list (max 6), highest-impact first. For
          each: the project name, what to do, and why it matters — one line each.
        - End with a brief note on any quick wins.
        Be specific and reference real project names and findings. Output Markdown.
        SYS;

        return $this->clean($this->client->generateText($this->portfolioTable($projects), $system));
    }

    private function projectContext(Project $project): string
    {
        $path = $project->resolved_path ?? $project->root_path;
        $metrics = $project->metrics ?? [];
        $tree = $this->context->fileTree($path);
        $keyFiles = collect($this->context->keyFileExcerpts($path, $metrics['stack'] ?? null))
            ->map(fn ($kf) => "### {$kf['path']}\n```\n{$kf['content']}\n```")
            ->implode("\n\n");

        $stack = $metrics['stack'] ?? 'unknown';
        $readme = $metrics['files']['readme_excerpt'] ?? '(no README)';

        return <<<TXT
        # Project: {$project->name}
        Stack: {$stack}

        ## README excerpt
        {$readme}

        ## Structure
        ```
        {$tree}
        ```

        ## Key files
        {$keyFiles}
        TXT;
    }

    private function portfolioTable(Collection $projects): string
    {
        $rows = $projects->map(function (Project $p) {
            $git = $p->metrics['git'] ?? [];
            $open = $p->findings->where('status', 'open');
            $top = $open->take(4)->map(fn ($f) => "[{$f->severity}] {$f->message}")->implode('; ');
            $gitState = ($git['has_commits'] ?? false)
                ? "{$git['commit_count']} commits, last {$git['last_commit_at']}"
                : 'no commits';

            return "- {$p->name} ({$p->stack}) — {$gitState}; {$open->count()} open findings"
                .($top !== '' ? " — top: {$top}" : '');
        })->implode("\n");

        return "# Projects\n".$rows;
    }

    private function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
