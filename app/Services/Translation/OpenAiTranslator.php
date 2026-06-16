<?php

namespace App\Services\Translation;

use App\Services\OpenAi\OpenAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiTranslator
{
    protected OpenAiService $openAiService;
    protected const CACHE_PREFIX = 'openai_translation_';
    protected const DEFAULT_RATE_LIMIT_DELAY_SECONDS = 0.5;
    protected static $lastRequestTime = 0.0;
    protected static $rateLimitRpm = null;
    protected static $calculatedDelay = null;

    /** @var array<string, array{label: string, schema: string, instruction: string}> */
    protected const ARABIC_VARIANTS = [
        'saudi' => [
            'label' => 'Saudi Arabic',
            'schema' => 'Saudi Arabic (Gulf dialect) translation of the English word, as commonly used in Saudi Arabia',
            'instruction' => 'Use Saudi Arabic (Gulf dialect) as spoken in Saudi Arabia—natural colloquial forms learners would hear locally, not formal Modern Standard Arabic. Keep single-word vocabulary concise.',
        ],
        'msa' => [
            'label' => 'Modern Standard Arabic',
            'schema' => 'Modern Standard Arabic translation of the English word',
            'instruction' => 'Use Modern Standard Arabic (MSA)—neutral formal Arabic suitable across the Arab world.',
        ],
    ];

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    public function enabled(): bool
    {
        return $this->openAiService->enabled();
    }

    public function arabicVariant(): string
    {
        $variant = strtolower((string) config('services.openai.arabic_variant', 'saudi'));

        return array_key_exists($variant, self::ARABIC_VARIANTS) ? $variant : 'saudi';
    }

    public function arabicVariantLabel(): string
    {
        return self::ARABIC_VARIANTS[$this->arabicVariant()]['label'];
    }

    /**
     * Translate an English word into Hebrew and/or Arabic.
     *
     * @return array{hebrew:?string,arabic:?string}
     */
    public function translate(
        string $englishWord,
        bool $needsHebrew = true,
        bool $needsArabic = true,
        bool $forceRefresh = false
    ): array {
        $englishWord = trim($englishWord);

        if (!$this->enabled() || empty($englishWord) || (!$needsHebrew && !$needsArabic)) {
            return [
                'hebrew' => null,
                'arabic' => null,
            ];
        }

        $cacheKey = $this->cacheKey($englishWord);
        $cached = $forceRefresh ? null : Cache::get($cacheKey);

        if (is_array($cached)) {
            return [
                'hebrew' => $needsHebrew ? ($cached['hebrew'] ?? null) : null,
                'arabic' => $needsArabic ? ($cached['arabic'] ?? null) : null,
            ];
        }

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

    public function forgetCachedTranslation(string $englishWord): void
    {
        Cache::forget($this->cacheKey($englishWord));
    }

    protected function cacheKey(string $englishWord): string
    {
        return self::CACHE_PREFIX . md5(strtolower($englishWord) . '|' . $this->arabicVariant());
    }

    protected function getRateLimitDelay(): float
    {
        $configDelay = config('services.openai.rate_limit_delay');
        if ($configDelay !== null) {
            return (float) $configDelay;
        }

        if (self::$calculatedDelay !== null) {
            return self::$calculatedDelay;
        }

        return self::DEFAULT_RATE_LIMIT_DELAY_SECONDS;
    }

    protected function enforceRateLimit(): void
    {
        $delay = $this->getRateLimitDelay();
        $now = microtime(true);
        $timeSinceLastRequest = $now - self::$lastRequestTime;

        if ($timeSinceLastRequest < $delay) {
            $sleepTime = $delay - $timeSinceLastRequest;
            if ($sleepTime > 0.1) {
                Log::debug('Rate limiting: sleeping ' . round($sleepTime, 2) . ' seconds before next OpenAI request');
            }
            usleep((int) ($sleepTime * 1000000));
        }

        self::$lastRequestTime = microtime(true);
    }

    protected function arabicPromptConfig(): array
    {
        return self::ARABIC_VARIANTS[$this->arabicVariant()];
    }

    protected function systemPrompt(): string
    {
        $arabic = $this->arabicPromptConfig();

        return 'You translate English vocabulary words for middle-school ESL students. '
            . 'Respond with JSON containing "hebrew" and "arabic" keys. '
            . 'Hebrew: use modern Israeli Hebrew. '
            . 'Arabic: ' . $arabic['instruction'];
    }

    protected function requestTranslation(string $englishWord, int $retryCount = 0): array
    {
        $maxRetries = 3;
        $arabicConfig = $this->arabicPromptConfig();

        try {
            $apiKey = $this->openAiService->getApiKey();
            if (!$apiKey) {
                return [];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
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
                                    'description' => $arabicConfig['schema'],
                                ],
                            ],
                        ],
                    ],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->systemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => "English word: {$englishWord}",
                    ],
                ],
            ]);

            if ($response->status() === 429 && $retryCount < $maxRetries) {
                $body = $response->json();
                $retryAfter = $this->extractRetryAfter($body);

                Log::warning("OpenAI rate limit hit for '{$englishWord}'. Retrying after {$retryAfter} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");

                sleep($retryAfter);
                self::$lastRequestTime = microtime(true);

                return $this->requestTranslation($englishWord, $retryCount + 1);
            }

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

    protected function updateRateLimitInfo($response): void
    {
        $headers = $response->headers();
        $rpmLimit = null;

        foreach ($headers as $key => $values) {
            if (strtolower($key) === 'x-ratelimit-limit-requests') {
                $rpmLimit = (int) ($values[0] ?? null);
                break;
            }
        }

        if ($rpmLimit && $rpmLimit !== self::$rateLimitRpm) {
            self::$rateLimitRpm = $rpmLimit;
            self::$calculatedDelay = (60 / $rpmLimit) * 1.2;

            Log::info("Detected OpenAI rate limit: {$rpmLimit} RPM. Using delay of " . round(self::$calculatedDelay, 2) . ' seconds between requests.');
        }
    }

    protected function extractRetryAfter(array $body): int
    {
        $errorMessage = $body['error']['message'] ?? '';

        if (preg_match('/try again in (\d+)s/i', $errorMessage, $matches)) {
            return (int) $matches[1] + 2;
        }

        return (int) ceil($this->getRateLimitDelay() * 10);
    }
}
