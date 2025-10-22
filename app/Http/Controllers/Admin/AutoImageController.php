<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AutoImageController extends Controller
{
    private $unsplashAccessKey;
    private $pixabayApiKey;

    public function __construct()
    {
        $this->unsplashAccessKey = config('services.unsplash.access_key');
        $this->pixabayApiKey = config('services.pixabay.api_key');
    }

    /**
     * Show auto-image finder for a lesson's vocabulary
     */
    public function index(Lesson $lesson)
    {
        $vocabulary = $lesson->vocabulary()->where('is_active', true)->get();
        
        return view('admin.vocabulary.auto-images', compact('lesson', 'vocabulary'));
    }

    /**
     * Find and suggest images for a vocabulary word
     */
    public function findImages(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        $word = $vocabulary->english_word;
        $images = [];

        // Try Unsplash first (better quality)
        if ($this->unsplashAccessKey) {
            $images = array_merge($images, $this->searchUnsplash($word));
        }

        // Try Pixabay as backup
        if ($this->pixabayApiKey && count($images) < 3) {
            $images = array_merge($images, $this->searchPixabay($word));
        }

        return response()->json([
            'word' => $word,
            'images' => array_slice($images, 0, 6) // Return max 6 images
        ]);
    }

    /**
     * Apply selected image to vocabulary item
     */
    public function applyImage(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        $request->validate([
            'image_url' => 'required|url',
            'image_source' => 'required|string'
        ]);

        try {
            // Download image
            $imageContent = Http::timeout(30)->get($request->image_url)->body();
            
            // Generate filename
            $extension = pathinfo(parse_url($request->image_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'vocabulary_' . $vocabulary->id . '_' . time() . '.' . $extension;
            
            // Store image
            $path = 'vocabulary/' . $filename;
            Storage::disk('public')->put($path, $imageContent);
            
            // Update vocabulary item
            $vocabulary->update(['image_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Image applied successfully!',
                'image_path' => asset('storage/' . $path)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search Unsplash for images
     */
    private function searchUnsplash($query)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Client-ID ' . $this->unsplashAccessKey
            ])->get('https://api.unsplash.com/search/photos', [
                'query' => $query,
                'per_page' => 6,
                'orientation' => 'landscape',
                'content_filter' => 'high'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return array_map(function($photo) {
                    return [
                        'url' => $photo['urls']['regular'],
                        'thumb' => $photo['urls']['thumb'],
                        'description' => $photo['description'] ?? $photo['alt_description'] ?? '',
                        'source' => 'Unsplash',
                        'photographer' => $photo['user']['name'] ?? 'Unknown'
                    ];
                }, $data['results'] ?? []);
            }
        } catch (\Exception $e) {
            \Log::warning('Unsplash API error: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Search Pixabay for images
     */
    private function searchPixabay($query)
    {
        try {
            $response = Http::get('https://pixabay.com/api/', [
                'key' => $this->pixabayApiKey,
                'q' => $query,
                'image_type' => 'photo',
                'orientation' => 'horizontal',
                'category' => 'education',
                'safesearch' => 'true',
                'per_page' => 6
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return array_map(function($hit) {
                    return [
                        'url' => $hit['webformatURL'],
                        'thumb' => $hit['previewURL'],
                        'description' => $hit['tags'] ?? '',
                        'source' => 'Pixabay',
                        'photographer' => $hit['user'] ?? 'Unknown'
                    ];
                }, $data['hits'] ?? []);
            }
        } catch (\Exception $e) {
            \Log::warning('Pixabay API error: ' . $e->getMessage());
        }

        return [];
    }
}
