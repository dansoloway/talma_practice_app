<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class StockImageGenerator
{
    protected $unsplashAccessKey;
    protected $pixabayApiKey;

    public function __construct()
    {
        $this->unsplashAccessKey = config('services.unsplash.access_key');
        $this->pixabayApiKey = config('services.pixabay.api_key');
    }

    public function enabled(): bool
    {
        return filled($this->unsplashAccessKey) || filled($this->pixabayApiKey);
    }

    /**
     * Generate/find an image for a vocabulary word using stock photo APIs.
     *
     * @param string $vocabularyWord The English vocabulary word
     * @return string|null The relative path to the saved image, or null on failure
     */
    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Stock image APIs not configured. Skipping image search.');
            return null;
        }

        // Try Unsplash first (better quality, cleaner images)
        if ($this->unsplashAccessKey) {
            $imageUrl = $this->searchUnsplash($vocabularyWord);
            if ($imageUrl) {
                return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
            }
        }

        // Try Pixabay as backup
        if ($this->pixabayApiKey) {
            $imageUrl = $this->searchPixabay($vocabularyWord);
            if ($imageUrl) {
                return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
            }
        }

        Log::warning("No suitable stock image found for '{$vocabularyWord}'");
        return null;
    }

    /**
     * Search Unsplash for a simple, clear image.
     */
    protected function searchUnsplash(string $query): ?string
    {
        try {
            // Add "clipart" or "illustration" to get simpler images
            $searchQuery = $query . ' clipart simple';
            
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . $this->unsplashAccessKey
            ])->timeout(30)->get('https://api.unsplash.com/search/photos', [
                'query' => $searchQuery,
                'per_page' => 10,
                'orientation' => 'squarish', // Better for flashcards
                'content_filter' => 'high',
                'order_by' => 'relevance'
            ]);

            if (!$response->successful()) {
                Log::warning('Unsplash API error', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();
            $results = $data['results'] ?? [];

            if (empty($results)) {
                // Try without "clipart" modifier
                return $this->searchUnsplashSimple($query);
            }

            // Prefer images with simple backgrounds (check description/tags)
            foreach ($results as $photo) {
                $description = strtolower($photo['description'] ?? $photo['alt_description'] ?? '');
                $tags = implode(' ', array_column($photo['tags'] ?? [], 'title'));
                
                // Prefer simple, clean images
                if (stripos($description . ' ' . $tags, 'simple') !== false ||
                    stripos($description . ' ' . $tags, 'white background') !== false ||
                    stripos($description . ' ' . $tags, 'isolated') !== false) {
                    return $photo['urls']['regular'] ?? null;
                }
            }

            // Return first result if no perfect match
            return $results[0]['urls']['regular'] ?? null;

        } catch (\Throwable $e) {
            Log::error('Unsplash search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    /**
     * Search Unsplash with simpler query (fallback).
     */
    protected function searchUnsplashSimple(string $query): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . $this->unsplashAccessKey
            ])->timeout(30)->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'per_page' => 5,
                'orientation' => 'squarish',
                'content_filter' => 'high',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];
                return $results[0]['urls']['regular'] ?? null;
            }
        } catch (\Throwable $e) {
            // Silent fail, will try Pixabay
        }

        return null;
    }

    /**
     * Search Pixabay for a simple, clear image.
     */
    protected function searchPixabay(string $query): ?string
    {
        try {
            // Pixabay has better clipart/illustration support
            $response = Http::timeout(30)->get('https://pixabay.com/api/', [
                'key' => $this->pixabayApiKey,
                'q' => $query,
                'image_type' => 'all', // Include illustrations
                'orientation' => 'all',
                'category' => 'education',
                'safesearch' => 'true',
                'per_page' => 20,
                'order' => 'popular' // Popular images are usually better quality
            ]);

            if (!$response->successful()) {
                Log::warning('Pixabay API error', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();
            $hits = $data['hits'] ?? [];

            if (empty($hits)) {
                return null;
            }

            // Prefer illustrations/clipart over photos for flashcards
            foreach ($hits as $hit) {
                $tags = strtolower($hit['tags'] ?? '');
                if (stripos($tags, 'clipart') !== false ||
                    stripos($tags, 'illustration') !== false ||
                    stripos($tags, 'isolated') !== false) {
                    return $hit['webformatURL'] ?? null;
                }
            }

            // Return first result
            return $hits[0]['webformatURL'] ?? null;

        } catch (\Throwable $e) {
            Log::error('Pixabay search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    /**
     * Download image from URL and save it to storage.
     */
    protected function downloadAndSaveImage(string $imageUrl, string $vocabularyWord): ?string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download stock image', [
                    'url' => $imageUrl,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            // Determine file extension from URL or content
            $extension = 'jpg';
            $pathInfo = pathinfo(parse_url($imageUrl, PHP_URL_PATH));
            if (!empty($pathInfo['extension'])) {
                $extension = $pathInfo['extension'];
            } elseif (strpos($imageContent, "\xFF\xD8") === 0) {
                $extension = 'jpg';
            } elseif (strpos($imageContent, "\x89PNG") === 0) {
                $extension = 'png';
            }

            $filename = 'vocab_' . time() . '_' . uniqid() . '.' . $extension;
            $relativePath = 'images/vocabulary/' . $filename;

            Storage::disk('public')->put($relativePath, $imageContent);

            Log::info("Successfully downloaded and saved stock image for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'size' => strlen($imageContent),
                'source' => 'Stock Photo API',
            ]);

            return $relativePath;

        } catch (\Throwable $e) {
            Log::error('Failed to download/save stock image', [
                'message' => $e->getMessage(),
                'url' => $imageUrl,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}

