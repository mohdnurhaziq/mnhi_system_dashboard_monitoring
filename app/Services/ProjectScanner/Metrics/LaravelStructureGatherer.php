<?php

namespace App\Services\ProjectScanner\Metrics;

class LaravelStructureGatherer
{
    /**
     * @return array{models_count: int, migrations_count: int, controllers_count: int, route_files: int}
     */
    public function gather(string $path): array
    {
        return [
            'models_count' => $this->countPhpFiles($path.'/app/Models'),
            'migrations_count' => $this->countPhpFiles($path.'/database/migrations'),
            'controllers_count' => $this->countPhpFiles($path.'/app/Http/Controllers', recursive: true),
            'route_files' => $this->countPhpFiles($path.'/routes'),
        ];
    }

    private function countPhpFiles(string $dir, bool $recursive = false): int
    {
        if (! is_dir($dir)) {
            return 0;
        }

        $count = 0;

        try {
            if ($recursive) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $count++;
                    }
                }
            } else {
                foreach (scandir($dir) ?: [] as $entry) {
                    if (str_ends_with($entry, '.php')) {
                        $count++;
                    }
                }
            }
        } catch (\Throwable) {
            return $count;
        }

        return $count;
    }
}
