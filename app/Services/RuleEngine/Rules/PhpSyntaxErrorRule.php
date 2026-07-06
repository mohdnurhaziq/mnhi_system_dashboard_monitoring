<?php

namespace App\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;

class PhpSyntaxErrorRule implements GapRule
{
    public function key(): string
    {
        return 'php_syntax_error';
    }

    public function severity(): string
    {
        return 'critical';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        $syntax = $metrics['diagnostics']['php_syntax'] ?? [];
        $errors = (int) ($syntax['errors'] ?? 0);

        if ($errors > 0) {
            return new FindingResult(
                $this->key(),
                $this->severity(),
                "{$errors} PHP file(s) with syntax errors (sampled).",
                ['samples' => $syntax['samples'] ?? []],
                category: 'error',
            );
        }

        return null;
    }
}
