<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingTestsRule;
use Tests\TestCase;

class MissingTestsRuleTest extends TestCase
{
    public function test_flags_missing_tests_directory(): void
    {
        $rule = new MissingTestsRule;

        $result = $rule->evaluate(new Project, ['stack' => 'laravel', 'files' => ['has_tests' => false]]);

        $this->assertNotNull($result);
    }

    public function test_returns_null_when_tests_present(): void
    {
        $rule = new MissingTestsRule;

        $result = $rule->evaluate(new Project, ['stack' => 'laravel', 'files' => ['has_tests' => true]]);

        $this->assertNull($result);
    }

    public function test_skips_unknown_stack(): void
    {
        $rule = new MissingTestsRule;

        $result = $rule->evaluate(new Project, ['stack' => 'unknown', 'files' => ['has_tests' => false]]);

        $this->assertNull($result);
    }

    public function test_skips_docker_stack(): void
    {
        $rule = new MissingTestsRule;

        $result = $rule->evaluate(new Project, ['stack' => 'docker', 'files' => ['has_tests' => false]]);

        $this->assertNull($result);
    }
}
