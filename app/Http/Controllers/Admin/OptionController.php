<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\Prompt;
use Illuminate\Http\Request;

class OptionController extends Controller
{
    /**
     * Show the form for creating a new option.
     */
    public function create(Prompt $prompt)
    {
        $prompt->load('lesson');
        
        return view('admin.options.create', compact('prompt'));
    }

    /**
     * Store a newly created option.
     */
    public function store(Request $request, Prompt $prompt)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:64',
            'option_type' => 'required|in:image,text',
            'option_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image_path' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['prompt_id'] = $prompt->id;

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('public/images/options', $filename);
            $validated['image_path'] = 'storage/images/options/' . $filename;
        } elseif (empty($validated['image_path'])) {
            $validated['image_path'] = '';
        }

        $option = Option::create($validated);

        return redirect()
            ->route('admin.prompts.show', $prompt)
            ->with('success', 'Option created successfully!');
    }

    /**
     * Show the form for editing the option.
     */
    public function edit(Option $option)
    {
        $option->load(['prompt.lesson']);
        
        return view('admin.options.edit', compact('option'));
    }

    /**
     * Update the specified option.
     */
    public function update(Request $request, Option $option)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:64',
            'option_type' => 'required|in:image,text',
            'option_text' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image_path' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            $path = $image->storeAs('public/images/options', $filename);
            $validated['image_path'] = 'storage/images/options/' . $filename;
        }

        $option->update($validated);

        return redirect()
            ->route('admin.prompts.show', $option->prompt_id)
            ->with('success', 'Option updated successfully!');
    }

    /**
     * Remove the specified option.
     */
    public function destroy(Option $option)
    {
        $promptId = $option->prompt_id;
        $option->delete();

        return redirect()
            ->route('admin.prompts.show', $promptId)
            ->with('success', 'Option deleted successfully!');
    }
}

