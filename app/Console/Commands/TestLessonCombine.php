<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\Vocabulary;
use App\Models\MatchingGame;
use App\Models\FlashcardGame;
use App\Models\SpellingGame;
use App\Models\Prompt;
use App\Models\Option;
use App\Models\ClauseExercise;
use App\Models\TrueFalseGame;
use App\Models\TrueFalseQuestion;
use App\Models\Part;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestLessonCombine extends Command
{
    protected $signature = 'test:lesson-combine {--cleanup : Clean up test data after test}';
    protected $description = 'Test the lesson combination feature';

    public function handle()
    {
        $this->info('🧪 Testing Lesson Combination Feature');
        $this->info('=====================================');
        $this->newLine();

        $cleanup = $this->option('cleanup');
        
        try {
            // Step 1: Create test lessons
            $this->info('1️⃣  Creating test lessons...');
            $part1 = $this->createTestLesson('Test Lesson 6', 6, 1, 'Part 1');
            $part2 = $this->createTestLesson('Test Lesson 6', 6, 2, 'Part 2');
            $target = $this->createTestLesson('Test Lesson 6 Combined', 6, null, 'Target');
            
            $this->info("   ✓ Created Part 1 (ID: {$part1->id})");
            $this->info("   ✓ Created Part 2 (ID: {$part2->id})");
            $this->info("   ✓ Created Target (ID: {$target->id})");
            $this->newLine();

            // Step 2: Add vocabulary to test lessons
            $this->info('2️⃣  Adding vocabulary...');
            $part1Vocab = $this->addVocabulary($part1, ['cat', 'dog', 'bird', 'fish', 'duplicate']);
            $part2Vocab = $this->addVocabulary($part2, ['duplicate', 'elephant', 'tiger', 'lion', 'bear']);
            $targetVocab = $this->addVocabulary($target, ['zebra']);
            
            $this->info("   ✓ Part 1: " . count($part1Vocab) . " words");
            $this->info("   ✓ Part 2: " . count($part2Vocab) . " words (1 duplicate)");
            $this->info("   ✓ Target: " . count($targetVocab) . " words");
            $this->newLine();

            // Step 3: Add games to source lessons
            $this->info('3️⃣  Adding games to source lessons...');
            $this->addMatchingGame($part1, $part1Vocab);
            $this->addMatchingGame($part2, $part2Vocab);
            $this->addFlashcardGame($part1, $part1Vocab);
            $this->addSpellingGame($part2, $part2Vocab);
            $this->info("   ✓ Games added");
            $this->newLine();

            // Step 4: Add prompts
            $this->info('4️⃣  Adding prompts...');
            $prompt1 = $this->addPrompt($part1, 'What is this?');
            $prompt2 = $this->addPrompt($part2, 'Can you see it?');
            $this->info("   ✓ Prompts added");
            $this->newLine();

            // Step 5: Test combination
            $this->info('5️⃣  Testing combination...');
            $response = $this->callCombine($part1->id, $part2->id, $target->id);
            
            if (!$response['success']) {
                $this->error("   ✗ Combination failed: " . $response['message']);
                return 1;
            }
            
            $this->info("   ✓ Combination successful");
            $this->newLine();

            // Step 6: Verify results
            $this->info('6️⃣  Verifying results...');
            $target->refresh();
            
            $allTests = [
                $this->testVocabularyMerge($target, $part1Vocab, $part2Vocab, $targetVocab),
                $this->testMatchingGameSplitting($target),
                $this->testOtherGames($target),
                $this->testPrompts($target),
                $this->testSourceArchived($part1, $part2),
            ];

            $passed = array_filter($allTests);
            $failed = array_filter($allTests, fn($v) => !$v);

            $this->newLine();
            $this->info('📊 Test Results:');
            $this->info("   ✅ Passed: " . count($passed) . "/" . count($allTests));
            $this->info("   ❌ Failed: " . count($failed) . "/" . count($allTests));

            // Step 7: Cleanup
            if ($cleanup) {
                $this->newLine();
                $this->info('7️⃣  Cleaning up test data...');
                $this->cleanupTestData([$part1->id, $part2->id, $target->id]);
                $this->info("   ✓ Cleanup complete");
            } else {
                $this->newLine();
                $this->warn('⚠️  Test data not cleaned up. Use --cleanup flag to remove.');
                $this->info("   Test lessons: {$part1->id}, {$part2->id}, {$target->id}");
            }

            return count($failed) === 0 ? 0 : 1;

        } catch (\Exception $e) {
            $this->error("❌ Test failed with exception: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    private function createTestLesson($title, $session, $part, $suffix)
    {
        $timestamp = time();
        return Lesson::create([
            'title' => $title . ' ' . $suffix,
            'slug' => 'test-lesson-' . $session . ($part ? '-part-' . $part : '-target') . '-' . $timestamp,
            'session_number' => $session,
            'part_number' => $part,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function addVocabulary($lesson, $words)
    {
        $vocab = [];
        foreach ($words as $index => $word) {
            $vocab[] = Vocabulary::create([
                'lesson_id' => $lesson->id,
                'english_word' => $word,
                'hebrew_translation' => 'test',
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
        }
        return $vocab;
    }

    private function addMatchingGame($lesson, $vocab)
    {
        $vocabIds = collect($vocab)->pluck('id')->toArray();
        MatchingGame::create([
            'lesson_id' => $lesson->id,
            'title' => $lesson->title . ' Matching Game',
            'vocabulary_ids' => $vocabIds,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function addFlashcardGame($lesson, $vocab)
    {
        $vocabIds = collect($vocab)->pluck('id')->toArray();
        FlashcardGame::create([
            'lesson_id' => $lesson->id,
            'title' => $lesson->title . ' Flashcards',
            'vocabulary_ids' => $vocabIds,
            'game_types' => ['image_to_text'], // Default game type
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function addSpellingGame($lesson, $vocab)
    {
        $vocabIds = collect($vocab)->pluck('id')->toArray();
        SpellingGame::create([
            'lesson_id' => $lesson->id,
            'title' => $lesson->title . ' Spelling',
            'vocabulary_ids' => $vocabIds,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function addPrompt($lesson, $text)
    {
        $part = $lesson->getOrCreateDefaultPart();
        $prompt = Prompt::create([
            'lesson_id' => $lesson->id,
            'part_id' => $part->id,
            'prompt_text' => $text,
            'template' => '{{answer}}',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Option::create([
            'prompt_id' => $prompt->id,
            'label' => 'Option 1',
            'image_path' => '',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        return $prompt;
    }

    private function callCombine($part1Id, $part2Id, $targetId)
    {
        $controller = app(\App\Http\Controllers\Admin\LessonController::class);
        
        // Create a proper request object
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'source_lesson_ids' => [$part1Id, $part2Id],
            'target_lesson_id' => $targetId,
        ]);
        $request->setMethod('POST');
        
        $response = $controller->combine($request);
        return json_decode($response->getContent(), true);
    }

    private function testVocabularyMerge($target, $part1Vocab, $part2Vocab, $targetVocab)
    {
        $this->info('   Testing vocabulary merge...');
        
        $targetVocabWords = $target->vocabulary()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('english_word')
            ->toArray();
        
        // Should have: zebra (from target), cat, dog, bird, fish, duplicate (from part1), elephant, tiger, lion, bear (from part2)
        // Duplicate should be skipped
        $expectedCount = 1 + 5 + 4; // target + part1 + part2 (minus 1 duplicate)
        $actualCount = count($targetVocabWords);
        
        // Check order: target first, then part1, then part2
        $expectedOrder = ['zebra', 'cat', 'dog', 'bird', 'fish', 'duplicate', 'elephant', 'tiger', 'lion', 'bear'];
        
        $passed = $actualCount === $expectedCount && $targetVocabWords === $expectedOrder;
        
        if ($passed) {
            $this->info("      ✅ Vocabulary count and order correct ({$actualCount} words)");
        } else {
            $this->error("      ❌ Vocabulary test failed");
            $this->error("         Expected: " . implode(', ', $expectedOrder));
            $this->error("         Actual: " . implode(', ', $targetVocabWords));
        }
        
        return $passed;
    }

    private function testMatchingGameSplitting($target)
    {
        $this->info('   Testing matching game splitting...');
        
        $matchingGames = $target->matchingGames;
        $totalVocabInGames = 0;
        
        foreach ($matchingGames as $game) {
            $totalVocabInGames += count($game->vocabulary_ids);
            // Each game should have <= 12 words
            if (count($game->vocabulary_ids) > 12) {
                $this->error("      ❌ Matching game has more than 12 words: " . count($game->vocabulary_ids));
                return false;
            }
        }
        
        // Should have 10 words total (after merge)
        $expectedVocabCount = 10;
        $passed = $totalVocabInGames >= $expectedVocabCount && $matchingGames->count() >= 1;
        
        if ($passed) {
            $this->info("      ✅ Matching games created correctly ({$matchingGames->count()} game(s), {$totalVocabInGames} words total)");
        } else {
            $this->error("      ❌ Matching game test failed");
        }
        
        return $passed;
    }

    private function testOtherGames($target)
    {
        $this->info('   Testing other games...');
        
        $flashcardGames = $target->flashcardGames->count();
        $spellingGames = $target->spellingGames->count();
        
        // Should have at least 1 flashcard game and 1 spelling game
        $passed = $flashcardGames >= 1 && $spellingGames >= 1;
        
        if ($passed) {
            $this->info("      ✅ Other games created ({$flashcardGames} flashcard, {$spellingGames} spelling)");
        } else {
            $this->error("      ❌ Other games test failed");
        }
        
        return $passed;
    }

    private function testPrompts($target)
    {
        $this->info('   Testing prompts import...');
        
        $prompts = $target->prompts;
        $expectedCount = 2; // One from each source lesson
        
        $passed = $prompts->count() === $expectedCount;
        
        if ($passed) {
            $this->info("      ✅ Prompts imported correctly ({$prompts->count()} prompts)");
        } else {
            $this->error("      ❌ Prompts test failed (expected {$expectedCount}, got {$prompts->count()})");
        }
        
        return $passed;
    }

    private function testSourceArchived($part1, $part2)
    {
        $this->info('   Testing source lessons archived...');
        
        $part1->refresh();
        $part2->refresh();
        
        $passed = $part1->isArchived() && $part2->isArchived();
        
        if ($passed) {
            $this->info("      ✅ Source lessons archived correctly");
        } else {
            $this->error("      ❌ Source lessons not archived");
        }
        
        return $passed;
    }

    private function cleanupTestData($lessonIds)
    {
        DB::transaction(function () use ($lessonIds) {
            foreach ($lessonIds as $lessonId) {
                $lesson = Lesson::find($lessonId);
                if ($lesson) {
                    // Delete related data
                    $lesson->vocabulary()->delete();
                    $lesson->matchingGames()->delete();
                    $lesson->flashcardGames()->delete();
                    $lesson->spellingGames()->delete();
                    $lesson->prompts()->each(function ($prompt) {
                        $prompt->options()->delete();
                        $prompt->delete();
                    });
                    $lesson->clauseExercises()->delete();
                    $lesson->trueFalseGames()->each(function ($game) {
                        $game->questions()->delete();
                        $game->delete();
                    });
                    $lesson->sentenceBuilderGames()->delete();
                    $lesson->parts()->delete();
                    $lesson->delete();
                }
            }
        });
    }
}
