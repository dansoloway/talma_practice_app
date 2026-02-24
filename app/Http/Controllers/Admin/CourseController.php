<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    /**
     * Get the current organization from request (when in org-scoped route).
     */
    protected function currentOrganization(Request $request): ?Organization
    {
        return $request->attributes->get('currentOrganization');
    }

    /**
     * Resolve course for org-scoped routes; return 404 if not attached to org.
     */
    protected function resolveCourseForOrg(Request $request, int|Course $courseIdOrModel): Course
    {
        $org = $this->currentOrganization($request);
        if (!$org) {
            return $courseIdOrModel instanceof Course ? $courseIdOrModel : Course::findOrFail($courseIdOrModel);
        }
        $id = $courseIdOrModel instanceof Course ? $courseIdOrModel->id : $courseIdOrModel;
        return $org->courses()->whereKey($id)->firstOrFail();
    }

    /**
     * Get the courses index route (org-scoped or legacy).
     */
    protected function coursesIndexRoute(Request $request): string
    {
        $org = $this->currentOrganization($request);
        return $org
            ? route('org.admin.courses.index', ['organization' => $org->slug])
            : route('admin.courses.index');
    }

    /**
     * Display a listing of all courses.
     */
    public function index(Request $request)
    {
        // Determine if we should show archived courses
        $showArchived = $request->boolean('view_archived');
        
        $org = $this->currentOrganization($request);
        $query = $org
            ? $org->courses()->getQuery()
            : Course::query();
        
        if ($showArchived) {
            // Show only archived courses
            $query->whereNotNull('archived_at');
        } else {
            // Show only non-archived courses
            $query->whereNull('archived_at');
        }
        
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
        $courseRouteParams = $org ? ['organization' => $org->slug] : [];

        return view('admin.courses.index', compact('courses', 'sortBy', 'sortDir', 'showArchived', 'courseRouteParams'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create(Request $request)
    {
        $org = $this->currentOrganization($request);
        $courseRouteParams = $org ? ['organization' => $org->slug] : [];
        return view('admin.courses.create', compact('courseRouteParams'));
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

        $course = Course::create($validated);

        $org = $this->currentOrganization($request);
        if ($org) {
            $org->courses()->syncWithoutDetaching([$course->id]);
        }

        return redirect()->to($this->coursesIndexRoute($request))
            ->with('success', 'Course created successfully.');
    }

    /**
     * Display the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function show(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        // Load only active, non-archived lessons
        $course->load(['lessons' => function ($query) {
            $query->where('is_active', true)->whereNull('archived_at');
        }]);
        $org = $this->currentOrganization($request);
        $courseRouteParams = $org ? ['organization' => $org->slug] : [];
        return view('admin.courses.show', compact('course', 'courseRouteParams'));
    }

    /**
     * Show the form for editing the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function edit(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        $org = $this->currentOrganization($request);
        $courseRouteParams = $org ? ['organization' => $org->slug] : [];
        return view('admin.courses.edit', compact('course', 'courseRouteParams'));
    }

    /**
     * Update the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function update(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:courses,slug,' . $course->id,
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
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

        return redirect()->to($this->coursesIndexRoute($request))
            ->with('success', 'Course updated successfully.');
    }

    /**
     * Remove the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function destroy(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        // Check if course has lessons
        if ($course->lessons()->count() > 0) {
            return redirect()->to($this->coursesIndexRoute($request))
                ->with('error', 'Cannot delete course with existing lessons. Please remove or reassign lessons first.');
        }

        // Delete cover image if exists
        if ($course->cover_image_path) {
            Storage::disk('public')->delete($course->cover_image_path);
        }

        $course->delete();

        return redirect()->to($this->coursesIndexRoute($request))
            ->with('success', 'Course deleted successfully.');
    }

    /**
     * Archive the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function archive(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        $course->archive();

        return redirect()->back()
            ->with('success', 'Course archived successfully.');
    }

    /**
     * Unarchive the specified course.
     * Org-scoped routes pass (Request, Organization, Course); legacy routes pass (Request, Course).
     */
    public function unarchive(Request $request, $organizationOrCourse = null, Course $course = null)
    {
        $course = $this->resolveCourseForOrg($request, $course ?? $organizationOrCourse);
        $course->unarchive();

        return redirect()->back()
            ->with('success', 'Course unarchived successfully.');
    }

    /**
     * Toggle org-wide access for a course (org-scoped only).
     */
    public function toggleOrgWide(Request $request, Organization $organization, Course $course)
    {
        $course = $organization->courses()->whereKey($course->id)->firstOrFail();
        $current = (bool) $course->pivot->is_org_wide;
        $organization->courses()->updateExistingPivot($course->id, ['is_org_wide' => !$current]);

        return redirect()->back()
            ->with('success', $current ? 'Course is now class-only.' : 'Course is now org-wide.');
    }
}
