<?php

namespace Tests\Unit\Services\RuleEngine\Rules;

use App\Models\Project;
use App\Services\RuleEngine\Rules\PhpSyntaxErrorRule;
use Tests\TestCase;

class PhpSyntaxErrorRuleTest extends TestCase
{
    public function test_flags_syntax_errors(): void
    {
        $rule = new PhpSyntaxErrorRule;

        $result = $rule->evaluate(new Project, [
            'diagnostics' => ['php_syntax' => ['errors' => 2, 'samples' => ['app/Foo.php: syntax error']]],
        ]);

        $this->assertNotNull($result);
        $this->assertSame('critical', $result->severity);
        $this->assertSame('error', $result->category);
        $this->assertStringContainsString('2 PHP file(s)', $result->message);
    }

    public function test_returns_null_when_no_errors(): void
    {
        $rule = new PhpSyntaxErrorRule;

        $result = $rule->evaluate(new Project, [
            'diagnostics' => ['php_syntax' => ['errors' => 0, 'samples' => []]],
        ]);

        $this->assertNull($result);
    }
}
