<?php

namespace App\Console\Commands;

use App\Models\ClauseExercise;
use App\Models\Course;
use App\Models\FlashcardGame;
use App\Models\GrammarConcept;
use App\Models\GrammarSet;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Option;
use App\Models\Part;
use App\Models\Prompt;
use App\Models\PromptOptionAsset;
use App\Models\SentenceBuilderGame;
use App\Models\SentenceBuilderQuestion;
use App\Models\SpellingGame;
use App\Models\TrueFalseGame;
use App\Models\TrueFalseQuestion;
use App\Models\Vocabulary;
use App\Models\VocabularyPresentation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExportContentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'content:export
                            {--output= : Output file path (default: storage/app/content-export-{date}.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all course content (courses, lessons, vocabulary, games, etc.) to JSON for backup before production upgrades';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $outputPath = $this->option('output')
            ?? storage_path('app/content-export-' . now()->format('Y-m-d-His') . '.json');

        $this->info('Exporting content...');

        $export = [
            'exported_at' => now()->toIso8601String(),
            'version' => '1.0',
            'counts' => [],
            'content' => [],
        ];

        // Courses
        $export['content']['courses'] = $this->exportModel(Course::class);
        $export['counts']['courses'] = count($export['content']['courses']);

        // Organization-course pivot (which orgs have which courses)
        $export['content']['organization_course'] = DB::table('organization_course')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
        $export['counts']['organization_course'] = count($export['content']['organization_course']);

        // Lessons
        $export['content']['lessons'] = $this->exportModel(Lesson::class);
        $export['counts']['lessons'] = count($export['content']['lessons']);

        // Lesson review sources pivot
        $export['content']['lesson_review_sources'] = DB::table('lesson_review_sources')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
        $export['counts']['lesson_review_sources'] = count($export['content']['lesson_review_sources']);

        // Parts
        $export['content']['parts'] = $this->exportModel(Part::class);
        $export['counts']['parts'] = count($export['content']['parts']);

        // Prompts
        $export['content']['prompts'] = $this->exportModel(Prompt::class);
        $export['counts']['prompts'] = count($export['content']['prompts']);

        // Options
        $export['content']['options'] = $this->exportModel(Option::class);
        $export['counts']['options'] = count($export['content']['options']);

        // Prompt option assets
        $export['content']['prompt_option_assets'] = $this->exportModel(PromptOptionAsset::class);
        $export['counts']['prompt_option_assets'] = count($export['content']['prompt_option_assets']);

        // Vocabulary
        $export['content']['vocabulary'] = $this->exportModel(Vocabulary::class);
        $export['counts']['vocabulary'] = count($export['content']['vocabulary']);

        // Vocabulary presentations
        $export['content']['vocabulary_presentations'] = $this->exportModel(VocabularyPresentation::class);
        $export['counts']['vocabulary_presentations'] = count($export['content']['vocabulary_presentations']);

        // Matching games
        $export['content']['matching_games'] = $this->exportModel(MatchingGame::class);
        $export['counts']['matching_games'] = count($export['content']['matching_games']);

        // Flashcard games
        $export['content']['flashcard_games'] = $this->exportModel(FlashcardGame::class);
        $export['counts']['flashcard_games'] = count($export['content']['flashcard_games']);

        // Spelling games
        $export['content']['spelling_games'] = $this->exportModel(SpellingGame::class);
        $export['counts']['spelling_games'] = count($export['content']['spelling_games']);

        // Sentence builder games
        $export['content']['sentence_builder_games'] = $this->exportModel(SentenceBuilderGame::class);
        $export['counts']['sentence_builder_games'] = count($export['content']['sentence_builder_games']);

        // Sentence builder questions
        $export['content']['sentence_builder_questions'] = $this->exportModel(SentenceBuilderQuestion::class);
        $export['counts']['sentence_builder_questions'] = count($export['content']['sentence_builder_questions']);

        // True/False games
        $export['content']['true_false_games'] = $this->exportModel(TrueFalseGame::class);
        $export['counts']['true_false_games'] = count($export['content']['true_false_games']);

        // True/False questions
        $export['content']['true_false_questions'] = $this->exportModel(TrueFalseQuestion::class);
        $export['counts']['true_false_questions'] = count($export['content']['true_false_questions']);

        // Clause exercises
        $export['content']['clause_exercises'] = $this->exportModel(ClauseExercise::class);
        $export['counts']['clause_exercises'] = count($export['content']['clause_exercises']);

        // Grammar sets
        $export['content']['grammar_sets'] = $this->exportModel(GrammarSet::class);
        $export['counts']['grammar_sets'] = count($export['content']['grammar_sets']);

        // Grammar concepts
        $export['content']['grammar_concepts'] = $this->exportModel(GrammarConcept::class);
        $export['counts']['grammar_concepts'] = count($export['content']['grammar_concepts']);

        // Grammar set <-> lesson pivot
        $export['content']['grammar_set_lesson'] = DB::table('grammar_set_lesson')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->toArray();
        $export['counts']['grammar_set_lesson'] = count($export['content']['grammar_set_lesson']);

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->error('Failed to encode export as JSON: ' . json_last_error_msg());
            return self::FAILURE;
        }

        file_put_contents($outputPath, $json);

        $this->info("Export saved to: {$outputPath}");
        $this->table(
            ['Table', 'Rows'],
            collect($export['counts'])->map(fn ($c, $k) => [$k, $c])->values()->toArray()
        );

        return self::SUCCESS;
    }

    /**
     * Export model rows as arrays with proper date serialization.
     */
    protected function exportModel(string $modelClass): array
    {
        return $modelClass::all()
            ->map(fn ($model) => $this->modelToExportArray($model))
            ->values()
            ->toArray();
    }

    /**
     * Convert a model to an exportable array (attributes with dates as ISO strings).
     */
    protected function modelToExportArray(object $model): array
    {
        $attrs = $model->getAttributes();

        foreach ($model->getCasts() as $key => $cast) {
            if (! isset($attrs[$key])) {
                continue;
            }
            if (in_array($cast, ['date', 'datetime', 'immutable_date', 'immutable_datetime'], true)) {
                $attrs[$key] = $model->{$key}?->toIso8601String();
            }
        }

        return $attrs;
    }
}
