<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Vocabulary icons via Iconify API (free, no API key required).
 * @see https://iconify.design/docs/api/
 */
class IconifyImageGenerator
{
    public function enabled(): bool
    {
        if (config('services.image.iconify_enabled') === false) {
            return false;
        }

        return true;
    }

    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            return null;
        }

        $iconId = $this->searchIcon($vocabularyWord);
        if (!$iconId) {
            $iconId = $this->searchIcon($vocabularyWord . ' icon', allowSuffixRetry: false);
        }

        if (!$iconId) {
            Log::info("No Iconify icon found for '{$vocabularyWord}'");
            return null;
        }

        return $this->downloadIcon($iconId, $vocabularyWord);
    }

    protected function searchIcon(string $query, bool $allowSuffixRetry = true): ?string
    {
        try {
            $response = Http::timeout(30)->get('https://api.iconify.design/search', [
                'query' => $query,
                'limit' => 32,
            ]);

            if (!$response->successful()) {
                Log::warning('Iconify search API error', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return null;
            }

            $icons = $response->json('icons') ?? [];
            if (empty($icons)) {
                return null;
            }

            $needle = strtolower($query);

            foreach ($icons as $iconId) {
                if (str_contains(strtolower($iconId), $needle)) {
                    return $iconId;
                }
            }

            foreach ($icons as $iconId) {
                $lower = strtolower($iconId);
                if (str_contains($lower, '-color') || str_contains($lower, '-flat')) {
                    return $iconId;
                }
            }

            return $icons[0];
        } catch (\Throwable $e) {
            Log::error('Iconify search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    protected function downloadIcon(string $iconId, string $vocabularyWord): ?string
    {
        if (!str_contains($iconId, ':')) {
            return null;
        }

        [$prefix, $name] = explode(':', $iconId, 2);
        $height = (int) config('services.image.iconify_size', 512);

        try {
            $url = "https://api.iconify.design/{$prefix}/{$name}.svg";
            $response = Http::timeout(60)->get($url, ['height' => $height]);

            if (!$response->successful() || empty($response->body())) {
                Log::warning('Iconify icon download failed', [
                    'icon' => $iconId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $filename = 'vocab_' . time() . '_' . uniqid() . '.svg';
            $relativePath = 'images/vocabulary/' . $filename;

            Storage::disk('public')->put($relativePath, $response->body());

            Log::info("Successfully saved Iconify icon for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'icon' => $iconId,
                'source' => 'Iconify',
            ]);

            return $relativePath;
        } catch (\Throwable $e) {
            Log::error('Iconify download exception', [
                'message' => $e->getMessage(),
                'icon' => $iconId,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}
