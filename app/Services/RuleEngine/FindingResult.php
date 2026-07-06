<?php

namespace App\Services\RuleEngine;

class FindingResult
{
    public function __construct(
        public string $ruleKey,
        public string $severity,
        public string $message,
        public array $details = [],
    ) {}
}
