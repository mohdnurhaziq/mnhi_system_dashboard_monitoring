<?php

namespace Tests\Feature\Services;

use App\Models\Project;
use App\Services\ProjectScanner\ProjectScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ProjectScanServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/dashboard-scan-test-'.uniqid();
        File::makeDirectory($this->fixturePath, 0755, true);

        File::put($this->fixturePath.'/composer.json', json_encode([
            'name' => 'acme/fixture',
            'require' => ['php' => '>=8.2'],
        ]));
        File::put($this->fixturePath.'/README.md', '# Fixture project');

        $this->git(['init']);
        $this->git(['add', '.']);
        $this->git(['-c', 'user.email=test@example.com', '-c', 'user.name=Test', 'commit', '-m', 'init']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixturePath);

        parent::tearDown();
    }

    private function git(array $args): void
    {
        (new Process(['git', ...$args], $this->fixturePath))->mustRun();
    }

    private function makeProject(): Project
    {
        return Project::factory()->create(['root_path' => $this->fixturePath]);
    }

    public function test_scan_detects_stack_and_git_metrics(): void
    {
        $project = $this->makeProject();

        app(ProjectScanService::class)->scan($project);
        $project->refresh();

        $this->assertSame('php-composer', $project->stack);
        $this->assertTrue($project->has_commits);
        $this->assertNotNull($project->last_commit_at);
        $this->assertNotNull($project->last_scanned_at);
        $this->assertSame(1, $project->scanSnapshots()->count());
    }

    public function test_scan_raises_findings_for_detected_gaps(): void
    {
        $project = $this->makeProject();

        app(ProjectScanService::class)->scan($project);

        $openRuleKeys = $project->findings()->where('status', 'open')->pluck('rule_key');

        $this->assertContains('missing_tests', $openRuleKeys);
        $this->assertContains('missing_ci', $openRuleKeys);
        $this->assertContains('missing_env_example', $openRuleKeys);
        $this->assertContains('dependencies_not_installed', $openRuleKeys);
        $this->assertContains('missing_lock_file', $openRuleKeys);

        // README exists and the repo has a commit — these must not fire.
        $this->assertNotContains('missing_readme', $openRuleKeys);
        $this->assertNotContains('no_git_repo', $openRuleKeys);
        $this->assertNotContains('zero_commits', $openRuleKeys);
    }

    public function test_rescanning_resolves_findings_that_no_longer_apply(): void
    {
        $project = $this->makeProject();
        $scanner = app(ProjectScanService::class);

        $scanner->scan($project);
        $this->assertSame(
            'open',
            $project->findings()->where('rule_key', 'dependencies_not_installed')->value('status')
        );

        // Fix the gap on disk, then rescan.
        File::makeDirectory($this->fixturePath.'/vendor');
        File::put($this->fixturePath.'/composer.lock', '{}');
        $scanner->scan($project);

        $this->assertSame(
            'resolved',
            $project->findings()->where('rule_key', 'dependencies_not_installed')->value('status')
        );
        $this->assertSame(
            'resolved',
            $project->findings()->where('rule_key', 'missing_lock_file')->value('status')
        );
    }

    public function test_dismissed_finding_is_deleted_once_it_no_longer_applies(): void
    {
        $project = $this->makeProject();
        $scanner = app(ProjectScanService::class);

        $scanner->scan($project);
        $project->findings()->where('rule_key', 'missing_ci')->update(['status' => 'dismissed']);

        File::makeDirectory($this->fixturePath.'/.github/workflows', 0755, true);
        File::put($this->fixturePath.'/.github/workflows/ci.yml', 'name: CI');
        $scanner->scan($project);

        $this->assertSame(0, $project->findings()->where('rule_key', 'missing_ci')->count());
    }

    public function test_dismissed_finding_reopens_if_the_gap_regresses(): void
    {
        $project = $this->makeProject();
        $scanner = app(ProjectScanService::class);

        $scanner->scan($project);
        File::makeDirectory($this->fixturePath.'/vendor');
        File::put($this->fixturePath.'/composer.lock', '{}');
        $scanner->scan($project);
        $this->assertSame(
            'resolved',
            $project->findings()->where('rule_key', 'dependencies_not_installed')->value('status')
        );

        // Regression: vendor/ disappears again (e.g. a clean checkout).
        File::deleteDirectory($this->fixturePath.'/vendor');
        $scanner->scan($project);

        $this->assertSame(
            'open',
            $project->findings()->where('rule_key', 'dependencies_not_installed')->value('status')
        );
    }
}
