<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class MissingAppKeyRule implements GapRule
{
    public function key(): string
    {
        return 'missing_app_key';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        if (! str_starts_with($metrics['stack'] ?? '', 'laravel')) {
            return null;
        }

        $env = $metrics['diagnostics']['env'] ?? [];

        // Only meaningful when a .env exists but APP_KEY is blank.
        if (($env['has_env'] ?? false) === true && ($env['has_app_key'] ?? true) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'APP_KEY is empty — run `php artisan key:generate`.',
                [],
                category: 'error',
            );
        }

        return null;
    }
}
