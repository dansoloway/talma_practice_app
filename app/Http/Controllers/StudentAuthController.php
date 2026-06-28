<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\TermsAndCondition;
use App\Models\User;
use App\Services\LearnerSessionService;
use App\Helpers\PhoneRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
    public function __construct(
        protected LearnerSessionService $learnerSession,
        protected OrgParentSignupController $parentSignup,
    ) {}

    public function showLogin(Organization $organization)
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            if ($user->canAccessStudentPortal() && ($user->isAdmin() || $user->isMemberOfOrg($organization->id))) {
                return redirect()->route('org.student.index', $organization);
            }
        }

        return view('student.auth.login', compact('organization'));
    }

    public function login(Request $request, Organization $organization)
    {
        $key = 'student-login:'.$organization->slug.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again in '.ceil($seconds / 60).' minutes.'],
            ]);
        }

        $credentials = $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        $loginUser = $this->resolveLoginUser($credentials['email']);

        if (! $loginUser || ! Hash::check($credentials['password'], $loginUser->password)) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials.']);
        }

        if (! $loginUser->is_active || ! $loginUser->canAccessStudentPortal()) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account cannot access the student portal.']);
        }

        if (! $loginUser->isAdmin() && ! $loginUser->isMemberOfOrg($organization->id)) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'You do not have access to this program.']);
        }

        Auth::guard('admin')->login($loginUser, $request->boolean('remember'));
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->learnerSession->redirectAfterAuth($loginUser, $organization);
    }

    protected function resolveLoginUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', strtolower($identifier))->first();
        }

        $normalizedPhone = PhoneRules::normalize($identifier);
        if ($normalizedPhone !== '') {
            $byPhone = User::where('phone_number', $normalizedPhone)->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        return User::where('email', strtolower($identifier))->first();
    }

    public function showRegister(Organization $organization)
    {
        if (! $organization->allow_self_registration) {
            abort(404);
        }

        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            if ($user->canAccessStudentPortal() && $user->isMemberOfOrg($organization->id)) {
                return redirect()->route('org.student.index', $organization);
            }
        }

        if ($organization->usesParentSignup()) {
            return $this->parentSignup->showRegister($organization);
        }

        $terms = TermsAndCondition::getStudentSignupTerms();

        return view('student.auth.register', compact('organization', 'terms'));
    }

    public function register(Request $request, Organization $organization)
    {
        if (! $organization->allow_self_registration) {
            abort(404);
        }

        if ($organization->access_mode !== 'restricted') {
            abort(404);
        }

        if ($organization->usesParentSignup()) {
            return $this->parentSignup->register($request, $organization);
        }

        $terms = TermsAndCondition::getStudentSignupTerms();
        $termsRules = $terms ? ['terms_accepted' => ['required', 'accepted']] : [];

        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'age' => [$organization->retain_voice_recordings ? 'required' : 'nullable', 'integer', 'min:5', 'max:120'],
            'gender' => [$organization->retain_voice_recordings ? 'required' : 'nullable', 'in:male,female'],
            'voice_recording_consent' => [$organization->retain_voice_recordings ? 'accepted' : 'nullable'],
        ], $termsRules));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'is_active' => true,
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'voice_recording_consented_at' => $organization->retain_voice_recordings ? now() : null,
            'terms_accepted_at' => $terms ? now() : null,
            'terms_version' => $terms?->version,
        ]);

        $organization->users()->syncWithoutDetaching([
            $user->id => ['role' => 'student'],
        ]);

        Auth::guard('admin')->login($user);
        $request->session()->regenerate();

        return redirect()->route('org.student.index', $organization)
            ->with('success', 'Welcome! Your account has been created.');
    }

    public function logout(Request $request, Organization $organization)
    {
        Auth::guard('admin')->logout();
        $request->session()->forget('selected_student_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('org.student.login', $organization);
    }
}
