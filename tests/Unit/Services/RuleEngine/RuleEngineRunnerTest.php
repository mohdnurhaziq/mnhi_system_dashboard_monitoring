<?php

namespace Tests\Unit\Services\RuleEngine;

use App\Models\Project;
use App\Services\RuleEngine\Contracts\GapRule;
use App\Services\RuleEngine\FindingResult;
use App\Services\RuleEngine\RuleEngineRunner;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RuleEngineRunnerTest extends TestCase
{
    public function test_only_enabled_rules_are_run(): void
    {
        Config::set('dashboard.rules', [
            'working' => WorkingTestGapRule::class,
            'other' => WorkingTestGapRule::class,
        ]);
        Config::set('dashboard.rules_enabled', [
            'working' => true,
            'other' => false,
        ]);

        $results = (new RuleEngineRunner)->run(new Project, []);

        $this->assertCount(1, $results);
        $this->assertSame('working_test_rule', $results->first()->ruleKey);
    }

    public function test_a_throwing_rule_does_not_abort_the_run(): void
    {
        Config::set('dashboard.rules', [
            'working' => WorkingTestGapRule::class,
            'broken' => ThrowingTestGapRule::class,
        ]);
        Config::set('dashboard.rules_enabled', [
            'working' => true,
            'broken' => true,
        ]);

        $results = (new RuleEngineRunner)->run(new Project, []);

        $this->assertCount(1, $results);
        $this->assertSame('working_test_rule', $results->first()->ruleKey);
    }

    public function test_returns_empty_collection_when_no_rules_are_enabled(): void
    {
        Config::set('dashboard.rules', ['working' => WorkingTestGapRule::class]);
        Config::set('dashboard.rules_enabled', ['working' => false]);

        $results = (new RuleEngineRunner)->run(new Project, []);

        $this->assertCount(0, $results);
    }
}

class WorkingTestGapRule implements GapRule
{
    public function key(): string
    {
        return 'working_test_rule';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        return new FindingResult($this->key(), $this->severity(), 'always fires');
    }
}

class ThrowingTestGapRule implements GapRule
{
    public function key(): string
    {
        return 'throwing_test_rule';
    }

    public function severity(): string
    {
        return 'info';
    }

    public function evaluate(Project $project, array $metrics): ?FindingResult
    {
        throw new \RuntimeException('boom');
    }
}
