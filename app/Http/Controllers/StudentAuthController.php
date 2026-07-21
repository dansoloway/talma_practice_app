<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\TermsAndCondition;
use App\Models\User;
use App\Services\LearnerSessionService;
use App\Support\SignupLocale;
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

        if ($organization->usesParentSignup()) {
            SignupLocale::apply(request());
        }

        return view('student.auth.login', compact('organization'));
    }

    public function login(Request $request, Organization $organization)
    {
        if ($organization->usesParentSignup()) {
            SignupLocale::apply($request);
        }

        $key = 'student-login:'.$organization->slug.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => [__('parent-signup.login_errors.too_many_attempts', [
                    'minutes' => (int) ceil($seconds / 60),
                ])],
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
                ->withErrors(['email' => __('parent-signup.login_errors.invalid_credentials')]);
        }

        if (! $loginUser->is_active || ! $loginUser->canAccessStudentPortal()) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('parent-signup.login_errors.cannot_access_portal')]);
        }

        if (! $loginUser->isAdmin() && ! $loginUser->isMemberOfOrg($organization->id)) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('parent-signup.login_errors.no_program_access')]);
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
        $privacyPolicy = TermsAndCondition::getPrivacyPolicy();

        return view('student.auth.register', compact('organization', 'terms', 'privacyPolicy'));
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
        $privacyPolicy = TermsAndCondition::getPrivacyPolicy();
        $consentRules = [];
        if ($terms) {
            $consentRules['terms_accepted'] = ['required', 'accepted'];
        }
        if ($privacyPolicy) {
            $consentRules['privacy_policy_read'] = ['required', 'accepted'];
        }

        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'age' => [$organization->retain_voice_recordings ? 'required' : 'nullable', 'integer', 'min:5', 'max:120'],
            'gender' => [$organization->retain_voice_recordings ? 'required' : 'nullable', 'in:male,female'],
            'native_language' => [$organization->retain_voice_recordings ? 'required' : 'nullable', 'in:hebrew,arabic,english,other'],
            'voice_recording_consent' => [$organization->retain_voice_recordings ? 'accepted' : 'nullable'],
        ], $consentRules));

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'is_active' => true,
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'native_language' => $validated['native_language'] ?? null,
            'voice_recording_consented_at' => $organization->retain_voice_recordings ? now() : null,
            'terms_accepted_at' => $terms ? now() : null,
            'terms_version' => $terms?->version,
            'privacy_policy_read_at' => $privacyPolicy ? now() : null,
            'privacy_policy_version' => $privacyPolicy?->version,
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
