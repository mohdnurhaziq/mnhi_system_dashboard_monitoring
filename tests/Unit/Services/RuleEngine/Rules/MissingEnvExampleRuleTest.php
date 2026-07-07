<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingEnvExampleRule;
use Tests\TestCase;

class MissingEnvExampleRuleTest extends TestCase
{
    public function test_flags_missing_env_example_for_laravel(): void
    {
        $rule = new MissingEnvExampleRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'files' => ['has_env_example' => false],
        ]);

        $this->assertNotNull($result);
    }

    public function test_flags_missing_env_example_for_node(): void
    {
        $rule = new MissingEnvExampleRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'node-react',
            'files' => ['has_env_example' => false],
        ]);

        $this->assertNotNull($result);
    }

    public function test_does_not_apply_to_go(): void
    {
        $rule = new MissingEnvExampleRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'go',
            'files' => ['has_env_example' => false],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_present(): void
    {
        $rule = new MissingEnvExampleRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'files' => ['has_env_example' => true],
        ]);

        $this->assertNull($result);
    }
}
