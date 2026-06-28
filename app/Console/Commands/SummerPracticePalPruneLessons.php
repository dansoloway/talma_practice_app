<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Lesson;
use App\Services\Import\LessonGameCreator;
use App\Services\Import\SummerImportOptions;
use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Console\Command;

class SummerPracticePalPruneLessons extends Command
{
    protected $signature = 'talma:summer-practice-pal-prune-lessons
                            {--cefr= : Limit to one CEFR level (Pre-A1, A1, A2, or B1)}
                            {--inactive : Delete lessons marked inactive (default when no other filter)}
                            {--dry-run : Show what would be deleted without making changes}';

    protected $description = 'Permanently delete inactive or duplicate Summer Practice Pal lessons';

    public function handle(LessonGameCreator $lessonGameCreator): int
    {
        $slugs = $this->courseSlugs();
        if ($slugs === null) {
            $this->error('Unknown --cefr value. Use Pre-A1, A1, A2, or B1.');

            return self::FAILURE;
        }

        $courses = Course::query()->whereIn('slug', $slugs)->orderBy('sort_order')->get();
        if ($courses->isEmpty()) {
            $this->warn('No Summer Practice Pal courses found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $removed = 0;

        foreach ($courses as $course) {
            $lessons = Lesson::query()
                ->where('course_id', $course->id)
                ->where('is_active', false)
                ->orderBy('sort_order')
                ->get();

            if ($lessons->isEmpty()) {
                continue;
            }

            $this->info("{$course->title}: {$lessons->count()} inactive lesson(s) to remove");

            foreach ($lessons as $lesson) {
                $this->line("  - {$lesson->title} ({$lesson->slug})");
                if (!$dryRun) {
                    $lessonGameCreator->deleteLesson($lesson);
                }
                $removed++;
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->info("Dry run — would remove {$removed} lesson(s). Re-run without --dry-run to delete.");
        } else {
            $this->newLine();
            $this->info("Removed {$removed} lesson(s).");
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>|null
     */
    private function courseSlugs(): ?array
    {
        $cefr = $this->option('cefr');
        if ($cefr === null || $cefr === '') {
            return array_values(SummerVocabAssetArchiver::COURSE_SLUGS);
        }

        $normalized = SummerImportOptions::normalizeCefr((string) $cefr);
        if (!isset(SummerVocabAssetArchiver::COURSE_SLUGS[$normalized])) {
            return null;
        }

        return [SummerVocabAssetArchiver::COURSE_SLUGS[$normalized]];
    }
}
