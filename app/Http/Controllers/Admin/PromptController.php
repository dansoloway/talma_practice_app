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
            'sort_order' => 'integer',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['tts_voice'] = $validated['tts_voice'] ?? 'default';

        $prompt = Prompt::create($validated);

        return redirect()
            ->route('admin.prompts.show', $prompt)
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
}

