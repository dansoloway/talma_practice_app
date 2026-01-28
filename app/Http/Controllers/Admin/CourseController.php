<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    /**
     * Display a listing of all courses.
     */
    public function index(Request $request)
    {
        $query = Course::query();
        
        // Filter by search text (title, slug)
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('slug', 'LIKE', "%{$searchTerm}%");
            });
        }
        
        // Handle sorting - default to sort_order asc
        $sortBy = $request->get('sort_by', 'sort_order');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }
        
        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortDir);
                break;
            case 'updated_at':
                $query->orderBy('updated_at', $sortDir);
                break;
            default:
                $query->orderBy('sort_order', 'asc');
                break;
        }
        
        $courses = $query->withCount('lessons')->get();
        
        return view('admin.courses.index', compact('courses', 'sortBy', 'sortDir'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        return view('admin.courses.create');
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
            // Ensure uniqueness
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Course::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            $image = $request->file('cover_image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('courses', $filename, 'public');
            $validated['cover_image_path'] = 'courses/' . $filename;
        }

        $validated['is_active'] = $request->has('is_active');

        Course::create($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $course->load('lessons');
        return view('admin.courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified course.
     */
    public function edit(Course $course)
    {
        return view('admin.courses.edit', compact('course'));
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
            // Ensure uniqueness
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Course::where('slug', $validated['slug'])->where('id', '!=', $course->id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        // Handle cover image upload
        if ($request->hasFile('cover_image')) {
            // Delete old image if exists
            if ($course->cover_image_path) {
                Storage::disk('public')->delete($course->cover_image_path);
            }
            
            $image = $request->file('cover_image');
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('courses', $filename, 'public');
            $validated['cover_image_path'] = 'courses/' . $filename;
        }

        $validated['is_active'] = $request->has('is_active');

        $course->update($validated);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Course $course)
    {
        // Check if course has lessons
        if ($course->lessons()->count() > 0) {
            return redirect()->route('admin.courses.index')
                ->with('error', 'Cannot delete course with existing lessons. Please remove or reassign lessons first.');
        }

        // Delete cover image if exists
        if ($course->cover_image_path) {
            Storage::disk('public')->delete($course->cover_image_path);
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully.');
    }
}
