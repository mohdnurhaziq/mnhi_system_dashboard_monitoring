<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingLockFileRule implements GapRule
{
    public function key(): string
    {
        return 'missing_lock_file';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $missing = $metrics['dependencies']['missing_locks'] ?? [];
        if (empty($missing)) {
            return null;
        }

        $lockfiles = array_map(
            fn ($m) => $m === 'composer' ? 'composer.lock' : 'package-lock.json',
            $missing
        );

        return new FindingResult(
            $this->key(),
            $this->severity(),
            'Dependencies declared but no lock file ('.implode(', ', $missing).') — installs are not reproducible. Commit '.implode(' / ', $lockfiles).'.',
            ['managers' => $missing, 'lockfiles' => $lockfiles],
            category: 'gap',
        );
    }
}
