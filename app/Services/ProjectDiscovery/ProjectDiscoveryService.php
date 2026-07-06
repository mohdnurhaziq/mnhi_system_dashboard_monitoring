<?php

namespace App\Services\ProjectDiscovery;

use App\Models\Project;
use Symfony\Component\Finder\Finder;

class ProjectDiscoveryService
{
    public function __construct(private ProjectRootResolver $resolver) {}

    /**
     * Discover projects under the configured scan roots and upsert Project rows.
     *
     * @return array{created: int, existing: int}
     */
    public function discover(): array
    {
        $created = 0;
        $existing = 0;
        $seenRootPaths = [];

        foreach (config('dashboard.scan_roots', []) as $root) {
            $rootPath = $root['path'] ?? null;
            if (! $rootPath || ! is_dir($rootPath)) {
                continue;
            }

            foreach ($this->topLevelDirs($rootPath) as $candidate) {
                if ($this->isExcluded(basename($candidate))) {
                    continue;
                }

                $resolution = $this->resolver->resolve($candidate);
                $seenRootPaths[] = $candidate;

                $project = Project::firstOrNew(['root_path' => $candidate]);

                if (! $project->exists) {
                    $project->name = basename($resolution['path']);
                    $project->status = 'included';
                    $project->resolved_path = $resolution['path'];
                    $project->save();
                    $created++;
                } else {
                    $existing++;
                }
            }
        }

        return ['created' => $created, 'existing' => $existing];
    }

    /**
     * @return list<string> absolute real paths of immediate child directories
     */
    private function topLevelDirs(string $rootPath): array
    {
        $dirs = [];

        try {
            $finder = (new Finder())
                ->directories()
                ->in($rootPath)
                ->depth('== 0')
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs();

            foreach ($finder as $dir) {
                $dirs[] = $dir->getRealPath();
            }
        } catch (\Throwable) {
            // Root vanished or unreadable — skip.
        }

        return $dirs;
    }

    private function isExcluded(string $basename): bool
    {
        foreach (config('dashboard.excluded_path_patterns', []) as $pattern) {
            if (fnmatch($pattern, $basename)) {
                return true;
            }
        }

        return false;
    }
}
