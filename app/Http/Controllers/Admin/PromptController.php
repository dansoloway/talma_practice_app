<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Prompt;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Http\Request;
// (intentionally using fully qualified facade names inline to satisfy static analysis)

class PromptController extends Controller
{
    use \App\Http\Controllers\Admin\GeneratesTtsAudio;
    
    public function __construct(
        protected ElevenLabsTtsService $ttsService
    ) {
    }
    
    /**
     * List all prompts for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $prompts = $lesson->prompts()->with('options')->orderBy('sort_order')->get();
        return view('admin.prompts.index', compact('lesson', 'prompts'));
    }
    /**
     * Show the form for creating a new prompt.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.prompts.create', compact('lesson'));
    }

    /**
     * Store a newly created prompt.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'prompt_text' => 'required|string|max:255',
            'template' => 'required|string|max:255',
            'tts_voice' => 'nullable|string|max:64',
            'part_id' => 'nullable|exists:parts,id',
            'sort_order' => 'integer',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['tts_voice'] = $validated['tts_voice'] ?? 'default';
        
        // Note: Parts are no longer used, activities belong directly to lessons

        $prompt = Prompt::create($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Prompt created successfully!');
    }

    /**
     * Display the specified prompt with its options.
     */
    public function show(Prompt $prompt)
    {
        $prompt->load(['lesson', 'options']);
        
        return view('admin.prompts.show', compact('prompt'));
    }

    /**
     * Show the form for editing the prompt.
     */
    public function edit(Prompt $prompt)
    {
        $prompt->load(['lesson', 'options']);
        
        return view('admin.prompts.edit', compact('prompt'));
    }

    /**
     * Update the specified prompt.
     */
    public function update(Request $request, Prompt $prompt)
    {
        $validated = $request->validate([
            'prompt_text' => 'required|string|max:255',
            'template' => 'required|string|max:255',
            'tts_voice' => 'nullable|string|max:64',
            'correct_answer' => 'nullable|integer|min:1',
            'sort_order' => 'integer',
        ]);

        $validated['tts_voice'] = $validated['tts_voice'] ?? 'default';

        // Ensure correct_answer does not exceed options count
        if (isset($validated['correct_answer'])) {
            $optionsCount = $prompt->options()->count();
            if ($validated['correct_answer'] > $optionsCount) {
                unset($validated['correct_answer']);
            }
        }

        // Check if template changed - if so, regenerate all sentence TTS
        $templateChanged = isset($validated['template']) && $prompt->template !== $validated['template'];
        $needsTtsRegeneration = $templateChanged;

        $prompt->update($validated);

        // If template changed, regenerate sentence TTS for all options
        if ($needsTtsRegeneration) {
            $prompt->load('options');
            foreach ($prompt->options as $option) {
                try {
                    $this->generateSingleSentenceTts($option);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to regenerate sentence TTS for option {$option->id}: " . $e->getMessage());
                }
            }
        }

        return redirect()
            ->route('admin.lessons.prompts.index', $prompt->lesson)
            ->with('success', 'Prompt updated successfully!');
    }

    /**
     * Remove the specified prompt.
     */
    public function destroy(Prompt $prompt)
    {
        $lessonId = $prompt->lesson_id;
        $prompt->delete();

        return redirect()
            ->route('admin.lessons.show', $lessonId)
            ->with('success', 'Prompt deleted successfully!');
    }

    /**
     * Show the CSV import form for prompts.
     */
    public function showImport(Lesson $lesson)
    {
        return view('admin.prompts.import', compact('lesson'));
    }

