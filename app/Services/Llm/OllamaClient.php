<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;

class OllamaClient
{
    private string $baseUrl;

    private string $model;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('dashboard.ollama.base_url'), '/');
        $this->model = (string) config('dashboard.ollama.model');
        $this->timeout = (int) config('dashboard.ollama.timeout', 180);
    }

    public function enabled(): bool
    {
        return (bool) config('dashboard.ollama.enabled', false);
    }

    /**
     * Is the Ollama server reachable and does it have the configured model?
     */
    public function isAvailable(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get($this->baseUrl.'/api/tags');
            if (! $response->successful()) {
                return false;
            }

            $models = collect($response->json('models', []))->pluck('name');

            return $models->contains($this->model)
                || $models->contains(fn ($n) => str_starts_with($n, explode(':', $this->model)[0]));
        } catch (\Throwable) {
            return false;
        }
    }

    public function model(): string
    {
        return $this->model;
    }

    /**
     * Run a single-shot generation, requesting JSON output. Returns the raw
     * model response string, or null on any failure (caller degrades).
     */
    public function generateJson(string $prompt, ?string $system = null): ?string
    {
        return $this->generate($prompt, $system, json: true, temperature: 0.2);
    }

    /**
     * Run a single-shot generation returning free-form text. Returns the raw
     * model response string, or null on any failure (caller degrades).
     */
    public function generateText(string $prompt, ?string $system = null, float $temperature = 0.3): ?string
    {
        return $this->generate($prompt, $system, json: false, temperature: $temperature);
    }

    private function generate(string $prompt, ?string $system, bool $json, float $temperature): ?string
    {
        try {
            $payload = [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => ['temperature' => $temperature],
            ];
            if ($json) {
                $payload['format'] = 'json';
            }
            if ($system !== null) {
                $payload['system'] = $system;
            }

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl.'/api/generate', $payload);

            if (! $response->successful()) {
                return null;
            }

            return $response->json('response');
        } catch (\Throwable) {
            return null;
        }
    }
}
