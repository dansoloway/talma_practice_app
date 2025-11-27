<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\TrueFalseQuestion;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateTrueFalseQuestions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'true-false:generate 
                            {lesson : Lesson ID or slug}
                            {--count=6 : Number of questions to generate (5-8)}
                            {--approve : Auto-approve generated questions}
                            {--generate-audio : Generate TTS audio for statements}';

    /**
     * The console command description.
     */
    protected $description = 'Generate True/False questions for a lesson using AI';

    protected OpenAiQuestionGenerator $questionGenerator;
    protected ElevenLabsTtsService $ttsService;

    public function __construct(OpenAiQuestionGenerator $questionGenerator, ElevenLabsTtsService $ttsService)
    {
        parent::__construct();
        $this->questionGenerator = $questionGenerator;
        $this->ttsService = $ttsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lessonIdentifier = $this->argument('lesson');
        $count = (int) $this->option('count');
        $autoApprove = $this->option('approve');
        $generateAudio = $this->option('generate-audio');

        // Validate count
        if ($count < 5 || $count > 8) {
            $this->error('Count must be between 5 and 8');
            return Command::FAILURE;
        }

        // Find lesson
        $lesson = is_numeric($lessonIdentifier)
            ? Lesson::find($lessonIdentifier)
            : Lesson::where('slug', $lessonIdentifier)->first();

        if (!$lesson) {
            $this->error("Lesson not found: {$lessonIdentifier}");
            return Command::FAILURE;
        }

        $this->info("Generating {$count} True/False questions for: {$lesson->title}");

        // Check if OpenAI is configured
        if (!$this->questionGenerator->enabled()) {
            $this->error('OpenAI API key not configured. Set OPENAI_API_KEY in .env');
            return Command::FAILURE;
        }

        // Load lesson data
        $lesson->load(['vocabulary', 'prompts.options']);
        
        $lessonData = [
            'title' => $lesson->title,
            'vocabulary' => $lesson->vocabulary->map(fn($v) => [
                'english_word' => $v->english_word,
                'hebrew_translation' => $v->hebrew_translation,
                'arabic_translation' => $v->arabic_translation,
            ])->toArray(),
            'prompts' => $lesson->prompts->map(fn($p) => [
                'prompt_text' => $p->prompt_text,
                'template' => $p->template,
                'options' => $p->options->pluck('label')->toArray(),
            ])->toArray(),
        ];

        // Generate questions
        $this->info('Calling OpenAI to generate questions...');
        try {
            $questions = $this->questionGenerator->generateQuestions($lessonData, $count);
        } catch (\Exception $e) {
            $this->error('Failed to generate questions: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Generated " . count($questions) . " questions");

        // Create question records
        $created = 0;
        foreach ($questions as $index => $questionData) {
            $audioPath = null;

            // Generate audio if requested
            if ($generateAudio && $this->ttsService->enabled()) {
                try {
                    $result = $this->ttsService->generateAndSaveVocabulary(
                        $questionData['statement'],
                        null, // No old path
                        'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                    );
                    if ($result) {
                        $audioPath = $result['path'];
                    }
                } catch (\Exception $e) {
                    $this->warn("Failed to generate audio for question " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            TrueFalseQuestion::create([
                'lesson_id' => $lesson->id,
                'statement' => $questionData['statement'],
                'is_true' => $questionData['is_true'],
                'explanation' => $questionData['explanation'],
                'category' => $questionData['category'] ?? null,
                'audio_path' => $audioPath,
                'is_approved' => $autoApprove,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            $created++;
            $this->line("  ✓ Created: " . substr($questionData['statement'], 0, 60) . "...");
        }

        $this->newLine();
        $this->info("✅ Created {$created} question(s)");
        
        if (!$autoApprove) {
            $this->info("⚠️  Questions are pending approval. Review them in the admin panel.");
        }

        return Command::SUCCESS;
    }
}
