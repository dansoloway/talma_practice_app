<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VocabularyController extends Controller
{
    /**
     * Display a listing of vocabulary for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $vocabulary = $lesson->vocabulary()->ordered()->get();
        
        return view('admin.vocabulary.index', compact('lesson', 'vocabulary'));
    }

    /**
     * Show the form for creating a new vocabulary item.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.vocabulary.create', compact('lesson'));
    }

    /**
     * Store a newly created vocabulary item.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'english_word' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $validated['lesson_id'] = $lesson->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('images/vocabulary', $filename, 'public');
            $validated['image_path'] = 'images/vocabulary/' . $filename;
        }

        $vocabulary = Vocabulary::create($validated);
        
        // Generate audio for the new vocabulary word
        $this->generateVocabularyAudio($vocabulary);

        return redirect()
            ->route('admin.lessons.vocabulary.show', [$lesson, $vocabulary])
            ->with('success', 'Vocabulary item created successfully!');
    }

    /**
     * Display the specified vocabulary item.
     */
    public function show(Lesson $lesson, Vocabulary $vocabulary)
    {
        return view('admin.vocabulary.show', compact('lesson', 'vocabulary'));
    }

    /**
     * Show the form for editing the vocabulary item.
     */
    public function edit(Lesson $lesson, Vocabulary $vocabulary)
    {
        return view('admin.vocabulary.edit', compact('lesson', 'vocabulary'));
    }

    /**
     * Update the specified vocabulary item.
     */
    public function update(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        $validated = $request->validate([
            'english_word' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($vocabulary->image_path && Storage::disk('public')->exists($vocabulary->image_path)) {
                Storage::disk('public')->delete($vocabulary->image_path);
            }
            
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('images/vocabulary', $filename, 'public');
            $validated['image_path'] = 'images/vocabulary/' . $filename;
        }

        $vocabulary->update($validated);

        return redirect()
            ->route('admin.lessons.vocabulary.show', [$lesson, $vocabulary])
            ->with('success', 'Vocabulary item updated successfully!');
    }

    /**
     * Remove the specified vocabulary item.
     */
    public function destroy(Lesson $lesson, Vocabulary $vocabulary)
    {
        // Delete image if exists
        if ($vocabulary->image_path && Storage::disk('public')->exists($vocabulary->image_path)) {
            Storage::disk('public')->delete($vocabulary->image_path);
        }

        $vocabulary->delete();

        return redirect()
            ->route('admin.lessons.vocabulary.index', $lesson)
            ->with('success', 'Vocabulary item deleted successfully!');
    }

    /**
     * Show CSV upload form.
     */
    public function csvUpload(Lesson $lesson)
    {
        return view('admin.vocabulary.csv-upload', compact('lesson'));
    }

    /**
     * Process CSV upload.
     */
    public function processCsv(Request $request, Lesson $lesson)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'import_mode' => 'required|in:add,replace',
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $importMode = $request->input('import_mode');
        
        // If replace mode, delete existing vocabulary
        if ($importMode === 'replace') {
            $lesson->vocabulary()->delete();
        }
        
        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            // Skip header row if it exists
            if ($index === 0 && (strtolower($row[0]) === 'word' || strtolower($row[0]) === 'english_word')) {
                continue;
            }

            if (empty($row[0])) {
                continue;
            }

            try {
                Vocabulary::create([
                    'lesson_id' => $lesson->id,
                    'english_word' => trim($row[0]),
                    'sort_order' => $imported + 1,
                    'is_active' => true,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        $action = $importMode === 'replace' ? 'replaced with' : 'added';
        $message = "Successfully {$action} {$imported} vocabulary items.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()
            ->route('admin.lessons.vocabulary.index', $lesson)
            ->with('success', $message);
    }

    /**
     * Download CSV template.
     */
    public function csvTemplate()
    {
        $csvContent = "English Word\n";
        $csvContent .= "air pollution\n";
        $csvContent .= "water pollution\n";
        $csvContent .= "soil pollution\n";
        $csvContent .= "noise pollution\n";

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="vocabulary_template.csv"');
    }

    public function updateImage(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Delete old image if exists
        if ($vocabulary->image_path && \Storage::disk('public')->exists($vocabulary->image_path)) {
            \Storage::disk('public')->delete($vocabulary->image_path);
        }

        // Store new image
        $image = $request->file('image');
        $filename = time() . '_' . $image->getClientOriginalName();
        $path = $image->storeAs('images/vocabulary', $filename, 'public');
        
        $vocabulary->update(['image_path' => 'images/vocabulary/' . $filename]);

        return redirect()
            ->route('admin.lessons.vocabulary.index', $lesson)
            ->with('success', 'Image updated successfully!');
    }

    public function removeImage(Lesson $lesson, Vocabulary $vocabulary)
    {
        // Delete image file if exists
        if ($vocabulary->image_path && \Storage::disk('public')->exists($vocabulary->image_path)) {
            \Storage::disk('public')->delete($vocabulary->image_path);
        }

        $vocabulary->update(['image_path' => null]);

        return redirect()
            ->route('admin.lessons.vocabulary.index', $lesson)
            ->with('success', 'Image removed successfully!');
    }

    /**
     * Generate TTS audio for a vocabulary word
     */
    private function generateVocabularyAudio(Vocabulary $vocabulary)
    {
        $apiKey = config('services.elevenlabs.api_key') ?: env('ELEVENLABS_API_KEY');
        if (!$apiKey) {
            \Log::warning('ELEVENLABS_API_KEY not found, skipping audio generation for: ' . $vocabulary->english_word);
            return;
        }

        try {
            $voiceId = 'pNInz6obpgDQGcFmaJgB'; // Default voice ID
            
            $response = \Http::withHeaders([
                'Accept' => 'audio/mpeg',
                'Content-Type' => 'application/json',
                'xi-api-key' => $apiKey,
            ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                'text' => $vocabulary->english_word,
                'model_id' => 'eleven_monolingual_v1',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.5
                ]
            ]);

            if ($response->successful()) {
                $filename = 'vocabulary_' . time() . '_' . uniqid() . '.mp3';
                $path = 'vocabulary-audio/' . $filename;
                
                \Storage::disk('public')->put($path, $response->body());
                
                $vocabulary->update(['word_audio_path' => $path]);
                \Log::info('Generated audio for: ' . $vocabulary->english_word);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to generate audio for ' . $vocabulary->english_word . ': ' . $e->getMessage());
        }
    }
}