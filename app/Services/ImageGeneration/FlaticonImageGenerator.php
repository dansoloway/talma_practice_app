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
        if (!filled($this->apiKey)) {
            return false;
        }

        // Freepik keys start with "FPSX"; they cannot authenticate with Flaticon.
        if (str_starts_with($this->apiKey, 'FPSX')) {
            Log::warning('FLATICON_API_KEY looks like a Freepik key. Request a Flaticon API key at https://api.flaticon.com');
            return false;
        }

        return true;
    }

    /**
     * Get or refresh the Flaticon access token (valid 24 hours).
     */
    protected function getAccessToken(): ?string
    {
        if ($this->accessToken && $this->tokenExpiry && $this->tokenExpiry > time()) {
            return $this->accessToken;
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->post('https://api.flaticon.com/v3/app/authentication', [
                    'apikey' => $this->apiKey,
                ]);

            if (!$response->successful()) {
                Log::error('Flaticon authentication failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            $this->accessToken = $data['token'] ?? $data['data']['token'] ?? null;

            if ($this->accessToken) {
                $expires = $data['expires'] ?? $data['data']['expires'] ?? (time() + (23 * 60 * 60));
                $this->tokenExpiry = (int) $expires;
                Log::info('Flaticon access token obtained successfully', [
                    'expires_at' => date('Y-m-d H:i:s', $this->tokenExpiry),
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
     */
    public function generateVocabularyImage(string $vocabularyWord): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Flaticon API key not configured. Skipping icon search.');
            return null;
        }

        $imageUrl = $this->searchFlaticon($vocabularyWord);

        if ($imageUrl) {
            return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
        }

        Log::info("No Flaticon icon found for '{$vocabularyWord}'");
        return null;
    }

    protected function searchFlaticon(string $query): ?string
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        $icon = $this->fetchIcons($query, 'priority', $accessToken)
            ?? $this->fetchIcons($query . ' icon', 'priority', $accessToken);

        if (!$icon) {
            return null;
        }

        return $this->imageUrlFromIcon($icon);
    }

    protected function fetchIcons(string $query, string $orderBy, string $accessToken): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get("https://api.flaticon.com/v3/search/icons/{$orderBy}", [
                'q' => $query,
                'limit' => 20,
                'styleShape' => 'fill',
            ]);

            if (!$response->successful()) {
                Log::warning('Flaticon search API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $query,
                ]);
                return null;
            }

            $icons = $response->json('data') ?? [];
            if (empty($icons)) {
                return null;
            }

            $needle = strtolower($query);

            foreach ($icons as $icon) {
                $description = strtolower($icon['description'] ?? '');
                $tags = strtolower($icon['tags'] ?? '');

                if (str_contains($description, $needle) || str_contains($tags, $needle)) {
                    return $icon;
                }
            }

            return $icons[0];
        } catch (\Throwable $e) {
            Log::error('Flaticon search exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);
            return null;
        }
    }

    protected function imageUrlFromIcon(array $icon): ?string
    {
        $images = $icon['images'] ?? [];
        if (!is_array($images) || empty($images)) {
            return $this->getIconDownloadUrl($icon['id'] ?? null);
        }

        $preferredSizes = ['512', '256', '128', '64'];
        foreach ($preferredSizes as $size) {
            if (!empty($images[$size])) {
                return $images[$size];
            }
        }

        return reset($images) ?: null;
    }

    /**
     * Fallback: official download endpoint when search results lack CDN URLs.
     */
    protected function getIconDownloadUrl($iconId): ?string
    {
        if (!$iconId) {
            return null;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get("https://api.flaticon.com/v3/item/icon/download/{$iconId}/png", [
                'size' => 512,
            ]);

            if (!$response->successful()) {
                Log::warning('Flaticon download request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'icon_id' => $iconId,
                ]);
                return null;
            }

            $data = $response->json();
            return $data['data']['url'] ?? $data['url'] ?? null;
        } catch (\Throwable $e) {
            Log::error('Flaticon download exception', [
                'message' => $e->getMessage(),
                'icon_id' => $iconId,
            ]);
            return null;
        }
    }

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
