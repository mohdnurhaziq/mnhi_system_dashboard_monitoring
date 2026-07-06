<?php

namespace App\Services\ProjectScanner\Metrics;

class FileMarkerGatherer
{
    /**
     * @return array{has_readme: bool, readme_excerpt: ?string, has_env_example: bool, has_ci: bool, has_tests: bool, has_gitignore: bool}
     */
    public function gather(string $path): array
    {
        $readme = $this->findFirst($path, ['README.md', 'README.MD', 'readme.md', 'README', 'README.txt']);

        return [
            'has_readme' => $readme !== null,
            'readme_excerpt' => $readme !== null ? $this->excerpt($readme) : null,
            'has_env_example' => file_exists($path.'/.env.example') || file_exists($path.'/.env.sample'),
            'has_ci' => $this->hasCi($path),
            'has_tests' => $this->hasTests($path),
            'has_gitignore' => file_exists($path.'/.gitignore'),
        ];
    }

    private function findFirst(string $path, array $names): ?string
    {
        foreach ($names as $name) {
            $full = $path.'/'.$name;
            if (is_file($full)) {
                return $full;
            }
        }

        return null;
    }

    private function excerpt(string $file, int $length = 500): ?string
    {
        try {
            $contents = file_get_contents($file, false, null, 0, $length + 200);
            if ($contents === false) {
                return null;
            }

            return mb_substr(trim($contents), 0, $length);
        } catch (\Throwable) {
            return null;
        }
    }

    private function hasCi(string $path): bool
    {
        if (is_dir($path.'/.github/workflows')) {
            $entries = @scandir($path.'/.github/workflows') ?: [];
            foreach ($entries as $e) {
                if (str_ends_with($e, '.yml') || str_ends_with($e, '.yaml')) {
                    return true;
                }
            }
        }

        return file_exists($path.'/.gitlab-ci.yml')
            || file_exists($path.'/Jenkinsfile')
            || file_exists($path.'/.circleci/config.yml')
            || file_exists($path.'/bitbucket-pipelines.yml');
    }

    private function hasTests(string $path): bool
    {
        return is_dir($path.'/tests')
            || is_dir($path.'/test')
            || is_dir($path.'/spec')
            || is_dir($path.'/__tests__');
    }
}
