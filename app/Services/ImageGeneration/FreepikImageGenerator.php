<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FreepikImageGenerator
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.freepik.api_key');
    }

    public function enabled(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Generate/find an image for a vocabulary word using Freepik API.
     *
     * @param string $vocabularyWord The English vocabulary word
     * @return string|null The relative path to the saved image, or null on failure
     */
    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Freepik API key not configured. Skipping image search.');
            return null;
        }

        // Search for images/icons matching the vocabulary word
        $imageUrl = $this->searchFreepik($vocabularyWord);
        
        if ($imageUrl) {
            return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
        }

        Log::info("No Freepik image found for '{$vocabularyWord}' - will need manual upload");
        return null;
    }

    /**
     * Search Freepik for an image/icon matching the vocabulary word.
     */
    protected function searchFreepik(string $query): ?string
    {
        try {
            // Freepik API v1 icons endpoint - use 'term' parameter for icon search
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get('https://api.freepik.com/v1/icons', [
                'term' => $query,
                'per_page' => 20,
                'order' => 'relevance', // Order by relevance to the search term
            ]);

            if (!$response->successful()) {
                Log::warning('Freepik API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();
            
            // Response structure: {"data": [...], "meta": {...}}
            $icons = $data['data'] ?? [];
            
            if (empty($icons)) {
                Log::info("No Freepik icons found for query: {$query}");
                return null;
            }

            // Prefer icons with names that match the query exactly or closely
            $selectedIcon = null;
            foreach ($icons as $icon) {
                $name = strtolower($icon['name'] ?? '');
                
                // Prefer icons that match the word exactly
                if (strtolower($name) === strtolower($query)) {
                    $selectedIcon = $icon;
                    break;
                }
            }

            // Check tags for matches if no exact name match
            if (!$selectedIcon) {
                foreach ($icons as $icon) {
                    $tags = $icon['tags'] ?? [];
                    foreach ($tags as $tag) {
                        $tagName = strtolower($tag['name'] ?? $tag['slug'] ?? '');
                        if ($tagName === strtolower($query)) {
                            $selectedIcon = $icon;
                            break 2;
                        }
                    }
                }
            }

            // Use first result if no exact match
            if (!$selectedIcon && !empty($icons)) {
                $selectedIcon = $icons[0];
            }

            // Fetch full icon details to get all thumbnail sizes (search only returns 128x128)
            if ($selectedIcon && isset($selectedIcon['id'])) {
                return $this->getFullIconImageUrl($selectedIcon['id']);
            }

            return null;

        } catch (\Throwable $e) {
            Log::error('Freepik search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }


    /**
     * Fetch full icon details and get the largest available thumbnail URL.
     * The search endpoint only returns 128x128, but the detail endpoint returns all sizes.
     */
    protected function getFullIconImageUrl(int $iconId): ?string
    {
        try {
            $response = Http::withHeaders([
                'x-freepik-api-key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(30)->get("https://api.freepik.com/v1/icons/{$iconId}");

            if (!$response->successful()) {
                Log::warning('Freepik icon detail fetch failed', [
                    'icon_id' => $iconId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();
            $icon = $data['data'] ?? null;
            
            if (!$icon) {
                return null;
            }

            return $this->getImageUrl($icon);
        } catch (\Throwable $e) {
            Log::error('Freepik icon detail fetch exception', [
                'icon_id' => $iconId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Extract image URL from Freepik icon object.
     * Freepik Icons API structure: thumbnails array with different sizes (128, 256, 512)
     * Always returns the largest available size (512x512 preferred)
     */
    protected function getImageUrl(?array $icon): ?string
    {
        if (!$icon) {
            return null;
        }

        // Freepik Icons API returns thumbnails array with different sizes
        // Available sizes: 128, 256, 512 (512 is the largest available)
        $thumbnails = $icon['thumbnails'] ?? [];
        
        if (empty($thumbnails)) {
            return null;
        }
        
        // Always get the largest available thumbnail (512x512 is max)
        $largest = null;
        $maxWidth = 0;
        foreach ($thumbnails as $thumbnail) {
            $width = $thumbnail['width'] ?? 0;
            if ($width > $maxWidth) {
                $maxWidth = $width;
                $largest = $thumbnail['url'] ?? null;
            }
        }
        
        // Log which size we're using for debugging
        if ($largest) {
            Log::info("Freepik icon selected", [
                'icon_id' => $icon['id'] ?? 'unknown',
                'icon_name' => $icon['name'] ?? 'unknown',
                'selected_size' => $maxWidth . 'x' . $maxWidth,
                'url' => $largest,
            ]);
        }
        
        return $largest;
    }

    /**
     * Download image from URL and save it to storage.
     */
    protected function downloadAndSaveImage(string $imageUrl, string $vocabularyWord): ?string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download Freepik image', [
                    'url' => $imageUrl,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            // Determine file extension
            $extension = 'png';
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            if (!empty($pathInfo['extension'])) {
                $extension = strtolower($pathInfo['extension']);
            } elseif (strpos($imageContent, '<svg') === 0 || strpos($imageContent, '<?xml') === 0) {
                $extension = 'svg';
            } elseif (strpos($imageContent, "\xFF\xD8") === 0) {
                $extension = 'jpg';
            } elseif (strpos($imageContent, "\x89PNG") === 0) {
                $extension = 'png';
            }

            $filename = 'vocab_' . time() . '_' . uniqid() . '.' . $extension;
            $relativePath = 'images/vocabulary/' . $filename;

            Storage::disk('public')->put($relativePath, $imageContent);

            Log::info("Successfully downloaded and saved Freepik image for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'size' => strlen($imageContent),
                'source' => 'Freepik',
            ]);

            return $relativePath;

        } catch (\Throwable $e) {
            Log::error('Failed to download/save Freepik image', [
                'message' => $e->getMessage(),
                'url' => $imageUrl,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}

