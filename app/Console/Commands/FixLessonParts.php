<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\FlashcardGame;
use App\Models\Prompt;
use Illuminate\Console\Command;

class FixLessonParts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lessons:fix-parts';

    /**
     * The console command description.
     */
    protected $description = 'Fix lessons by ensuring all games and prompts are assigned to parts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing lesson parts...');

        $lessons = Lesson::all();
        $fixedCount = 0;

        foreach ($lessons as $lesson) {
            $this->line("Processing lesson: {$lesson->title}");
            
            $hasContent = false;
            
            // Check if lesson has any content (games or prompts)
            $matchingGamesCount = $lesson->matchingGames()->count();
            $flashcardGamesCount = $lesson->flashcardGames()->count();
            $promptsCount = $lesson->prompts()->count();
            
            if ($matchingGamesCount > 0 || $flashcardGamesCount > 0 || $promptsCount > 0) {
                $hasContent = true;
                $this->line("  - Found {$matchingGamesCount} matching games, {$flashcardGamesCount} flashcard games, {$promptsCount} prompts");
            }
            
            // If lesson has content but no parts, create a default part
            if ($hasContent && $lesson->parts()->count() === 0) {
                $this->warn("  - Lesson has content but no parts. Creating default part...");
                
                $defaultPart = $lesson->getOrCreateDefaultPart();
                $this->info("  - Created part: {$defaultPart->title}");
                
                // Assign all unassigned matching games to this part
                $unassignedMatchingGames = $lesson->matchingGames()->whereNull('part_id')->get();
                foreach ($unassignedMatchingGames as $game) {
                    $game->update(['part_id' => $defaultPart->id]);
                    $this->line("    - Assigned matching game '{$game->title}' to part");
                }
                
                // Assign all unassigned flashcard games to this part
                $unassignedFlashcardGames = $lesson->flashcardGames()->whereNull('part_id')->get();
                foreach ($unassignedFlashcardGames as $game) {
                    $game->update(['part_id' => $defaultPart->id]);
                    $this->line("    - Assigned flashcard game '{$game->title}' to part");
                }
                
                // Assign all unassigned prompts to this part
                $unassignedPrompts = $lesson->prompts()->whereNull('part_id')->get();
                foreach ($unassignedPrompts as $prompt) {
                    $prompt->update(['part_id' => $defaultPart->id]);
                    $this->line("    - Assigned prompt '{$prompt->prompt_text}' to part");
                }
                
                $fixedCount++;
            } else if ($hasContent) {
                // Lesson has parts, but check if any content is unassigned
                $unassignedContent = false;
                
                $unassignedMatchingGames = $lesson->matchingGames()->whereNull('part_id')->get();
                $unassignedFlashcardGames = $lesson->flashcardGames()->whereNull('part_id')->get();
                $unassignedPrompts = $lesson->prompts()->whereNull('part_id')->get();
                
                if ($unassignedMatchingGames->count() > 0 || $unassignedFlashcardGames->count() > 0 || $unassignedPrompts->count() > 0) {
                    $unassignedContent = true;
                    $this->warn("  - Found unassigned content. Assigning to last part...");
                    
                    $lastPart = $lesson->getLastPart();
                    
                    foreach ($unassignedMatchingGames as $game) {
                        $game->update(['part_id' => $lastPart->id]);
                        $this->line("    - Assigned matching game '{$game->title}' to part '{$lastPart->title}'");
                    }
                    
                    foreach ($unassignedFlashcardGames as $game) {
                        $game->update(['part_id' => $lastPart->id]);
                        $this->line("    - Assigned flashcard game '{$game->title}' to part '{$lastPart->title}'");
                    }
                    
                    foreach ($unassignedPrompts as $prompt) {
                        $prompt->update(['part_id' => $lastPart->id]);
                        $this->line("    - Assigned prompt '{$prompt->prompt_text}' to part '{$lastPart->title}'");
                    }
                    
                    $fixedCount++;
                }
            }
            
            if (!$hasContent) {
                $this->line("  - No content found, skipping");
            } else if (!$unassignedContent ?? true) {
                $this->line("  - All content already properly assigned to parts");
            }
        }

        $this->newLine();
        $this->info("Fixed {$fixedCount} lessons");
        $this->info('Done!');

        return 0;
    }
}