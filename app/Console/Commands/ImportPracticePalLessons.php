<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\PracticePalLessonsSeeder;

class ImportPracticePalLessons extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'talma:import-lessons {--force : Force import even if lessons already exist}';

    /**
     * The list of command aliases.
     *
     * @var array<int, string>
     */
    protected $aliases = ['wespeak:import-lessons'];

    /**
     * The console command description.
     */
    protected $description = 'Import TALMA Practice Pal lessons and vocabulary from CSV files';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 TALMA Practice Pal Lesson Import Tool');
        $this->info('================================');

        // Check if lessons already exist
        $existingLessons = \App\Models\Lesson::count();
        if ($existingLessons > 0 && !$this->option('force')) {
            $this->warn("Found {$existingLessons} existing lessons in the database.");
            
            if (!$this->confirm('Do you want to continue? This will add more lessons.')) {
                $this->info('Import cancelled.');
                return 0;
            }
        }

        // Check if CSV files exist
        $sessionsFile = base_path('data/we speak vocab - sessions.csv');
        $vocabFile = base_path('data/we speak vocab - vocab.csv');

        if (!file_exists($sessionsFile)) {
            $this->error('❌ Sessions CSV file not found: ' . $sessionsFile);
            $this->info('Please make sure "we speak vocab - sessions.csv" is in the data/ directory.');
            return 1;
        }

        if (!file_exists($vocabFile)) {
            $this->error('❌ Vocabulary CSV file not found: ' . $vocabFile);
            $this->info('Please make sure "we speak vocab - vocab.csv" is in the data/ directory.');
            return 1;
        }

        $this->info('📁 Found CSV files:');
        $this->info('  ✓ ' . $sessionsFile);
        $this->info('  ✓ ' . $vocabFile);
        $this->newLine();

        // Run the seeder
        try {
            $seeder = new PracticePalLessonsSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('🎉 Import completed successfully!');
            
            // Show summary
            $totalLessons = \App\Models\Lesson::count();
            $totalVocab = \App\Models\Vocabulary::count();
            
            $this->info("📊 Database Summary:");
            $this->info("  • Total Lessons: {$totalLessons}");
            $this->info("  • Total Vocabulary Words: {$totalVocab}");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
