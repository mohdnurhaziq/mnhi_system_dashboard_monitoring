<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingReadmeRule;
use Tests\TestCase;

class MissingReadmeRuleTest extends TestCase
{
    public function test_flags_missing_readme(): void
    {
        $rule = new MissingReadmeRule;

        $result = $rule->evaluate(new Project, ['files' => ['has_readme' => false]]);

        $this->assertNotNull($result);
        $this->assertSame('warning', $result->severity);
    }

    public function test_returns_null_when_readme_present(): void
    {
        $rule = new MissingReadmeRule;

        $result = $rule->evaluate(new Project, ['files' => ['has_readme' => true]]);

        $this->assertNull($result);
    }
}
