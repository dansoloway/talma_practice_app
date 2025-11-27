<?php

namespace App\Services\ImageGeneration;

use App\Services\OpenAi\OpenAiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OpenAiImageGenerator
{
    protected OpenAiService $openAiService;
    protected $model;
    protected $size;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
        $this->model = config('services.openai.image_model', 'dall-e-3');
        $this->size = config('services.openai.image_size', '1024x1024');
    }

    public function enabled(): bool
    {
        return $this->openAiService->enabled();
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
            Log::warning('OpenAI API key not configured. Skipping image generation.');
            return null;
        }

        $prompt = $this->buildPrompt($vocabularyWord);
        $maxRetries = 3;

        try {
            $options = [
                'model' => $this->model,
                'size' => $this->size,
                'quality' => 'standard',
                'n' => 1,
                'response_format' => 'url',
                'timeout' => 120,
            ];

            $response = $this->openAiService->imageGeneration($prompt, $options);

            // Handle retries for server errors (response is null on error)
            if (!$response && $retryCount < $maxRetries) {
                $waitTime = ($retryCount + 1) * 5; // Exponential backoff: 5s, 10s, 15s
                Log::warning("OpenAI server error for '{$vocabularyWord}'. Retrying in {$waitTime} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");
                sleep($waitTime);
                return $this->generateVocabularyImage($vocabularyWord, $retryCount + 1);
            }

            if (!$response) {
                Log::error('OpenAI image generation failed', [
                    'word' => $vocabularyWord,
                    'retry_count' => $retryCount,
                ]);
                return null;
            }

            $imageUrl = $this->openAiService->extractImageUrl($response);

            if (!$imageUrl) {
                Log::error('No image URL in OpenAI response', [
                    'response' => $response,
                    'word' => $vocabularyWord,
                ]);
                return null;
            }

            // Download the image (with increased timeout for large images)
            return $this->downloadAndSaveImage($imageUrl, $vocabularyWord);

        } catch (\Throwable $e) {
            // Retry on timeout errors
            if (strpos($e->getMessage(), 'timeout') !== false && $retryCount < $maxRetries) {
                $waitTime = ($retryCount + 1) * 5;
                Log::warning("OpenAI timeout for '{$vocabularyWord}'. Retrying in {$waitTime} seconds (attempt " . ($retryCount + 1) . "/{$maxRetries})");
                sleep($waitTime);
                return $this->generateVocabularyImage($vocabularyWord, $retryCount + 1);
            }
            
            Log::error('OpenAI image generation exception', [
                'message' => $e->getMessage(),
                'word' => $vocabularyWord,
                'retry_count' => $retryCount,
            ]);
            return null;
        }
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
            // Download the image (increased timeout for large images)
            $imageContent = Http::timeout(60)->get($imageUrl)->body();

            if (empty($imageContent)) {
                Log::error('Failed to download image from OpenAI', [
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
            ]);

            return $relativePath;

        } catch (\Throwable $e) {
            Log::error('Failed to download/save OpenAI image', [
                'message' => $e->getMessage(),
                'url' => $imageUrl,
                'word' => $vocabularyWord,
            ]);
            return null;
        }
    }
}

