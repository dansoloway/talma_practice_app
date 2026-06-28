<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\LearnerVoiceProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LearnerProfileController extends Controller
{
    public function __construct(
        protected LearnerVoiceProfileCompletion $profileCompletion,
    ) {}

    public function showCompleteVoiceProfile(Organization $organization): View|RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isMemberOfOrg($organization->id)) {
            return redirect()->route('org.student.login', $organization);
        }

        if (! $this->profileCompletion->requiresCompletion($user, $organization)) {
            return redirect()->route('org.student.index', $organization);
        }

        $context = $this->profileCompletion->formContext($user);

        return view('student.auth.complete-voice-profile', compact('organization', 'context'));
    }

    public function storeCompleteVoiceProfile(Request $request, Organization $organization): RedirectResponse
    {
        $user = Auth::guard('admin')->user();

        if (! $user || ! $user->isMemberOfOrg($organization->id)) {
            return redirect()->route('org.student.login', $organization);
        }

        if (! $this->profileCompletion->requiresCompletion($user, $organization)) {
            return redirect()->route('org.student.index', $organization);
        }

        $validated = $request->validate($this->profileCompletion->validationRules($user, $organization));

        $this->profileCompletion->apply($user, $organization, $validated);

        if ($this->profileCompletion->requiresCompletion($user->fresh(), $organization)) {
            return back()->withErrors(['profile' => 'Please complete all required fields to continue.']);
        }

        return redirect()->route('org.student.index', $organization)
            ->with('success', 'Your learner profile is ready. You can start practicing!');
    }
}
