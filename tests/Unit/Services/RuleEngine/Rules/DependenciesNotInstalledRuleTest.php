<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\DependenciesNotInstalledRule;
use Tests\TestCase;

class DependenciesNotInstalledRuleTest extends TestCase
{
    public function test_returns_null_when_dependencies_are_installed(): void
    {
        $rule = new DependenciesNotInstalledRule;

        $result = $rule->evaluate(new Project, [
            'diagnostics' => ['deps' => ['needs_composer_install' => false, 'needs_npm_install' => false]],
        ]);

        $this->assertNull($result);
    }

    public function test_flags_missing_composer_install(): void
    {
        $rule = new DependenciesNotInstalledRule;

        $result = $rule->evaluate(new Project, [
            'diagnostics' => ['deps' => ['needs_composer_install' => true, 'needs_npm_install' => false]],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('error', $result->category);
        $this->assertSame(['composer install'], $result->details['commands']);
        $this->assertStringContainsString('composer install', $result->message);
    }

    public function test_flags_both_managers_when_both_are_missing(): void
    {
        $rule = new DependenciesNotInstalledRule;

        $result = $rule->evaluate(new Project, [
            'diagnostics' => ['deps' => ['needs_composer_install' => true, 'needs_npm_install' => true]],
        ]);

        $this->assertNotNull($result);
        $this->assertSame(['composer install', 'npm install'], $result->details['commands']);
        $this->assertStringContainsString('composer install && npm install', $result->message);
    }
}
