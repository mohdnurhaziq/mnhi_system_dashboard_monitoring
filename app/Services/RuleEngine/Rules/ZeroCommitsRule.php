<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class ZeroCommitsRule implements GapRule
{
    public function key(): string
    {
        return 'zero_commits';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $git = $metrics['git'] ?? [];

        if (($git['has_git'] ?? false) === true && ($git['has_commits'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'Git repository initialised but has zero commits — nothing is saved.',
            );
        }

        return null;
    }
}
