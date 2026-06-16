<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Concerns\GuardsRootCourseContent;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\SpellingGame;
use App\Models\Vocabulary;
use App\Services\ImageGeneration\ImageGeneratorService;
use App\Services\Translation\OpenAiTranslator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VocabularyController extends Controller
{
    use GuardsRootCourseContent;

    public function __construct(
        protected OpenAiTranslator $translator,
        protected ElevenLabsTtsService $ttsService,
        protected ImageGeneratorService $imageGenerator,
    ) {
    }

    /**
     * Display a listing of vocabulary for a lesson.
     */
    public function index(Lesson $lesson)
    {
        // For review lessons, get vocabulary from source lessons
        if ($lesson->is_review) {
            $vocabulary = $lesson->getVocabularyForGames();
        } else {
            $vocabulary = $lesson->vocabulary()->ordered()->get();
        }
        
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
        $this->guardRootCourseContent($lesson);
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
            // Use secure filename generation to prevent directory traversal and other attacks
            $filename = \App\Services\FileUploadSecurity::generateSecureFilename($image, 'vocab');
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
     * Store multiple vocabulary items from bulk paste (one word per line).
     */
    public function bulkStore(Request $request, Lesson $lesson)
    {
        $request->validate([
            'words' => 'required|string',
        ]);

        // Split by newlines and clean up
        $words = array_filter(
            array_map('trim', explode("\n", $request->input('words'))),
            function($word) {
                return !empty($word);
            }
        );

        if (empty($words)) {
            return redirect()
                ->route('admin.lessons.vocabulary.index', $lesson)
                ->with('error', 'No valid words found. Please enter at least one word.');
        }

        // Get the highest sort_order for this lesson
        $maxSortOrder = $lesson->vocabulary()->max('sort_order') ?? -1;
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($words as $word) {
            // Skip if word already exists for this lesson
            $exists = $lesson->vocabulary()
                ->where('english_word', $word)
                ->exists();

            if ($exists) {
                $skippedCount++;
                continue;
            }

            $maxSortOrder++;

            $vocabularyData = [
                'lesson_id' => $lesson->id,
                'english_word' => $word,
                'sort_order' => $maxSortOrder,
                'is_active' => true,
            ];

            // Auto-translate if translator is enabled
            if ($this->translator->enabled()) {
                $translations = $this->translator->translate($word, true, true);
                if (!empty($translations['hebrew'])) {
                    $vocabularyData['hebrew_translation'] = $translations['hebrew'];
                }
                if (!empty($translations['arabic'])) {
                    $vocabularyData['arabic_translation'] = $translations['arabic'];
                }
            }

            $vocabulary = Vocabulary::create($vocabularyData);

            // Generate image automatically if image generator is enabled
            if (empty($vocabulary->image_path) && $this->imageGenerator->enabled()) {
                \Log::info("Auto-generating image for vocabulary word: {$vocabulary->english_word} (ID: {$vocabulary->id})");
                try {
                    $imagePath = $this->imageGenerator->generateVocabularyImage($vocabulary->english_word);
                    if ($imagePath) {
                        $vocabulary->update(['image_path' => $imagePath]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to auto-generate image for '{$vocabulary->english_word}': " . $e->getMessage());
                }
            }

            // Generate audio for the new vocabulary word
            $this->generateVocabularyAudio($vocabulary);

            $createdCount++;
        }

        // Create games automatically if new vocabulary was created
        if ($createdCount > 0) {
            $this->createGamesForLesson($lesson);
        }

        $message = "Successfully created {$createdCount} vocabulary word(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} word(s) were skipped because they already exist.";
        }

        return redirect()
            ->route('admin.lessons.vocabulary.index', $lesson)
            ->with('success', $message);
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
        $this->guardRootCourseContent($lesson);
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
            // Use secure filename generation to prevent directory traversal and other attacks
            $filename = \App\Services\FileUploadSecurity::generateSecureFilename($image, 'vocab');
            $path = $image->storeAs('images/vocabulary', $filename, 'public');
            $validated['image_path'] = 'images/vocabulary/' . $filename;
        }

        $vocabulary->update($validated);

        // Handle JSON requests (for inline editing)
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Translation updated successfully',
                'vocabulary' => [
                    'hebrew_translation' => $vocabulary->hebrew_translation,
                    'arabic_translation' => $vocabulary->arabic_translation,
                ]
            ]);
        }

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
            // Skip header row if it exists (check for common header variations)
            if ($index === 0 && (
                strtolower(trim($row[0] ?? '')) === 'word' || 
                strtolower(trim($row[0] ?? '')) === 'english_word' || 
                strtolower(trim($row[0] ?? '')) === 'english' ||
                strtolower(trim($row[0] ?? '')) === 'english word'
            )) {
                continue;
            }

            // Only process the first column (English word)
            if (empty($row[0])) {
                continue;
            }

            $englishWord = trim($row[0]);
            
            // Validate that it's only English characters (allow spaces, hyphens, apostrophes for phrases)
            if (!preg_match('/^[a-zA-Z\s\-\']+$/', $englishWord)) {
                $errors[] = "Row " . ($index + 1) . ": '{$englishWord}' contains non-English characters. Only English words are allowed.";
                continue;
            }
            
            // Check for duplicate within the CSV
            if (in_array(strtolower($englishWord), $processedWords)) {
                $errors[] = "Row " . ($index + 1) . ": Duplicate word '{$englishWord}' found in CSV";
                continue;
            }
            
            // Check for duplicate in existing database (only in add mode)
            if ($importMode === 'add') {
                $existingWord = $lesson->vocabulary()->where('english_word', $englishWord)->first();
                if ($existingWord) {
                    $errors[] = "Row " . ($index + 1) . ": Word '{$englishWord}' already exists. Use 'Replace' mode to replace all vocabulary.";
                    continue;
                }
            }

            // Only English words in CSV - translations will be generated automatically
            $hebrew = null;
            $arabic = null;
            
            // Automatically translate if translator is enabled
            if ($this->translator->enabled()) {
                $translations = $this->translator->translate($englishWord, true, true);
                if (!empty($translations['hebrew'])) {
                    $hebrew = $translations['hebrew'];
                }
                if (!empty($translations['arabic'])) {
                    $arabic = $translations['arabic'];
                }
            }
            
            try {
                $vocabulary = Vocabulary::create([
                    'lesson_id' => $lesson->id,
                    'english_word' => $englishWord,
                    'hebrew_translation' => $hebrew,
                    'arabic_translation' => $arabic,
                    'sort_order' => $imported + 1,
                    'is_active' => true,
                ]);
                
                // Generate image automatically if image generator is enabled
                if ($this->imageGenerator->enabled()) {
                    \Log::info("Auto-generating image for vocabulary word: {$englishWord} (ID: {$vocabulary->id})");
                    try {
                        $imagePath = $this->imageGenerator->generateVocabularyImage($englishWord);
                        if ($imagePath) {
                            $vocabulary->update(['image_path' => $imagePath]);
                        }
                    } catch (\Exception $e) {
                        \Log::error("Failed to auto-generate image for '{$englishWord}': " . $e->getMessage());
                    }
                }
                
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

        // Create games automatically if vocabulary was imported
        if ($imported > 0) {
            $this->createGamesForLesson($lesson);
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
        $csvContent = "air pollution\n";
        $csvContent .= "water pollution\n";
        $csvContent .= "soil pollution\n";
        $csvContent .= "recycle\n";
        $csvContent .= "environment\n";

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
        // Use secure filename generation to prevent directory traversal and other attacks
        $filename = \App\Services\FileUploadSecurity::generateSecureFilename($image, 'vocab');
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
        
        $imageGenerator = $this->imageGenerator;

        if (!$imageGenerator->enabled()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image service configured. Please set FREEPIK_API_KEY, FLATICON_API_KEY, UNSPLASH_ACCESS_KEY, PIXABAY_API_KEY, LEONARDO_API_KEY, or OPENAI_API_KEY in your .env file.',
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
     * Generate images for all vocabulary items (bulk operation)
     */
    public function generateImages(Request $request, Lesson $lesson)
    {
        // Per-lesson lock to prevent overlapping requests
        $lock = \Illuminate\Support\Facades\Cache::lock('lesson:' . $lesson->id . ':vocab_images', 5);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'processed' => 0,
                'errors' => [],
                'remaining' => $lesson->vocabulary()->whereNull('image_path')->count(),
                'completed' => false,
                'locked' => true,
            ]);
        }

        try {
            // Always process exactly 1 item to avoid duplicates/repeats
            $forceRecreate = $request->input('force', false);
            
            // Create dedicated image generation log file
            $imageLogFile = storage_path('logs/image_generation.log');
            
            $imageGenerator = $this->imageGenerator;

            if (!$imageGenerator->enabled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No image service configured.',
                    'processed' => 0,
                    'remaining' => 0,
                    'completed' => true,
                ], 400);
            }
            
            // Get vocabulary items that need image generation
            // Priority: 1) No path, 2) Path exists but file missing, 3) Force recreate (all items)
            $vocabulary = null;
            
            if ($forceRecreate) {
                // Force recreate: process all items, starting with oldest
                // Process items that don't have valid files first, then regenerate existing ones
                $vocabulary = $lesson->vocabulary()
                    ->orderBy('id')
                    ->get()
                    ->first(function($vocab) use ($imageLogFile) {
                        if (!$vocab->image_path) {
                            file_put_contents($imageLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: No path, needs generation\n", FILE_APPEND);
                            return true; // No path, needs generation
                        }
                        
                        // Normalize path: remove /storage/ or storage/ prefix if present
                        $originalPath = $vocab->image_path;
                        $relativePath = $originalPath;
                        // Remove leading slash if present
                        $relativePath = ltrim($relativePath, '/');
                        // Remove storage/ prefix (handles both /storage/ and storage/)
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        
                        file_put_contents($imageLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Original path='{$originalPath}', normalized='{$relativePath}'\n", FILE_APPEND);
                        
                        // Check if file actually exists
                        $exists = Storage::disk('public')->exists($relativePath);
                        if (!$exists) {
                            file_put_contents($imageLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Path set but file missing '{$relativePath}', needs generation\n", FILE_APPEND);
                            return true; // File missing, needs generation
                        } else {
                            // File exists - in force recreate mode, we'll regenerate it, but prioritize items without files first
                            file_put_contents($imageLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: File exists '{$relativePath}', will regenerate later\n", FILE_APPEND);
                            return false; // Skip for now, process items without files first
                        }
                    });
                
                // If all items have files, start regenerating from the beginning
                if (!$vocabulary) {
                    $vocabulary = $lesson->vocabulary()
                        ->orderBy('id')
                        ->first();
                    if ($vocabulary) {
                        file_put_contents($imageLogFile, "[" . now() . "] All items have files, starting regeneration from vocab_id={$vocabulary->id}\n", FILE_APPEND);
                    }
                }
            } else {
                // Normal mode: only process items without images or where file is missing
                $vocabulary = $lesson->vocabulary()
                    ->where(function($query) {
                        $query->whereNull('image_path')
                              ->orWhere('image_path', '');
                    })
                    ->orderBy('id')
                    ->first();
                
                // If no items without path, check for missing files
                if (!$vocabulary) {
                    $vocabulary = $lesson->vocabulary()
                        ->orderBy('id')
                        ->get()
                        ->first(function($vocab) use ($imageLogFile) {
                            if (!$vocab->image_path) {
                                return true;
                            }
                            // Check if file exists - handle path normalization
                            $relativePath = $vocab->image_path;
                            $relativePath = ltrim($relativePath, '/');
                            $relativePath = preg_replace('#^storage/#', '', $relativePath);
                            
                            $exists = Storage::disk('public')->exists($relativePath);
                            if (!$exists) {
                                file_put_contents($imageLogFile, "[" . now() . "] Checking vocab_id={$vocab->id}: Path set but file missing '{$relativePath}', needs generation\n", FILE_APPEND);
                            }
                            return !$exists;
                        });
                }
            }

            $processed = 0;
            $errors = [];
            
            if ($vocabulary) {
                file_put_contents($imageLogFile, "[" . now() . "] Starting single vocabulary image generation for lesson {$lesson->id} | vocab_id={$vocabulary->id} word='{$vocabulary->english_word}'\n", FILE_APPEND);
                try {
                    // Delete old image if exists (for force recreate or if file is missing)
                    if ($vocabulary->image_path) {
                        $relativePath = $vocabulary->image_path;
                        $relativePath = ltrim($relativePath, '/');
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        
                        if (Storage::disk('public')->exists($relativePath)) {
                            Storage::disk('public')->delete($relativePath);
                            file_put_contents($imageLogFile, "[" . now() . "] Deleted old image file: {$relativePath}\n", FILE_APPEND);
                        }
                    }
                    
                    \Log::info("Bulk generating image for vocabulary word: {$vocabulary->english_word} (ID: {$vocabulary->id})");
                    file_put_contents($imageLogFile, "[" . now() . "] Calling image generator for '{$vocabulary->english_word}'\n", FILE_APPEND);
                    
                    $imagePath = $imageGenerator->generateVocabularyImage($vocabulary->english_word);
                    
                    if ($imagePath) {
                        // Verify the file was actually created
                        $relativePath = ltrim($imagePath, '/');
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        
                        if (Storage::disk('public')->exists($relativePath)) {
                            $vocabulary->update(['image_path' => $imagePath]);
                            $processed = 1;
                            $fileSize = Storage::disk('public')->size($relativePath);
                            $successMsg = "Successfully generated image for '{$vocabulary->english_word}' (path: {$imagePath}, size: {$fileSize} bytes)";
                            \Log::info($successMsg);
                            file_put_contents($imageLogFile, "[" . now() . "] SUCCESS: {$successMsg}\n", FILE_APPEND);
                        } else {
                            $errorMsg = "Image generator returned path but file doesn't exist: {$imagePath}";
                            \Log::error($errorMsg);
                            file_put_contents($imageLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                            $errors[] = "Failed to generate image for '{$vocabulary->english_word}': File not found";
                        }
                    } else {
                        $errorMsg = "Image generator returned null for '{$vocabulary->english_word}'";
                        \Log::warning($errorMsg);
                        file_put_contents($imageLogFile, "[" . now() . "] WARNING: {$errorMsg}\n", FILE_APPEND);
                        $errors[] = "Failed to generate image for '{$vocabulary->english_word}'";
                    }
                } catch (\Exception $e) {
                    $errorMsg = "Failed to generate image for '{$vocabulary->english_word}': " . $e->getMessage();
                    \Log::error($errorMsg);
                    \Log::error("Stack trace: " . $e->getTraceAsString());
                    file_put_contents($imageLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                    file_put_contents($imageLogFile, "[" . now() . "] Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
                    $errors[] = $errorMsg;
                }
            } else {
                file_put_contents($imageLogFile, "[" . now() . "] No vocabulary items found that need image generation\n", FILE_APPEND);
            }

            // Count remaining items
            if ($forceRecreate) {
                // For force recreate: count all items that still need processing
                // After processing one, remaining = total - 1 (since we process sequentially)
                $total = $lesson->vocabulary()->count();
                // Count items that have been successfully processed (have valid files)
                $processedCount = $lesson->vocabulary()
                    ->get()
                    ->filter(function($vocab) {
                        if (!$vocab->image_path) {
                            return false; // No path, not processed yet
                        }
                        $relativePath = $vocab->image_path;
                        $relativePath = ltrim($relativePath, '/');
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        return Storage::disk('public')->exists($relativePath);
                    })
                    ->count();
                // Remaining = total - processed (but we just processed one, so subtract that)
                $remaining = max(0, $total - $processedCount);
            } else {
                // Count items without images or where file is missing
                $remaining = $lesson->vocabulary()
                    ->get()
                    ->filter(function($vocab) {
                        if (!$vocab->image_path) {
                            return true;
                        }
                        $relativePath = $vocab->image_path;
                        $relativePath = ltrim($relativePath, '/');
                        $relativePath = preg_replace('#^storage/#', '', $relativePath);
                        return !Storage::disk('public')->exists($relativePath);
                    })
                    ->count();
            }
            
            return response()->json([
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'remaining' => $remaining,
                'completed' => $remaining <= 0,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Generate or regenerate TTS for a single vocabulary word
     */
    public function generateSingleTts(Request $request, Lesson $lesson, Vocabulary $vocabulary)
    {
        try {
            // Get custom settings from request if provided
            $customSettings = $request->input('settings');
            $this->generateVocabularyAudio($vocabulary, $customSettings);
            
            return response()->json([
                'success' => true,
                'message' => "TTS generated successfully for '{$vocabulary->english_word}'",
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to generate TTS for vocabulary {$vocabulary->id}: " . $e->getMessage());
            
            // Extract user-friendly error message from API errors
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'invalid_voice_settings') !== false || strpos($errorMessage, 'Invalid setting') !== false) {
                $errorMessage = 'Invalid TTS settings. Please check that speed is between 0.7 and 1.2, and other values are within valid ranges.';
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate TTS: ' . $errorMessage,
            ], 500);
        }
    }

    /**
     * Generate TTS audio for a vocabulary word
     */
    private function generateVocabularyAudio(Vocabulary $vocabulary, ?array $customSettings = null)
    {
        if (!$this->ttsService->enabled()) {
            $errorMsg = 'ELEVENLABS_API_KEY not found, skipping audio generation for: ' . $vocabulary->english_word;
            \Log::warning($errorMsg);
            // Also log to TTS log file
            $ttsLogFile = storage_path('logs/tts_generation.log');
            file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
            return;
        }

        // Create dedicated TTS log file entry
        $ttsLogFile = storage_path('logs/tts_generation.log');
        $settingsInfo = $customSettings ? json_encode($customSettings) : 'using defaults';
        file_put_contents($ttsLogFile, "[" . now() . "] Starting TTS generation for vocabulary word: '{$vocabulary->english_word}' (ID: {$vocabulary->id}) with settings: {$settingsInfo}\n", FILE_APPEND);

        try {
            // Always delete old audio file when regenerating (force regeneration)
            $oldAudioPath = $vocabulary->word_audio_path;
            
            // Use centralized TTS service method that handles everything
            // Custom settings are passed as parameter to this method
            $result = $this->ttsService->generateAndSaveVocabulary(
                $vocabulary->english_word,
                $oldAudioPath, // Old path to delete if regenerating
                null, // Use default voice
                $customSettings // Pass custom settings if provided (null = use defaults)
            );

            if ($result !== null) {
                // Update vocabulary with new audio path
                $vocabulary->update(['word_audio_path' => $result['path']]);
                
                $successMsg = "Successfully generated and verified audio for: '{$vocabulary->english_word}' (path: {$result['path']}, size: {$result['size']} bytes)";
                \Log::info($successMsg);
                file_put_contents($ttsLogFile, "[" . now() . "] SUCCESS: {$successMsg}\n", FILE_APPEND);
            } else {
                $errorMsg = "TTS generation failed for '{$vocabulary->english_word}': Service returned null";
                \Log::error($errorMsg);
                file_put_contents($ttsLogFile, "[" . now() . "] ERROR: {$errorMsg}\n", FILE_APPEND);
                throw new \Exception($errorMsg);
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
                // Force recreate: process ALL items sequentially, starting with oldest
                // Use cache to track the last processed ID for this lesson
                $cacheKey = 'lesson:' . $lesson->id . ':tts_last_processed_id';
                
                // Reset cache if requested (first call of a new batch)
                if ($request->input('reset', false)) {
                    \Cache::forget($cacheKey);
                    file_put_contents($ttsLogFile, "[" . now() . "] Force recreate mode: Starting new batch, cache cleared\n", FILE_APPEND);
                }
                
                $lastProcessedId = \Cache::get($cacheKey, 0);
                
                // Get the next item to process (ID greater than last processed)
                $vocabulary = $lesson->vocabulary()
                    ->where('id', '>', $lastProcessedId)
                    ->orderBy('id')
                    ->first();
                
                // If no items found with ID > last processed, we're done!
                if (!$vocabulary) {
                    // Clear cache and return completed
                    \Cache::forget($cacheKey);
                    file_put_contents($ttsLogFile, "[" . now() . "] Force recreate mode: All items processed, starting from beginning\n", FILE_APPEND);
                    
                    return response()->json([
                        'success' => true,
                        'processed' => 0,
                        'errors' => [],
                        'remaining' => 0,
                        'completed' => true
                    ]);
                }
                
                file_put_contents($ttsLogFile, "[" . now() . "] Force recreate mode: Processing vocab_id={$vocabulary->id} word='{$vocabulary->english_word}' (last processed: {$lastProcessedId})\n", FILE_APPEND);
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
                    // Pass custom settings if provided
                    $customSettings = $request->input('settings');
                    $this->generateVocabularyAudio($vocabulary, $customSettings);
                    $processed = 1;
                    \Log::info("Successfully generated word TTS for '{$vocabulary->english_word}' (Vocabulary ID: {$vocabulary->id})");
                    
                    // Update cache with last processed ID (only in force recreate mode)
                    if ($forceRecreate) {
                        $cacheKey = 'lesson:' . $lesson->id . ':tts_last_processed_id';
                        \Cache::put($cacheKey, $vocabulary->id, 3600); // Store for 1 hour
                    }
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
                // In force recreate mode, we need to process ALL items sequentially
                // Count how many items come AFTER the one we just processed (by ID)
                $total = $lesson->vocabulary()->count();
                
                if ($vocabulary) {
                    // Count items with ID greater than the one we just processed
                    $remainingCount = $lesson->vocabulary()
                        ->where('id', '>', $vocabulary->id)
                        ->count();
                    // Add 1 if current item failed (so it needs to be retried)
                    if ($processed === 0 && empty($errors)) {
                        // Item was processed successfully, so remaining is items after it
                        $totalRemaining = $remainingCount;
                    } else {
                        // Item failed or wasn't found, include current item in remaining
                        $totalRemaining = $remainingCount + ($vocabulary ? 1 : 0);
                    }
                } else {
                    // No vocabulary found to process
                    $totalRemaining = 0;
                }
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

    /**
     * Create matching, flashcard, and spelling games automatically for a lesson
     * using all available vocabulary.
     */
    private function createGamesForLesson(Lesson $lesson)
    {
        // Get all active vocabulary IDs for this lesson (or from source lessons if review)
        $vocabularyIds = $lesson->getVocabularyForGames()->pluck('id')->toArray();
        
        if (empty($vocabularyIds) || count($vocabularyIds) < 2) {
            \Log::info("Skipping game creation for lesson {$lesson->id}: insufficient vocabulary (need at least 2 words)");
            return;
        }

        try {
            // Create Matching Game if none exists
            if ($lesson->matchingGames()->count() === 0) {
                $matchingGameTitle = trim(sprintf('%s Matching Game 1', $lesson->title));
                
                MatchingGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => $matchingGameTitle,
                    'vocabulary_ids' => $vocabularyIds,
                    'is_active' => true,
                ]);
                \Log::info("Created matching game for lesson {$lesson->id}");
            }

            // Create Flashcard Game if none exists
            if ($lesson->flashcardGames()->count() === 0) {
                $flashcardGameTitle = trim(sprintf('%s Flashcards 1', $lesson->title));
                
                // Determine game types based on vocabulary assets
                $missingImages = Vocabulary::whereIn('id', $vocabularyIds)
                    ->where(function($q){ $q->whereNull('image_path')->orWhere('image_path', ''); })
                    ->count();
                $missingAudio = Vocabulary::whereIn('id', $vocabularyIds)
                    ->whereNull('word_audio_path')
                    ->count();
                
                $gameTypes = [];
                if ($missingImages > 0 && $missingAudio > 0) {
                    $gameTypes = [];
                } elseif ($missingImages > 0) {
                    $gameTypes = ['audio_to_word'];
                } elseif ($missingAudio > 0) {
                    $gameTypes = ['image_to_word'];
                } else {
                    $gameTypes = ['image_to_word', 'audio_to_word'];
                }
                
                FlashcardGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => $flashcardGameTitle,
                    'vocabulary_ids' => $vocabularyIds,
                    'game_types' => $gameTypes,
                    'cards_per_game' => min(10, count($vocabularyIds)),
                    'is_active' => true,
                ]);
                \Log::info("Created flashcard game for lesson {$lesson->id}");
            }

            // Create Spelling Game if none exists
            if ($lesson->spellingGames()->count() === 0) {
                $spellingGameTitle = trim($lesson->title . ' Spelling Practice 1');
                
                SpellingGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => $spellingGameTitle,
                    'vocabulary_ids' => $vocabularyIds,
                    'difficulty' => 'medium',
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
                \Log::info("Created spelling game for lesson {$lesson->id}");
            }

        } catch (\Exception $e) {
            \Log::error("Failed to create games for lesson {$lesson->id}: " . $e->getMessage());
            // Don't throw - allow vocabulary upload to succeed even if game creation fails
        }
    }
}