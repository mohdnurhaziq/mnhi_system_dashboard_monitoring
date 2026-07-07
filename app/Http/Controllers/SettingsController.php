<?php

namespace App\Http\Controllers;

use App\Services\Llm\OllamaClient;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(private OllamaClient $ollama) {}

    public function edit(): Response
    {
        return Inertia::render('Settings/Edit', [
            'config' => [
                'scan_roots' => config('dashboard.scan_roots'),
                'excluded_path_patterns' => config('dashboard.excluded_path_patterns'),
                'stale_threshold_days' => config('dashboard.stale_threshold_days'),
                'todo_density_threshold' => config('dashboard.todo_density_threshold'),
                'max_files_for_todo_scan' => config('dashboard.max_files_for_todo_scan'),
                'snapshots_to_keep' => config('dashboard.snapshots_to_keep'),
                'rules_enabled' => config('dashboard.rules_enabled'),
            ],
            'configPath' => 'config/dashboard.php',
            'aiModel' => [
                'enabled' => $this->ollama->enabled(),
                'available' => $this->ollama->isAvailable(),
                'configured_name' => $this->ollama->model(),
                'base_url' => config('dashboard.ollama.base_url'),
                'timeout' => config('dashboard.ollama.timeout'),
                'specs' => $this->ollama->specs(),
            ],
        ]);
    }
}
