<?php

use App\Services\RuleEngine\Rules\AmbiguousProjectRootRule;
use App\Services\RuleEngine\Rules\HighTodoDensityRule;
use App\Services\RuleEngine\Rules\MissingCiRule;
use App\Services\RuleEngine\Rules\MissingEnvExampleRule;
use App\Services\RuleEngine\Rules\MissingReadmeRule;
use App\Services\RuleEngine\Rules\MissingTestsRule;
use App\Services\RuleEngine\Rules\NoGitRepoRule;
use App\Services\RuleEngine\Rules\StaleRepoRule;
use App\Services\RuleEngine\Rules\UncommittedChangesRule;
use App\Services\RuleEngine\Rules\ZeroCommitsRule;

return [

    /*
    |--------------------------------------------------------------------------
    | Scan Roots
    |--------------------------------------------------------------------------
    | Top-level directories to discover projects in. Each entry is scanned at
    | depth 0 (immediate children only) — never a recursive crawl. Set
    | 'shallow_only' => true for roots (like your home dir) that contain lots
    | of non-project folders; dotdirs are always skipped regardless.
    */
    'scan_roots' => [
        ['path' => '/Users/ozt/Laravel_Project', 'shallow_only' => false],
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Path Patterns
    |--------------------------------------------------------------------------
    | Directory basenames (or fnmatch globs) that are skipped during discovery
    | and never treated as projects.
    */
    'excluded_path_patterns' => [
        'node_modules',
        'vendor',
        '.git',
        'Docker',
        '.DS_Store',
        '*.lock',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    */
    'stale_threshold_days' => 90,
    'todo_density_threshold' => 10,
    'max_files_for_todo_scan' => 5000,
    'snapshots_to_keep' => 20,

    /*
    |--------------------------------------------------------------------------
    | Project Markers
    |--------------------------------------------------------------------------
    | Files whose presence identifies a directory as a project root. Used by
    | ProjectRootResolver to resolve nested "wrapper" directories.
    */
    'project_markers' => [
        'composer.json',
        'package.json',
        'go.mod',
        'requirements.txt',
        'pyproject.toml',
        '.git',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Engine
    |--------------------------------------------------------------------------
    | rule_key => rule class. Toggle individual rules via 'rules_enabled'.
    */
    'rules' => [
        'no_git_repo' => NoGitRepoRule::class,
        'zero_commits' => ZeroCommitsRule::class,
        'stale_repo' => StaleRepoRule::class,
        'uncommitted_changes' => UncommittedChangesRule::class,
        'missing_readme' => MissingReadmeRule::class,
        'missing_env_example' => MissingEnvExampleRule::class,
        'missing_tests' => MissingTestsRule::class,
        'missing_ci' => MissingCiRule::class,
        'high_todo_density' => HighTodoDensityRule::class,
        'ambiguous_project_root' => AmbiguousProjectRootRule::class,
    ],

    'rules_enabled' => [
        'no_git_repo' => true,
        'zero_commits' => true,
        'stale_repo' => true,
        'uncommitted_changes' => true,
        'missing_readme' => true,
        'missing_env_example' => true,
        'missing_tests' => true,
        'missing_ci' => true,
        'high_todo_density' => true,
        'ambiguous_project_root' => true,
    ],
];
