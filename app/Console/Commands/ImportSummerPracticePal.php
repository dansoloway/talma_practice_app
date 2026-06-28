<?php

namespace App\Console\Commands;

use App\Services\Import\SummerImportOptions;
use App\Services\Import\SummerImportReporter;
use App\Services\Import\SummerPracticePalImporter;
use Illuminate\Console\Command;

class ImportSummerPracticePal extends Command
{
    protected $signature = 'talma:import-summer-practice-pal
                            {--dry-run : Parse the spreadsheet and show counts without writing to the database}
                            {--cefr= : Import a single CEFR level (Pre-A1, A1, A2, or B1)}
                            {--with-enrichment : Enable translations, images, and TTS (slow; uses API credits)}
                            {--skip-translations : With --with-enrichment, skip Hebrew/Arabic translations}
                            {--skip-images : With --with-enrichment, skip vocabulary images}
                            {--skip-tts : With --with-enrichment, skip vocabulary and prompt TTS}
                            {--force : Replace all vocabulary and prompts on existing lessons (destructive)}
                            {--no-detach-from-default : Keep courses attached to TALMA Community Resources org}';

    protected $description = 'Import Summer Practice Pal courses, lessons, vocabulary, games, and prompts (structure-only by default; safe to re-run)';

    public function handle(SummerPracticePalImporter $importer): int
    {
        $this->info('Summer Practice Pal Import');
        $this->info('==========================');

        $xlsxPath = base_path('data/Summer Practice Pal Prompts.xlsx');
        if (!file_exists($xlsxPath)) {
            $this->error("XLSX file not found: {$xlsxPath}");

            return self::FAILURE;
        }

        try {
            $options = SummerImportOptions::fromCommandFlags($this->options());
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $reporter = new SummerImportReporter(function (string $message) {
            $this->line($message);
        });

        $this->info("Source: {$xlsxPath}");
        $this->info("Log file: {$reporter->logPath()}");
        $this->comment('Tail log: tail -f storage/logs/summer_practice_pal_import.log');
        $this->newLine();

        if ($options->dryRun) {
            $this->warn('Dry run — no database changes will be made.');
        }
        if ($options->cefr !== null) {
            $this->info("CEFR filter: {$options->cefr}");
        }
        if ($options->isStructureOnly()) {
            $this->info('Mode: structure only (no translations, images, or TTS). Safe to re-run.');
        } else {
            $this->warn('Mode: with enrichment (API calls — may take hours).');
        }
        if ($options->force) {
            $this->warn('--force: existing vocab and prompts on touched lessons will be replaced.');
        }

        $this->newLine();

        try {
            $summary = $importer->import($xlsxPath, $options, $reporter);
        } catch (\Throwable $e) {
            $reporter->error('Import failed: ' . $e->getMessage(), [
                'exception' => $e::class,
            ]);
            $this->error('Import failed: ' . $e->getMessage());
            $this->error("See log: {$reporter->logPath()}");

            return self::FAILURE;
        }

        $this->newLine();
        $this->renderSummary($summary, $reporter);

        return self::SUCCESS;
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function renderSummary(array $summary, SummerImportReporter $reporter): void
    {
        if (!empty($summary['dry_run'])) {
            $this->info('Dry-run summary');
            $this->table(
                ['CEFR', 'Course slug', 'Lessons', 'Vocab words', 'Prompts', 'Vocab-only lessons'],
                collect($summary['courses'] ?? [])->map(function ($course, $cefr) {
                    return [
                        $cefr,
                        $course['slug'],
                        $course['lessons'],
                        $course['vocabulary_words'],
                        $course['prompts'],
                        $course['lessons_vocab_only'],
                    ];
                })->values()->all()
            );

            $totals = $summary['totals'] ?? [];
            $this->info(sprintf(
                'Totals: %d course(s), %d lesson(s), %d vocab word(s), %d prompt(s), %d vocab-only lesson(s)',
                $totals['courses'] ?? 0,
                $totals['lessons'] ?? 0,
                $totals['vocabulary_words'] ?? 0,
                $totals['prompts'] ?? 0,
                $totals['lessons_vocab_only'] ?? 0
            ));
            $this->info(sprintf(
                'Source rows: %d vocab, %d fill-in-the-blank',
                $summary['source_vocab_rows'] ?? 0,
                $summary['source_prompt_rows'] ?? 0
            ));

            return;
        }

        $duration = $summary['duration_seconds'] ?? null;
        $this->info('Import summary' . ($duration !== null ? " ({$duration}s)" : ''));

        $rows = [
            ['Courses created', $summary['courses_created'] ?? 0],
            ['Courses updated', $summary['courses_updated'] ?? 0],
            ['Lessons created', $summary['lessons_created'] ?? 0],
            ['Lessons skipped (already exist)', $summary['lessons_skipped'] ?? 0],
            ['Vocabulary created', $summary['vocabulary_created'] ?? 0],
            ['Vocabulary skipped', $summary['vocabulary_skipped'] ?? 0],
            ['Prompts created', $summary['prompts_created'] ?? 0],
            ['Prompts skipped', $summary['prompts_skipped'] ?? 0],
            ['Options created', $summary['options_created'] ?? 0],
            ['Games ensured', $summary['games_ensured'] ?? 0],
        ];

        if (!($summary['structure_only'] ?? true)) {
            $rows[] = ['Translations OK', $summary['translations_ok'] ?? 0];
            $rows[] = ['Images OK', $summary['images_ok'] ?? 0];
            $rows[] = ['Vocab TTS OK', $summary['tts_ok'] ?? 0];
            $rows[] = ['Enrichment errors', $summary['vocab_enrichment_errors'] ?? 0];
            $rows[] = ['Prompt TTS generated', $summary['prompt_tts_generated'] ?? 0];
        }

        $rows[] = ['Lessons vocab-only', $summary['lessons_vocab_only'] ?? 0];

        $this->table(['Metric', 'Count'], $rows);

        if (!empty($summary['by_cefr'])) {
            $this->newLine();
            $this->table(
                ['CEFR', 'Lessons processed', 'Vocab created', 'Prompts created'],
                collect($summary['by_cefr'])->map(function ($row, $cefr) {
                    return [$cefr, $row['lessons'], $row['vocabulary'], $row['prompts']];
                })->values()->all()
            );
        }

        $this->newLine();
        $this->info("Full log: {$reporter->logPath()}");
    }
}
