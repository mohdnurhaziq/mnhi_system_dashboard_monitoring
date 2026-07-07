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
     * Fetch the configured model's specs (size, params, context length, quant)
     * from Ollama's local registry, for display in the UI. Returns null if the
     * server is unreachable or the model isn't pulled.
     *
     * @return array{name: string, size_gb: float, parameter_size: string, quantization: string, context_length: int, family: string}|null
     */
    public function specs(): ?array
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl.'/api/tags');
            if (! $response->successful()) {
                return null;
            }

            $entry = collect($response->json('models', []))
                ->first(fn ($m) => ($m['name'] ?? null) === $this->model);

            if ($entry === null) {
                return null;
            }

            $details = $entry['details'] ?? [];

            return [
                'name' => $entry['name'],
                'size_gb' => round(($entry['size'] ?? 0) / 1_000_000_000, 1),
                'parameter_size' => $details['parameter_size'] ?? 'unknown',
                'quantization' => $details['quantization_level'] ?? 'unknown',
                'context_length' => $details['context_length'] ?? 0,
                'family' => $details['family'] ?? 'unknown',
            ];
        } catch (\Throwable) {
            return null;
        }
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
        // Local LLM generations routinely exceed PHP's default 30s
        // max_execution_time (esp. on the `php artisan serve` dev server), which
        // would kill the request before Ollama responds. Extend it to cover the
        // HTTP timeout we're willing to wait for.
        @set_time_limit($this->timeout + 30);

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
