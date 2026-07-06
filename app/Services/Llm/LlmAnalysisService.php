<?php

namespace App\Services\Llm;

use App\Models\Finding;
use App\Models\Project;
use App\Services\PromptGeneration\ProjectContextGatherer;

class LlmAnalysisService
{
    public function __construct(
        private OllamaClient $client,
        private ProjectContextGatherer $context,
    ) {}

    /**
     * Analyse a project with the local LLM and persist idea/ui/error findings
     * (source = llm). Returns a status array for the caller/UI.
     *
     * @return array{ok: bool, message: string, count: int}
     */
    public function analyze(Project $project): array
    {
        if (! $this->client->isAvailable()) {
            return [
                'ok' => false,
                'message' => 'Local LLM (Ollama) is not reachable or the model is not pulled.',
                'count' => 0,
            ];
        }

        $path = $project->resolved_path ?? $project->root_path;
        $prompt = $this->buildPrompt($project, $path);

        $raw = $this->client->generateJson($prompt, $this->systemPrompt());
        if ($raw === null) {
            return ['ok' => false, 'message' => 'The model did not return a response.', 'count' => 0];
        }

        $items = $this->parseItems($raw);
        if ($items === null) {
            return ['ok' => false, 'message' => 'Could not parse the model response as JSON.', 'count' => 0];
        }

        $count = $this->persist($project, $items);

        return [
            'ok' => true,
            'message' => "AI analysis complete — {$count} suggestion(s) via {$this->client->model()}.",
            'count' => $count,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'SYS'
        You are a senior engineer reviewing a local software project. You are given
        its structure and key files. Identify concrete, actionable items in three
        categories:
          - "error": something broken or risky that will cause bugs or failures
          - "idea": a valuable feature or architectural improvement
          - "ui": a specific user-interface / UX improvement (only if the project has a UI)
        Respond ONLY with a JSON object of this exact shape:
        {"items":[{"category":"error|idea|ui","severity":"info|warning|critical","title":"short title","detail":"1-3 sentence explanation with a concrete suggestion"}]}
        Return at most 8 items total, highest-value first. Be specific to THIS project's
        code — no generic advice. If a category doesn't apply, omit it.
        SYS;
    }

    private function buildPrompt(Project $project, string $path): string
    {
        $metrics = $project->metrics ?? [];
        $tree = $this->context->fileTree($path);
        $keyFiles = $this->context->keyFileExcerpts($path, $metrics['stack'] ?? null);

        $findings = $project->findings()
            ->where('source', 'heuristic')
            ->get()
            ->map(fn ($f) => "- [{$f->severity}] {$f->message}")
            ->implode("\n");

        $keyFilesText = collect($keyFiles)
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
        {$keyFilesText}

        ## Already-known heuristic findings (do not repeat these)
        {$findings}

        Now produce your JSON analysis.
        TXT;
    }

    /**
     * @return list<array{category:string,severity:string,title:string,detail:string}>|null
     */
    private function parseItems(string $raw): ?array
    {
        // The model may wrap JSON in prose or code fences; extract the object.
        $json = $this->extractJson($raw);
        if ($json === null) {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        $items = $decoded['items'] ?? (is_list($decoded ?? []) ? $decoded : null);
        if (! is_array($items)) {
            return null;
        }

        $valid = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['title'])) {
                continue;
            }
            $category = in_array($item['category'] ?? '', ['error', 'idea', 'ui'], true)
                ? $item['category']
                : 'idea';
            $severity = in_array($item['severity'] ?? '', ['info', 'warning', 'critical'], true)
                ? $item['severity']
                : 'info';

            $valid[] = [
                'category' => $category,
                'severity' => $severity,
                'title' => mb_substr((string) $item['title'], 0, 250),
                'detail' => (string) ($item['detail'] ?? ''),
            ];
        }

        return $valid;
    }

    private function extractJson(string $raw): ?string
    {
        $raw = trim($raw);
        $start = strpos($raw, '{');
        $end = strrpos($raw, '}');

        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        return substr($raw, $start, $end - $start + 1);
    }

    /**
     * Replace all prior LLM findings for the project with the new set.
     */
    private function persist(Project $project, array $items): int
    {
        $project->findings()->where('source', 'llm')->delete();

        $n = 0;
        foreach ($items as $i => $item) {
            Finding::create([
                'project_id' => $project->id,
                'rule_key' => 'llm_'.$item['category'].'_'.($i + 1),
                'category' => $item['category'],
                'source' => 'llm',
                'severity' => $item['severity'],
                'message' => $item['title'],
                'details' => ['detail' => $item['detail']],
                'status' => 'open',
                'first_detected_at' => now(),
                'last_seen_at' => now(),
            ]);
            $n++;
        }

        return $n;
    }
}
