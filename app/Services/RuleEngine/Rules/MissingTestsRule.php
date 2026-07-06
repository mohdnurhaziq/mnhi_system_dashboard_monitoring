<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingTestsRule implements GapRule
{
    public function key(): string
    {
        return 'missing_tests';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $stack = $metrics['stack'] ?? 'unknown';

        // Skip stacks where a tests dir isn't a meaningful expectation.
        if (in_array($stack, ['unknown', 'docker'], true)) {
            return null;
        }

        if (($metrics['files']['has_tests'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No tests directory found.',
            );
        }

        return null;
    }
}
