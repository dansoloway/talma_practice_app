<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Vocabulary;
use App\Services\Import\SummerImportOptions;
use App\Services\Import\VocabularyEnricher;
use Illuminate\Console\Command;

class EnrichSummerPracticePal extends Command
{
    protected $signature = 'talma:enrich-summer-practice-pal
                            {--cefr= : CEFR level (Pre-A1, A1, A2, or B1)}
                            {--with-enrichment : Enable translations, images, and TTS (required)}
                            {--skip-translations : Skip Hebrew/Arabic translations}
                            {--skip-images : Skip vocabulary images}
                            {--skip-tts : Skip vocabulary TTS}
                            {--dry-run : Show counts only; no API calls}';

    protected $description = 'Enrich existing Summer Practice Pal vocabulary (translations, images, TTS) without re-importing structure';

    public function handle(VocabularyEnricher $enricher): int
    {
        try {
            $options = SummerImportOptions::fromCommandFlags($this->options());
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($options->cefr === null) {
            $this->error('--cefr is required (Pre-A1, A1, A2, or B1).');

            return self::FAILURE;
        }

        if ($options->isStructureOnly()) {
            $this->error('Pass --with-enrichment (and optionally --skip-translations, --skip-images, --skip-tts).');

            return self::FAILURE;
        }

        $cefr = SummerImportOptions::normalizeCefr($options->cefr);
        $courseSlug = $this->courseSlugForCefr($cefr);
        $course = Course::where('slug', $courseSlug)->where('is_active', true)->first();

        if (!$course) {
            $this->error("Course not found: {$courseSlug}");

            return self::FAILURE;
        }

        $vocabulary = Vocabulary::query()
            ->where('is_active', true)
            ->whereHas('lesson', function ($q) use ($course) {
                $q->where('course_id', $course->id)->where('is_active', true)->whereNull('archived_at');
            })
            ->with('lesson')
            ->orderBy('lesson_id')
            ->orderBy('sort_order')
            ->get();

        if ($vocabulary->isEmpty()) {
            $this->warn('No active vocabulary found for this course.');

            return self::SUCCESS;
        }

        $this->info("Summer Practice Pal enrichment — {$cefr}");
        $this->info("Course: {$course->title} ({$course->slug})");
        $this->info("Vocabulary words: {$vocabulary->count()}");
        $this->line('Translations: ' . ($options->translate ? 'yes' : 'no'));
        $this->line('Images: ' . ($options->generateImages ? 'yes' : 'no'));
        $this->line('TTS: ' . ($options->generateTts ? 'yes' : 'no'));

        if ($options->dryRun) {
            $needsTrans = $vocabulary->filter(fn (Vocabulary $v) => empty($v->hebrew_translation) || empty($v->arabic_translation))->count();
            $needsImages = $vocabulary->filter(fn (Vocabulary $v) => empty($v->image_path))->count();
            $needsTts = $vocabulary->filter(fn (Vocabulary $v) => empty($v->word_audio_path))->count();
            $this->newLine();
            $this->table(['Field', 'Missing'], [
                ['Translations', $needsTrans],
                ['Images', $needsImages],
                ['Vocabulary TTS', $needsTts],
            ]);

            return self::SUCCESS;
        }

        $stats = [
            'translations_ok' => 0,
            'images_ok' => 0,
            'tts_ok' => 0,
            'errors' => 0,
        ];

        $bar = $this->output->createProgressBar($vocabulary->count());
        $bar->start();

        foreach ($vocabulary as $word) {
            $result = $enricher->enrich($word->fresh(), $options);

            if ($result['translations_ok']) {
                $stats['translations_ok']++;
            }
            if ($result['images_ok']) {
                $stats['images_ok']++;
            }
            if ($result['tts_ok']) {
                $stats['tts_ok']++;
            }
            $stats['errors'] += count($result['errors']);

            foreach ($result['errors'] as $error) {
                $this->newLine();
                $this->warn($error);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Enrichment complete.');
        $this->table(['Metric', 'Count'], [
            ['Words processed', $vocabulary->count()],
            ['Translations OK', $stats['translations_ok']],
            ['Images OK', $stats['images_ok']],
            ['TTS OK', $stats['tts_ok']],
            ['Errors', $stats['errors']],
        ]);

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function courseSlugForCefr(string $cefr): string
    {
        return match ($cefr) {
            'Pre-A1' => 'summer-practice-pal-pre-a1',
            'A1' => 'summer-practice-pal-a1',
            'A2' => 'summer-practice-pal-a2',
            'B1' => 'summer-practice-pal-b1',
            default => throw new \InvalidArgumentException("Unsupported CEFR level: {$cefr}"),
        };
    }
}
