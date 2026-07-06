<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingEnvExampleRule implements GapRule
{
    public function key(): string
    {
        return 'missing_env_example';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $stack = $metrics['stack'] ?? 'unknown';
        $appliesTo = str_starts_with($stack, 'laravel')
            || str_starts_with($stack, 'php')
            || str_starts_with($stack, 'node');

        if (! $appliesTo) {
            return null;
        }

        if (($metrics['files']['has_env_example'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No .env.example — onboarding lacks a documented environment template.',
            );
        }

        return null;
    }
}
