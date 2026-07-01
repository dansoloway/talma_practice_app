<?php

namespace App\Support;

use App\Models\User;

class VoiceSampleViewerAccess
{
    public static function allows(?User $user): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        $allowlist = config('app.voice_sample_viewer_emails', []);

        return in_array(strtolower($user->email), $allowlist, true);
    }
}
