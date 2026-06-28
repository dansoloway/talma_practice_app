<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\User;

class LearnerVoiceProfileCompletion
{
    public function requiresCompletion(User $user, Organization $organization): bool
    {
        if (! $organization->collectsVoiceRecordings()) {
            return false;
        }

        if (! $user->canAccessStudentPortal()) {
            return false;
        }

        if ($user->isAdmin() && ! $user->isParent() && ! $user->isStudent()) {
            return false;
        }

        if ($user->isParent()) {
            return ! $this->hasVoiceConsent($user);
        }

        return VoiceSampleLearnerProfile::resolve($user) === null;
    }

    /**
     * @return array{
     *     mode: string,
     *     needs_voice_consent: bool,
     *     needs_student_fields: bool,
     *     student: ?ParentStudent,
     *     user: User
     * }
     */
    public function formContext(User $user): array
    {
        if ($user->isParent()) {
            return [
                'mode' => 'parent',
                'needs_voice_consent' => ! $this->hasVoiceConsent($user),
                'needs_student_fields' => false,
                'student' => null,
                'user' => $user,
            ];
        }

        $linked = $user->linkedParentStudent;
        if ($linked) {
            $parent = $linked->parent;

            return [
                'mode' => 'linked_student',
                'needs_voice_consent' => $parent !== null && ! $this->hasVoiceConsent($parent),
                'needs_student_fields' => ! $this->childProfileComplete($linked),
                'student' => $linked,
                'user' => $user,
            ];
        }

        return [
            'mode' => 'student',
            'needs_voice_consent' => ! $this->hasVoiceConsent($user),
            'needs_student_fields' => $user->age === null || ! $user->gender || ! $user->native_language,
            'student' => null,
            'user' => $user,
        ];
    }

    public function apply(User $user, Organization $organization, array $validated): void
    {
        $context = $this->formContext($user);

        if ($context['needs_voice_consent'] && ! empty($validated['voice_recording_consent'])) {
            if ($context['mode'] === 'linked_student' && $context['student']?->parent) {
                $context['student']->parent->update(['voice_recording_consented_at' => now()]);
                app(StudentProfileService::class)->propagateVoiceConsent($context['student']->parent);
            } elseif ($context['mode'] === 'parent') {
                $user->update(['voice_recording_consented_at' => now()]);
                app(StudentProfileService::class)->propagateVoiceConsent($user);
            } else {
                $user->update(['voice_recording_consented_at' => now()]);
            }
        }

        if ($context['mode'] === 'student') {
            $user->update([
                'age' => (int) $validated['age'],
                'gender' => $validated['gender'],
                'native_language' => $validated['native_language'],
            ]);
        }

        if ($context['mode'] === 'linked_student' && $context['needs_student_fields'] && $context['student']) {
            $this->applyChildProfileFields($context['student'], $validated, $user);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(User $user, Organization $organization): array
    {
        $context = $this->formContext($user);
        $rules = [];

        if ($context['needs_voice_consent']) {
            $rules['voice_recording_consent'] = ['accepted'];
        }

        if ($context['mode'] === 'student' || ($context['mode'] === 'linked_student' && $context['needs_student_fields'])) {
            if ($context['mode'] === 'student') {
                $rules['age'] = ['required', 'integer', 'min:5', 'max:120'];
            } elseif ($context['student'] && ! $context['student']->birth_date) {
                $rules['birth_year'] = ['required', 'integer', 'min:1990', 'max:'.date('Y')];
            }

            $rules['gender'] = ['required', 'in:male,female,other'];
            $rules['native_language'] = ['required', 'in:hebrew,arabic,english,other'];
        }

        return $rules;
    }

    private function hasVoiceConsent(User $user): bool
    {
        return $user->voice_recording_consented_at !== null || $user->terms_accepted_at !== null;
    }

    private function childProfileComplete(ParentStudent $student): bool
    {
        return $student->gender
            && $student->native_language
            && $student->birth_date
            && $student->age() !== null;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function applyChildProfileFields(ParentStudent $student, array $validated, User $actingUser): void
    {
        $birthDate = ! empty($validated['birth_year'])
            ? \Illuminate\Support\Carbon::createFromDate((int) $validated['birth_year'], 6, 1)
            : $student->birth_date;

        $student->update([
            'gender' => $validated['gender'] ?? $student->gender,
            'native_language' => $validated['native_language'] ?? $student->native_language,
            'birth_date' => $birthDate,
        ]);

        if ($student->user_id) {
            $age = $birthDate ? (int) $birthDate->age : null;
            $student->user?->update([
                'age' => $age,
                'gender' => $student->gender,
                'native_language' => $student->native_language,
                'voice_recording_consented_at' => $student->parent?->voice_recording_consented_at
                    ?? $actingUser->voice_recording_consented_at,
            ]);
        }
    }
}
