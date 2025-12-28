<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Show the forgot password form.
     */
    public function showForgotPasswordForm()
    {
        return view('admin.forgot-password');
    }

    /**
     * Send password reset email.
     */
    public function sendResetLinkEmail(Request $request)
    {
        // Don't use 'exists' validation to prevent user enumeration
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        // Always return success message to prevent user enumeration
        // Only send email if user exists and is active
        if ($user && $user->is_active) {
            // Check if a reset token was recently sent (within last 2 minutes)
            $existingToken = DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->first();

            if ($existingToken && now()->diffInMinutes($existingToken->created_at) < 2) {
                // Token was recently sent, don't send another one
                // Still return success to prevent enumeration
                return back()->with('status', 'If that email address exists in our system, we have sent a password reset link.');
            }

            // Generate reset token
            $token = Str::random(64);
            
            // Store token in database (using Laravel's password_reset_tokens table)
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Send notification
            $user->notify(new AdminPasswordResetNotification($token));
        }

        // Always return the same success message regardless of whether user exists
        // This prevents user enumeration attacks
        return back()->with('status', 'If that email address exists in our system, we have sent a password reset link.');
    }

    /**
     * Show the reset password form.
     */
    public function showResetForm(Request $request, $token)
    {
        return view('admin.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'We could not find a user with that email address.']);
        }

        // Verify token
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->first();

        if (!$resetRecord) {
            return back()->withErrors(['token' => 'This password reset token is invalid.']);
        }

        // Check if token is expired (24 hours)
        if (now()->diffInHours($resetRecord->created_at) > 24) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            return back()->withErrors(['token' => 'This password reset token has expired.']);
        }

        // Verify token matches
        if (!Hash::check($request->token, $resetRecord->token)) {
            return back()->withErrors(['token' => 'This password reset token is invalid.']);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete reset token
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Your password has been reset successfully. Please log in with your new password.');
    }
}
