<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingCiRule;
use Tests\TestCase;

class MissingCiRuleTest extends TestCase
{
    public function test_flags_missing_ci(): void
    {
        $rule = new MissingCiRule;

        $result = $rule->evaluate(new Project, ['files' => ['has_ci' => false]]);

        $this->assertNotNull($result);
        $this->assertSame('info', $result->severity);
    }

    public function test_returns_null_when_ci_present(): void
    {
        $rule = new MissingCiRule;

        $result = $rule->evaluate(new Project, ['files' => ['has_ci' => true]]);

        $this->assertNull($result);
    }
}
