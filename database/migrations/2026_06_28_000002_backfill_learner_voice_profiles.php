<?php

use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'voice_recording_consented_at')) {
            return;
        }

        User::query()
            ->where('role', User::ROLE_PARENT)
            ->whereNotNull('terms_accepted_at')
            ->whereNull('voice_recording_consented_at')
            ->update([
                'voice_recording_consented_at' => DB::raw('terms_accepted_at'),
            ]);

        ParentStudent::query()
            ->whereNotNull('user_id')
            ->with(['user', 'parent'])
            ->chunkById(100, function ($students) {
                foreach ($students as $student) {
                    $user = $student->user;
                    if (! $user) {
                        continue;
                    }

                    $updates = [];
                    if ($student->gender && ! $user->gender) {
                        $updates['gender'] = $student->gender;
                    }
                    if ($student->native_language && ! $user->native_language) {
                        $updates['native_language'] = $student->native_language;
                    }
                    if ($student->birth_date && $user->age === null) {
                        $updates['age'] = (int) $student->birth_date->age;
                    }
                    if (! $user->voice_recording_consented_at) {
                        $consent = $student->parent?->voice_recording_consented_at
                            ?? $student->parent?->terms_accepted_at
                            ?? $user->terms_accepted_at;
                        if ($consent) {
                            $updates['voice_recording_consented_at'] = $consent;
                        }
                    }

                    if ($updates !== []) {
                        $user->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Non-destructive backfill; no rollback.
    }
};
