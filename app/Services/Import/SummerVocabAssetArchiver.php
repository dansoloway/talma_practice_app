<?php

namespace App\Services\Import;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SummerVocabAssetArchiver
{
    /** @var array<string, string> */
    public const COURSE_SLUGS = [
        'Pre-A1' => 'summer-practice-pal-pre-a1',
        'A1' => 'summer-practice-pal-a1',
        'A2' => 'summer-practice-pal-a2',
        'B1' => 'summer-practice-pal-b1',
    ];

    /**
     * @param list<string>|null $courseSlugs
     * @return array<string, mixed>
     */
    public function archiveAll(?array $courseSlugs = null, string $reason = 'manual'): array
    {
        $slugs = $courseSlugs ?? array_values(self::COURSE_SLUGS);
        $archiveDir = $this->createArchiveDirectory();
        $summary = $this->emptySummary($archiveDir);

        $courses = Course::query()->whereIn('slug', $slugs)->get();
        $summary['courses'] = $courses->count();

        foreach ($courses as $course) {
            $lessonIds = $course->lessons()->pluck('id');
            $vocabulary = Vocabulary::query()->whereIn('lesson_id', $lessonIds)->get();
            $lessonSummary = $this->archiveVocabularyCollection($vocabulary, $archiveDir, $reason);
            $this->mergeSummary($summary, $lessonSummary);
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveLesson(Lesson $lesson, string $reason = 'pre_reimport_cleanup'): array
    {
        $archiveDir = $this->createArchiveDirectory();
        $vocabulary = $lesson->vocabulary()->get();

        return $this->archiveVocabularyCollection($vocabulary, $archiveDir, $reason);
    }

    /**
     * @param Collection<int, Vocabulary> $vocabulary
     * @return array<string, mixed>
     */
    public function archiveVocabularyCollection(Collection $vocabulary, string $archiveDir, string $reason): array
    {
        $summary = $this->emptySummary($archiveDir);
        $manifestPath = $archiveDir . '/manifest.jsonl';
        $lessonIds = [];

        foreach ($vocabulary as $item) {
            $item->loadMissing('lesson.course');
            $summary['vocabulary_rows']++;

            $entry = [
                'vocabulary_id' => $item->id,
                'lesson_slug' => $item->lesson?->slug,
                'lesson_title' => $item->lesson?->title,
                'cefr' => $this->cefrFromCourse($item->lesson?->course?->slug),
                'english_word' => $item->english_word,
                'hebrew_translation' => $item->hebrew_translation,
                'arabic_translation' => $item->arabic_translation,
                'original_image_path' => $item->image_path,
                'archived_image_path' => null,
                'original_audio_path' => $item->word_audio_path,
                'archived_audio_path' => null,
                'archived_at' => now()->toIso8601String(),
                'reason' => $reason,
            ];

            if ($item->image_path) {
                $archived = $this->copyAsset(
                    $item->image_path,
                    $archiveDir . '/images',
                    $item->id . '_' . basename($this->toStorageRelativePath($item->image_path) ?? 'image')
                );
                if ($archived !== null) {
                    $entry['archived_image_path'] = $archived;
                    $summary['images_copied']++;
                }
            }

            if ($item->word_audio_path) {
                $archived = $this->copyAsset(
                    $item->word_audio_path,
                    $archiveDir . '/audio',
                    $item->id . '_' . basename($this->toStorageRelativePath($item->word_audio_path) ?? 'audio')
                );
                if ($archived !== null) {
                    $entry['archived_audio_path'] = $archived;
                    $summary['audio_copied']++;
                }
            }

            if ($item->lesson_id) {
                $lessonIds[(string) $item->lesson_id] = true;
            }

            file_put_contents(
                $manifestPath,
                json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }

        $summary['lessons'] = count($lessonIds);

        return $summary;
    }

    public function createArchiveDirectory(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $dir = storage_path("app/archived/summer-practice-pal/{$timestamp}");
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        mkdir($dir . '/images', 0755, true);
        mkdir($dir . '/audio', 0755, true);

        return $dir;
    }

    private function copyAsset(string $path, string $targetDir, string $targetFilename): ?string
    {
        $relative = $this->toStorageRelativePath($path);
        if ($relative === null) {
            return null;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($relative)) {
            return null;
        }

        $targetPath = $targetDir . '/' . $targetFilename;
        $contents = $disk->get($relative);
        if ($contents === null) {
            return null;
        }

        file_put_contents($targetPath, $contents);

        return Str::after($targetPath, storage_path('app/') . '');
    }

    public function toStorageRelativePath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = ltrim($path, '/');

        return preg_replace('#^storage/#', '', $normalized) ?: null;
    }

    private function cefrFromCourse(?string $slug): ?string
    {
        if ($slug === null) {
            return null;
        }

        foreach (self::COURSE_SLUGS as $cefr => $courseSlug) {
            if ($courseSlug === $slug) {
                return $cefr;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySummary(string $archiveDir): array
    {
        return [
            'archive_dir' => $archiveDir,
            'manifest_path' => $archiveDir . '/manifest.jsonl',
            'courses' => 0,
            'lessons' => 0,
            'vocabulary_rows' => 0,
            'images_copied' => 0,
            'audio_copied' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $target
     * @param array<string, mixed> $source
     */
    private function mergeSummary(array &$target, array $source): void
    {
        $target['vocabulary_rows'] += $source['vocabulary_rows'];
        $target['images_copied'] += $source['images_copied'];
        $target['audio_copied'] += $source['audio_copied'];
        $target['lessons'] = (int) $target['lessons'] + (int) $source['lessons'];
    }
}
