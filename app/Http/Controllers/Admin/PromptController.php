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
     * Import prompts from CSV.
     */
    public function import(Request $request, Lesson $lesson)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $file = $request->file('csv_file');
            $csvData = array_map('str_getcsv', file($file->path()));
            
            // Remove header row if it exists
            $header = array_shift($csvData);
            
            $importedCount = 0;
            $errors = [];

            foreach ($csvData as $index => $row) {
                $rowNumber = $index + 2; // +2 because we removed header and arrays are 0-indexed
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Validate row has required columns
                if (count($row) < 2) {
                    $errors[] = "Row {$rowNumber}: Missing required columns. Expected: prompt_text, template, [tts_voice], [options...]";
                    continue;
                }

                $promptText = trim($row[0]);
                $template = trim($row[1]);
                $ttsVoice = isset($row[2]) && !empty(trim($row[2])) ? trim($row[2]) : 'default';

                // Validate required fields
                if (empty($promptText)) {
                    $errors[] = "Row {$rowNumber}: Prompt text is required";
                    continue;
                }

                if (empty($template)) {
                    $errors[] = "Row {$rowNumber}: Template is required";
                    continue;
                }

                // Check if template contains placeholder
                if (strpos($template, '{{answer}}') === false) {
                    $errors[] = "Row {$rowNumber}: Template must contain {{answer}} placeholder";
                    continue;
                }

                // Create the prompt
                $prompt = Prompt::create([
                    'lesson_id' => $lesson->id,
                    'prompt_text' => $promptText,
                    'template' => $template,
                    'tts_voice' => $ttsVoice,
                    'sort_order' => $importedCount + 1,
                ]);

                // Note: Parts are no longer used, prompts belong directly to lessons

                // Create options from remaining columns
                $optionColumns = array_slice($row, 3);
                $optionOrder = 1;

                foreach ($optionColumns as $optionText) {
                    $optionText = trim($optionText);
                    if (!empty($optionText)) {
                        $prompt->options()->create([
                            'label' => $optionText,
                            'image_path' => '', // Can be added later
                            'is_active' => true,
                            'sort_order' => $optionOrder++,
                        ]);
                    }
                }

                $importedCount++;
            }

            $message = "Successfully imported {$importedCount} prompts.";
            if (!empty($errors)) {
                $message .= " " . count($errors) . " errors occurred.";
            }

            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error importing CSV: ' . $e->getMessage())
                ->withInput();
        }
    }
}

