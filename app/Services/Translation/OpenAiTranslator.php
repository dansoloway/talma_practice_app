<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiTranslator
{
    protected const CACHE_PREFIX = 'openai_translation_';
    protected const DEFAULT_RATE_LIMIT_DELAY_SECONDS = 0.5; // Default delay (200 RPM = ~0.3s between requests, using 0.5s for safety)
    protected static $lastRequestTime = 0.0; // Use float for microsecond precision
    protected static $rateLimitRpm = null; // Cached rate limit from API headers
    protected static $calculatedDelay = null; // Calculated delay based on rate limits

    public function enabled(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * Get the delay between requests based on rate limits.
     * Uses config value if set, otherwise calculates from API limits.
     */
    protected function getRateLimitDelay(): float
    {
        // Check if manually configured
        $configDelay = config('services.openai.rate_limit_delay');
        if ($configDelay !== null) {
            return (float) $configDelay;
        }

        // Use cached calculated delay if available
        if (self::$calculatedDelay !== null) {
            return self::$calculatedDelay;
        }

        // Default to safe delay (will be updated after first API call)
        return self::DEFAULT_RATE_LIMIT_DELAY_SECONDS;
    }

    /**
     * Translate an English word into Hebrew and/or Arabic.
     *
     * @param  string  $englishWord
     * @param  bool  $needsHebrew
     * @param  bool  $needsArabic
     * @return array{hebrew:?string,arabic:?string}
     */
    public function translate(string $englishWord, bool $needsHebrew = true, bool $needsArabic = true): array
    {
        $englishWord = trim($englishWord);

        if (!$this->enabled() || empty($englishWord) || (!$needsHebrew && !$needsArabic)) {
            return [
                'hebrew' => null,
                'arabic' => null,
            ];
        }

        $cacheKey = self::CACHE_PREFIX . md5(strtolower($englishWord));
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return [
                'hebrew' => $needsHebrew ? ($cached['hebrew'] ?? null) : null,
                'arabic' => $needsArabic ? ($cached['arabic'] ?? null) : null,
            ];
        }

        // Rate limiting: ensure we don't exceed OpenAI's rate limits
        $this->enforceRateLimit();

        $result = $this->requestTranslation($englishWord);

        if (!empty($result)) {
            Cache::put($cacheKey, $result, now()->addMonths(6));
        }

        return [
            'hebrew' => $needsHebrew ? ($result['hebrew'] ?? null) : null,
            'arabic' => $needsArabic ? ($result['arabic'] ?? null) : null,
        ];
    }

    /**
     * Enforce rate limiting by adding delays between requests.
     */
    protected function enforceRateLimit(): void
    {
        $delay = $this->getRateLimitDelay();
        $now = microtime(true);
        $timeSinceLastRequest = $now - self::$lastRequestTime;

        if ($timeSinceLastRequest < $delay) {
            $sleepTime = $delay - $timeSinceLastRequest;
            if ($sleepTime > 0.1) { // Only log if significant delay
                Log::debug("Rate limiting: sleeping " . round($sleepTime, 2) . " seconds before next OpenAI request");
            }
            usleep((int) ($sleepTime * 1000000)); // Convert to microseconds
        }

        self::$lastRequestTime = microtime(true);
    }

    protected function requestTranslation(string $englishWord, int $retryCount = 0): array
    {
        $maxRetries = 3;
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.key'),
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(config('services.openai.endpoint', 'https://api.openai.com/v1/chat/completions'), [
                'model' => config('services.openai.translation_model', 'gpt-4o-mini'),
                'temperature' => 0,
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'translations',
                        'schema' => [
                            'type' => 'object',
                            'required' => ['hebrew', 'arabic'],
                            'properties' => [
                                'hebrew' => [
                                    'type' => 'string',
                                    'description' => 'Modern Hebrew translation of the English word',
                                ],
                                'arabic' => [
                                    'type' => 'string',
                                    'description' => 'Modern Standard Arabic translation of the English word',
                                ],
                            ],
                        ],
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You translate English vocabulary words. Respond with JSON containing "hebrew" and "arabic" keys. Use modern educational vocabulary appropriate for middle-school students.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "English word: {$englishWord}",
                    ],
                ],
            ]);

            // Handle rate limit errors with retry
            if ($response->status() === 429 && $retryCount < $maxRetries) {
                $body = $response->json();
                $retryAfter = $this->extractRetryAfter($body);
                
                Log::warning("OpenAI rate limit hit for '{$englishWord}'. Retrying after {$retryAfter} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");
                
                sleep($retryAfter);
                
                // Update last request time after sleep
                self::$lastRequestTime = microtime(true);
                
                return $this->requestTranslation($englishWord, $retryCount + 1);
            }

            // Update rate limit info from headers (if available)
            $this->updateRateLimitInfo($response);

            if (!$response->successful()) {
                Log::warning('OpenAI translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'word' => $englishWord,
                ]);
                return [];
            }

            $content = data_get($response->json(), 'choices.0.message.content');
            if (!$content) {
                return [];
            }

            $data = json_decode($content, true);
            if (!is_array($data)) {
                return [];
            }

            return [
                'hebrew' => isset($data['hebrew']) ? trim((string) $data['hebrew']) : null,
                'arabic' => isset($data['arabic']) ? trim((string) $data['arabic']) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI translation exception', [
                'message' => $e->getMessage(),
                'word' => $englishWord,
            ]);
            return [];
        }
    }

    /**
     * Update rate limit information from API response headers.
     */
    protected function updateRateLimitInfo($response): void
    {
        $headers = $response->headers();
        $rpmLimit = null;

        // Check for rate limit headers
        foreach ($headers as $key => $values) {
            if (strtolower($key) === 'x-ratelimit-limit-requests') {
                $rpmLimit = (int) ($values[0] ?? null);
                break;
            }
        }

        if ($rpmLimit && $rpmLimit !== self::$rateLimitRpm) {
            self::$rateLimitRpm = $rpmLimit;
            // Calculate delay: 60 seconds / RPM, with 20% buffer for safety
            // Example: 200 RPM = 60/200 = 0.3s, with buffer = 0.36s
            self::$calculatedDelay = (60 / $rpmLimit) * 1.2;
            
            Log::info("Detected OpenAI rate limit: {$rpmLimit} RPM. Using delay of " . round(self::$calculatedDelay, 2) . " seconds between requests.");
        }
    }

    /**
     * Extract retry-after time from OpenAI rate limit error response.
     */
    protected function extractRetryAfter(array $body): int
    {
        // Try to extract retry time from error message
        $errorMessage = $body['error']['message'] ?? '';
        
        // Look for "try again in Xs" pattern
        if (preg_match('/try again in (\d+)s/i', $errorMessage, $matches)) {
            return (int) $matches[1] + 2; // Add 2 seconds buffer
        }
        
        // Default to calculated delay or fallback
        return (int) ceil($this->getRateLimitDelay() * 10);
    }
}

