<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class HighTodoDensityRule implements GapRule
{
    public function key(): string
    {
        return 'high_todo_density';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $count = (int) ($metrics['todos']['count'] ?? 0);
        $threshold = (int) config('dashboard.todo_density_threshold', 10);

        if ($count >= $threshold) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                "{$count} TODO/FIXME markers found (threshold {$threshold}).",
                ['count' => $count, 'samples' => $metrics['todos']['samples'] ?? []],
            );
        }

        return null;
    }
}
