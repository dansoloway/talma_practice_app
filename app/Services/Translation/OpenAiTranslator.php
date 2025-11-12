<?php

namespace App\Services\Translation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiTranslator
{
    protected const CACHE_PREFIX = 'openai_translation_';

    public function enabled(): bool
    {
        return filled(config('services.openai.key'));
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

        $result = $this->requestTranslation($englishWord);

        if (!empty($result)) {
            Cache::put($cacheKey, $result, now()->addMonths(6));
        }

        return [
            'hebrew' => $needsHebrew ? ($result['hebrew'] ?? null) : null,
            'arabic' => $needsArabic ? ($result['arabic'] ?? null) : null,
        ];
    }

    protected function requestTranslation(string $englishWord): array
    {
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

            if (!$response->successful()) {
                Log::warning('OpenAI translation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
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
}

