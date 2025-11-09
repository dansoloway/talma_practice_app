<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Support\Str;

class PracticePalLessonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating TALMA Practice Pal lessons and vocabulary...');

        // Read sessions CSV
        $sessionsFile = base_path('data/we speak vocab - sessions.csv');
        if (!file_exists($sessionsFile)) {
            $this->command->error('Sessions CSV file not found: ' . $sessionsFile);
            return;
        }

        // Read vocabulary CSV
        $vocabFile = base_path('data/we speak vocab - vocab.csv');
        if (!file_exists($vocabFile)) {
            $this->command->error('Vocabulary CSV file not found: ' . $vocabFile);
            return;
        }

        // Parse sessions CSV
        $sessions = [];
        $handle = fopen($sessionsFile, 'r');
        while (($data = fgetcsv($handle)) !== FALSE) {
            $sessions[$data[0]] = [
                'id' => $data[0],
                'grade_level' => $data[1],
                'session_number' => $this->extractSessionNumber($data[2]),
                'session_title' => $data[2],
                'title' => $data[3] ?? 'Untitled Lesson',
            ];
        }
        fclose($handle);

        // Parse vocabulary CSV
        $vocabulary = [];
        $handle = fopen($vocabFile, 'r');
        while (($data = fgetcsv($handle)) !== FALSE) {
            $sessionId = $data[0];
            $word = trim($data[1]);
            $hebrew = isset($data[2]) ? trim($data[2]) : null;
            $arabic = isset($data[3]) ? trim($data[3]) : null;
            
            if (!isset($vocabulary[$sessionId])) {
                $vocabulary[$sessionId] = [];
            }
            $vocabulary[$sessionId][] = [
                'word' => $word,
                'hebrew' => $hebrew,
                'arabic' => $arabic,
            ];
        }
        fclose($handle);

        // Create lessons and vocabulary
        foreach ($sessions as $sessionData) {
            $sessionId = $sessionData['id'];
            
            $this->command->info("Creating lesson: {$sessionData['title']}");
            
            // Check if lesson already exists
            $slug = Str::slug($sessionData['title']);
            $existingLesson = Lesson::where('slug', $slug)->first();
            
            if ($existingLesson) {
                $this->command->warn("  ⚠️  Lesson already exists: {$sessionData['title']} (ID: {$existingLesson->id})");
                continue;
            }
            
            // Create lesson
            $lesson = Lesson::create([
                'title' => $sessionData['title'],
                'slug' => $slug,
                'grade_level' => $this->extractGradeNumber($sessionData['grade_level']),
                'session_number' => $sessionData['session_number'],
                'session_title' => $sessionData['session_title'],
                'instructions' => 'Complete the vocabulary presentation and activities for this lesson.',
                'is_active' => true,
                'sort_order' => $sessionId,
            ]);

            // Add vocabulary for this lesson
            if (isset($vocabulary[$sessionId])) {
                $this->command->info("  Adding " . count($vocabulary[$sessionId]) . " vocabulary words");
                
                foreach ($vocabulary[$sessionId] as $index => $vocabData) {
                    Vocabulary::create([
                        'lesson_id' => $lesson->id,
                        'english_word' => $vocabData['word'],
                        'hebrew_translation' => $vocabData['hebrew'],
                        'arabic_translation' => $vocabData['arabic'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                    ]);
                }
            }

            $this->command->info("  ✓ Created lesson: {$lesson->title} (ID: {$lesson->id})");
        }

        $this->command->info('✅ TALMA Practice Pal lessons and vocabulary created successfully!');
    }

    /**
     * Extract session number from session title
     */
    private function extractSessionNumber(string $sessionTitle): int
    {
        // Extract number from strings like "Session 3", "Session 4 - Part A", etc.
        if (preg_match('/Session (\d+)/', $sessionTitle, $matches)) {
            return (int) $matches[1];
        }
        return 1; // Default to 1 if no number found
    }

    /**
     * Extract grade number from grade level string
     */
    private function extractGradeNumber(string $gradeLevel): string
    {
        // Extract number from strings like "7th Grade", "8th Grade"
        if (preg_match('/(\d+)th Grade/', $gradeLevel, $matches)) {
            return $matches[1];
        }
        return '7'; // Default to 7th grade
    }
}
