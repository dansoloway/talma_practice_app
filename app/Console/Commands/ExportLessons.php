<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportLessons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lessons:export 
                            {--grade= : Filter by grade level (e.g., 7, 8)}
                            {--all : Export all lessons including archived}
                            {--output= : Output file path (default: storage/exports/lessons-{timestamp}.json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export lessons with all related content (vocabulary, prompts, games, etc.) to JSON';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $grade = $this->option('grade');
        $all = $this->option('all');
        $outputPath = $this->option('output') ?? storage_path('exports/lessons-' . now()->format('Y-m-d-His') . '.json');

        // Ensure exports directory exists
        $exportDir = dirname($outputPath);
        if (!File::exists($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $this->info('Exporting lessons...');

        // Build query
        $query = Lesson::query();

        if ($grade) {
            $query->where('grade_level', $grade);
            $this->info("Filtering by grade level: {$grade}");
        }

        if (!$all) {
            $query->active();
            $this->info('Exporting only active, non-archived lessons');
        } else {
            $this->info('Exporting all lessons (including archived)');
        }

        $lessons = $query->with([
            'parts' => function ($q) {
                $q->orderBy('sort_order');
            },
            'vocabulary' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            },
            'prompts' => function ($q) {
                $q->where('is_active', true)
                  ->orderBy('sort_order')
                  ->with([
                      'options' => function ($opt) {
                          $opt->where('is_active', true)->orderBy('sort_order');
                      },
                      'assets' => function ($asset) {
                          $asset->orderBy('option_id');
                      }
                  ]);
            },
            'matchingGames' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            },
            'flashcardGames' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order');
            },
            'vocabularyPresentations' => function ($q) {
                $q->orderBy('sort_order');
            }
        ])->orderBy('grade_level')
          ->orderBy('session_number')
          ->orderBy('sort_order')
          ->get();

        if ($lessons->isEmpty()) {
            $this->warn('No lessons found matching criteria.');
            return Command::FAILURE;
        }

        $this->info("Found {$lessons->count()} lesson(s)");

        // Transform to exportable format
        $exportData = [
            'export_date' => now()->toIso8601String(),
            'export_version' => '1.0',
            'total_lessons' => $lessons->count(),
            'lessons' => $lessons->map(function ($lesson) {
                return [
                    'lesson' => [
                        'title' => $lesson->title,
                        'slug' => $lesson->slug,
                        'instructions' => $lesson->instructions,
                        'grade_level' => $lesson->grade_level,
                        'session_number' => $lesson->session_number,
                        'session_title' => $lesson->session_title,
                        'is_active' => $lesson->is_active,
                        'sort_order' => $lesson->sort_order,
                        'archived_at' => $lesson->archived_at?->toIso8601String(),
                    ],
                    'parts' => $lesson->parts->map(function ($part) {
                        return [
                            'title' => $part->title,
                            'description' => $part->description,
                            'is_active' => $part->is_active,
                            'sort_order' => $part->sort_order,
                        ];
                    })->toArray(),
                    'vocabulary' => $lesson->vocabulary->map(function ($vocab) {
                        return [
                            'english_word' => $vocab->english_word,
                            'hebrew_translation' => $vocab->hebrew_translation,
                            'arabic_translation' => $vocab->arabic_translation,
                            'image_path' => $vocab->image_path,
                            'word_audio_path' => $vocab->word_audio_path,
                            'is_active' => $vocab->is_active,
                            'sort_order' => $vocab->sort_order,
                        ];
                    })->toArray(),
                    'prompts' => $lesson->prompts->map(function ($prompt) {
                        return [
                            'prompt_text' => $prompt->prompt_text,
                            'template' => $prompt->template,
                            'prompt_audio_path' => $prompt->prompt_audio_path,
                            'correct_answer' => $prompt->correct_answer,
                            'tts_voice' => $prompt->tts_voice,
                            'is_active' => $prompt->is_active,
                            'sort_order' => $prompt->sort_order,
                            'part_id' => $prompt->part_id,
                            'options' => $prompt->options->map(function ($option) {
                                return [
                                    'label' => $option->label,
                                    'image_path' => $option->image_path,
                                    'word_audio_path' => $option->word_audio_path,
                                    'sentence_audio_path' => $option->sentence_audio_path,
                                    'is_active' => $option->is_active,
                                    'sort_order' => $option->sort_order,
                                ];
                            })->toArray(),
                            'assets' => $prompt->assets->map(function ($asset) {
                                return [
                                    'option_id' => $asset->option_id,
                                    'generated_sentence' => $asset->generated_sentence,
                                    'audio_path' => $asset->audio_path,
                                    'duration_ms' => $asset->duration_ms,
                                ];
                            })->toArray(),
                        ];
                    })->toArray(),
                    'matching_games' => $lesson->matchingGames->map(function ($game) {
                        return [
                            'title' => $game->title,
                            'vocabulary_ids' => $game->vocabulary_ids,
                            'grid_size' => $game->grid_size,
                            'is_active' => $game->is_active,
                            'sort_order' => $game->sort_order,
                        ];
                    })->toArray(),
                    'flashcard_games' => $lesson->flashcardGames->map(function ($game) {
                        return [
                            'title' => $game->title,
                            'game_types' => $game->game_types,
                            'vocabulary_ids' => $game->vocabulary_ids,
                            'cards_per_game' => $game->cards_per_game,
                            'is_active' => $game->is_active,
                            'sort_order' => $game->sort_order,
                        ];
                    })->toArray(),
                    'vocabulary_presentations' => $lesson->vocabularyPresentations->map(function ($vp) {
                        return [
                            'vocabulary_ids' => $vp->vocabulary_ids,
                            'is_active' => $vp->is_active,
                            'sort_order' => $vp->sort_order,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        // Write to file
        File::put($outputPath, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("✅ Export complete!");
        $this->info("📁 File: {$outputPath}");
        $this->info("📊 Exported {$lessons->count()} lesson(s)");

        // Show summary
        $totalVocab = $lessons->sum(fn($l) => $l->vocabulary->count());
        $totalPrompts = $lessons->sum(fn($l) => $l->prompts->count());
        $totalMatching = $lessons->sum(fn($l) => $l->matchingGames->count());
        $totalFlashcard = $lessons->sum(fn($l) => $l->flashcardGames->count());

        $this->newLine();
        $this->info("Summary:");
        $this->line("  • Vocabulary items: {$totalVocab}");
        $this->line("  • Prompts: {$totalPrompts}");
        $this->line("  • Matching games: {$totalMatching}");
        $this->line("  • Flashcard games: {$totalFlashcard}");

        return Command::SUCCESS;
    }
}

