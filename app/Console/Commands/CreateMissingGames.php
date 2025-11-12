<?php

namespace App\Console\Commands;

use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Vocabulary;
use Illuminate\Console\Command;

class CreateMissingGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'games:create-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create matching and flashcard games for lessons that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking lessons for missing games...');
        $this->newLine();

        $lessons = Lesson::where('is_active', true)
            ->whereNull('archived_at')
            ->get();

        $createdMatching = 0;
        $createdFlashcard = 0;
        $skippedMatching = [];
        $skippedFlashcard = [];

        foreach ($lessons as $lesson) {
            $vocabulary = $lesson->vocabulary()->where('is_active', true)->get();
            $vocabCount = $vocabulary->count();

            // Check for matching games
            $hasMatchingGame = $lesson->matchingGames()->exists();
            if (!$hasMatchingGame) {
                // For matching games, need at least 8 vocabulary items for a 4x4 grid (minimum)
                // But we'll allow 2+ for a smaller grid
                if ($vocabCount >= 2) {
                    // Determine grid size based on vocabulary count
                    // 4x4 grid needs 8 pairs, 6x6 needs 18 pairs, 8x8 needs 32 pairs
                    $pairs = floor($vocabCount / 2);
                    $gridSize = 4; // Default to 4x4
                    
                    if ($pairs >= 18) {
                        $gridSize = 6;
                    } elseif ($pairs >= 32) {
                        $gridSize = 8;
                    }
                    
                    // Use up to the required pairs
                    $vocabIds = $vocabulary->take($pairs * 2)->pluck('id')->toArray();
                    
                    MatchingGame::create([
                        'lesson_id' => $lesson->id,
                        'title' => $this->generateMatchingTitle($lesson),
                        'vocabulary_ids' => $vocabIds,
                        'grid_size' => $gridSize,
                        'is_active' => true,
                        'sort_order' => 1,
                    ]);
                    
                    $createdMatching++;
                    $this->info("✓ Created matching game for: {$lesson->title}");
                } else {
                    $skippedMatching[] = [
                        'lesson' => $lesson->title,
                        'vocab_count' => $vocabCount,
                        'reason' => 'Need at least 2 vocabulary items',
                    ];
                }
            }

            // Check for flashcard games
            $hasFlashcardGame = $lesson->flashcardGames()->exists();
            if (!$hasFlashcardGame) {
                // For flashcard games, need at least 1 vocabulary item
                if ($vocabCount >= 1) {
                    $vocabIds = $vocabulary->pluck('id')->toArray();
                    
                    // Determine game types based on vocabulary assets
                    $hasImages = $vocabulary->whereNotNull('image_path')->count() > 0;
                    $hasAudio = $vocabulary->whereNotNull('word_audio_path')->count() > 0;
                    
                    $gameTypes = [];
                    if ($hasImages && $hasAudio) {
                        $gameTypes = ['image_to_word', 'image_to_audio', 'audio_to_image', 'audio_to_word'];
                    } elseif ($hasImages) {
                        $gameTypes = ['image_to_word'];
                    } elseif ($hasAudio) {
                        $gameTypes = ['audio_to_word'];
                    }
                    
                    // Only create if we have at least one game type
                    if (!empty($gameTypes)) {
                        FlashcardGame::create([
                            'lesson_id' => $lesson->id,
                            'title' => $this->generateFlashcardTitle($lesson),
                            'vocabulary_ids' => $vocabIds,
                            'game_types' => $gameTypes,
                            'cards_per_game' => min(10, $vocabCount),
                            'is_active' => true,
                            'sort_order' => 1,
                        ]);
                        
                        $createdFlashcard++;
                        $this->info("✓ Created flashcard game for: {$lesson->title}");
                    } else {
                        $skippedFlashcard[] = [
                            'lesson' => $lesson->title,
                            'vocab_count' => $vocabCount,
                            'reason' => 'Vocabulary items need images or audio',
                        ];
                    }
                } else {
                    $skippedFlashcard[] = [
                        'lesson' => $lesson->title,
                        'vocab_count' => $vocabCount,
                        'reason' => 'Need at least 1 vocabulary item',
                    ];
                }
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Matching games created: {$createdMatching}");
        $this->info("  Flashcard games created: {$createdFlashcard}");

        if (!empty($skippedMatching)) {
            $this->newLine();
            $this->warn("Lessons skipped for matching games:");
            foreach ($skippedMatching as $skipped) {
                $this->line("  - {$skipped['lesson']}: {$skipped['reason']} (has {$skipped['vocab_count']} vocab items)");
            }
        }

        if (!empty($skippedFlashcard)) {
            $this->newLine();
            $this->warn("Lessons skipped for flashcard games:");
            foreach ($skippedFlashcard as $skipped) {
                $this->line("  - {$skipped['lesson']}: {$skipped['reason']} (has {$skipped['vocab_count']} vocab items)");
            }
        }

        return Command::SUCCESS;
    }

    private function generateMatchingTitle(Lesson $lesson): string
    {
        $count = $lesson->matchingGames()->count() + 1;
        return trim(sprintf('%s Matching Game %d', $lesson->title, $count));
    }

    private function generateFlashcardTitle(Lesson $lesson): string
    {
        $count = $lesson->flashcardGames()->count() + 1;
        return trim(sprintf('%s Flashcards %d', $lesson->title, $count));
    }
}

