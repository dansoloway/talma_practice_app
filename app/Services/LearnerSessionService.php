<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class LearnerSessionService
{
    public function __construct(
        protected StudentProfileService $studentProfiles,
    ) {}

    public function redirectAfterAuth(User $user, Organization $organization): RedirectResponse
    {
        if (! $organization->usesParentSignup()) {
            return redirect()->intended(route('org.student.index', $organization));
        }

        if ($user->isParent()) {
            return $this->redirectParent($user, $organization);
        }

        if ($user->isStudent()) {
            $linked = $user->linkedParentStudent;
            if ($linked) {
                session(['selected_student_id' => $linked->id]);
            }
        }

        return redirect()->intended(route('org.student.index', $organization));
    }

    public function redirectParent(User $user, Organization $organization): RedirectResponse
    {
        $shared = $this->studentProfiles->getSharedLoginChildren($user);

        if ($shared->count() === 1) {
            session(['selected_student_id' => $shared->first()->id]);

            return redirect()->intended(route('org.student.index', $organization));
        }

        if ($shared->count() > 1) {
            return redirect()->route('org.student.select-child', $organization);
        }

        return redirect()->intended(route('org.student.index', $organization));
    }

    public function requiresChildSelection(User $user): bool
    {
        if (! $user->isParent()) {
            return false;
        }

        return $this->studentProfiles->getSharedLoginChildren($user)->count() > 1
            && ! session('selected_student_id');
    }

    public function selectedStudentId(): ?int
    {
        $id = session('selected_student_id');

        return $id ? (int) $id : null;
    }
}
