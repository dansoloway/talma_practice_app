<?php

namespace App\Support;

use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PracticeLearnerScope
{
    /**
     * Stable progress key for activity events and lesson progress.
     *
     * Logged-in learners (students or a parent's selected child) use a persistent
     * account-scoped id. Guests and admin previews keep using the browser session cookie.
     */
    public static function resolve(Request $request): ?string
    {
        $browserSessionId = $request->attributes->get('practice_session_id')
            ?? $request->cookie(config('app.practice_session_cookie', 'talma_session_id'));

        return self::forUser(Auth::guard('admin')->user(), $browserSessionId);
    }

    public static function forUser(?User $user, ?string $browserSessionId): ?string
    {
        if ($user instanceof User) {
            $learnerScope = self::learnerScopeForUser($user);

            if ($learnerScope !== null) {
                return $learnerScope;
            }
        }

        return $browserSessionId ? (string) $browserSessionId : null;
    }

    private static function learnerScopeForUser(User $user): ?string
    {
        if ($user->isParent()) {
            $childId = session('selected_student_id');
            if (! $childId) {
                return null;
            }

            $child = ParentStudent::query()
                ->where('parent_id', $user->id)
                ->find($childId);

            return $child ? 'child:'.$child->id : null;
        }

        if ($user->isStudent()) {
            $linked = $user->linkedParentStudent;

            return $linked
                ? 'child:'.$linked->id
                : 'user:'.$user->id;
        }

        return null;
    }
}
