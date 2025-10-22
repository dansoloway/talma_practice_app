<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Part;
use Illuminate\Http\Request;

class PartController extends Controller
{
    /**
     * Display a listing of parts for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $parts = $lesson->parts()->ordered()->get();
        
        return view('admin.parts.index', compact('lesson', 'parts'));
    }

    /**
     * Show the form for creating a new part.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.parts.create', compact('lesson'));
    }

    /**
     * Store a newly created part.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $part = Part::create($validated);

        return redirect()
            ->route('admin.lessons.parts.show', [$lesson, $part])
            ->with('success', 'Part created successfully!');
    }

    /**
     * Display the specified part with its prompts.
     */
    public function show(Lesson $lesson, Part $part)
    {
        $part->load('prompts');
        
        return view('admin.parts.show', compact('lesson', 'part'));
    }

    /**
     * Show the form for editing the part.
     */
    public function edit(Lesson $lesson, Part $part)
    {
        return view('admin.parts.edit', compact('lesson', 'part'));
    }

    /**
     * Update the specified part.
     */
    public function update(Request $request, Lesson $lesson, Part $part)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $part->update($validated);

        return redirect()
            ->route('admin.lessons.parts.show', [$lesson, $part])
            ->with('success', 'Part updated successfully!');
    }

    /**
     * Remove the specified part.
     */
    public function destroy(Lesson $lesson, Part $part)
    {
        $part->delete();

        return redirect()
            ->route('admin.lessons.parts.index', $lesson)
            ->with('success', 'Part deleted successfully!');
    }
}