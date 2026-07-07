<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\NoGitRepoRule;
use Tests\TestCase;

class NoGitRepoRuleTest extends TestCase
{
    public function test_flags_missing_git(): void
    {
        $rule = new NoGitRepoRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_git' => false]]);

        $this->assertNotNull($result);
        $this->assertSame('warning', $result->severity);
    }

    public function test_returns_null_when_git_present(): void
    {
        $rule = new NoGitRepoRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_git' => true]]);

        $this->assertNull($result);
    }
}
