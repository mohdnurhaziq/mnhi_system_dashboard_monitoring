<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\UncommittedChangesRule;
use Tests\TestCase;

class UncommittedChangesRuleTest extends TestCase
{
    public function test_flags_uncommitted_files(): void
    {
        $rule = new UncommittedChangesRule;

        $result = $rule->evaluate(new Project, ['git' => ['uncommitted_files' => 3]]);

        $this->assertNotNull($result);
        $this->assertSame(3, $result->details['uncommitted_files']);
    }

    public function test_returns_null_when_clean(): void
    {
        $rule = new UncommittedChangesRule;

        $result = $rule->evaluate(new Project, ['git' => ['uncommitted_files' => 0]]);

        $this->assertNull($result);
    }
}
