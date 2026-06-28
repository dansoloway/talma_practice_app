<?php

namespace App\Console\Commands;

use App\Services\Import\SummerVocabAssetArchiver;
use Illuminate\Console\Command;

class ArchiveSummerVocabAssets extends Command
{
    protected $signature = 'talma:archive-summer-vocab-assets
                            {--cefr= : Limit to one CEFR level (Pre-A1, A1, A2, or B1)}';

    protected $description = 'Copy Summer Practice Pal vocabulary images and audio to an archive before re-import cleanup';

    public function handle(SummerVocabAssetArchiver $archiver): int
    {
        $slugs = $this->courseSlugs();
        if ($slugs === null) {
            $this->error('Unknown --cefr value. Use Pre-A1, A1, A2, or B1.');

            return self::FAILURE;
        }

        $this->info('Archiving Summer Practice Pal vocabulary assets...');

        $summary = $archiver->archiveAll($slugs, 'manual');

        $lessonCount = (int) $summary['lessons'];

        $this->table(
            ['Metric', 'Count'],
            [
                ['Courses scanned', $summary['courses']],
                ['Lessons with vocabulary', $lessonCount],
                ['Vocabulary rows', $summary['vocabulary_rows']],
                ['Images copied', $summary['images_copied']],
                ['Audio copied', $summary['audio_copied']],
            ],
        );

        $this->newLine();
        $this->info('Archive directory: ' . $summary['archive_dir']);
        $this->info('Manifest: ' . $summary['manifest_path']);

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

        $normalized = \App\Services\Import\SummerImportOptions::normalizeCefr((string) $cefr);
        if (!isset(SummerVocabAssetArchiver::COURSE_SLUGS[$normalized])) {
            return null;
        }

        return [SummerVocabAssetArchiver::COURSE_SLUGS[$normalized]];
    }
}
