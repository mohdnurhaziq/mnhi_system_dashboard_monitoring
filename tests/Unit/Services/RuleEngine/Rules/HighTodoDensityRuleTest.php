<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\HighTodoDensityRule;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class HighTodoDensityRuleTest extends TestCase
{
    public function test_returns_null_below_threshold(): void
    {
        Config::set('dashboard.todo_density_threshold', 10);
        $rule = new HighTodoDensityRule;

        $result = $rule->evaluate(new Project, ['todos' => ['count' => 9]]);

        $this->assertNull($result);
    }

    public function test_flags_at_threshold(): void
    {
        Config::set('dashboard.todo_density_threshold', 10);
        $rule = new HighTodoDensityRule;

        $result = $rule->evaluate(new Project, ['todos' => ['count' => 10, 'samples' => ['app/Foo.php:12']]]);

        $this->assertNotNull($result);
        $this->assertSame(10, $result->details['count']);
        $this->assertSame(['app/Foo.php:12'], $result->details['samples']);
        $this->assertStringContainsString('10 TODO/FIXME', $result->message);
    }
}
