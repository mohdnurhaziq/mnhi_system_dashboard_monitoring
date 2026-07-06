<?php

namespace App\Services\ProjectScanner;

class StackDetector
{
    /**
     * @return array{stack: string, stack_version: ?string, detection_failed: bool}
     */
    public function detect(string $path): array
    {
        $composer = $this->readJson($path.'/composer.json');
        if ($composer !== null) {
            return $this->fromComposer($composer);
        }
        // composer.json present but unreadable/malformed
        if (file_exists($path.'/composer.json')) {
            return $this->result('unknown', null, true);
        }

        $package = $this->readJson($path.'/package.json');
        if ($package !== null) {
            return $this->fromPackage($package);
        }
        if (file_exists($path.'/package.json')) {
            return $this->result('unknown', null, true);
        }

        if (file_exists($path.'/go.mod')) {
            return $this->result('go', null, false);
        }

        if (file_exists($path.'/requirements.txt') || file_exists($path.'/pyproject.toml')) {
            return $this->result('python', null, false);
        }

        if (file_exists($path.'/docker-compose.yml') || file_exists($path.'/Dockerfile')) {
            return $this->result('docker', null, false);
        }

        return $this->result('unknown', null, false);
    }

    private function fromComposer(array $composer): array
    {
        $require = array_merge(
            $composer['require'] ?? [],
            $composer['require-dev'] ?? []
        );

        if (isset($require['laravel/framework'])) {
            $version = $require['laravel/framework'];

            if (isset($require['livewire/livewire'])) {
                return $this->result('laravel-livewire', "laravel/framework {$version}", false);
            }
            if (isset($require['inertiajs/inertia-laravel'])) {
                return $this->result('laravel-inertia-react', "laravel/framework {$version}", false);
            }

            return $this->result('laravel', "laravel/framework {$version}", false);
        }

        return $this->result('php-composer', null, false);
    }

    private function fromPackage(array $package): array
    {
        $deps = array_merge(
            $package['dependencies'] ?? [],
            $package['devDependencies'] ?? []
        );

        if (isset($deps['react-native']) || isset($deps['expo'])) {
            return $this->result('node-react-native', null, false);
        }
        if (isset($deps['react'])) {
            return $this->result('node-react', $deps['react'] ?? null, false);
        }
        if (isset($deps['vue'])) {
            return $this->result('node-vue', $deps['vue'] ?? null, false);
        }

        return $this->result('node-generic', null, false);
    }

    private function readJson(string $file): ?array
    {
        if (! is_file($file)) {
            return null;
        }

        try {
            $contents = file_get_contents($file);
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function result(string $stack, ?string $version, bool $failed): array
    {
        return [
            'stack' => $stack,
            'stack_version' => $version,
            'detection_failed' => $failed,
        ];
    }
}
