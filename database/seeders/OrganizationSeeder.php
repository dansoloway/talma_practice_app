<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the Default organization and attach all courses and admin/teacher users.
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $org = Organization::firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default',
                'description' => 'Default organization for existing content',
                'is_active' => true,
                'access_mode' => 'open',
            ]
        );

        $this->command->info("Default organization: {$org->name} (id: {$org->id})");

        // Attach all courses to Default org (no duplicates via syncWithoutDetaching)
        $courseIds = Course::pluck('id')->toArray();
        $org->courses()->syncWithoutDetaching($courseIds);
        $this->command->info("Attached {$org->courses()->count()} course(s) to Default org.");

        // Attach all admin/teacher users to Default org as org_admin (no duplicates)
        $adminTeacherIds = User::whereIn('role', ['admin', 'teacher'])->pluck('id')->toArray();
        foreach ($adminTeacherIds as $userId) {
            $org->users()->syncWithoutDetaching([
                $userId => ['role' => 'org_admin'],
            ]);
        }
        $this->command->info("Attached {$org->users()->count()} admin/teacher user(s) to Default org as org_admin.");
    }
}
