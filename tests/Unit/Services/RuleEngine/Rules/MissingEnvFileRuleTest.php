<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingEnvFileRule;
use Tests\TestCase;

class MissingEnvFileRuleTest extends TestCase
{
    public function test_flags_missing_env_for_laravel(): void
    {
        $rule = new MissingEnvFileRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'diagnostics' => ['env' => ['has_env' => false]],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('critical', $result->severity);
        $this->assertSame('error', $result->category);
    }

    public function test_returns_null_when_env_present(): void
    {
        $rule = new MissingEnvFileRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'diagnostics' => ['env' => ['has_env' => true]],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_for_non_laravel_stack(): void
    {
        $rule = new MissingEnvFileRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'node-generic',
            'diagnostics' => ['env' => ['has_env' => false]],
        ]);

        $this->assertNull($result);
    }
}
