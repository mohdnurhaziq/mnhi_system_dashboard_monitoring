<?php

namespace App\Services\ProjectScanner\Metrics;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class DiagnosticsGatherer
{
    /**
     * Run real, local, no-AI diagnostics to surface concrete errors that would
     * stop the project from running or building.
     *
     * @return array{
     *   deps: array{needs_composer_install: bool, needs_npm_install: bool},
     *   env: array{has_env: bool, has_app_key: bool},
     *   composer_valid: ?bool,
     *   php_syntax: array{errors: int, samples: list<string>}
     * }
     */
    public function gather(string $path, string $stack): array
    {
        return [
            'deps' => $this->deps($path, $stack),
            'env' => $this->env($path, $stack),
            'composer_valid' => $this->composerValid($path),
            'php_syntax' => $this->phpSyntax($path, $stack),
        ];
    }

    private function deps(string $path, string $stack): array
    {
        $needsComposer = false;
        $needsNpm = false;

        if (is_file($path.'/composer.json') && ! is_dir($path.'/vendor')) {
            $needsComposer = true;
        }
        if (is_file($path.'/package.json') && ! is_dir($path.'/node_modules')) {
            $needsNpm = true;
        }

        return [
            'needs_composer_install' => $needsComposer,
            'needs_npm_install' => $needsNpm,
        ];
    }

    private function env(string $path, string $stack): array
    {
        $isLaravel = str_starts_with($stack, 'laravel');
        if (! $isLaravel) {
            return ['has_env' => true, 'has_app_key' => true]; // n/a → don't flag
        }

        $hasEnv = is_file($path.'/.env');
        $hasAppKey = false;

        if ($hasEnv) {
            try {
                $contents = file_get_contents($path.'/.env', false, null, 0, 20000) ?: '';
                if (preg_match('/^APP_KEY=(.+)$/m', $contents, $m)) {
                    $hasAppKey = trim($m[1]) !== '';
                }
            } catch (\Throwable) {
                $hasAppKey = false;
            }
        }

        return ['has_env' => $hasEnv, 'has_app_key' => $hasAppKey];
    }

    private function composerValid(string $path): ?bool
    {
        if (! is_file($path.'/composer.json')) {
            return null;
        }

        try {
            $process = new Process(
                ['composer', 'validate', '--no-check-all', '--no-check-publish', '--quiet'],
                $path, null, null, 20.0
            );
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Bounded `php -l` over a small sample of first-party PHP files.
     */
    private function phpSyntax(string $path, string $stack, int $maxFiles = 25): array
    {
        $isPhp = str_starts_with($stack, 'laravel') || str_starts_with($stack, 'php');
        if (! $isPhp) {
            return ['errors' => 0, 'samples' => []];
        }

        $dirs = array_filter([$path.'/app', $path.'/routes', $path.'/src'], 'is_dir');
        if (empty($dirs)) {
            return ['errors' => 0, 'samples' => []];
        }

        $errors = 0;
        $samples = [];
        $checked = 0;

        try {
            $finder = (new Finder())
                ->files()
                ->in($dirs)
                ->name('*.php')
                ->exclude(['vendor', 'node_modules'])
                ->ignoreUnreadableDirs()
                ->size('< 1M');

            foreach ($finder as $file) {
                if ($checked >= $maxFiles) {
                    break;
                }
                $checked++;

                $process = new Process(['php', '-l', $file->getRealPath()], null, null, null, 10.0);
                $process->run();

                if (! $process->isSuccessful()) {
                    $errors++;
                    if (count($samples) < 10) {
                        $rel = str_replace($path.'/', '', $file->getRealPath());
                        $samples[] = $rel.': '.trim($process->getErrorOutput() ?: $process->getOutput());
                    }
                }
            }
        } catch (\Throwable) {
            // Leave whatever we gathered.
        }

        return ['errors' => $errors, 'samples' => $samples];
    }
}
