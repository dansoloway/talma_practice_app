<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Log;

class ImageGeneratorService
{
    /** @var array<int, object{enabled(): bool, generateVocabularyImage(string): ?string}> */
    protected array $providers;

    /** @var array<string, class-string> */
    protected const PROVIDER_MAP = [
        'iconify' => IconifyImageGenerator::class,
        'flaticon' => FlaticonImageGenerator::class,
        'freepik' => FreepikImageGenerator::class,
        'stock' => StockImageGenerator::class,
        'leonardo' => LeonardoImageGenerator::class,
        'openai' => OpenAiImageGenerator::class,
    ];

    public function __construct(
        IconifyImageGenerator $iconify,
        FlaticonImageGenerator $flaticon,
        FreepikImageGenerator $freepik,
        StockImageGenerator $stock,
        LeonardoImageGenerator $leonardo,
        OpenAiImageGenerator $openAi,
    ) {
        $instances = compact('iconify', 'flaticon', 'freepik', 'stock', 'leonardo', 'openAi');
        $order = $this->configuredProviderOrder();

        $this->providers = [];
        foreach ($order as $slug) {
            if (isset($instances[$slug])) {
                $this->providers[] = $instances[$slug];
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function configuredProviderOrder(): array
    {
        $raw = (string) config('services.image.providers', 'iconify,stock,leonardo,openai');
        $slugs = array_filter(array_map('trim', explode(',', strtolower($raw))));

        return !empty($slugs) ? array_values($slugs) : ['iconify', 'stock', 'leonardo', 'openai'];
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
