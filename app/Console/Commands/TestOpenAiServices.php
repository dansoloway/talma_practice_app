<?php

namespace App\Console\Commands;

use App\Services\ImageGeneration\OpenAiImageGenerator;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Translation\OpenAiTranslator;
use Illuminate\Console\Command;

class TestOpenAiServices extends Command
{
    protected $signature = 'test:openai-services 
                            {--skip-questions : Skip question generation test}
                            {--skip-image : Skip image generation test}
                            {--skip-translation : Skip translation test}';

    protected $description = 'Test all OpenAI services (questions, images, translations)';

    public function handle()
    {
        $this->info('🧪 Testing OpenAI Services');
        $this->info('==========================');
        $this->newLine();

        $allPassed = true;

        // Test 1: Question Generation
        if (!$this->option('skip-questions')) {
            $allPassed = $this->testQuestionGeneration() && $allPassed;
            $this->newLine();
        }

        // Test 2: Image Generation
        if (!$this->option('skip-image')) {
            $allPassed = $this->testImageGeneration() && $allPassed;
            $this->newLine();
        }

        // Test 3: Translation
        if (!$this->option('skip-translation')) {
            $allPassed = $this->testTranslation() && $allPassed;
            $this->newLine();
        }

        if ($allPassed) {
            $this->info('✅ All tests passed!');
            return 0;
        } else {
            $this->error('❌ Some tests failed. Check logs for details.');
            return 1;
        }
    }

    protected function testQuestionGeneration(): bool
    {
        $this->info('1️⃣  Testing Question Generation...');
        
        $generator = app(OpenAiQuestionGenerator::class);
        
        if (!$generator->enabled()) {
            $this->error('   ❌ OpenAI API key not configured');
            return false;
        }

        $this->info('   Generating test questions...');
        
        try {
            $lessonData = [
                'title' => 'Test Lesson',
                'vocabulary' => [
                    ['english_word' => 'water'],
                    ['english_word' => 'ice'],
                    ['english_word' => 'paper'],
                ],
                'prompts' => [
                    ['template' => 'We use {} for experiments'],
                ],
            ];

            $questions = $generator->generateQuestions($lessonData, 3);
            
            if (empty($questions)) {
                $this->error('   ❌ No questions generated');
                return false;
            }

            $this->info("   ✅ Generated " . count($questions) . " questions");
            
            // Show first question as example
            if (isset($questions[0])) {
                $q = $questions[0];
                $this->line("   Example: \"{$q['statement']}\" -> " . ($q['is_true'] ? 'TRUE' : 'FALSE'));
            }
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            return false;
        }
    }

    protected function testImageGeneration(): bool
    {
        $this->info('2️⃣  Testing Image Generation...');
        
        $generator = app(OpenAiImageGenerator::class);
        
        if (!$generator->enabled()) {
            $this->error('   ❌ OpenAI API key not configured');
            return false;
        }

        $testWord = 'book';
        $this->info("   Generating image for: {$testWord}...");
        $this->warn('   ⚠️  This may take 30-60 seconds...');
        
        try {
            $imagePath = $generator->generateVocabularyImage($testWord);
            
            if (!$imagePath) {
                $this->error('   ❌ Image generation failed');
                $this->info('   Check logs: tail -f storage/logs/laravel.log');
                return false;
            }

            $this->info("   ✅ Image generated successfully!");
            $this->line("   Path: {$imagePath}");
            $this->line("   URL: " . asset('storage/' . $imagePath));
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            $this->info('   Check logs: tail -f storage/logs/laravel.log');
            return false;
        }
    }

    protected function testTranslation(): bool
    {
        $this->info('3️⃣  Testing Translation...');
        
        $translator = app(OpenAiTranslator::class);
        
        if (!$translator->enabled()) {
            $this->error('   ❌ OpenAI API key not configured');
            return false;
        }

        $testWord = 'water';
        $this->info("   Translating: {$testWord}...");
        
        try {
            $translations = $translator->translate($testWord, true, true);
            
            if (empty($translations['hebrew']) && empty($translations['arabic'])) {
                $this->error('   ❌ Translation failed');
                $this->info('   Check logs: tail -f storage/logs/laravel.log');
                return false;
            }

            $this->info('   ✅ Translation successful!');
            $this->table(
                ['Language', 'Translation'],
                [
                    ['English', $testWord],
                    ['Hebrew', $translations['hebrew'] ?? 'N/A'],
                    ['Arabic', $translations['arabic'] ?? 'N/A'],
                ]
            );
            
            return true;
        } catch (\Exception $e) {
            $this->error("   ❌ Error: " . $e->getMessage());
            $this->info('   Check logs: tail -f storage/logs/laravel.log');
            return false;
        }
    }
}

