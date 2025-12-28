<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'teacher');

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search by name or email
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(20);

        return view('admin.teachers.index', compact('teachers'));
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create()
    {
        return view('admin.teachers.create');
    }

    /**
     * Store a newly created teacher.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'teacher',
            'is_active' => $request->has('is_active') ? $validated['is_active'] : true,
        ]);

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher created successfully.');
    }

    /**
     * Show the form for editing a teacher.
     */
    public function edit(User $teacher)
    {
        // Ensure this is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        return view('admin.teachers.edit', compact('teacher'));
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, User $teacher)
    {
        // Ensure this is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($teacher->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
        ]);

        $teacher->name = $validated['name'];
        $teacher->email = $validated['email'];
        
        if (!empty($validated['password'])) {
            $teacher->password = Hash::make($validated['password']);
        }
        
        $teacher->is_active = $request->has('is_active') ? $validated['is_active'] : $teacher->is_active;
        $teacher->save();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(User $teacher)
    {
        // Ensure this is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404);
        }

        $teacher->delete();

        return redirect()->route('admin.teachers.index')
            ->with('success', 'Teacher deleted successfully.');
    }
}
