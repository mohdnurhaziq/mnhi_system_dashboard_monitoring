<?php

namespace App\Services\ProjectDiscovery;

use Symfony\Component\Finder\Finder;

class ProjectRootResolver
{
    /**
     * Resolve the actual project root for a discovered directory, handling
     * nested "wrapper" directories (e.g. Foo/Foo where the inner Foo is the
     * real project).
     *
     * @return array{path: string, ambiguous: bool}
     */
    public function resolve(string $path): array
    {
        if ($this->hasMarker($path)) {
            return ['path' => $path, 'ambiguous' => false];
        }

        $childrenWithMarker = [];

        try {
            $finder = (new Finder())
                ->directories()
                ->in($path)
                ->depth('== 0')
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs();

            foreach ($finder as $dir) {
                if ($this->hasMarker($dir->getRealPath())) {
                    $childrenWithMarker[] = $dir->getRealPath();
                }
            }
        } catch (\Throwable) {
            // Unreadable / vanished directory — treat as no children.
        }

        if (count($childrenWithMarker) === 1) {
            return ['path' => $childrenWithMarker[0], 'ambiguous' => false];
        }

        // Zero or multiple candidate children: keep the outer path but flag it.
        return ['path' => $path, 'ambiguous' => count($childrenWithMarker) > 1];
    }

    private function hasMarker(string $path): bool
    {
        foreach (config('dashboard.project_markers', []) as $marker) {
            if (file_exists($path.DIRECTORY_SEPARATOR.$marker)) {
                return true;
            }
        }

        return false;
    }
}
