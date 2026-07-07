<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingAppKeyRule;
use Tests\TestCase;

class MissingAppKeyRuleTest extends TestCase
{
    public function test_flags_blank_app_key_when_env_exists(): void
    {
        $rule = new MissingAppKeyRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'diagnostics' => ['env' => ['has_env' => true, 'has_app_key' => false]],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('critical', $result->severity);
    }

    public function test_returns_null_when_app_key_present(): void
    {
        $rule = new MissingAppKeyRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'diagnostics' => ['env' => ['has_env' => true, 'has_app_key' => true]],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_env_file_missing(): void
    {
        // Covered separately by MissingEnvFileRule — avoid double-flagging.
        $rule = new MissingAppKeyRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'laravel',
            'diagnostics' => ['env' => ['has_env' => false, 'has_app_key' => false]],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_for_non_laravel_stack(): void
    {
        $rule = new MissingAppKeyRule;

        $result = $rule->evaluate(new Project, [
            'stack' => 'node-react',
            'diagnostics' => ['env' => ['has_env' => true, 'has_app_key' => false]],
        ]);

        $this->assertNull($result);
    }
}
