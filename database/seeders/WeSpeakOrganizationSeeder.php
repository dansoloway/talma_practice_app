<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class WeSpeakOrganizationSeeder extends Seeder
{
    /**
     * Course slugs to move from TALMA Community Resources into We Speak org.
     * Edit this array to specify which courses to move.
     */
    protected array $courseSlugsToMove = ['grade-1', 'grade-2'];

    /**
     * Create We Speak organization (public, no sign-in) and move specified courses from default.
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $defaultOrg = Organization::where('slug', 'default')->first();
        if (!$defaultOrg) {
            $this->command->warn('Default org not found; run OrganizationSeeder first.');
            return;
        }

        $weSpeakOrg = Organization::firstOrCreate(
            ['slug' => 'we-speak'],
            [
                'name' => 'We Speak',
                'description' => 'We Speak vocabulary and practice courses',
                'is_active' => true,
                'access_mode' => 'open',
            ]
        );
        $weSpeakOrg->update([
            'name' => 'We Speak',
            'description' => 'We Speak vocabulary and practice courses',
            'access_mode' => 'open',
        ]);

        $this->command->info("We Speak organization: {$weSpeakOrg->name} (id: {$weSpeakOrg->id})");

        $moved = 0;
        foreach ($this->courseSlugsToMove as $slug) {
            $course = Course::where('slug', $slug)->first();
            if (!$course) {
                $this->command->warn("Course '{$slug}' not found; skipping.");
                continue;
            }

            $defaultOrg->courses()->detach($course->id);
            $weSpeakOrg->courses()->syncWithoutDetaching([
                $course->id => ['is_org_wide' => true],
            ]);
            $moved++;
            $this->command->info("Moved course '{$course->title}' to We Speak org.");
        }

        $this->command->info("Moved {$moved} course(s) to We Speak org.");

        // Attach admin/teacher users to We Speak org (so they can manage it)
        $adminTeacherIds = User::whereIn('role', ['admin', 'teacher'])->pluck('id')->toArray();
        foreach ($adminTeacherIds as $userId) {
            $weSpeakOrg->users()->syncWithoutDetaching([
                $userId => ['role' => 'org_admin'],
            ]);
        }
    }
}
