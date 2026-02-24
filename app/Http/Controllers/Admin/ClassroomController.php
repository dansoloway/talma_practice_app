<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    protected function currentOrganization(Request $request): ?Organization
    {
        return $request->attributes->get('currentOrganization');
    }

    /**
     * Classes the current user can manage (all if org_admin, else only teaching).
     */
    protected function manageableClasses(Organization $org)
    {
        $user = auth('admin')->user();
        if ($user->role === 'admin') {
            return $org->classes();
        }
        $orgUser = $org->users()->where('users.id', $user->id)->first();
        if ($orgUser?->pivot?->role === 'org_admin') {
            return $org->classes();
        }
        return $user->teachingClasses()->where('classrooms.organization_id', $org->id);
    }

    /**
     * Display a listing of classes.
     */
    public function index(Request $request)
    {
        $org = $this->currentOrganization($request);
        if (!$org) {
            return redirect()->route('admin.org.select');
        }
        $classes = $this->manageableClasses($org)
            ->withCount(['students', 'teachers', 'courses'])
            ->orderBy('name')
            ->get();
        return view('admin.classrooms.index', compact('org', 'classes'));
    }

    /**
     * Show the form for creating a class.
     */
    public function create(Request $request)
    {
        $org = $this->currentOrganization($request);
        if (!$org) {
            return redirect()->route('admin.org.select');
        }
        return view('admin.classrooms.create', compact('org'));
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request)
    {
        $org = $this->currentOrganization($request);
        if (!$org) {
            return redirect()->route('admin.org.select');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('classrooms', 'slug')->where('organization_id', $org->id),
            ],
        ]);
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            $base = $validated['slug'];
            $n = 1;
            while (Classroom::where('organization_id', $org->id)->where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $base . '-' . $n++;
            }
        }
        $org->classes()->create($validated);
        return redirect()->route('org.admin.classrooms.index', ['organization' => $org->slug])
            ->with('success', 'Class created successfully.');
    }

    /**
     * Display the specified class.
     */
    public function show(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $classroom->load(['students', 'teachers', 'courses']);
        $orgMembers = $organization->users()->get();
        $orgCourses = $organization->courses()->orderBy('title')->get();
        return view('admin.classrooms.show', compact('organization', 'classroom', 'orgMembers', 'orgCourses'));
    }

    /**
     * Show the form for editing the class.
     */
    public function edit(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        return view('admin.classrooms.edit', compact('organization', 'classroom'));
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('classrooms', 'slug')->where('organization_id', $organization->id)->ignore($classroom->id),
            ],
        ]);
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $classroom->update($validated);
        return redirect()->route('org.admin.classrooms.show', ['organization' => $organization->slug, 'classroom' => $classroom->slug])
            ->with('success', 'Class updated successfully.');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $classroom->delete();
        return redirect()->route('org.admin.classrooms.index', ['organization' => $organization->slug])
            ->with('success', 'Class deleted successfully.');
    }

    /**
     * Sync students for the class.
     */
    public function syncStudents(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $validated = $request->validate(['user_ids' => 'array', 'user_ids.*' => 'exists:users,id']);
        $userIds = $validated['user_ids'] ?? [];
        $classroom->students()->sync($userIds);
        return redirect()->back()->with('success', 'Students updated.');
    }

    /**
     * Sync teachers for the class.
     */
    public function syncTeachers(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $validated = $request->validate(['user_ids' => 'array', 'user_ids.*' => 'exists:users,id']);
        $userIds = $validated['user_ids'] ?? [];
        $classroom->teachers()->sync($userIds);
        return redirect()->back()->with('success', 'Teachers updated.');
    }

    /**
     * Sync courses for the class.
     */
    public function syncCourses(Request $request, Organization $organization, Classroom $classroom)
    {
        $this->authorizeClassroom($organization, $classroom);
        $validated = $request->validate(['course_ids' => 'array', 'course_ids.*' => 'exists:courses,id']);
        $courseIds = $validated['course_ids'] ?? [];
        foreach ($courseIds as $id) {
            if (!$organization->courses()->whereKey($id)->exists()) {
                return redirect()->back()->with('error', 'All courses must belong to this organization.');
            }
        }
        $classroom->courses()->sync($courseIds);
        return redirect()->back()->with('success', 'Courses updated.');
    }

    protected function authorizeClassroom(Organization $organization, Classroom $classroom): void
    {
        if ($classroom->organization_id !== $organization->id) {
            abort(404);
        }
        $user = auth('admin')->user();
        if ($user->role === 'admin') {
            return;
        }
        if ($organization->users()->where('users.id', $user->id)->whereIn('organization_user.role', ['org_admin'])->exists()) {
            return;
        }
        if (!$classroom->teachers()->where('users.id', $user->id)->exists()) {
            abort(403, 'You can only manage classes you teach.');
        }
    }
}
