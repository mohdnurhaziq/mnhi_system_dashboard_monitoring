<?php

namespace App\Services\RuleEngine\Contracts;

use App\Models\Project;
use App\Services\RuleEngine\FindingResult;

interface GapRule
{
    /** Stable machine key, matching the config registration key. */
    public function key(): string;

    /** Default severity: info | warning | critical. */
    public function severity(): string;

    /**
     * Evaluate the rule against a project's latest scan metrics.
     * Return a FindingResult if a gap is detected, or null otherwise.
     */
    public function evaluate(Project $project, array $metrics): ?FindingResult;
}
