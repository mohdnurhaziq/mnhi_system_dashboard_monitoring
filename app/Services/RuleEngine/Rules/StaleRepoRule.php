<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;
use Carbon\Carbon;

class StaleRepoRule implements GapRule
{
    public function key(): string
    {
        return 'stale_repo';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $git = $metrics['git'] ?? [];

        if (($git['has_commits'] ?? false) !== true || empty($git['last_commit_at'])) {
            return null;
        }

        $threshold = (int) config('dashboard.stale_threshold_days', 90);
        $lastCommit = Carbon::parse($git['last_commit_at']);
        $days = (int) $lastCommit->diffInDays(now());

        if ($days > $threshold) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                "No commits in {$days} days (threshold {$threshold}).",
                ['days_since_last_commit' => $days, 'last_commit_at' => $git['last_commit_at']],
            );
        }

        return null;
    }
}
