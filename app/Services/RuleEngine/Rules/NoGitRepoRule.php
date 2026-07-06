<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class NoGitRepoRule implements GapRule
{
    public function key(): string
    {
        return 'no_git_repo';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (($metrics['git']['has_git'] ?? false) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'No git repository — project is not under version control.',
            );
        }

        return null;
    }
}
