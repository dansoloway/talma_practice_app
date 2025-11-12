<?php

namespace App\Services\ImageGeneration;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LeonardoImageGenerator
{
    protected $apiKey;
    protected $model;
    protected $width;
    protected $height;

    public function __construct()
    {
        $this->apiKey = config('services.leonardo.api_key');
        $this->model = config('services.leonardo.model', 'leonardo-flash-xl');
        $size = config('services.leonardo.size', '1024x1024');
        
        // Parse size (e.g., "1024x1024" -> width=1024, height=1024)
        $sizeParts = explode('x', $size);
        $this->width = (int) ($sizeParts[0] ?? 1024);
        $this->height = (int) ($sizeParts[1] ?? 1024);
    }

    public function enabled(): bool
    {
        return filled($this->apiKey);
    }

    /**
     * Generate an ESL clipart image for a vocabulary word.
     *
     * @param string $vocabularyWord The English vocabulary word
     * @param int $retryCount Current retry attempt
     * @return string|null The relative path to the saved image, or null on failure
     */
    public function generateVocabularyImage(string $vocabularyWord, int $retryCount = 0): ?string
    {
        if (!$this->enabled()) {
            Log::warning('Leonardo.ai API key not configured. Skipping image generation.');
            return null;
        }

        $prompt = $this->buildPrompt($vocabularyWord);
        $maxRetries = 3;

        try {
            // Step 1: Create generation request
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://cloud.leonardo.ai/api/rest/v1/generations', [
                'prompt' => $prompt,
                'modelId' => $this->model,
                'width' => $this->width,
                'height' => $this->height,
                'num_images' => 1,
                'guidance_scale' => 7,
                'negative_prompt' => 'text, words, letters, labels, writing, typography, text overlay, word sand, word ocean, word windy, faces on objects, cluttered background, complex scene, watermark, signature',
            ]);

            // Retry on server errors
            if (!$response->successful() && in_array($response->status(), [500, 502, 503, 504]) && $retryCount < $maxRetries) {
                $waitTime = ($retryCount + 1) * 5;
                Log::warning("Leonardo.ai server error ({$response->status()}) for '{$vocabularyWord}'. Retrying in {$waitTime} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");
                sleep($waitTime);
                return $this->generateVocabularyImage($vocabularyWord, $retryCount + 1);
            }

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = is_array($errorBody) 
                    ? ($errorBody['error'] ?? $errorBody['message'] ?? 'Unknown error')
                    : ($response->body() ?: 'Unknown error');
                
                Log::error('Leonardo.ai image generation failed', [
                    'status' => $response->status(),
                    'message' => $errorMessage,
                    'body' => $response->body(),
                    'word' => $vocabularyWord,
                    'retry_count' => $retryCount,
                ]);
                return null;
            }

            $data = $response->json();
            
            // Handle different response formats
            $generationId = $data['sdGenerationJob']['generationId'] 
                ?? $data['generationId'] 
                ?? $data['sd_generation_job']['generationId']
                ?? null;

            if (!$generationId) {
                Log::error('No generation ID in Leonardo.ai response', [
                    'response' => $data,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            // Step 2: Poll for generation completion
            return $this->pollForImage($generationId, $vocabularyWord, $retryCount);

        } catch (\Throwable $e) {
            // Retry on timeout errors
            if (strpos($e->getMessage(), 'timeout') !== false && $retryCount < $maxRetries) {
                $waitTime = ($retryCount + 1) * 5;
                Log::warning("Leonardo.ai timeout for '{$vocabularyWord}'. Retrying in {$waitTime} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");
                sleep($waitTime);
                return $this->generateVocabularyImage($vocabularyWord, $retryCount + 1);
            }
            
            Log::error('Leonardo.ai image generation exception', [
                'message' => $e->getMessage(),
                'word' => $vocabularyWord,
                'retry_count' => $retryCount,
            ]);
            return null;
        }
    }

    /**
     * Poll Leonardo.ai API for generation completion and download the image.
     */
    protected function pollForImage(string $generationId, string $vocabularyWord, int $retryCount = 0): ?string
    {
        $maxPolls = 30; // Poll up to 30 times (5 minutes max)
        $pollInterval = 10; // Wait 10 seconds between polls

        for ($i = 0; $i < $maxPolls; $i++) {
            sleep($pollInterval);

            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->timeout(30)->get("https://cloud.leonardo.ai/api/rest/v1/generations/{$generationId}");

                if (!$response->successful()) {
                    Log::warning("Leonardo.ai poll failed for generation {$generationId}", [
                        'status' => $response->status(),
                        'attempt' => $i + 1,
                    ]);
                    continue;
                }

                $data = $response->json();
                
                // Handle different response formats
                $generations = $data['generations_by_pk']['generated_images'] 
                    ?? $data['generated_images']
                    ?? $data['images']
                    ?? [];

                if (!empty($generations)) {
                    // Get the first generated image URL
                    $firstImage = $generations[0];
                    $imageUrl = is_array($firstImage) 
                        ? ($firstImage['url'] ?? $firstImage['imageUrl'] ?? null)
                        : $firstImage;
                    
                    if ($imageUrl) {
                        return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);
                    }
                }

                // Check if generation is still processing
                $status = $data['generations_by_pk']['status'] 
                    ?? $data['status'] 
                    ?? 'PENDING';
                    
                if ($status === 'FAILED' || $status === 'FAILURE') {
                    Log::error("Leonardo.ai generation failed for '{$vocabularyWord}'", [
                        'generation_id' => $generationId,
                        'status' => $status,
                        'response' => $data,
                    ]);
                    return null;
                }
                
                // If status is COMPLETE but no images, log and continue polling
                if ($status === 'COMPLETE' && empty($generations)) {
                    Log::warning("Leonardo.ai generation marked complete but no images found", [
                        'generation_id' => $generationId,
                        'response' => $data,
                    ]);
                }

            } catch (\Throwable $e) {
                Log::warning("Leonardo.ai poll exception for generation {$generationId}", [
                    'message' => $e->getMessage(),
                    'attempt' => $i + 1,
                ]);
            }
        }

        Log::error("Leonardo.ai generation timed out for '{$vocabularyWord}'", [
            'generation_id' => $generationId,
            'max_polls' => $maxPolls,
        ]);
        return null;
    }

    /**
     * Build the prompt for image generation.
     */
    protected function buildPrompt(string $vocabularyWord): string
    {
        return "A simple, realistic illustration showing the meaning of {$vocabularyWord}. The image should be clear, child-friendly, and educational — like a school flashcard. Illustration only, no text, no words, no letters, no labels, no writing, no faces on objects, no extra elements unrelated to the word. Plain white or light background, flat design, bright colors, centered composition.";
    }

    /**
     * Download image from URL and save it to storage.
     */
    protected function downloadAndSaveImage(string $imageUrl, string $vocabularyWord): ?string
    {
        try {
            // Download the image
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download image from Leonardo.ai', [
                    'url' => $imageUrl,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            // Generate filename
            $filename = 'vocab_' . time() . '_' . uniqid() . '.png';
            $relativePath = 'images/vocabulary/' . $filename;

            // Save to storage
            Storage::disk('public')->put($relativePath, $imageContent);

            Log::info("Successfully generated and saved image for vocabulary word: {$vocabularyWord}", [
                'path' => $relativePath,
                'size' => strlen($imageContent),
                'provider' => 'Leonardo.ai',
            ]);

            return $relativePath;

        } catch (\Throwable $e) {
            Log::error('Failed to download/save Leonardo.ai image', [
                'message' => $e->getMessage(),
                'url' => $imageUrl,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}

