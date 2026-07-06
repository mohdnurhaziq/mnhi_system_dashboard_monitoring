<?php

namespace App\Services\RuleEngine;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use Illuminate\Support\Collection;

class RuleEngineRunner
{
    /**
     * Run all enabled rules against the project's metrics.
     *
     * @return Collection<int, FindingResult>
     */
    public function run(Project $project, array $metrics): Collection
    {
        $rules = config('dashboard.rules', []);
        $enabled = config('dashboard.rules_enabled', []);

        return collect($rules)
            ->filter(fn ($class, $key) => ($enabled[$key] ?? false))
            ->map(function ($class) {
                /** @var GapRule $rule */
                $rule = app($class);

                return $rule;
            })
            ->map(fn (GapRule $rule) => $this->safeEvaluate($rule, $project, $metrics))
            ->filter()
            ->values();
    }

    private function safeEvaluate(GapRule $rule, Project $project, array $metrics): ?FindingResult
    {
        try {
            return $rule->evaluate($project, $metrics);
        } catch (\Throwable) {
            // A single misbehaving rule must never abort the whole scan.
            return null;
        }
    }
}
