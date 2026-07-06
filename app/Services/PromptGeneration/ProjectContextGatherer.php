<?php

namespace App\Services\PromptGeneration;

use Symfony\Component\Finder\Finder;

class ProjectContextGatherer
{
    private const IGNORED_DIRS = [
        'vendor', 'node_modules', '.git', 'storage', 'dist', 'build',
        'public/build', 'bootstrap/cache', '.idea', '.vscode', 'coverage',
    ];

    /**
     * Build a compact, indented directory tree (bounded), skipping heavy/noise
     * directories. Depth- and entry-limited so prompts stay small.
     */
    public function fileTree(string $path, int $maxDepth = 3, int $maxEntries = 120): string
    {
        if (! is_dir($path)) {
            return '';
        }

        try {
            $finder = (new Finder())
                ->in($path)
                ->exclude(self::IGNORED_DIRS)
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs()
                ->depth('< '.$maxDepth)
                ->sortByName();

            $lines = [];
            $count = 0;

            foreach ($finder as $item) {
                if ($count >= $maxEntries) {
                    $lines[] = '  … (truncated)';
                    break;
                }

                $relative = str_replace($path.'/', '', $item->getRealPath() ?: $item->getPathname());
                $depth = substr_count($relative, '/');
                $indent = str_repeat('  ', $depth);
                $name = basename($relative).($item->isDir() ? '/' : '');

                $lines[] = $indent.$name;
                $count++;
            }

            return implode("\n", $lines);
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Return short excerpts of the most informative files for the project's
     * stack, so a prompt reader can orient without re-exploring.
     *
     * @return list<array{path: string, content: string}>
     */
    public function keyFileExcerpts(string $path, ?string $stack): array
    {
        $candidates = $this->candidatesForStack($stack);
        $excerpts = [];

        foreach ($candidates as $relative) {
            $full = $path.'/'.$relative;
            if (! is_file($full)) {
                continue;
            }

            $content = $this->readExcerpt($full);
            if ($content !== null && $content !== '') {
                $excerpts[] = ['path' => $relative, 'content' => $content];
            }

            if (count($excerpts) >= 6) {
                break;
            }
        }

        return $excerpts;
    }

    /** @return list<string> */
    private function candidatesForStack(?string $stack): array
    {
        $stack ??= 'unknown';

        if (str_starts_with($stack, 'laravel')) {
            return [
                'composer.json',
                'routes/web.php',
                'routes/api.php',
                'artisan',
            ];
        }

        if (str_starts_with($stack, 'node')) {
            return [
                'package.json',
                'src/App.jsx',
                'src/App.tsx',
                'src/main.jsx',
                'src/index.js',
                'app.json',
            ];
        }

        if ($stack === 'go') {
            return ['go.mod', 'main.go', 'cmd/main.go'];
        }

        if ($stack === 'python') {
            return ['pyproject.toml', 'requirements.txt', 'main.py', 'app.py'];
        }

        if ($stack === 'docker') {
            return ['docker-compose.yml', 'Dockerfile'];
        }

        // Generic fallback — whatever manifest-like files exist.
        return ['composer.json', 'package.json', 'Makefile', 'README.md'];
    }

    private function readExcerpt(string $file, int $maxBytes = 2000, int $maxLines = 60): ?string
    {
        try {
            $raw = file_get_contents($file, false, null, 0, $maxBytes);
            if ($raw === false) {
                return null;
            }

            $lines = explode("\n", $raw);
            if (count($lines) > $maxLines) {
                $lines = array_slice($lines, 0, $maxLines);
                $lines[] = '… (truncated)';
            }

            return trim(implode("\n", $lines));
        } catch (\Throwable) {
            return null;
        }
    }
}
