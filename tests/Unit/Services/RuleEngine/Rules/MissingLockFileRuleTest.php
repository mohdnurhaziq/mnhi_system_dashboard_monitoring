<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\MissingLockFileRule;
use Tests\TestCase;

class MissingLockFileRuleTest extends TestCase
{
    public function test_returns_null_when_no_managers_missing_locks(): void
    {
        $rule = new MissingLockFileRule;

        $result = $rule->evaluate(new Project, ['dependencies' => ['missing_locks' => []]]);

        $this->assertNull($result);
    }

    public function test_flags_missing_composer_lock(): void
    {
        $rule = new MissingLockFileRule;

        $result = $rule->evaluate(new Project, ['dependencies' => ['missing_locks' => ['composer']]]);

        $this->assertNotNull($result);
        $this->assertSame(['composer'], $result->details['managers']);
        $this->assertSame(['composer.lock'], $result->details['lockfiles']);
        $this->assertStringContainsString('composer.lock', $result->message);
    }

    public function test_flags_both_managers(): void
    {
        $rule = new MissingLockFileRule;

        $result = $rule->evaluate(new Project, ['dependencies' => ['missing_locks' => ['composer', 'npm']]]);

        $this->assertNotNull($result);
        $this->assertSame(['composer.lock', 'package-lock.json'], $result->details['lockfiles']);
    }
}
