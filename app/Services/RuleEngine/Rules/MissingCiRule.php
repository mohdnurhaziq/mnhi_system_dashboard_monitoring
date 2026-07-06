<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingCiRule implements GapRule
{
    public function key(): string
    {
        return 'missing_ci';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (($metrics['files']['has_ci'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No CI configuration (GitHub Actions / GitLab CI / Jenkins).',
            );
        }

        return null;
    }
}
