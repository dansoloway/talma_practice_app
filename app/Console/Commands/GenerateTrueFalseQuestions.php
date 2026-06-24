<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use App\Models\TrueFalseQuestion;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Tts\TrueFalseQuestionTtsService;
use Illuminate\Console\Command;

class GenerateTrueFalseQuestions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'true-false:generate 
                            {lesson : Lesson ID or slug}
                            {--count=6 : Number of questions to generate (5-8)}
                            {--approve : Auto-approve generated questions}
                            {--skip-audio : Skip automatic TTS audio generation}';

    /**
     * The console command description.
     */
    protected $description = 'Generate True/False questions for a lesson using AI';

    public function __construct(
        protected OpenAiQuestionGenerator $questionGenerator,
        protected TrueFalseQuestionTtsService $questionTts
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lessonIdentifier = $this->argument('lesson');
        $count = (int) $this->option('count');
        $autoApprove = $this->option('approve');
        $skipAudio = $this->option('skip-audio');

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
            $question = TrueFalseQuestion::create([
                'lesson_id' => $lesson->id,
                'statement' => $questionData['statement'],
                'is_true' => $questionData['is_true'],
                'explanation' => $questionData['explanation'],
                'category' => $questionData['category'] ?? null,
                'is_approved' => $autoApprove,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            if (!$skipAudio) {
                $this->questionTts->ensure($question);
            }

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
