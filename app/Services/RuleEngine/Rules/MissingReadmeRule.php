<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingReadmeRule implements GapRule
{
    public function key(): string
    {
        return 'missing_readme';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (($metrics['files']['has_readme'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No README — project purpose and setup are undocumented.',
            );
        }

        return null;
    }
}
