<?php

namespace App\Services\ImageGeneration;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Icon search via Magnific API (formerly Freepik API).
 * Uses FREEPIK_API_KEY from .env — keys are issued at magnific.com/developers/dashboard.
 */
class FreepikImageGenerator
{
    protected $apiKey;

    /** @var list<string> */
    protected array $apiBases = [
        'https://api.magnific.com',
        'https://api.freepik.com',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.freepik.api_key');
    }

    public function enabled(): bool
    {
        return filled($this->apiKey);
    }

    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Magnific/Freepik API key not configured. Skipping image search.');
            return null;
        }

        $imageUrl = $this->searchIcons($vocabularyWord);

        if ($imageUrl) {
            return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
        }

        Log::info("No Magnific/Freepik icon found for '{$vocabularyWord}'");
        return null;
    }

    protected function searchIcons(string $query): ?string
    {
        try {
            $response = $this->apiGet('/v1/icons', [
                'term' => $query,
                'per_page' => 20,
            ]);

            if (!$response || !$response->successful()) {
                return null;
            }

            $icons = $response->json('data') ?? [];

            if (empty($icons)) {
                Log::info("No Magnific/Freepik icons found for query: {$query}");
                return null;
            }

            $selectedIcon = null;
            $needle = strtolower($query);

            foreach ($icons as $icon) {
                if (strtolower($icon['name'] ?? '') === $needle) {
                    $selectedIcon = $icon;
                    break;
                }
            }

            if (!$selectedIcon) {
                foreach ($icons as $icon) {
                    foreach ($icon['tags'] ?? [] as $tag) {
                        $tagName = strtolower($tag['name'] ?? $tag['slug'] ?? '');
                        if ($tagName === $needle) {
                            $selectedIcon = $icon;
                            break 2;
                        }
                    }
                }
            }

            if (!$selectedIcon) {
                $selectedIcon = $icons[0];
            }

            if (isset($selectedIcon['id'])) {
                return $this->getFullIconImageUrl($selectedIcon['id']);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('Magnific/Freepik search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    protected function getFullIconImageUrl(int $iconId): ?string
    {
        try {
            $response = $this->apiGet("/v1/icons/{$iconId}");

            if (!$response || !$response->successful()) {
                Log::warning('Magnific/Freepik icon detail fetch failed', [
                    'icon_id' => $iconId,
                    'status' => $response?->status(),
                ]);
                return null;
            }

            return $this->getImageUrl($response->json('data'));
        } catch (\Throwable $e) {
            Log::error('Magnific/Freepik icon detail fetch exception', [
                'icon_id' => $iconId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function getImageUrl(?array $icon): ?string
    {
        if (!$icon) {
            return null;
        }

        $thumbnails = $icon['thumbnails'] ?? [];
        if (empty($thumbnails)) {
            return null;
        }

        $largest = null;
        $maxWidth = 0;
        foreach ($thumbnails as $thumbnail) {
            $width = $thumbnail['width'] ?? 0;
            if ($width > $maxWidth) {
                $maxWidth = $width;
                $largest = $thumbnail['url'] ?? null;
            }
        }

        if ($largest) {
            Log::info('Magnific/Freepik icon selected', [
                'icon_id' => $icon['id'] ?? 'unknown',
                'icon_name' => $icon['name'] ?? 'unknown',
                'selected_size' => $maxWidth . 'x' . $maxWidth,
            ]);
        }

        return $largest;
    }

    protected function apiGet(string $path, array $query = []): ?Response
    {
        foreach ($this->apiBases as $base) {
            $response = Http::withHeaders([
                'x-magnific-api-key' => $this->apiKey,
                'x-freepik-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get($base . $path, $query);

            if ($response->successful()) {
                return $response;
            }

            Log::warning('Magnific/Freepik API error', [
                'host' => parse_url($base, PHP_URL_HOST),
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
                'query' => $query,
            ]);

            if ($response->status() === 401) {
                Log::error('Magnific/Freepik API key rejected. Generate a new key at https://www.magnific.com/developers/dashboard/api-key');
                return null;
            }
        }

        return null;
    }

    protected function downloadAndSaveImage(string $imageUrl, string $vocabularyWord): ?string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download Magnific/Freepik image', [
                    'url' => $imageUrl,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            $extension = 'png';
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            if (!empty($pathInfo['extension'])) {
                $extension = strtolower($pathInfo['extension']);
            } elseif (str_starts_with($imageContent, '<svg') || str_starts_with($imageContent, '<?xml')) {
                $extension = 'svg';
            } elseif (str_starts_with($imageContent, "\xFF\xD8")) {
                $extension = 'jpg';
            }

            $filename = 'vocab_' . time() . '_' . uniqid() . '.' . $extension;
            $relativePath = 'images/vocabulary/' . $filename;

            Storage::disk('public')->put($relativePath, $imageContent);

            Log::info("Successfully downloaded and saved Magnific/Freepik image for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'size' => strlen($imageContent),
            ]);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::error('Failed to download/save Magnific/Freepik image', [
                'message' => $e->getMessage(),
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}
