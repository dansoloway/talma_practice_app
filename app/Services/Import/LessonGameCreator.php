<?php

namespace App\Services\Import;

use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\SpellingGame;
use App\Models\Vocabulary;
use Illuminate\Support\Facades\Log;

class LessonGameCreator
{
    /**
     * Create matching, flashcard, and spelling games for a lesson when enough vocabulary exists.
     */
    public function createGamesForLesson(Lesson $lesson): void
    {
        $vocabularyIds = $lesson->getVocabularyForGames()->pluck('id')->toArray();

        if ($vocabularyIds === [] || count($vocabularyIds) < 2) {
            Log::info("Skipping game creation for lesson {$lesson->id}: insufficient vocabulary (need at least 2 words)");
            return;
        }

        try {
            if ($lesson->matchingGames()->count() === 0) {
                MatchingGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => trim(sprintf('%s Matching Game 1', $lesson->title)),
                    'vocabulary_ids' => $vocabularyIds,
                    'is_active' => true,
                ]);
                Log::info("Created matching game for lesson {$lesson->id}");
            }

            if ($lesson->flashcardGames()->count() === 0) {
                $missingImages = Vocabulary::whereIn('id', $vocabularyIds)
                    ->where(function ($q) {
                        $q->whereNull('image_path')->orWhere('image_path', '');
                    })
                    ->count();
                $missingAudio = Vocabulary::whereIn('id', $vocabularyIds)
                    ->whereNull('word_audio_path')
                    ->count();

                $gameTypes = [];
                if ($missingImages > 0 && $missingAudio > 0) {
                    $gameTypes = [];
                } elseif ($missingImages > 0) {
                    $gameTypes = ['audio_to_word'];
                } elseif ($missingAudio > 0) {
                    $gameTypes = ['image_to_word'];
                } else {
                    $gameTypes = ['image_to_word', 'audio_to_word'];
                }

                FlashcardGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => trim(sprintf('%s Flashcards 1', $lesson->title)),
                    'vocabulary_ids' => $vocabularyIds,
                    'game_types' => $gameTypes,
                    'cards_per_game' => min(10, count($vocabularyIds)),
                    'is_active' => true,
                ]);
                Log::info("Created flashcard game for lesson {$lesson->id}");
            }

            if ($lesson->spellingGames()->count() === 0) {
                SpellingGame::create([
                    'lesson_id' => $lesson->id,
                    'title' => trim($lesson->title . ' Spelling Practice 1'),
                    'vocabulary_ids' => $vocabularyIds,
                    'difficulty' => 'medium',
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
                Log::info("Created spelling game for lesson {$lesson->id}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to create games for lesson {$lesson->id}: {$e->getMessage()}");
        }
    }

    /**
     * Remove auto-created games so they can be rebuilt after a forced vocab re-import.
     */
    public function clearGamesForLesson(Lesson $lesson): void
    {
        $lesson->matchingGames()->delete();
        $lesson->flashcardGames()->delete();
        $lesson->spellingGames()->delete();
    }

    /**
     * Permanently remove a lesson and its activities (used when pruning duplicate Summer imports).
     */
    public function deleteLesson(Lesson $lesson): void
    {
        $this->clearGamesForLesson($lesson);

        $lesson->prompts()->each(function ($prompt) {
            $prompt->options()->delete();
            $prompt->delete();
        });

        $lesson->vocabulary()->delete();
        $lesson->delete();
    }
}
