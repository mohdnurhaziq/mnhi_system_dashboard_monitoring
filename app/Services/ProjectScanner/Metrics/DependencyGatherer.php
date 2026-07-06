<?php

namespace App\Services\ProjectScanner\Metrics;

class DependencyGatherer
{
    /**
     * Inspect a project's dependency manifests and lock files to report
     * dependency health: which package managers are in use, how many
     * dependencies are declared, whether a lock file is committed, and whether
     * dependencies are installed.
     *
     * @return array{
     *   has_manifest: bool,
     *   managers: list<string>,
     *   composer: ?array{declared:int, dev:int, has_lock:bool, installed:bool},
     *   npm: ?array{declared:int, dev:int, has_lock:bool, lockfile:?string, installed:bool},
     *   missing_locks: list<string>
     * }
     */
    public function gather(string $path): array
    {
        $composer = $this->composer($path);
        $npm = $this->npm($path);

        $managers = [];
        $missingLocks = [];
        foreach (['composer' => $composer, 'npm' => $npm] as $name => $info) {
            if ($info === null) {
                continue;
            }
            $managers[] = $name;
            // Declares dependencies but has no lock file → installs aren't reproducible.
            if (($info['declared'] + $info['dev']) > 0 && ! $info['has_lock']) {
                $missingLocks[] = $name;
            }
        }

        return [
            'has_manifest' => $managers !== [],
            'managers' => $managers,
            'composer' => $composer,
            'npm' => $npm,
            'missing_locks' => $missingLocks,
        ];
    }

    private function composer(string $path): ?array
    {
        if (! is_file($path.'/composer.json')) {
            return null;
        }

        $data = $this->readJson($path.'/composer.json');

        return [
            'declared' => is_array($data['require'] ?? null) ? count($data['require']) : 0,
            'dev' => is_array($data['require-dev'] ?? null) ? count($data['require-dev']) : 0,
            'has_lock' => is_file($path.'/composer.lock'),
            'installed' => is_dir($path.'/vendor'),
        ];
    }

    private function npm(string $path): ?array
    {
        if (! is_file($path.'/package.json')) {
            return null;
        }

        $data = $this->readJson($path.'/package.json');
        $lockfile = collect(['package-lock.json', 'yarn.lock', 'pnpm-lock.yaml', 'npm-shrinkwrap.json'])
            ->first(fn ($f) => is_file($path.'/'.$f));

        return [
            'declared' => is_array($data['dependencies'] ?? null) ? count($data['dependencies']) : 0,
            'dev' => is_array($data['devDependencies'] ?? null) ? count($data['devDependencies']) : 0,
            'has_lock' => $lockfile !== null,
            'lockfile' => $lockfile,
            'installed' => is_dir($path.'/node_modules'),
        ];
    }

    private function readJson(string $file): array
    {
        try {
            $contents = file_get_contents($file, false, null, 0, 200000);
            if ($contents === false) {
                return [];
            }
            $data = json_decode($contents, true);

            return is_array($data) ? $data : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
