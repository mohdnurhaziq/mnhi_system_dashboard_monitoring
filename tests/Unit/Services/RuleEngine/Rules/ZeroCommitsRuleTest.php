<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\ZeroCommitsRule;
use Tests\TestCase;

class ZeroCommitsRuleTest extends TestCase
{
    public function test_flags_git_repo_with_zero_commits(): void
    {
        $rule = new ZeroCommitsRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_git' => true, 'has_commits' => false]]);

        $this->assertNotNull($result);
        $this->assertSame('critical', $result->severity);
    }

    public function test_returns_null_without_git(): void
    {
        $rule = new ZeroCommitsRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_git' => false, 'has_commits' => false]]);

        $this->assertNull($result);
    }

    public function test_returns_null_with_commits(): void
    {
        $rule = new ZeroCommitsRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_git' => true, 'has_commits' => true]]);

        $this->assertNull($result);
    }
}
