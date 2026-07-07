<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\StaleRepoRule;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StaleRepoRuleTest extends TestCase
{
    public function test_flags_repo_older_than_threshold(): void
    {
        Config::set('dashboard.stale_threshold_days', 90);
        $rule = new StaleRepoRule;

        $result = $rule->evaluate(new Project, [
            'git' => ['has_commits' => true, 'last_commit_at' => now()->subDays(120)->toIso8601String()],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('warning', $result->severity);
        $this->assertSame(120, $result->details['days_since_last_commit']);
    }

    public function test_returns_null_within_threshold(): void
    {
        Config::set('dashboard.stale_threshold_days', 90);
        $rule = new StaleRepoRule;

        $result = $rule->evaluate(new Project, [
            'git' => ['has_commits' => true, 'last_commit_at' => now()->subDays(10)->toIso8601String()],
        ]);

        $this->assertNull($result);
    }

    public function test_returns_null_without_commits(): void
    {
        $rule = new StaleRepoRule;

        $result = $rule->evaluate(new Project, ['git' => ['has_commits' => false, 'last_commit_at' => null]]);

        $this->assertNull($result);
    }
}
