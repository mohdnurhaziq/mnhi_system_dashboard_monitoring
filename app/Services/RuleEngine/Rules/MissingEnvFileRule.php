<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingEnvFileRule implements GapRule
{
    public function key(): string
    {
        return 'missing_env_file';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (! str_starts_with($metrics['stack'] ?? '', 'laravel')) {
            return null;
        }

        if (($metrics['diagnostics']['env']['has_env'] ?? true) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No .env file — the app cannot boot. Copy .env.example and configure it.',
                [],
                category: 'error',
            );
        }

        return null;
    }
}
