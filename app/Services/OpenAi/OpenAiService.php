<?php

namespace App\Services\OpenAi;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $defaultTimeout;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?: env('OPENAI_API_KEY');
        // Handle both full URL and base URL formats
        $endpoint = config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions');
        if (strpos($endpoint, '/chat/completions') !== false) {
            $this->baseUrl = str_replace('/chat/completions', '', $endpoint);
        } else {
            $this->baseUrl = $endpoint;
        }
        $this->defaultTimeout = config('services.openai.timeout', 60);
    }

    /**
     * Check if OpenAI service is enabled
     */
    public function enabled(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get the API key
     */
    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    /**
     * Make a chat completion request
     * 
     * @param array $messages Array of message objects
     * @param array $options Additional options (model, temperature, etc.)
     * @return array|null Response data or null on failure
     */
    public function chatCompletion(array $messages, array $options = []): ?array
    {
        if (!$this->enabled()) {
            Log::warning('OpenAI API key not configured');
            return null;
        }

        $defaultOptions = [
            'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
            'temperature' => 0.7,
        ];

        // Extract timeout from options (not part of API payload)
        $timeout = $options['timeout'] ?? $this->defaultTimeout;
        unset($options['timeout']);

        // Merge options, but don't override messages if it's in options
        $payload = array_merge($defaultOptions, $options);
        $payload['messages'] = $messages;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($timeout)
              ->post("{$this->baseUrl}/chat/completions", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Log API usage (tokens and cost)
                $this->logApiUsage($payload['model'], $responseData, 'chat_completion');
                
                return $responseData;
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Make an image generation request
     * 
     * @param string $prompt The image prompt
     * @param array $options Additional options (model, size, etc.)
     * @return array|null Response data or null on failure
     */
    public function imageGeneration(string $prompt, array $options = []): ?array
    {
        if (!$this->enabled()) {
            Log::warning('OpenAI API key not configured');
            return null;
        }

        $defaultOptions = [
            'model' => config('services.openai.image_model', 'dall-e-3'),
            'size' => config('services.openai.image_size', '1024x1024'),
            'quality' => 'standard',
            'n' => 1,
        ];

        // Extract timeout from options (not part of API payload)
        $timeout = $options['timeout'] ?? 120;
        unset($options['timeout']);

        $payload = array_merge($defaultOptions, $options, [
            'prompt' => $prompt,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout($timeout)
              ->post("{$this->baseUrl}/images/generations", $payload);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('OpenAI image generation error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('OpenAI image generation exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract content from chat completion response
     * 
     * @param array|null $response Response from chatCompletion
     * @return string|null Content string or null
     */
    public function extractContent(?array $response): ?string
    {
        if (!$response) {
            return null;
        }

        return data_get($response, 'choices.0.message.content');
    }

    /**
     * Extract image URL from image generation response
     * 
     * @param array|null $response Response from imageGeneration
     * @return string|null Image URL or null
     */
    public function extractImageUrl(?array $response): ?string
    {
        if (!$response) {
            return null;
        }

        return data_get($response, 'data.0.url');
    }

    /**
     * Log API usage including tokens and cost
     * 
     * @param string $model The model used
     * @param array $response The API response
     * @param string $operation The operation type (chat_completion, image_generation, etc.)
     */
    protected function logApiUsage(string $model, array $response, string $operation): void
    {
        $usage = $response['usage'] ?? null;
        if (!$usage) {
            return;
        }

        $promptTokens = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;
        $totalTokens = $usage['total_tokens'] ?? 0;

        // Calculate cost based on model pricing (as of 2024)
        // gpt-4o-mini: $0.15/1M input, $0.60/1M output
        // gpt-4o: $2.50/1M input, $10.00/1M output
        // gpt-4-turbo: $10.00/1M input, $30.00/1M output
        $cost = $this->calculateCost($model, $promptTokens, $completionTokens);

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'operation' => $operation,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost_usd' => $cost,
        ];

        // Log to dedicated OpenAI usage log file
        $logFile = storage_path('logs/openai_usage.log');
        $logLine = json_encode($logData) . "\n";
        file_put_contents($logFile, $logLine, FILE_APPEND);

        // Also log to Laravel log for immediate visibility
        Log::info('OpenAI API Usage', $logData);
    }

    /**
     * Calculate cost based on model and token usage
     * 
     * @param string $model The model name
     * @param int $promptTokens Input tokens
     * @param int $completionTokens Output tokens
     * @return float Cost in USD
     */
    protected function calculateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        // Pricing per 1M tokens (as of 2024)
        $pricing = [
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
            'gpt-4' => ['input' => 30.00, 'output' => 60.00],
            'gpt-3.5-turbo' => ['input' => 0.50, 'output' => 1.50],
        ];

        // Normalize model name (handle variations)
        $modelKey = strtolower($model);
        foreach ($pricing as $key => $rates) {
            if (strpos($modelKey, $key) !== false) {
                $inputCost = ($promptTokens / 1_000_000) * $rates['input'];
                $outputCost = ($completionTokens / 1_000_000) * $rates['output'];
                return round($inputCost + $outputCost, 6);
            }
        }

        // Default to gpt-4o-mini pricing if model not found
        $inputCost = ($promptTokens / 1_000_000) * 0.15;
        $outputCost = ($completionTokens / 1_000_000) * 0.60;
        return round($inputCost + $outputCost, 6);
    }
}

