<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FlaticonImageGenerator
{
    protected $apiKey;
    protected $accessToken;
    protected $tokenExpiry;

    public function __construct()
    {
        $this->apiKey = config('services.flaticon.api_key');
    }

    public function enabled(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Get or refresh the Flaticon access token.
     * Tokens are valid for 24 hours.
     */
    protected function getAccessToken(): ?string
    {
        // Check if we have a cached token that's still valid
        if ($this->accessToken && $this->tokenExpiry && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        try {
            // Flaticon API expects multipart/form-data (not form-urlencoded)
            $response = Http::asMultipart()->timeout(30)->post('https://api.flaticon.com/v3/app/authentication', [
                [
                    'name' => 'apikey',
                    'contents' => $this->apiKey,
                ],
            ]);

            if (!$response->successful()) {
                Log::error('Flaticon authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            // Response format: { "token": "...", "expires": 1234567890 }
            $this->accessToken = $data['token'] ?? null;
            
            if ($this->accessToken) {
                // Use expires timestamp from API, or default to 23 hours
                $expires = $data['expires'] ?? (time() + (23 * 60 * 60));
                $this->tokenExpiry = $expires;
                Log::info('Flaticon access token obtained successfully', [
                    'expires_at' => date('Y-m-d H:i:s', $expires),
                ]);
            }

            return $this->accessToken;

        } catch (\Throwable $e) {
            Log::error('Flaticon authentication exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate/find an icon for a vocabulary word using Flaticon API.
     *
     * @param string $vocabularyWord The English vocabulary word
     * @return string|null The relative path to the saved image, or null on failure
     */
    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Flaticon API key not configured. Skipping icon search.');
            return null;
        }

        // Search for icons matching the vocabulary word
        $iconUrl = $this->searchFlaticon($vocabularyWord);
        
        if ($iconUrl) {
            return $this->downloadAndSaveImage($iconUrl, $vocabularyWord);
        }

        Log::info("No Flaticon icon found for '{$vocabularyWord}' - will need manual upload");
        return null;
    }

    /**
     * Search Flaticon for an icon matching the vocabulary word.
     */
    protected function searchFlaticon(string $query): ?string
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        try {
            // Flaticon API v3 endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->timeout(30)->get('https://api.flaticon.com/v3/search/icons', [
                'q' => $query,
                'limit' => 20,
                'order_by' => 'downloads', // Most popular/downloaded icons first
                'style' => 'flat', // Prefer flat style for flashcards
            ]);

            if (!$response->successful()) {
                Log::warning('Flaticon API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();
            $icons = $data['data'] ?? [];

            if (empty($icons)) {
                // Try with "icon" suffix
                return $this->searchFlaticonWithSuffix($query);
            }

            // Prefer icons with simple, clear names
            foreach ($icons as $icon) {
                $iconName = strtolower($icon['description'] ?? $icon['tags'] ?? '');
                
                // Prefer icons that match the word exactly or closely
                if (stripos($iconName, $query) !== false) {
                    // Get download URL for this icon
                    return $this->getIconDownloadUrl($icon['id'] ?? null, $accessToken);
                }
            }

            // Return first result if no exact match
            return $this->getIconDownloadUrl($icons[0]['id'] ?? null, $accessToken);

        } catch (\Throwable $e) {
            Log::error('Flaticon search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    /**
     * Search Flaticon with "icon" suffix as fallback.
     */
    protected function searchFlaticonWithSuffix(string $query): ?string
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->timeout(30)->get('https://api.flaticon.com/v3/search/icons', [
                'q' => $query . ' icon',
                'limit' => 10,
                'order_by' => 'downloads',
                'style' => 'flat',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $icons = $data['data'] ?? [];
                if (!empty($icons)) {
                    return $this->getIconDownloadUrl($icons[0]['id'] ?? null, $accessToken);
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

        return null;
    }

    /**
     * Get the download URL for an icon by its ID.
     */
    protected function getIconDownloadUrl(?string $iconId, string $accessToken): ?string
    {
        if (!$iconId) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->timeout(30)->get("https://api.flaticon.com/v3/icons/{$iconId}/download");

            if (!$response->successful()) {
                Log::warning('Flaticon download URL request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'icon_id' => $iconId,
                ]);
                return null;
            }

            $data = $response->json();
            return $data['data']['download_url'] ?? null;

        } catch (\Throwable $e) {
            Log::error('Flaticon download URL exception', [
                'message' => $e->getMessage(),
                'icon_id' => $iconId,
            ]);
            return null;
        }
    }

    /**
     * Download icon from URL and save it to storage.
     */
    protected function downloadAndSaveImage(string $imageUrl, string $vocabularyWord): ?string
    {
        try {
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download Flaticon icon', [
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

            Log::info("Successfully downloaded and saved Flaticon icon for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'size' => strlen($imageContent),
                'source' => 'Flaticon',
            ]);

            return $relativePath;

        } catch (\Throwable $e) {
            Log::error('Failed to download/save Flaticon icon', [
                'message' => $e->getMessage(),
                'url' => $imageUrl,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}

