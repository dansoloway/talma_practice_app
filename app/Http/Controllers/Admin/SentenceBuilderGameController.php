<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SentenceBuilderGame;
use App\Models\SentenceBuilderQuestion;
use App\Services\QuestionGeneration\OpenAiSentenceBuilderGenerator;
use Illuminate\Http\Request;

class SentenceBuilderGameController extends Controller
{
    /**
     * Display a listing of sentence builder games for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $games = $lesson->sentenceBuilderGames()->ordered()->get();
        return view('admin.sentence-builder-games.index', compact('lesson', 'games'));
    }

    /**
     * Show the form for creating a new sentence builder game.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.sentence-builder-games.create', compact('lesson'));
    }

    /**
     * Store a newly created sentence builder game.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'sort_order' => 'integer',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['title'] = $validated['title'] ?: $this->generateDefaultTitle($lesson);
        $validated['is_active'] = true; // Always active
        $validated['sort_order'] = $validated['sort_order'] ?? $lesson->sentenceBuilderGames()->max('sort_order') + 1 ?? 0;

        $game = SentenceBuilderGame::create($validated);

        return redirect()
            ->route('admin.lessons.sentence-builder-games.show', [$lesson, $game])
            ->with('success', 'Sentence Builder game created successfully!');
    }

    /**
     * Display the specified sentence builder game.
     */
    public function show(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame)
    {
        $questions = $sentenceBuilderGame->questions()->ordered()->get();
        $game = $sentenceBuilderGame;
        return view('admin.sentence-builder-games.show', compact('lesson', 'game', 'questions'));
    }

    /**
     * Show the form for editing the specified sentence builder game.
     */
    public function edit(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame)
    {
        $game = $sentenceBuilderGame;
        return view('admin.sentence-builder-games.edit', compact('lesson', 'game'));
    }

    /**
     * Update the specified sentence builder game.
     */
    public function update(Request $request, Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = true; // Always active

        $sentenceBuilderGame->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Sentence Builder game updated successfully!');
    }

    /**
     * Remove the specified sentence builder game.
     */
    public function destroy(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame)
    {
        $sentenceBuilderGame->delete();

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Sentence Builder game deleted successfully!');
    }

    /**
     * Generate questions using AI.
     */
    public function generate(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame, Request $request)
    {
        $count = (int) $request->input('count', 6);

        // Validate count
        if ($count < 3 || $count > 10) {
            return redirect()
                ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
                ->with('error', 'Count must be between 3 and 10');
        }

        // Check if OpenAI is configured
        $generator = app(OpenAiSentenceBuilderGenerator::class);
        if (!$generator->enabled()) {
            return redirect()
                ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
                ->with('error', 'OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }

        try {
            // Load lesson data
            $lesson->load(['vocabulary', 'prompts']);
            
            $lessonData = [
                'title' => $lesson->title,
                'vocabulary' => $lesson->vocabulary->map(fn($v) => [
                    'english_word' => $v->english_word,
                ])->toArray(),
                'prompts' => $lesson->prompts->map(fn($p) => [
                    'template' => $p->template,
                ])->toArray(),
            ];

            // Generate questions
            $questions = $generator->generateQuestions($lessonData, $count);

            $created = 0;
            $maxSortOrder = $sentenceBuilderGame->questions()->max('sort_order') ?? 0;

            foreach ($questions as $index => $questionData) {
                SentenceBuilderQuestion::create([
                    'sentence_builder_game_id' => $sentenceBuilderGame->id,
                    'correct_sentence' => $questionData['correct_sentence'],
                    'word_options' => $questionData['word_options'],
                    'explanation' => $questionData['explanation'],
                    'difficulty' => $questionData['difficulty'],
                    'is_active' => true,
                    'sort_order' => $maxSortOrder + $index + 1,
                ]);
                $created++;
            }

            return redirect()
                ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
                ->with('success', "Generated {$created} sentence builder questions!");

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
                ->with('error', 'Failed to generate questions: ' . $e->getMessage());
        }
    }

    /**
     * Store a manually created question.
     */
    public function storeQuestion(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame, Request $request)
    {
        $validated = $request->validate([
            'correct_sentence' => 'required|array|min:3|max:5',
            'correct_sentence.*' => 'required|string',
            'word_options' => 'required|array|min:6',
            'word_options.*' => 'required|string',
            'explanation' => 'required|string|max:500',
            'difficulty' => 'required|in:easy,medium,hard',
            'sort_order' => 'integer',
        ]);

        $validated['sentence_builder_game_id'] = $sentenceBuilderGame->id;
        $validated['is_active'] = true;
        $validated['sort_order'] = $validated['sort_order'] ?? $sentenceBuilderGame->questions()->max('sort_order') + 1 ?? 0;

        SentenceBuilderQuestion::create($validated);

        return redirect()
            ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
            ->with('success', 'Question created successfully!');
    }

    /**
     * Update a question.
     */
    public function updateQuestion(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame, SentenceBuilderQuestion $question, Request $request)
    {
        $validated = $request->validate([
            'correct_sentence' => 'required|array|min:3|max:5',
            'correct_sentence.*' => 'required|string',
            'word_options' => 'required|array|min:6',
            'word_options.*' => 'required|string',
            'explanation' => 'required|string|max:500',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $question->update($validated);

        return redirect()
            ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Delete a question.
     */
    public function deleteQuestion(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame, SentenceBuilderQuestion $question)
    {
        $question->delete();

        return redirect()
            ->route('admin.lessons.sentence-builder-games.show', [$lesson, $sentenceBuilderGame])
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Student-facing play view.
     */
    public function play(Lesson $lesson, SentenceBuilderGame $sentenceBuilderGame)
    {
        $questions = $sentenceBuilderGame->activeQuestions()->ordered()->get();

        if ($questions->isEmpty()) {
            return redirect()
                ->route('lessons.show', $lesson->slug)
                ->with('info', 'No questions available for this sentence builder game.');
        }

        $game = $sentenceBuilderGame;
        return view('sentence-builder-games.play', compact('lesson', 'game', 'questions'));
    }

    protected function generateDefaultTitle(Lesson $lesson): string
    {
        return trim($lesson->title . ' Sentence Builder ' . ($lesson->sentenceBuilderGames()->count() + 1));
    }
}
