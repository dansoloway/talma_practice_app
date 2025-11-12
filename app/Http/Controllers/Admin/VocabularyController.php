<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Services\ImageGeneration\FlaticonImageGenerator;
use App\Services\ImageGeneration\LeonardoImageGenerator;
use App\Services\ImageGeneration\OpenAiImageGenerator;
use App\Services\ImageGeneration\StockImageGenerator;
use App\Services\Translation\OpenAiTranslator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VocabularyController extends Controller
{
    protected $imageGenerator;

    public function __construct(
        protected OpenAiTranslator $translator
    ) {
        // Priority: Flaticon > Stock images (Unsplash/Pixabay) > Leonardo.ai > OpenAI
        $flaticonGenerator = app(FlaticonImageGenerator::class);
        $stockGenerator = app(StockImageGenerator::class);
        $leonardoGenerator = app(LeonardoImageGenerator::class);
        $openAiGenerator = app(OpenAiImageGenerator::class);
        
        $this->imageGenerator = $flaticonGenerator->enabled() 
            ? $flaticonGenerator 
            : ($stockGenerator->enabled() 
                ? $stockGenerator 
                : ($leonardoGenerator->enabled() 
                    ? $leonardoGenerator 
                    : $openAiGenerator));
    }

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

        $needsHebrew = empty($validated['hebrew_translation']);
        $needsArabic = empty($validated['arabic_translation']);

        if (($needsHebrew || $needsArabic) && $this->translator->enabled()) {
            $translations = $this->translator->translate($validated['english_word'], $needsHebrew, $needsArabic);
            if ($needsHebrew && !empty($translations['hebrew'])) {
                $validated['hebrew_translation'] = $translations['hebrew'];
            }
            if ($needsArabic && !empty($translations['arabic'])) {
                $validated['arabic_translation'] = $translations['arabic'];
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('images/vocabulary', $filename, 'public');
            $validated['image_path'] = 'images/vocabulary/' . $filename;
        }

        $vocabulary = Vocabulary::create($validated);
        
        // Automatic image generation is currently disabled
        // Users can upload images manually or use the "Generate Image" button
        // if (empty($validated['image_path']) && $this->imageGenerator->enabled()) {
        //     \Log::info("Generating image for vocabulary word: {$vocabulary->english_word} (ID: {$vocabulary->id})");
        //     $imagePath = $this->imageGenerator->generateVocabularyImage($vocabulary->english_word);
        //     if ($imagePath) {
        //         $vocabulary->update(['image_path' => $imagePath]);
        //     }
        // }
        
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
        
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:2048',
                'import_mode' => 'required|in:add,replace',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('CSV upload validation failed', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            
            // Return JSON for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
            
            return redirect()
                ->route('admin.lessons.vocabulary.csv.upload', $lesson)
                ->withErrors($e->errors())
                ->withInput();
        }

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
        $totalRows = count($csvData);

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

            $hebrew = isset($row[1]) ? trim($row[1]) : null;
            $arabic = isset($row[2]) ? trim($row[2]) : null;

            $needsHebrew = blank($hebrew);
            $needsArabic = blank($arabic);

            if (($needsHebrew || $needsArabic) && $this->translator->enabled()) {
                $translations = $this->translator->translate($englishWord, $needsHebrew, $needsArabic);
                if ($needsHebrew && !empty($translations['hebrew'])) {
                    $hebrew = $translations['hebrew'];
                } elseif ($needsHebrew) {
                    $errors[] = "Row " . ($index + 1) . ": Failed to translate '{$englishWord}' to Hebrew (may have hit rate limit).";
                }
                if ($needsArabic && !empty($translations['arabic'])) {
                    $arabic = $translations['arabic'];
                } elseif ($needsArabic) {
                    $errors[] = "Row " . ($index + 1) . ": Failed to translate '{$englishWord}' to Arabic (may have hit rate limit).";
                }
            } elseif (($needsHebrew || $needsArabic) && !$this->translator->enabled()) {
                $errors[] = "Row " . ($index + 1) . ": Missing translations for '{$englishWord}' and OpenAI key is not configured.";
            }

            try {
                $vocabulary = Vocabulary::create([
                    'lesson_id' => $lesson->id,
                    'english_word' => $englishWord,
                    'hebrew_translation' => $hebrew ?: null,
                    'arabic_translation' => $arabic ?: null,
                    'sort_order' => $imported + 1,
                    'is_active' => true,
                ]);
                
                // Automatic image generation is currently disabled
                // Users can upload images manually or use the "Generate Image" button
                // if ($this->imageGenerator->enabled()) {
                //     \Log::info("Generating image for vocabulary word: {$englishWord} (ID: {$vocabulary->id})");
                //     $imagePath = $this->imageGenerator->generateVocabularyImage($englishWord);
                //     if ($imagePath) {
                //         $vocabulary->update(['image_path' => $imagePath]);
                //     }
                // }
                
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

        // Return JSON for AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
                'redirect_url' => route('admin.lessons.vocabulary.index', $lesson),
            ]);
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
     * Generate or regenerate image for a vocabulary word.
     */
    public function generateImage(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        // Increase execution time for image generation (can take up to 5 minutes for Leonardo.ai polling)
        set_time_limit(300); // 5 minutes
        
        // Refresh image generator in case config changed
        $flaticonGenerator = app(FlaticonImageGenerator::class);
        $stockGenerator = app(StockImageGenerator::class);
        $leonardoGenerator = app(LeonardoImageGenerator::class);
        $openAiGenerator = app(OpenAiImageGenerator::class);
        
        $imageGenerator = $flaticonGenerator->enabled() 
            ? $flaticonGenerator 
            : ($stockGenerator->enabled() 
                ? $stockGenerator 
                : ($leonardoGenerator->enabled() 
                    ? $leonardoGenerator 
                    : $openAiGenerator));
        
        if (!$imageGenerator->enabled()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image service configured. Please set FLATICON_API_KEY, UNSPLASH_ACCESS_KEY, PIXABAY_API_KEY, LEONARDO_API_KEY, or OPENAI_API_KEY in your .env file.',
                ], 400);
            }
            
            return redirect()
                ->route('admin.lessons.vocabulary.index', $lesson)
                ->with('error', 'No image service configured.');
        }

        // Delete old image if exists
        if ($vocabulary->image_path && Storage::disk('public')->exists($vocabulary->image_path)) {
            Storage::disk('public')->delete($vocabulary->image_path);
        }

        \Log::info("Generating image for vocabulary word: {$vocabulary->english_word} (ID: {$vocabulary->id})");
        $imagePath = $imageGenerator->generateVocabularyImage($vocabulary->english_word);

        if ($imagePath) {
            $vocabulary->update(['image_path' => $imagePath]);
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image generated successfully!',
                    'image_url' => $vocabulary->image_url,
                ]);
            }
            
            return redirect()
                ->route('admin.lessons.vocabulary.index', $lesson)
                ->with('success', 'Image generated successfully!');
        } else {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate image. Please check logs for details.',
                ], 500);
            }
            
            return redirect()
                ->route('admin.lessons.vocabulary.index', $lesson)
                ->with('error', 'Failed to generate image. Please check logs for details.');
        }
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
                    'stability' => 0.65,
                    'similarity_boost' => 0.6,
                    'style' => 0.2,
                    'speed' => 0.85,
                    'use_speaker_boost' => false,
                ]
            ]);

            if ($response->successful()) {
                $filename = 'vocabulary_' . time() . '_' . uniqid() . '.mp3';
                // Use tts/vocabulary/ instead of vocabulary-audio/ to inherit working permissions from tts directory
                $relativePath = 'tts/vocabulary/' . $filename;
                $fullPath = storage_path("app/public/{$relativePath}");
                
                \Log::info("Saving vocabulary audio file to: {$fullPath}");
                file_put_contents($ttsLogFile, "[" . now() . "] Attempting to save file to: {$fullPath}\n", FILE_APPEND);
                
                // Create directory if needed (same as prompts do)
                $dir = dirname($fullPath);
                if (!file_exists($dir)) {
                    \Log::info("Creating directory: {$dir}");
                    file_put_contents($ttsLogFile, "[" . now() . "] Creating directory: {$dir}\n", FILE_APPEND);
                    
                    $mkdirResult = @mkdir($dir, 0755, true);
                    if (!$mkdirResult && !file_exists($dir)) {
                        // Directory creation failed and still doesn't exist
                        $error = error_get_last();
                        $errorMsg = "Failed to create directory {$dir}: " . ($error['message'] ?? 'Unknown error');
                        \Log::error($errorMsg);
                        file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                        throw new \Exception($errorMsg);
                    } elseif (!$mkdirResult && file_exists($dir)) {
                        // Directory creation "failed" but directory actually exists (race condition or permission issue)
                        \Log::info("Directory creation returned false but directory exists: {$dir}");
                        file_put_contents($ttsLogFile, "[" . now() . "] Directory exists despite mkdir failure: {$dir}\n", FILE_APPEND);
                    } else {
                        \Log::info("Successfully created directory: {$dir}");
                        file_put_contents($ttsLogFile, "[" . now() . "] Successfully created directory: {$dir}\n", FILE_APPEND);
                    }
                } else {
                    \Log::info("Directory already exists: {$dir}");
                    file_put_contents($ttsLogFile, "[" . now() . "] Directory already exists: {$dir}\n", FILE_APPEND);
                }
                
                // Check if directory is writable
                if (!is_writable($dir)) {
                    $errorMsg = "Directory is not writable: {$dir}";
                    \Log::error($errorMsg);
                    file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                    throw new \Exception($errorMsg);
                }
                
                $audioData = $response->body();
                \Log::info("Audio data size: " . strlen($audioData) . " bytes");
                file_put_contents($ttsLogFile, "[" . now() . "] Audio data size: " . strlen($audioData) . " bytes\n", FILE_APPEND);
                
                $saved = @file_put_contents($fullPath, $audioData);
                if ($saved === false) {
                    $error = error_get_last();
                    $errorMsg = "Failed to save audio file to {$fullPath}: " . ($error['message'] ?? 'Unknown error');
                    \Log::error($errorMsg);
                    file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                    throw new \Exception($errorMsg);
                }
                
                \Log::info("Successfully saved audio file, bytes written: {$saved}");
                file_put_contents($ttsLogFile, "[" . now() . "] Successfully saved audio file, bytes written: {$saved}\n", FILE_APPEND);
                
                // Verify file was actually written and is readable
                if (!file_exists($fullPath) || !is_readable($fullPath)) {
                    $errorMsg = "File was written but is not readable or doesn't exist: {$fullPath}";
                    \Log::error($errorMsg);
                    file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                    throw new \Exception($errorMsg);
                }
                
                $fileSize = filesize($fullPath);
                \Log::info("Verified file exists, size: {$fileSize} bytes");
                file_put_contents($ttsLogFile, "[" . now() . "] Verified file exists, size: {$fileSize} bytes\n", FILE_APPEND);
                
                // Store path with /storage/ prefix like prompts do for consistency
                // Only update database if file was successfully written and verified
                $vocabulary->update(['word_audio_path' => "/storage/{$relativePath}"]);
                
                $successMsg = "Successfully generated and verified audio for: '{$vocabulary->english_word}' (path: /storage/{$relativePath}, size: {$fileSize} bytes)";
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
            
            // Create dedicated TTS log file (needed for logging in selection logic)
            $ttsLogFile = storage_path('logs/tts_generation.log');
            
            // Get vocabulary items that need audio generation
            // Priority: 1) No path, 2) Path exists but file missing, 3) Force recreate (all items)
            $vocabulary = null;
            
            if ($forceRecreate) {
                // Force recreate: process all items, starting with oldest
                // Check if item already has audio in new location (tts/vocabulary/)
                $vocabulary = $lesson->vocabulary()
                    ->orderBy('id')
                    ->get()
                    ->first(function($vocab) use ($ttsLogFile) {
                        if (!$vocab->word_audio_path) {
                            file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: No path, needs generation\n", FILE_APPEND);
                            return true; // No path, needs generation
                        }
                        // Normalize path: remove /storage/ or storage/ prefix if present
                        $originalPath = $vocab->word_audio_path;
                        $relativePath = $originalPath;
                        // Remove leading slash if present
                        $relativePath = ltrim($relativePath, '/');
                        // Remove storage/ prefix (handles both /storage/ and storage/)
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        
                        file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Original path='{$originalPath}', normalized='{$relativePath}'\n", FILE_APPEND);
                        
                        // If path is in old location (vocabulary-audio/), regenerate it
                        if (strpos($relativePath, 'vocabulary-audio/') === 0) {
                            file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Old location '{$relativePath}', needs migration\n", FILE_APPEND);
                            return true; // Old location, migrate to new location
                        }
                        // If path is in new location (tts/vocabulary/), check if file exists
                        if (strpos($relativePath, 'tts/vocabulary/') === 0) {
                            $exists = \Storage::disk('public')->exists($relativePath);
                            if ($exists) {
                                file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: File exists in new location '{$relativePath}', SKIPPING\n", FILE_APPEND);
                                return false; // File exists, skip it
                            } else {
                                file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Path in new location but file missing '{$relativePath}', needs generation\n", FILE_APPEND);
                                return true; // File missing, needs generation
                            }
                        }
                        // For any other path format, regenerate it
                        file_put_contents($ttsLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Unknown path format '{$relativePath}' (original: '{$originalPath}'), regenerating\n", FILE_APPEND);
                        return true;
                    });
            } else {
                // Normal mode: only process items without audio or where file is missing
                $vocabulary = $lesson->vocabulary()
                    ->where(function($query) {
                        $query->whereNull('word_audio_path')
                              ->orWhere('word_audio_path', '');
                    })
                    ->orderBy('id')
                    ->first();
                
                // If no items without path, check for missing files
                if (!$vocabulary) {
                    $vocabulary = $lesson->vocabulary()
                        ->orderBy('id')
                        ->get()
                        ->first(function($vocab) {
                            if (!$vocab->word_audio_path) {
                                return true;
                            }
                            // Check if file exists - handle both old and new path formats
                            $relativePath = str_replace('/storage/', '', ltrim($vocab->word_audio_path, '/'));
                            return !\Storage::disk('public')->exists($relativePath);
                        });
                }
            }

            $processed = 0;
            $errors = [];
            
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

            // Count remaining items
            if ($forceRecreate) {
                // Count items that need regeneration (old location or missing files)
                $totalRemaining = $lesson->vocabulary()
                    ->get()
                    ->filter(function($vocab) {
                        if (!$vocab->word_audio_path) {
                            return true; // No path, needs generation
                        }
                        // Normalize path: remove /storage/ or storage/ prefix if present
                        $relativePath = $vocab->word_audio_path;
                        // Remove leading slash if present
                        $relativePath = ltrim($relativePath, '/');
                        // Remove storage/ prefix (handles both /storage/ and storage/)
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        
                        // If in old location, needs migration
                        if (strpos($relativePath, 'vocabulary-audio/') === 0) {
                            return true;
                        }
                        // If in new location (tts/vocabulary/), only count if file doesn't exist
                        if (strpos($relativePath, 'tts/vocabulary/') === 0) {
                            return !\Storage::disk('public')->exists($relativePath);
                        }
                        // For any other path format, regenerate it
                        return true;
                    })
                    ->count();
            } else {
                // Count items without audio_path or where file doesn't exist
                $totalRemaining = $lesson->vocabulary()
                    ->get()
                    ->filter(function($vocab) {
                        if (!$vocab->word_audio_path) {
                            return true; // No path, needs generation
                        }
                        // Check if file actually exists
                        $relativePath = str_replace('/storage/', '', ltrim($vocab->word_audio_path, '/'));
                        return !\Storage::disk('public')->exists($relativePath);
                    })
                    ->count();
            }

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

    /**
     * View TTS generation logs.
     */
    public function viewLogs(Request $request)
    {
        $lines = (int) $request->input('lines', 100); // Default to last 100 lines
        $lines = min($lines, 1000); // Cap at 1000 lines
        
        $logFile = storage_path('logs/tts_generation.log');
        $logContent = '';
        
        if (file_exists($logFile)) {
            // Read last N lines from log file
            $file = new \SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key() + 1;
            
            $startLine = max(0, $totalLines - $lines);
            
            $logLines = [];
            $file->seek($startLine);
            while (!$file->eof()) {
                $line = $file->current();
                if ($line !== false) {
                    $logLines[] = $line;
                }
                $file->next();
            }
            
            $logContent = implode('', $logLines);
        } else {
            $logContent = 'Log file not found: ' . $logFile;
        }
        
        return view('admin.vocabulary.logs', compact('logContent', 'lines'));
    }
}