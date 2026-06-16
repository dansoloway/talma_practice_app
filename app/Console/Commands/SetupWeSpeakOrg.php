<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;

class SetupWeSpeakOrg extends Command
{
    protected $signature = 'org:setup-we-speak
                            {courses?* : Course slugs to move (e.g. grade-1 grade-2)}';

    protected $description = 'Create We Speak org and move specified courses from TALMA Community Resources';

    public function handle(): int
    {
        $courseSlugs = $this->argument('courses') ?: ['grade-1', 'grade-2'];

        $defaultOrg = Organization::where('slug', 'default')->first();
        if (!$defaultOrg) {
            $this->error('Default org not found.');
            return 1;
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
            'access_mode' => 'open',
        ]);

        $this->info("We Speak organization: {$weSpeakOrg->name}");

        $moved = 0;
        foreach ($courseSlugs as $slug) {
            $course = Course::where('slug', $slug)->first();
            if (!$course) {
                $this->warn("Course '{$slug}' not found; skipping.");
                continue;
            }

            $defaultOrg->courses()->detach($course->id);
            $weSpeakOrg->courses()->syncWithoutDetaching([
                $course->id => ['is_org_wide' => true],
            ]);
            $moved++;
            $this->info("Moved course '{$course->title}' to We Speak org.");
        }

        $this->info("Moved {$moved} course(s). Attach admin users if needed: admin panel → Organizations.");

        return 0;
    }
}
