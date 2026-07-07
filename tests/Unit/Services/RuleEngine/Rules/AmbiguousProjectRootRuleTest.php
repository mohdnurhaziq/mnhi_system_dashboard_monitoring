<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\AmbiguousProjectRootRule;
use Tests\TestCase;

class AmbiguousProjectRootRuleTest extends TestCase
{
    public function test_flags_ambiguous_root_resolution(): void
    {
        $rule = new AmbiguousProjectRootRule;

        $result = $rule->evaluate(new Project, [
            'root_resolution' => ['ambiguous' => true, 'configured_path' => '/tmp/foo'],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('ambiguous_project_root', $result->ruleKey);
        $this->assertSame('info', $result->severity);
        $this->assertSame('/tmp/foo', $result->details['configured_path']);
    }

    public function test_returns_null_when_not_ambiguous(): void
    {
        $rule = new AmbiguousProjectRootRule;

        $result = $rule->evaluate(new Project, [
            'root_resolution' => ['ambiguous' => false, 'configured_path' => '/tmp/foo'],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_key_missing(): void
    {
        $rule = new AmbiguousProjectRootRule;

        $this->assertNull($rule->evaluate(new Project, []));
    }
}
