<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Prompt;
use Illuminate\Http\Request;

class PromptController extends Controller
{
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
        $prompt->load('lesson');
        
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
            'sort_order' => 'integer',
        ]);

        $validated['tts_voice'] = $validated['tts_voice'] ?? 'default';

        $prompt->update($validated);

        return redirect()
            ->route('admin.prompts.show', $prompt)
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

                // Extract options
                $optionColumns = array_slice($row, 2);
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

                $previewData[] = [
                    'row_number' => $rowNumber,
                    'prompt_text' => $promptText,
                    'template' => $template,
                    'options' => $options,
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

            return view('admin.prompts.preview', compact('lesson', 'previewData', 'validationErrors', 'importMode'));

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
            
            // If replace mode, delete existing prompts and their options
            if ($importMode === 'replace') {
                $lesson->prompts()->delete(); // This will cascade delete options too
            }
            
            $importedCount = 0;
            $createdOptions = [];

            foreach ($data as $item) {
                // Create the prompt
                $prompt = Prompt::create([
                    'lesson_id' => $lesson->id,
                    'prompt_text' => $item['prompt_text'],
                    'template' => $item['template'],
                    'tts_voice' => 'default',
                    'sort_order' => $importedCount + 1,
                ]);

                // Create options
                $optionOrder = 1;
                foreach ($item['options'] as $optionText) {
                    $option = $prompt->options()->create([
                        'label' => $optionText,
                        'image_path' => '', // Can be added later
                        'is_active' => true,
                        'sort_order' => $optionOrder++,
                    ]);
                    
                    // Collect options for TTS generation
                    $createdOptions[] = $option;
                }

                $importedCount++;
            }

            // Clear session data
            session()->forget('csv_preview_data');

            $action = $importMode === 'replace' ? 'replaced with' : 'imported';
            $message = "Successfully {$action} {$importedCount} prompts. TTS generation will continue in the background.";

            // Return JSON response for AJAX handling
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'lesson_id' => $lesson->id,
                    'total_options' => count($createdOptions),
                    'total_sentences' => $importedCount * 4 // Assuming 4 options per prompt on average
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
            ['Prompt Text', 'Template', 'Option 1', 'Option 2', 'Option 3', 'Option 4'],
            ['What rolled the farthest?', 'The {} rolled the farthest', 'ball', 'cube', 'cylinder', 'sphere'],
            ['What object is the softest?', 'The {} is the softest', 'cotton', 'sponge', 'fabric', 'pillow'],
            ['What object is the hardest?', 'The {} is the hardest', 'rock', 'metal', 'wood', 'glass'],
            ['What absorbs the most water?', 'The {} absorbs the most water', 'sponge', 'paper towel', 'cloth', 'cotton'],
            ['What absorbs the least water?', 'The {} absorbs the least water', 'plastic', 'metal', 'glass', 'rubber'],
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
     * Generate TTS audio for options using ElevenLabs API.
     */
    private function generateTtsForOptions($options)
    {
        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            \Log::warning('ELEVENLABS_API_KEY not found, skipping TTS generation');
            return;
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice

        foreach ($options as $option) {
            try {
                // Call ElevenLabs API
                $response = \Http::withHeaders([
                    'xi-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                    'text' => $option->label,
                    'model_id' => 'eleven_monolingual_v1',
                    'voice_settings' => [
                        'stability' => 0.5,
                        'similarity_boost' => 0.75,
                    ]
                ]);
                
                if ($response->successful()) {
                    // Save the audio file
                    $filename = "word_o{$option->id}.mp3";
                    $relativePath = "tts/words/{$filename}";
                    $fullPath = storage_path("app/public/{$relativePath}");
                    
                    // Create directory if needed
                    $dir = dirname($fullPath);
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    file_put_contents($fullPath, $response->body());
                    
                    // Update option with audio path
                    $option->update(['word_audio_path' => "/storage/{$relativePath}"]);
                    
                    \Log::info("Generated TTS for option: {$option->label}");
                } else {
                    \Log::error("TTS API Error for option {$option->label}: " . $response->status());
                }
                
                // Rate limiting
                usleep(200000); // 0.2 seconds
                
            } catch (\Exception $e) {
                \Log::error("TTS Error for option {$option->label}: " . $e->getMessage());
            }
        }
    }

    /**
     * Generate TTS audio for all sentence combinations (prompt template + each option).
     */
    private function generateSentenceAudio($lesson)
    {
        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            \Log::warning('ELEVENLABS_API_KEY not found, skipping sentence TTS generation');
            return;
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice

        // Get all prompts with their options for this lesson
        $prompts = $lesson->prompts()->with('options')->get();

        foreach ($prompts as $prompt) {
            foreach ($prompt->options as $option) {
                try {
                    // Create the complete sentence
                    $completeSentence = str_replace('{}', $option->label, $prompt->template);
                    
                    // Call ElevenLabs API
                    $response = \Http::withHeaders([
                        'xi-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
                        'text' => $completeSentence,
                        'model_id' => 'eleven_monolingual_v1',
                        'voice_settings' => [
                            'stability' => 0.5,
                            'similarity_boost' => 0.75,
                        ]
                    ]);
                    
                    if ($response->successful()) {
                        // Save the audio file with a unique name
                        $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
                        $relativePath = "tts/sentences/{$filename}";
                        $fullPath = storage_path("app/public/{$relativePath}");
                        
                        // Create directory if needed
                        $dir = dirname($fullPath);
                        if (!file_exists($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        
                        file_put_contents($fullPath, $response->body());
                        
                        // Store the sentence audio path in the option (we'll add a new field for this)
                        $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
                        
                        \Log::info("Generated sentence TTS: {$completeSentence}");
                    } else {
                        \Log::error("Sentence TTS API Error for '{$completeSentence}': " . $response->status());
                    }
                    
                    // Small delay to avoid rate limiting
                    usleep(50000); // 0.05 seconds
                    
                } catch (\Exception $e) {
                    \Log::error("Sentence TTS generation failed for '{$completeSentence}': " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Generate TTS for word options via AJAX.
     */
    public function generateWordTts(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'batch_size' => 'integer|min:1|max:10',
            'offset' => 'integer|min:0'
        ]);

        $batchSize = $validated['batch_size'] ?? 5;
        $offset = $validated['offset'] ?? 0;

        // Get options that need word TTS
        $options = $lesson->prompts()
            ->with('options')
            ->get()
            ->pluck('options')
            ->flatten()
            ->whereNull('word_audio_path')
            ->skip($offset)
            ->take($batchSize);

        $processed = 0;
        $errors = [];
        
        \Log::info("Starting word TTS batch generation for lesson {$lesson->id} - Batch size: {$batchSize}, Offset: {$offset}");
        \Log::info("Processing " . $options->count() . " options in this batch");

        foreach ($options as $index => $option) {
            try {
                \Log::info("Processing word TTS {$index + 1}/{$options->count()} in batch: '{$option->label}' (Option ID: {$option->id})");
                $this->generateSingleWordTts($option);
                $processed++;
                \Log::info("Successfully generated word TTS for '{$option->label}'");
            } catch (\Exception $e) {
                $errorMsg = "Failed to generate TTS for '{$option->label}': " . $e->getMessage();
                \Log::error($errorMsg);
                \Log::error("Stack trace: " . $e->getTraceAsString());
                $errors[] = $errorMsg;
            }
        }

        $totalRemaining = $lesson->prompts()
            ->with('options')
            ->get()
            ->pluck('options')
            ->flatten()
            ->whereNull('word_audio_path')
            ->count() - $processed;

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'remaining' => max(0, $totalRemaining),
            'completed' => $totalRemaining <= 0
        ]);
    }

    /**
     * Generate TTS for sentence combinations via AJAX.
     */
    public function generateSentenceTts(Request $request, Lesson $lesson)
    {
        \Log::info("Starting sentence TTS generation for lesson {$lesson->id}");
        
        $validated = $request->validate([
            'batch_size' => 'integer|min:1|max:5',
            'offset' => 'integer|min:0'
        ]);

        $batchSize = $validated['batch_size'] ?? 3;
        $offset = $validated['offset'] ?? 0;

        // Get options that need sentence TTS
        $options = $lesson->prompts()
            ->with('options')
            ->get()
            ->pluck('options')
            ->flatten()
            ->whereNull('sentence_audio_path')
            ->skip($offset)
            ->take($batchSize);

        $processed = 0;
        $errors = [];
        
        \Log::info("Starting sentence TTS batch generation for lesson {$lesson->id} - Batch size: {$batchSize}, Offset: {$offset}");
        \Log::info("Processing " . $options->count() . " options in this batch");

        foreach ($options as $index => $option) {
            try {
                \Log::info("Processing sentence TTS {$index + 1}/{$options->count()} in batch: '{$option->label}' (Option ID: {$option->id})");
                $this->generateSingleSentenceTts($option);
                $processed++;
                \Log::info("Successfully generated sentence TTS for '{$option->label}'");
            } catch (\Exception $e) {
                $errorMsg = "Failed to generate sentence TTS for '{$option->label}': " . $e->getMessage();
                \Log::error($errorMsg);
                \Log::error("Stack trace: " . $e->getTraceAsString());
                $errors[] = $errorMsg;
            }
        }

        $totalRemaining = $lesson->prompts()
            ->with('options')
            ->get()
            ->pluck('options')
            ->flatten()
            ->whereNull('sentence_audio_path')
            ->count() - $processed;

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'errors' => $errors,
            'remaining' => max(0, $totalRemaining),
            'completed' => $totalRemaining <= 0
        ]);
    }

    /**
     * Generate TTS for a single word option.
     */
    private function generateSingleWordTts($option)
    {
        \Log::info("Starting TTS generation for word: '{$option->label}' (Option ID: {$option->id})");
        
        // Check if audio already exists for this option
        if ($option->word_audio_path) {
            $fullPath = public_path(ltrim($option->word_audio_path, '/'));
            \Log::info("Checking existing audio path: {$fullPath}");
            if (file_exists($fullPath)) {
                \Log::info("Word TTS already exists for option: {$option->label}");
                return; // Skip generation
            } else {
                \Log::warning("Audio path exists in DB but file not found: {$fullPath}");
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
                
                \Log::info("Reused existing TTS for word: {$option->label}");
                return; // Skip API generation
            }
        }

        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            \Log::error('ELEVENLABS_API_KEY not found in environment');
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice
        \Log::info("Making API call to ElevenLabs for word: '{$option->label}'");

        $response = \Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $option->label,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        \Log::info("API response status: " . $response->status());
        if (!$response->successful()) {
            \Log::error("API error response: " . $response->body());
        }
        
        if ($response->successful()) {
            // Save the audio file
            $filename = "word_o{$option->id}.mp3";
            $relativePath = "tts/words/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            \Log::info("Saving audio file to: {$fullPath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                \Log::info("Creating directory: {$dir}");
                mkdir($dir, 0755, true);
            }
            
            $audioData = $response->body();
            \Log::info("Audio data size: " . strlen($audioData) . " bytes");
            
            $saved = file_put_contents($fullPath, $audioData);
            if ($saved === false) {
                \Log::error("Failed to save audio file to: {$fullPath}");
                throw new \Exception("Failed to save audio file");
            }
            
            \Log::info("Successfully saved audio file, bytes written: {$saved}");
            
            // Update option with audio path
            $dbPath = "/storage/{$relativePath}";
            \Log::info("Updating option {$option->id} with audio path: {$dbPath}");
            $option->update(['word_audio_path' => $dbPath]);
            
            \Log::info("Generated TTS for option: {$option->label}");
        } else {
            \Log::error("TTS API Error: " . $response->status() . " - " . $response->body());
            throw new \Exception("TTS API Error: " . $response->status());
        }
    }

    /**
     * Generate TTS for a single sentence combination.
     */
    private function generateSingleSentenceTts($option)
    {
        // Get the prompt and complete sentence
        $prompt = $option->prompt;
        $completeSentence = str_replace('{}', $option->label, $prompt->template);

        // Check if sentence audio already exists for this option
        if ($option->sentence_audio_path) {
            $fullPath = public_path(ltrim($option->sentence_audio_path, '/'));
            if (file_exists($fullPath)) {
                \Log::info("Sentence TTS already exists: {$completeSentence}");
                return; // Skip generation
            }
        }

        // Check if we already have TTS for this exact sentence from another option
        // This happens when the same word is used in the same template
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
                
                \Log::info("Reused existing sentence TTS: {$completeSentence}");
                return; // Skip API generation
            }
        }

        $apiKey = env('ELEVENLABS_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('ELEVENLABS_API_KEY not found');
        }

        $voiceId = 'EXAVITQu4vr4xnSDxMaL'; // Rachel voice

        // Get the prompt for this option
        $prompt = $option->prompt;
        $completeSentence = str_replace('{}', $option->label, $prompt->template);

        $response = \Http::withHeaders([
            'xi-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $completeSentence,
            'model_id' => 'eleven_monolingual_v1',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
            ]
        ]);
        
        if ($response->successful()) {
            // Save the audio file with a unique name
            $filename = "sentence_p{$prompt->id}_o{$option->id}.mp3";
            $relativePath = "tts/sentences/{$filename}";
            $fullPath = storage_path("app/public/{$relativePath}");
            
            // Create directory if needed
            $dir = dirname($fullPath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            file_put_contents($fullPath, $response->body());
            
            // Store the sentence audio path in the option
            $option->update(['sentence_audio_path' => "/storage/{$relativePath}"]);
            
            \Log::info("Generated sentence TTS: {$completeSentence}");
        } else {
            throw new \Exception("Sentence TTS API Error: " . $response->status());
        }
    }
}

