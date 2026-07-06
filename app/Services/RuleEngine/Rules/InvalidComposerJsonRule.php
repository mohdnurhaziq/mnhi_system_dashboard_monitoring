<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class InvalidComposerJsonRule implements GapRule
{
    public function key(): string
    {
        return 'invalid_composer_json';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        // Malformed manifest that broke stack detection.
        if (($metrics['stack_detection_failed'] ?? false) === true) {
            return new FindingResult(
                $this->key(),
                'critical',
                'A manifest (composer.json / package.json) is malformed and could not be parsed.',
                [],
                category: 'error',
            );
        }

        // composer validate failed (structural issues, missing required fields, etc.).
        if (($metrics['diagnostics']['composer_valid'] ?? null) === false) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                'composer.json failed validation — run `composer validate` for details.',
                [],
                category: 'error',
            );
        }

        return null;
    }
}
