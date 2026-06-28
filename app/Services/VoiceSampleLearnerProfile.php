<?php

namespace App\Services;

use App\Models\ParentStudent;
use App\Models\User;

readonly class VoiceSampleLearnerProfile
{
    public function __construct(
        public int $age,
        public string $gender,
        public string $nativeLanguage,
    ) {}

    public static function resolve(?User $user): ?self
    {
        if (! $user) {
            return null;
        }

        $student = self::resolveParentStudent($user);
        if ($student) {
            return self::fromParentStudent($student);
        }

        if (
            ! $user->voice_recording_consented_at
            || $user->age === null
            || $user->gender === null
            || $user->native_language === null
        ) {
            return null;
        }

        return new self((int) $user->age, $user->gender, $user->native_language);
    }

    private static function resolveParentStudent(User $user): ?ParentStudent
    {
        if ($user->isParent()) {
            $id = session('selected_student_id');
            if (! $id) {
                return null;
            }

            return ParentStudent::where('parent_id', $user->id)->find($id);
        }

        return $user->linkedParentStudent;
    }

    private static function fromParentStudent(ParentStudent $student): ?self
    {
        $parent = $student->parent;
        $hasVoiceConsent = $parent?->voice_recording_consented_at || $parent?->terms_accepted_at;
        if (
            ! $hasVoiceConsent
            || ! $student->gender
            || ! $student->native_language
            || ! $student->birth_date
        ) {
            return null;
        }

        $age = $student->age();
        if ($age === null) {
            return null;
        }

        return new self($age, $student->gender, $student->native_language);
    }
}
