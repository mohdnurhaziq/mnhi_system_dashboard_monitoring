<?php

namespace App\Services\ProjectScanner\Metrics;

use Symfony\Component\Finder\Finder;

class TodoScanner
{
    private const SCAN_DIRS = ['app', 'src', 'resources', 'routes', 'lib', 'components'];

    private const EXTENSIONS = ['php', 'js', 'jsx', 'ts', 'tsx', 'vue', 'blade.php', 'py', 'go'];

    /**
     * @return array{count: int, samples: list<string>, skipped: bool}
     */
    public function gather(string $path): array
    {
        $maxFiles = (int) config('dashboard.max_files_for_todo_scan', 5000);
        $count = 0;
        $samples = [];
        $filesSeen = 0;

        $searchDirs = array_filter(
            array_map(fn ($d) => $path.'/'.$d, self::SCAN_DIRS),
            'is_dir'
        );

        if (empty($searchDirs)) {
            // Fall back to project root, but only top files (avoid vendor/node_modules).
            $searchDirs = [$path];
        }

        try {
            $finder = (new Finder())
                ->files()
                ->in($searchDirs)
                ->exclude(['vendor', 'node_modules', 'storage', '.git', 'dist', 'build'])
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs()
                ->size('< 2M');

            foreach ($finder as $file) {
                if (! $this->hasScannableExtension($file->getFilename())) {
                    continue;
                }

                $filesSeen++;
                if ($filesSeen > $maxFiles) {
                    return ['count' => $count, 'samples' => $samples, 'skipped' => true];
                }

                $lineNo = 0;
                $handle = @fopen($file->getRealPath(), 'r');
                if ($handle === false) {
                    continue;
                }

                while (($line = fgets($handle)) !== false) {
                    $lineNo++;
                    if (preg_match('/\b(TODO|FIXME)\b/', $line)) {
                        $count++;
                        if (count($samples) < 20) {
                            $rel = str_replace($path.'/', '', $file->getRealPath());
                            $samples[] = $rel.':'.$lineNo.': '.trim(mb_substr($line, 0, 120));
                        }
                    }
                }
                fclose($handle);
            }
        } catch (\Throwable) {
            return ['count' => $count, 'samples' => $samples, 'skipped' => true];
        }

        return ['count' => $count, 'samples' => $samples, 'skipped' => false];
    }

    private function hasScannableExtension(string $filename): bool
    {
        foreach (self::EXTENSIONS as $ext) {
            if (str_ends_with($filename, '.'.$ext)) {
                return true;
            }
        }

        return false;
    }
}
