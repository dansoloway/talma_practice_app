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
            'hebrew_translation' => 'nullable|string|max:255',
            'arabic_translation' => 'nullable|string|max:255',
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
            'hebrew_translation' => 'nullable|string|max:255',
            'arabic_translation' => 'nullable|string|max:255',
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
        // Increase execution time for TTS generation
        set_time_limit(300); // 5 minutes
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'import_mode' => 'required|in:add,replace',
        ]);

        $file = $request->file('csv_file');
        $csvData = array_map('str_getcsv', file($file->getRealPath()));
        $importMode = $request->input('import_mode');
        
        \Log::info("Starting CSV import for lesson {$lesson->id} in {$importMode} mode");
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Starting CSV vocabulary import for lesson {$lesson->id} in {$importMode} mode\n", FILE_APPEND);
        
        // If replace mode, delete existing vocabulary
        if ($importMode === 'replace') {
            $lesson->vocabulary()->delete();
        }
        
        $imported = 0;
        $errors = [];
        $processedWords = []; // Track words to prevent duplicates within CSV

        foreach ($csvData as $index => $row) {
            // Skip header row if it exists
            if ($index === 0 && (strtolower($row[0]) === 'word' || strtolower($row[0]) === 'english_word')) {
                continue;
            }

            if (empty($row[0])) {
                continue;
            }

            $englishWord = trim($row[0]);
            
            // Check for duplicate within the CSV
            if (in_array(strtolower($englishWord), $processedWords)) {
                $errors[] = "Row " . ($index + 1) . ": Duplicate word '{$englishWord}' found in CSV";
                continue;
            }
            
            // Check for duplicate in existing database (for replace mode)
            if ($importMode === 'replace') {
                $existingWord = $lesson->vocabulary()->where('english_word', $englishWord)->first();
                if ($existingWord) {
                    $errors[] = "Row " . ($index + 1) . ": Word '{$englishWord}' already exists in the database. Use 'Add' mode to add new words or update existing ones.";
                    continue;
                }
            }

            try {
                $vocabulary = Vocabulary::create([
                    'lesson_id' => $lesson->id,
                    'english_word' => $englishWord,
                    'hebrew_translation' => isset($row[1]) ? trim($row[1]) : null,
                    'arabic_translation' => isset($row[2]) ? trim($row[2]) : null,
                    'sort_order' => $imported + 1,
                    'is_active' => true,
                ]);
                
                // Generate TTS audio for the vocabulary word
                \Log::info("Generating TTS for vocabulary word: {$englishWord} (ID: {$vocabulary->id})");
                $this->generateVocabularyAudio($vocabulary);
                
                $processedWords[] = strtolower($englishWord);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                \Log::error("Failed to create vocabulary from CSV row " . ($index + 1) . ": " . $e->getMessage());
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
        $csvContent = "English Word,Hebrew Translation,Arabic Translation\n";
        $csvContent .= "variable,משתנה,متغير\n";
        $csvContent .= "conclusion,מסקנה,استنتاج\n";
        $csvContent .= "hypothesis,השערה,فرضية\n";
        $csvContent .= "experiment,ניסוי,تجربة\n";

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
            $errorMsg = 'ELEVENLABS_API_KEY not found, skipping audio generation for: ' . $vocabulary->english_word;
            \Log::warning($errorMsg);
            // Also log to TTS log file
            $ttsLogFile = storage_path('logs/tts_generation.log');
            file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
            return;
        }

        // Create dedicated TTS log file entry
        $ttsLogFile = storage_path('logs/tts_generation.log');
        file_put_contents($ttsLogFile, "[" . now() . "] Starting TTS generation for vocabulary word: '{$vocabulary->english_word}' (ID: {$vocabulary->id})\n", FILE_APPEND);

        try {
            $voiceId = 'pNInz6obpgDQGcFmaJgB'; // Default voice ID
            
            $response = \Http::withHeaders([
                'Accept' => 'audio/mpeg',
                'Content-Type' => 'application/json',
                'xi-api-key' => $apiKey,
            ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                'text' => $vocabulary->english_word,
                'model_id' => 'eleven_monolingual_v1',
                'voice_settings' => [
                    'stability' => 0.5,
                    'similarity_boost' => 0.5
                ]
            ]);

            if ($response->successful()) {
                $filename = 'vocabulary_' . time() . '_' . uniqid() . '.mp3';
                $relativePath = 'vocabulary-audio/' . $filename;
                
                // Use Storage facade which handles permissions and directory creation better
                \Storage::disk('public')->put($relativePath, $response->body());
                
                // Store path with /storage/ prefix like prompts do for consistency
                $vocabulary->update(['word_audio_path' => "/storage/{$relativePath}"]);
                
                $successMsg = "Successfully generated audio for: '{$vocabulary->english_word}' (path: /storage/{$relativePath})";
                \Log::info($successMsg);
                file_put_contents($ttsLogFile, "[" . now() . "] SUCCESS: {$successMsg}\n", FILE_APPEND);
            } else {
                $errorMsg = "TTS API Error for '{$vocabulary->english_word}': HTTP {$response->status()}";
                \Log::error($errorMsg);
                file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
            }
        } catch (\Exception $e) {
            $errorMsg = "Failed to generate audio for '{$vocabulary->english_word}': " . $e->getMessage();
            \Log::error($errorMsg);
            \Log::error("Stack trace: " . $e->getTraceAsString());
            file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
            file_put_contents($ttsLogFile, "[" . now() . "] Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
        }
    }

    /**
     * Generate TTS audio for vocabulary words via AJAX (one at a time).
     */
    public function generateTts(Request $request, Lesson $lesson)
    {
        // Per-lesson lock to prevent overlapping requests
        $lock = \Illuminate\Support\Facades\Cache::lock('lesson:' . $lesson->id . ':vocab_tts', 5);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'processed' => 0,
                'errors' => [],
                'remaining' => $lesson->vocabulary()->whereNull('word_audio_path')->count(),
                'completed' => false,
                'locked' => true,
            ]);
        }

        try {
            // Always process exactly 1 item to avoid duplicates/repeats
            $forceRecreate = $request->input('force', false);
            
            // First, try to get items without audio_path
            $vocabulary = $lesson->vocabulary()
                ->where(function($query) {
                    $query->whereNull('word_audio_path')
                          ->orWhere('word_audio_path', '');
                })
                ->orderBy('id')
                ->first();
            
            // If forcing recreation and all items have audio_path, 
            // clear the first item's audio_path to start recreation
            if ($forceRecreate && !$vocabulary) {
                $vocabulary = $lesson->vocabulary()->orderBy('id')->first();
                if ($vocabulary && $vocabulary->word_audio_path) {
                    // Clear audio_path to force regeneration
                    // Also delete the file if it exists
                    $audioPath = storage_path('app/public/' . $vocabulary->word_audio_path);
                    if (file_exists($audioPath)) {
                        @unlink($audioPath);
                    }
                    $vocabulary->update(['word_audio_path' => null]);
                }
            }
            
            // If forcing recreation and vocabulary exists, also clear its audio_path
            if ($forceRecreate && $vocabulary && $vocabulary->word_audio_path) {
                $audioPath = storage_path('app/public/' . $vocabulary->word_audio_path);
                if (file_exists($audioPath)) {
                    @unlink($audioPath);
                }
                $vocabulary->update(['word_audio_path' => null]);
            }

            $processed = 0;
            $errors = [];
            
            // Create dedicated TTS log file
            $ttsLogFile = storage_path('logs/tts_generation.log');
            
            if ($vocabulary) {
                file_put_contents($ttsLogFile, "[" . now() . "] Starting single vocabulary TTS for lesson {$lesson->id} | vocab_id={$vocabulary->id} word='{$vocabulary->english_word}'\n", FILE_APPEND);
                try {
                    $this->generateVocabularyAudio($vocabulary);
                    $processed = 1;
                    \Log::info("Successfully generated word TTS for '{$vocabulary->english_word}' (Vocabulary ID: {$vocabulary->id})");
                } catch (\Exception $e) {
                    $errorMsg = "Failed to generate TTS for '{$vocabulary->english_word}': " . $e->getMessage();
                    \Log::error($errorMsg);
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                    file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                    $errors[] = $errorMsg;
                }
            }

            // Count remaining items (items without audio_path)
            $totalRemaining = $lesson->vocabulary()
                ->where(function($query) {
                    $query->whereNull('word_audio_path')
                          ->orWhere('word_audio_path', '');
                })
                ->count();

            return response()->json([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'remaining' => max(0, $totalRemaining),
                'completed' => $totalRemaining <= 0
            ]);
        } finally {
            optional($lock)->release();
        }
    }
}