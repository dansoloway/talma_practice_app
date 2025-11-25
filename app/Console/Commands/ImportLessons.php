<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Part;
use App\Models\Vocabulary;
use App\Models\Prompt;
use App\Models\Option;
use App\Models\MatchingGame;
use App\Models\FlashcardGame;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportLessons extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lessons:import 
                            {file : Path to JSON export file}
                            {--skip-existing : Skip lessons that already exist (by slug)}
                            {--force : Force import even if lessons exist}
                            {--dry-run : Show what would be imported without actually importing}';

    /**
     * The console command description.
     */
    protected $description = 'Import lessons from JSON export file (created by lessons:export)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $skipExisting = $this->option('skip-existing');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('Reading export file...');
        $jsonContent = File::get($filePath);
        $exportData = json_decode($jsonContent, true);

        if (!$exportData || !isset($exportData['lessons'])) {
            $this->error('Invalid export file format.');
            return Command::FAILURE;
        }

        $this->info("Found {$exportData['total_lessons']} lesson(s) in export");
        $this->info("Export date: {$exportData['export_date']}");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be imported');
        }

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($exportData['lessons'] as $lessonData) {
            $lessonInfo = $lessonData['lesson'];
            $slug = $lessonInfo['slug'];

            // Check if lesson already exists
            $existingLesson = Lesson::where('slug', $slug)->first();
            
            if ($existingLesson) {
                if ($skipExisting) {
                    $this->warn("⏭️  Skipping existing lesson: {$lessonInfo['title']}");
                    $skipped++;
                    continue;
                } elseif (!$force) {
                    if (!$this->confirm("Lesson '{$lessonInfo['title']}' already exists. Overwrite?", false)) {
                        $skipped++;
                        continue;
                    }
                }
            }

            try {
                if ($dryRun) {
                    $this->line("Would import: {$lessonInfo['title']} (Grade {$lessonInfo['grade_level']})");
                    $this->line("  - Vocabulary: " . count($lessonData['vocabulary']));
                    $this->line("  - Prompts: " . count($lessonData['prompts']));
                    $this->line("  - Matching Games: " . count($lessonData['matching_games']));
                    $this->line("  - Flashcard Games: " . count($lessonData['flashcard_games']));
                    $imported++;
                    continue;
                }

                \DB::beginTransaction();

                // Create or update lesson
                $lesson = Lesson::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $lessonInfo['title'],
                        'instructions' => $lessonInfo['instructions'] ?? null,
                        'grade_level' => $lessonInfo['grade_level'],
                        'session_number' => $lessonInfo['session_number'] ?? null,
                        'session_title' => $lessonInfo['session_title'] ?? null,
                        'is_active' => $lessonInfo['is_active'] ?? true,
                        'sort_order' => $lessonInfo['sort_order'] ?? 0,
                        'archived_at' => isset($lessonInfo['archived_at']) ? \Carbon\Carbon::parse($lessonInfo['archived_at']) : null,
                    ]
                );

                // Import parts
                foreach ($lessonData['parts'] ?? [] as $partData) {
                    Part::updateOrCreate(
                        [
                            'lesson_id' => $lesson->id,
                            'title' => $partData['title'],
                        ],
                        [
                            'description' => $partData['description'] ?? null,
                            'is_active' => $partData['is_active'] ?? true,
                            'sort_order' => $partData['sort_order'] ?? 0,
                        ]
                    );
                }

                // Import vocabulary
                foreach ($lessonData['vocabulary'] ?? [] as $vocabData) {
                    Vocabulary::updateOrCreate(
                        [
                            'lesson_id' => $lesson->id,
                            'english_word' => $vocabData['english_word'],
                        ],
                        [
                            'hebrew_translation' => $vocabData['hebrew_translation'] ?? null,
                            'arabic_translation' => $vocabData['arabic_translation'] ?? null,
                            'image_path' => $vocabData['image_path'] ?? null,
                            'word_audio_path' => $vocabData['word_audio_path'] ?? null,
                            'is_active' => $vocabData['is_active'] ?? true,
                            'sort_order' => $vocabData['sort_order'] ?? 0,
                        ]
                    );
                }

                // Import prompts
                foreach ($lessonData['prompts'] ?? [] as $promptData) {
                    // Find or create part
                    $part = null;
                    if (isset($promptData['part_id'])) {
                        $part = Part::where('id', $promptData['part_id'])->first();
                    }
                    if (!$part) {
                        $part = $lesson->getOrCreateDefaultPart();
                    }

                    $prompt = Prompt::create([
                        'lesson_id' => $lesson->id,
                        'part_id' => $part->id,
                        'prompt_text' => $promptData['prompt_text'],
                        'template' => $promptData['template'],
                        'prompt_audio_path' => $promptData['prompt_audio_path'] ?? null,
                        'correct_answer' => $promptData['correct_answer'] ?? null,
                        'tts_voice' => $promptData['tts_voice'] ?? null,
                        'is_active' => $promptData['is_active'] ?? true,
                        'sort_order' => $promptData['sort_order'] ?? 0,
                    ]);

                    // Import options
                    foreach ($promptData['options'] ?? [] as $optionData) {
                        Option::create([
                            'prompt_id' => $prompt->id,
                            'label' => $optionData['label'],
                            'image_path' => $optionData['image_path'] ?? null,
                            'word_audio_path' => $optionData['word_audio_path'] ?? null,
                            'sentence_audio_path' => $optionData['sentence_audio_path'] ?? null,
                            'is_active' => $optionData['is_active'] ?? true,
                            'sort_order' => $optionData['sort_order'] ?? 0,
                        ]);
                    }
                }

                // Import matching games
                foreach ($lessonData['matching_games'] ?? [] as $gameData) {
                    MatchingGame::create([
                        'lesson_id' => $lesson->id,
                        'title' => $gameData['title'],
                        'vocabulary_ids' => $gameData['vocabulary_ids'] ?? [],
                        'grid_size' => $gameData['grid_size'] ?? 4,
                        'is_active' => $gameData['is_active'] ?? true,
                        'sort_order' => $gameData['sort_order'] ?? 0,
                    ]);
                }

                // Import flashcard games
                foreach ($lessonData['flashcard_games'] ?? [] as $gameData) {
                    FlashcardGame::create([
                        'lesson_id' => $lesson->id,
                        'title' => $gameData['title'],
                        'game_types' => $gameData['game_types'] ?? ['audio_to_word'],
                        'vocabulary_ids' => $gameData['vocabulary_ids'] ?? [],
                        'cards_per_game' => $gameData['cards_per_game'] ?? 10,
                        'is_active' => $gameData['is_active'] ?? true,
                        'sort_order' => $gameData['sort_order'] ?? 0,
                    ]);
                }

                \DB::commit();
                $this->info("✅ Imported: {$lessonInfo['title']}");
                $imported++;

            } catch (\Exception $e) {
                \DB::rollBack();
                $this->error("❌ Error importing {$lessonInfo['title']}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Import Summary:");
        $this->line("  ✅ Imported: {$imported}");
        $this->line("  ⏭️  Skipped: {$skipped}");
        if ($errors > 0) {
            $this->line("  ❌ Errors: {$errors}");
        }

        return Command::SUCCESS;
    }
}

