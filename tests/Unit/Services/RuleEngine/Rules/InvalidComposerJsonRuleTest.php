<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\InvalidComposerJsonRule;
use Tests\TestCase;

class InvalidComposerJsonRuleTest extends TestCase
{
    public function test_flags_malformed_manifest_as_critical(): void
    {
        $rule = new InvalidComposerJsonRule;

        $result = $rule->evaluate(new Project, [
            'stack_detection_failed' => true,
            'diagnostics' => ['composer_valid' => null],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('critical', $result->severity);
        $this->assertSame('error', $result->category);
    }

    public function test_flags_failed_composer_validate(): void
    {
        $rule = new InvalidComposerJsonRule;

        $result = $rule->evaluate(new Project, [
            'stack_detection_failed' => false,
            'diagnostics' => ['composer_valid' => false],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('warning', $result->severity);
    }

    public function test_returns_null_when_valid(): void
    {
        $rule = new InvalidComposerJsonRule;

        $result = $rule->evaluate(new Project, [
            'stack_detection_failed' => false,
            'diagnostics' => ['composer_valid' => true],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_when_composer_json_absent(): void
    {
        $rule = new InvalidComposerJsonRule;

        $result = $rule->evaluate(new Project, [
            'stack_detection_failed' => false,
            'diagnostics' => ['composer_valid' => null],
        ]);

        $this->assertNull($result);
    }
}
