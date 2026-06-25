<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
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
        $key = 'student-login:' . $organization->slug . ':' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.'],
            ]);
        }

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'nullable|boolean',
        ]);

        if (!Auth::guard('admin')->attempt(
            [
                'email' => $credentials['email'],
                'password' => $credentials['password'],
                'is_active' => true,
            ],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid credentials.']);
        }

        $user = Auth::guard('admin')->user();

        if (!$user->canAccessStudentPortal()) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account cannot access the student portal.']);
        }

        if (!$user->isAdmin() && !$user->isMemberOfOrg($organization->id)) {
            Auth::guard('admin')->logout();
            RateLimiter::hit($key, 300);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'You do not have access to this program.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('org.student.index', $organization));
    }

    public function showRegister(Organization $organization)
    {
        if (!$organization->allow_self_registration) {
            abort(404);
        }

        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            if ($user->canAccessStudentPortal() && $user->isMemberOfOrg($organization->id)) {
                return redirect()->route('org.student.index', $organization);
            }
        }

        return view('student.auth.register', compact('organization'));
    }

    public function register(Request $request, Organization $organization)
    {
        if (!$organization->allow_self_registration) {
            abort(404);
        }

        if ($organization->access_mode !== 'restricted') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
            'is_active' => true,
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
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('org.student.login', $organization);
    }
}