    /**
     * Preview CSV data before importing.
     */
    public function previewCsv(Request $request, Lesson $lesson)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
            'import_mode' => 'required|in:add,replace',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->path()));
            $importMode = $request->input('import_mode');
            
            // Remove header row if it exists
            $header = array_shift($csvData);
            
            $previewData = [];
            $validationErrors = [];
            $warningMessages = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validate row has required columns
                if (count($row) < 3) {
                    $validationErrors[] = "Row {$rowNumber}: Missing required columns. Expected: prompt_text, template, option1, [option2...]";
                    continue;
                }

                $promptText = trim($row[0]);
                $template = trim($row[1]);

                // Validate required fields
                if (empty($promptText)) {
                    $validationErrors[] = "Row {$rowNumber}: Prompt text is required";
                    continue;
                }

                if (empty($template)) {
                    $validationErrors[] = "Row {$rowNumber}: Template is required";
                    continue;
                }

                // Check if template contains placeholder
                if (strpos($template, '{}') === false) {
                    $validationErrors[] = "Row {$rowNumber}: Template must contain {} placeholder";
                    continue;
                }

                // Extract options and correct answer
                $optionColumns = array_slice($row, 2);
                $correctAnswer = null;
                
                // Check if the last column is a number (correct answer)
                if (count($optionColumns) > 4 && is_numeric(trim(end($optionColumns)))) {
                    $correctAnswer = (int) trim(array_pop($optionColumns));
                }
                
                $options = [];
                foreach ($optionColumns as $optionText) {
                    $optionText = trim($optionText);
                    if (!empty($optionText)) {
                        $options[] = $optionText;
                    }
                }

                if (empty($options)) {
                    $validationErrors[] = "Row {$rowNumber}: At least one option is required";
                    continue;
                }

                // Validate correct answer if provided
                if ($correctAnswer !== null && ($correctAnswer < 1 || $correctAnswer > count($options))) {
                    $validationErrors[] = "Row {$rowNumber}: Correct answer must be between 1 and " . count($options);
                    continue;
                }

                // Skip duplicates within the CSV (do not block import)
                $isDuplicate = false;
                foreach ($previewData as $existingItem) {
                    if ($existingItem['template'] === $template) {
                        $isDuplicate = true;
                        break;
                    }
                }
                if ($isDuplicate) {
                    $warningMessages[] = "Row {$rowNumber}: Duplicate template in CSV skipped ('{$template}').";
                    continue;
                }

                // Skip rows that already exist in DB (both modes) without blocking import
                $existingPrompt = $lesson->prompts()->where('template', $template)->first();
                if ($existingPrompt) {
                    $warningMessages[] = "Row {$rowNumber}: Template already exists in database, skipped ('{$template}').";
                    continue;
                }

                $previewData[] = [
                    'row_number' => $rowNumber,
                    'prompt_text' => $promptText,
                    'template' => $template,
                    'options' => $options,
                    'correct_answer' => $correctAnswer,
                    'generated_sentences' => array_map(function($option) use ($template) {
                        return str_replace('{}', $option, $template);
                    }, $options)
                ];
            }

            // Store CSV data in session for the actual import
            session(['csv_preview_data' => [
                'lesson_id' => $lesson->id,
                'import_mode' => $importMode,
                'data' => $previewData
            ]]);

            return view('admin.prompts.preview', compact('lesson', 'previewData', 'validationErrors', 'warningMessages', 'importMode'));

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error processing CSV: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Import prompts from CSV after preview confirmation.
     */
    public function confirmImport(Request $request, Lesson $lesson)
    {
        // Increase execution time for TTS generation
        set_time_limit(300); // 5 minutes
        
        // Get preview data from session
        $previewData = session('csv_preview_data');
        
        if (!$previewData || $previewData['lesson_id'] != $lesson->id) {
            return redirect()
                ->route('admin.lessons.prompts.import', $lesson)
                ->with('error', 'Preview data not found. Please upload your CSV again.');
        }

        try {
            $importMode = $previewData['import_mode'];
            $data = $previewData['data'];
            
            \Illuminate\Support\Facades\Log::info("Starting CSV import for lesson {$lesson->id} in {$importMode} mode");
            \Illuminate\Support\Facades\Log::info("Data items to import: " . count($data));
            
            // If replace mode, delete existing prompts and their options
            if ($importMode === 'replace') {
                \Illuminate\Support\Facades\Log::info("Deleting existing prompts for lesson {$lesson->id}");
                $lesson->prompts()->delete(); // This will cascade delete options too
            }
            
            $importedCount = 0;
            $createdOptions = [];

            foreach ($data as $index => $item) {
                \Illuminate\Support\Facades\Log::info("Creating prompt " . ($index + 1) . ": '{$item['prompt_text']}'");
                
                // Create the prompt
                $prompt = Prompt::create([
                    'lesson_id' => $lesson->id,
                    'prompt_text' => $item['prompt_text'],
                    'template' => $item['template'],
                    'tts_voice' => 'default',
                    'correct_answer' => $item['correct_answer'] ?? null,
                    'sort_order' => $importedCount + 1,
                ]);
                
                \Illuminate\Support\Facades\Log::info("Created prompt ID: {$prompt->id}");

                // Create options
                $optionOrder = 1;
                foreach ($item['options'] as $optionText) {
                    \Illuminate\Support\Facades\Log::info("Creating option: '{$optionText}'");
                    $option = $prompt->options()->create([
                        'label' => $optionText,
                        'image_path' => '', // Can be added later
                        'is_active' => true,
                        'sort_order' => $optionOrder++,
                    ]);
                    
                    \Illuminate\Support\Facades\Log::info("Created option ID: {$option->id}");
                    
                    // Collect options for TTS generation
                    $createdOptions[] = $option;
                }

                $importedCount++;
            }

            // Clear session data
            session()->forget('csv_preview_data');

            $action = $importMode === 'replace' ? 'replaced with' : 'imported';
            $message = "Successfully {$action} {$importedCount} prompts.";
            $startTts = (bool) $request->input('generate_tts', false);

            // Count how many options need word TTS and sentence TTS
            $totalOptions = count($createdOptions);
            $optionsNeedingWordTts = 0;
            $optionsNeedingSentenceTts = 0;
            
            foreach ($createdOptions as $option) {
                if (!$option->word_audio_path || !file_exists(public_path(ltrim($option->word_audio_path, '/')))) {
                    $optionsNeedingWordTts++;
                }
            }
            
            // Count sentence TTS needed (only for correct answers if set)
            $prompts = $lesson->prompts()->with('options')->get();
            foreach ($prompts as $prompt) {
                $options = $prompt->options->sortBy('sort_order')->values();
                $optionsToProcess = [];
                
                if ($prompt->correct_answer !== null && $prompt->correct_answer > 0) {
                    $correctIndex = $prompt->correct_answer - 1;
                    if (isset($options[$correctIndex])) {
                        $optionsToProcess[] = $options[$correctIndex];
                    }
                } else {
                    $optionsToProcess = $options->all();
                }
                
                foreach ($optionsToProcess as $option) {
                    if (!$option->sentence_audio_path || !file_exists(public_path(ltrim($option->sentence_audio_path, '/')))) {
                        $optionsNeedingSentenceTts++;
                    }
                }
            }

            // Return JSON response for AJAX handling
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'lesson_id' => $lesson->id,
                    'total_options' => $totalOptions,
                    'start_tts' => $startTts,
                    'options_needing_word_tts' => $optionsNeedingWordTts,
                    'options_needing_sentence_tts' => $optionsNeedingSentenceTts,
                    'redirect_url' => route('admin.lessons.manage', $lesson),
                ]);
            }

            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error importing CSV: ' . $e->getMessage());
        }
    }

    /**
     * Download a sample CSV template for prompts.
     */
    public function csvTemplate()
    {
        $csvData = [
            ['Prompt Text', 'Template', 'Option 1', 'Option 2', 'Option 3', 'Option 4', 'Correct'],
            ['What rolled the farthest?', 'The {} rolled the farthest', 'ball', 'cube', 'cylinder', 'sphere', '4'],
            ['What object is the softest?', 'The {} is the softest', 'cotton', 'sponge', 'fabric', 'pillow', '2'],
            ['What object is the hardest?', 'The {} is the hardest', 'rock', 'metal', 'wood', 'glass', '2'],
            ['What absorbs the most water?', 'The {} absorbs the most water', 'sponge', 'paper towel', 'cloth', 'cotton', '1'],
            ['What absorbs the least water?', 'The {} absorbs the least water', 'plastic', 'metal', 'glass', 'rubber', '4'],
        ];

        $filename = 'prompts_template.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate TTS audio for options using centralized TTS service.
     */
    private function generateTtsForOptions($options)
    {
        if (!$this->ttsService->enabled()) {
            \Illuminate\Support\Facades\Log::warning('ELEVENLABS_API_KEY not found, skipping TTS generation');
            return;
        }

        foreach ($options as $option) {
            try {
                // Check if audio already exists for this option
                if ($option->word_audio_path) {
                    $fullPath = public_path(ltrim($option->word_audio_path, '/'));
                    if (file_exists($fullPath)) {
                        \Illuminate\Support\Facades\Log::info("Word TTS already exists for option: {$option->label}, skipping");
                        continue; // Skip generation
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Audio path exists in DB but file not found: {$fullPath}, regenerating");
                    }
                }

                // Check if we already have TTS for this exact word from another option
                $existingOption = \App\Models\Option::where('label', $option->label)
                    ->whereNotNull('word_audio_path')
                    ->where('id', '!=', $option->id)
                    ->first();

                if ($existingOption && $existingOption->word_audio_path) {
                    $existingPath = public_path(ltrim($existingOption->word_audio_path, '/'));
                    if (file_exists($existingPath)) {
                        // Copy the existing file to a new location for this option
                        $filename = "word_o{$option->id}.mp3";
                        $relativePath = "tts/words/{$filename}";
                        $newPath = storage_path("app/public/{$relativePath}");
                        
                        // Create directory if needed
                        $dir = dirname($newPath);
                        if (!file_exists($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        
                        copy($existingPath, $newPath);
                        $option->update(['word_audio_path' => "/storage/{$relativePath}"]);
                        
                        \Illuminate\Support\Facades\Log::info("Reused existing TTS for word: {$option->label}");
                        continue; // Skip API generation
                    }
                }

                // Use centralized TTS service
                $result = $this->ttsService->generateAndSaveVocabulary(
                    $option->label,
                    $option->word_audio_path, // Old path to delete if regenerating
                    'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                );
                
                if ($result !== null) {
                    // Update option with audio path
                    $option->update(['word_audio_path' => $result['path']]);
                    \Illuminate\Support\Facades\Log::info("Generated TTS for option: {$option->label}");
                } else {
                    \Illuminate\Support\Facades\Log::error("TTS generation failed for option {$option->label}");
                }
                
                // Rate limiting
                usleep(200000); // 0.2 seconds
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("TTS Error for option {$option->label}: " . $e->getMessage());
            }
        }
    }

    /**
     * Generate TTS audio for sentence combinations (prompt template + option).
     * Only generates for the correct option if correct_answer is set, otherwise generates for all options.
     */
    private function generateSentenceAudio($lesson)
    {
        if (!$this->ttsService->enabled()) {
            \Illuminate\Support\Facades\Log::warning('ELEVENLABS_API_KEY not found, skipping sentence TTS generation');
            return;
        }

        // Get all prompts with their options for this lesson
        $prompts = $lesson->prompts()->with('options')->get();

        foreach ($prompts as $prompt) {
            // Get options ordered by sort_order (1-indexed)
            $options = $prompt->options->sortBy('sort_order')->values();
            
            // Determine which options to process
            $optionsToProcess = [];
            
            if ($prompt->correct_answer !== null && $prompt->correct_answer > 0) {
                // Only generate for the correct option (correct_answer is 1-indexed)
                $correctIndex = $prompt->correct_answer - 1;
                if (isset($options[$correctIndex])) {
                    $optionsToProcess[] = $options[$correctIndex];
                    \Illuminate\Support\Facades\Log::info("Prompt {$prompt->id}: Generating sentence TTS only for correct option #{$prompt->correct_answer}");
                } else {
                    \Illuminate\Support\Facades\Log::warning("Prompt {$prompt->id}: correct_answer ({$prompt->correct_answer}) is out of range, generating for all options");
                    $optionsToProcess = $options->all();
                }
            } else {
                // No correct answer set, generate for all options
                $optionsToProcess = $options->all();
                \Illuminate\Support\Facades\Log::info("Prompt {$prompt->id}: No correct_answer set, generating sentence TTS for all options");
            }
            
            foreach ($optionsToProcess as $option) {
                try {
                    // Create the complete sentence
                    $completeSentence = str_replace('{}', $option->label, $prompt->template);
                    
                    // Check if sentence audio already exists for this option
                    if ($option->sentence_audio_path) {
                        $fullPath = public_path(ltrim($option->sentence_audio_path, '/'));
                        if (file_exists($fullPath)) {
                            \Illuminate\Support\Facades\Log::info("Sentence TTS already exists: {$completeSentence}, skipping");
                            continue; // Skip generation
                        } else {
                            \Illuminate\Support\Facades\Log::warning("Audio path exists in DB but file not found: {$fullPath}, regenerating");
                        }
                    }

                    // Check if we already have TTS for this exact sentence from another option
                    $existingOption = \App\Models\Option::whereHas('prompt', function($query) use ($prompt) {
                            $query->where('template', $prompt->template);
                        })
                        ->where('label', $option->label)
                        ->whereNotNull('sentence_audio_path')
                        ->where('id', '!=', $option->id)
                        ->first();

                    if ($existingOption && $existingOption->sentence_audio_path) {
                        $existingPath = public_path(ltrim($existingOption->sentence_audio_path, '/'));
                        if (file_exists($existingPath)) {
                            // Copy the existing file to a new location for this option
                            $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
                            $relativePath = "tts/sentences/{$filename}";
                            $newPath = storage_path("app/public/{$relativePath}");
                            
                            // Create directory if needed
                            $dir = dirname($newPath);
                            if (!file_exists($dir)) {
                                mkdir($dir, 0755, true);
                            }
                            
                            copy($existingPath, $newPath);
                            $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
                            
                            \Illuminate\Support\Facades\Log::info("Reused existing sentence TTS: {$completeSentence}");
                            continue; // Skip API generation
                        }
                    }
                    
                    // Generate filename and path
                    $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
                    $relativePath = "tts/sentences/{$filename}";
                    
                    // Use centralized TTS service
                    $result = $this->ttsService->generateAndSaveSentence(
                        $completeSentence,
                        $relativePath,
                        $option->sentence_audio_path, // Old path to delete if regenerating
                        'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                    );
                    
                    if ($result !== null) {
                        // Store the sentence audio path in the option
                        $option->update(['sentence_audio_path' => $result['path']]);
                        \Illuminate\Support\Facades\Log::info("Generated sentence TTS: {$completeSentence}");
                    } else {
                        \Illuminate\Support\Facades\Log::error("Sentence TTS generation failed for '{$completeSentence}'");
                    }
                    
                    // Small delay to avoid rate limiting
                    usleep(50000); // 0.05 seconds
                    
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Sentence TTS generation failed for '{$completeSentence}': " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Generate TTS for word options via AJAX.
     */
    public function generateWordTts(Request $request, Lesson $lesson)
    {
        // Per-lesson lock to prevent overlapping requests
        $lock = \Illuminate\Support\Facades\Cache::lock('lesson:' . $lesson->id . ':word_tts', 5);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'processed' => 0,
                'errors' => [],
                'remaining' => \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) { $q->where('lesson_id', $lesson->id); })
                    ->whereNull('word_audio_path')
                    ->count(),
                'completed' => false,
                'locked' => true,
            ]);
        }

        try {
        // Always process exactly 1 item to avoid duplicates/repeats
        $option = \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) {
                $q->where('lesson_id', $lesson->id);
            })
            ->whereNull('word_audio_path')
            ->orderBy('id')
            ->first();

        $processed = 0;
        $errors = [];
        
        // Create dedicated TTS log file
        $ttsLogFile = storage_path('logs/tts_generation.log');
        if ($option) {
            file_put_contents($ttsLogFile, "[" . now() . "] Starting single word TTS for lesson {$lesson->id} | option_id={$option->id} label='{$option->label}'\n", FILE_APPEND);
            try {
                $this->generateSingleWordTts($option);
                $processed = 1;
                \Illuminate\Support\Facades\Log::info("Successfully generated word TTS for '{$option->label}' (Option ID: {$option->id})");
            } catch (\Exception $e) {
                $errorMsg = "Failed to generate TTS for '{$option->label}': " . $e->getMessage();
                \Illuminate\Support\Facades\Log::error($errorMsg);
                \Illuminate\Support\Facades\Log::error("Stack trace: " . $e->getTraceAsString());
                $errors[] = $errorMsg;
            }
        }

        $totalRemaining = \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) {
                $q->where('lesson_id', $lesson->id);
            })
            ->whereNull('word_audio_path')
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

    /**
     * Generate TTS for sentence combinations via AJAX.
     */
    public function generateSentenceTts(Request $request, Lesson $lesson)
    {
        \Illuminate\Support\Facades\Log::info("Starting sentence TTS generation for lesson {$lesson->id}");
        // Per-lesson lock to prevent overlapping requests
        $lock = \Illuminate\Support\Facades\Cache::lock('lesson:' . $lesson->id . ':sentence_tts', 5);
        if (!$lock->get()) {
            return response()->json([
                'success' => true,
                'processed' => 0,
                'errors' => [],
                'remaining' => \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) { $q->where('lesson_id', $lesson->id); })
                    ->whereNull('sentence_audio_path')
                    ->count(),
                'completed' => false,
                'locked' => true,
            ]);
        }

        try {
        // Always process exactly 1 item to avoid duplicates/repeats
        $option = \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) {
                $q->where('lesson_id', $lesson->id);
            })
            ->whereNull('sentence_audio_path')
            ->orderBy('id')
            ->first();

        $processed = 0;
        $errors = [];
        
        if ($option) {
            try {
                \Illuminate\Support\Facades\Log::info("Processing single sentence TTS for option '{$option->label}' (Option ID: {$option->id})");
                $this->generateSingleSentenceTts($option);
                $processed = 1;
                \Illuminate\Support\Facades\Log::info("Successfully generated sentence TTS for '{$option->label}'");
            } catch (\Exception $e) {
                $errorMsg = "Failed to generate sentence TTS for '{$option->label}': " . $e->getMessage();
                \Illuminate\Support\Facades\Log::error($errorMsg);
                \Illuminate\Support\Facades\Log::error("Stack trace: " . $e->getTraceAsString());
                $errors[] = $errorMsg;
            }
        }

        $totalRemaining = \App\Models\Option::whereHas('prompt', function ($q) use ($lesson) {
                $q->where('lesson_id', $lesson->id);
            })
            ->whereNull('sentence_audio_path')
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

