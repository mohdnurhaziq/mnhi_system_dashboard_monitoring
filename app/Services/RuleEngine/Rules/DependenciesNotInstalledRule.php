<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class DependenciesNotInstalledRule implements GapRule
{
    public function key(): string
    {
        return 'dependencies_not_installed';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $deps = $metrics['diagnostics']['deps'] ?? [];
        $needs = [];

        if ($deps['needs_composer_install'] ?? false) {
            $needs[] = 'composer install';
        }
        if ($deps['needs_npm_install'] ?? false) {
            $needs[] = 'npm install';
        }

        if (empty($needs)) {
            return null;
        }

        return new FindingResult(
            $this->key(),
            $this->severity(),
            'Dependencies not installed — run: '.implode(' && ', $needs),
            ['commands' => $needs],
            category: 'error',
        );
    }
}
