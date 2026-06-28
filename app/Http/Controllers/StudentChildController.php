<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\ParentStudent;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentChildController extends Controller
{
    public function __construct(
        protected StudentProfileService $studentProfiles,
    ) {}

    public function selectChild(Organization $organization): View|RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isParent() || ! $user->isMemberOfOrg($organization->id)) {
            return redirect()->route('org.student.login', $organization);
        }

        $students = $this->studentProfiles->getSharedLoginChildren($user);

        if ($students->count() <= 1) {
            if ($students->count() === 1) {
                session(['selected_student_id' => $students->first()->id]);
            }

            return redirect()->route('org.student.index', $organization);
        }

        return view('student.auth.select-child', compact('organization', 'students'));
    }

    public function storeSelectedChild(Request $request, Organization $organization): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isParent()) {
            return redirect()->route('org.student.login', $organization);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer'],
        ]);

        $student = ParentStudent::where('id', $validated['student_id'])
            ->where('parent_id', $user->id)
            ->firstOrFail();

        if (! $student->usesSharedLogin()) {
            abort(403);
        }

        session(['selected_student_id' => $student->id]);

        return redirect()->route('org.student.index', $organization);
    }
}
