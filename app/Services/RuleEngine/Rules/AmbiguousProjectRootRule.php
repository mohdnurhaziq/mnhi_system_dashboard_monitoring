<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class AmbiguousProjectRootRule implements GapRule
{
    public function key(): string
    {
        return 'ambiguous_project_root';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (($metrics['root_resolution']['ambiguous'] ?? false) === true) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'Multiple candidate project roots found — could not resolve automatically.',
                ['configured_path' => $metrics['root_resolution']['configured_path'] ?? null],
            );
        }

        return null;
    }
}
