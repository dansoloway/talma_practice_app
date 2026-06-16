<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Log;

class ImageGeneratorService
{
    /** @var array<int, object{enabled(): bool, generateVocabularyImage(string): ?string}> */
    protected array $providers;

    public function __construct(
        FlaticonImageGenerator $flaticon,
        FreepikImageGenerator $freepik,
        StockImageGenerator $stock,
        LeonardoImageGenerator $leonardo,
        OpenAiImageGenerator $openAi,
    ) {
        // Flaticon first (project default for vocabulary icons), then fallbacks.
        $this->providers = [
            $flaticon,
            $freepik,
            $stock,
            $leonardo,
            $openAi,
        ];
    }

    public function enabled(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->enabled()) {
                return true;
            }
        }

        return false;
    }

    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        foreach ($this->providers as $provider) {
            if (!$provider->enabled()) {
                continue;
            }

            $class = $provider::class;
            Log::info("Trying image provider {$class} for '{$vocabularyWord}'");

            try {
                $path = $provider->generateVocabularyImage($vocabularyWord);
                if ($path) {
                    Log::info("Image provider {$class} succeeded for '{$vocabularyWord}'", ['path' => $path]);
                    return $path;
                }
            } catch (\Throwable $e) {
                Log::warning("Image provider {$class} failed for '{$vocabularyWord}'", [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return null;
    }
}
