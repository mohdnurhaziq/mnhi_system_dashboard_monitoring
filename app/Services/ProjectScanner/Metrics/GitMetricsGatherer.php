<?php

namespace App\Services\ProjectScanner\Metrics;

use Symfony\Component\Process\Process;

class GitMetricsGatherer
{
    /**
     * @return array{has_git: bool, has_commits: bool, commit_count: int, last_commit_at: ?string, uncommitted_files: int, current_branch: ?string}
     */
    public function gather(string $path): array
    {
        if (! is_dir($path.'/.git')) {
            return $this->empty(false);
        }

        $lastCommit = $this->run($path, ['git', '-C', $path, 'log', '-1', '--format=%cI']);

        // Non-zero exit here means "has .git but no commits yet".
        if ($lastCommit === null || trim($lastCommit) === '') {
            return array_merge($this->empty(true), [
                'has_commits' => false,
            ]);
        }

        $countRaw = $this->run($path, ['git', '-C', $path, 'rev-list', '--count', 'HEAD']);
        $status = $this->run($path, ['git', '-C', $path, 'status', '--porcelain']);
        $branch = $this->run($path, ['git', '-C', $path, 'branch', '--show-current']);

        return [
            'has_git' => true,
            'has_commits' => true,
            'commit_count' => (int) trim((string) $countRaw),
            'last_commit_at' => trim($lastCommit),
            'uncommitted_files' => $this->countLines($status),
            'current_branch' => $branch !== null ? trim($branch) : null,
        ];
    }

    private function run(string $cwd, array $command): ?string
    {
        try {
            $process = new Process($command, $cwd, null, null, 15.0);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            return $process->getOutput();
        } catch (\Throwable) {
            return null;
        }
    }

    private function countLines(?string $output): int
    {
        if ($output === null) {
            return 0;
        }

        $trimmed = trim($output);
        if ($trimmed === '') {
            return 0;
        }

        return count(explode("\n", $trimmed));
    }

    private function empty(bool $hasGit): array
    {
        return [
            'has_git' => $hasGit,
            'has_commits' => false,
            'commit_count' => 0,
            'last_commit_at' => null,
            'uncommitted_files' => 0,
            'current_branch' => null,
        ];
    }
}
