<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class UncommittedChangesRule implements GapRule
{
    public function key(): string
    {
        return 'uncommitted_changes';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $count = (int) ($metrics['git']['uncommitted_files'] ?? 0);

        if ($count > 0) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                "{$count} uncommitted file(s) in the working tree.",
                ['uncommitted_files' => $count],
            );
        }

        return null;
    }
}
