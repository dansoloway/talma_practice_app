<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\ParentStudent;
use App\Models\StudentIdentity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class StudentProfileService
{
    public function createStudent(User $parent, Organization $organization, array $data): ParentStudent
    {
        $student = ParentStudent::create([
            'parent_id' => $parent->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'first_name_english' => $data['first_name_english'] ?? null,
            'last_name_english' => $data['last_name_english'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'grade' => $data['grade'] ?? null,
            'gender' => $data['gender'] ?? null,
        ]);

        if (($data['login_type'] ?? 'shared') === 'separate') {
            $childUser = User::create([
                'name' => trim(($data['first_name_english'] ?? $data['first_name']).' '.($data['last_name_english'] ?? $data['last_name'])),
                'email' => $data['email'],
                'phone_number' => $data['phone_number'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'student',
                'is_active' => true,
                'terms_accepted_at' => $data['terms_accepted_at'] ?? null,
                'terms_version' => $data['terms_version'] ?? null,
            ]);

            $student->update(['user_id' => $childUser->id]);

            $organization->users()->syncWithoutDetaching([
                $childUser->id => ['role' => 'student'],
            ]);

            StudentIdentity::create([
                'student_id' => $student->id,
                'email' => $data['email'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'login_type' => 'separate',
            ]);
        }

        return $student->fresh(['identity', 'user']);
    }

    public function getStudentsForParent(User $parent): Collection
    {
        return ParentStudent::where('parent_id', $parent->id)
            ->with(['identity', 'user'])
            ->orderBy('id')
            ->get();
    }

    public function getSharedLoginChildren(User $parent): Collection
    {
        return $this->getStudentsForParent($parent)->filter(fn (ParentStudent $s) => $s->usesSharedLogin())->values();
    }

    public function findBySeparateLoginEmail(string $email): ?ParentStudent
    {
        $identity = StudentIdentity::where('login_type', 'separate')
            ->where('email', strtolower(trim($email)))
            ->first();

        return $identity?->student;
    }
}
