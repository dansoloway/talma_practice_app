<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Part;
use App\Models\Prompt;
use Illuminate\Console\Command;

class MigratePromptsToParts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:prompts-to-parts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing prompts to Part 2 for each lesson';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Migrating existing prompts to Part 2...');

        $lessons = Lesson::with('prompts')->get();

        foreach ($lessons as $lesson) {
            if ($lesson->prompts->count() > 0) {
                $this->info("Processing lesson: {$lesson->title}");

                // Create Part 2 for this lesson
                $part = Part::create([
                    'lesson_id' => $lesson->id,
                    'title' => 'Part 2',
                    'description' => 'Questions migrated from original lesson structure',
                    'sort_order' => 2,
                    'is_active' => true,
                ]);

                $this->info("  Created Part 2 for lesson: {$lesson->title}");

                // Move all prompts to this part
                $promptsUpdated = 0;
                foreach ($lesson->prompts as $prompt) {
                    $prompt->update(['part_id' => $part->id]);
                    $promptsUpdated++;
                }

                $this->info("  Moved {$promptsUpdated} prompts to Part 2");
            }
        }

        $this->info('Migration completed!');
        $this->info('You can now create Part 1 and other parts as needed.');
    }
}