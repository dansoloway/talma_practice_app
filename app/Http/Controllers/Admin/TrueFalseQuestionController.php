<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\TrueFalseGame;
use App\Models\TrueFalseQuestion;
use App\Models\Vocabulary;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrueFalseQuestionController extends Controller
{
    /**
     * Display a listing of questions for a game.
     */
    public function index(Lesson $lesson, TrueFalseGame $trueFalseGame, Request $request)
    {
        $filterVersion = $request->get('filter_version');
        
        $query = $trueFalseGame->questions()->with('vocabulary');
        
        if ($filterVersion) {
            $query->where('game_version', $filterVersion);
        }
        
        $questions = $query->orderBy('is_approved')
            ->orderBy('sort_order')
            ->get();
        
        $pendingCount = $questions->where('is_approved', false)->count();
        $approvedCount = $questions->where('is_approved', true)->where('is_active', true)->count();
        
        // Calculate version counts for filter dropdown
        $allQuestions = $trueFalseGame->questions()->get();
        $versionCounts = [
            'easy' => [
                'total' => $allQuestions->where('game_version', 'easy')->count(),
                'approved' => $allQuestions->where('game_version', 'easy')->where('is_approved', true)->count(),
            ],
            'medium' => [
                'total' => $allQuestions->where('game_version', 'medium')->count(),
                'approved' => $allQuestions->where('game_version', 'medium')->where('is_approved', true)->count(),
            ],
            'hard' => [
                'total' => $allQuestions->where('game_version', 'hard')->count(),
                'approved' => $allQuestions->where('game_version', 'hard')->where('is_approved', true)->count(),
            ],
        ];
        
        return view('admin.true-false-questions.index', compact('lesson', 'trueFalseGame', 'questions', 'pendingCount', 'approvedCount', 'filterVersion', 'versionCounts'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $vocabulary = $lesson->getVocabularyForGames();
        
        return view('admin.true-false-questions.create', compact('lesson', 'trueFalseGame', 'vocabulary'));
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request, Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $validated = $request->validate([
            'statement' => 'required|string|max:500',
            'is_true' => 'required|boolean',
            'explanation' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['true_false_game_id'] = $trueFalseGame->id;
        $validated['game_version'] = $trueFalseGame->game_version; // Inherit from game
        $validated['is_approved'] = $request->boolean('is_approved', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? $trueFalseGame->questions()->max('sort_order') + 1;

        $question = TrueFalseQuestion::create($validated);
        
        // Attach vocabulary items
        $question->vocabulary()->attach($validated['vocabulary_ids']);

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', 'True/False question created successfully!');
    }

    /**
     * Display the specified question.
     */
    public function show(Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->load('vocabulary');
        return view('admin.true-false-questions.show', compact('lesson', 'trueFalseGame', 'trueFalseQuestion'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->load('vocabulary');
        $vocabulary = $lesson->getVocabularyForGames();
        $selectedVocabIds = $trueFalseQuestion->vocabulary->pluck('id')->toArray();
        
        return view('admin.true-false-questions.edit', compact('lesson', 'trueFalseGame', 'trueFalseQuestion', 'vocabulary', 'selectedVocabIds'));
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $validated = $request->validate([
            'statement' => 'required|string|max:500',
            'is_true' => 'required|boolean',
            'explanation' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_approved'] = $request->boolean('is_approved', $trueFalseQuestion->is_approved);
        $validated['is_active'] = $request->boolean('is_active', $trueFalseQuestion->is_active);

        $trueFalseQuestion->update($validated);
        
        // Sync vocabulary items
        $trueFalseQuestion->vocabulary()->sync($validated['vocabulary_ids']);

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->delete();

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Approve a question.
     */
    public function approve(Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->update(['is_approved' => true]);

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', 'Question approved!');
    }

    /**
     * Reject a question.
     */
    public function reject(Lesson $lesson, TrueFalseGame $trueFalseGame, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->update(['is_approved' => false]);

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', 'Question rejected.');
    }

    /**
     * Generate questions using AI.
     */
    public function generate(Lesson $lesson, TrueFalseGame $trueFalseGame, Request $request)
    {
        $count = (int) $request->input('count', 6);
        $generateAudio = $request->boolean('generate_audio', false);
        $autoApprove = $request->boolean('auto_approve', false);

        // Validate inputs
        if ($count < 5 || $count > 8) {
            return redirect()
                ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                ->with('error', 'Count must be between 5 and 8');
        }

        // Check if OpenAI is configured
        $questionGenerator = app(OpenAiQuestionGenerator::class);
        if (!$questionGenerator->enabled()) {
            return redirect()
                ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                ->with('error', 'OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }

        try {
            // Load lesson vocabulary
            $lesson->load('vocabulary');
            
            if ($lesson->vocabulary->isEmpty()) {
                return redirect()
                    ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                    ->with('error', 'This lesson has no vocabulary. Add vocabulary first.');
            }

            $lessonData = [
                'title' => $lesson->title,
                'game_version' => $trueFalseGame->game_version,
                'vocabulary' => $lesson->vocabulary->map(fn($v) => [
                    'english_word' => $v->english_word,
                    'id' => $v->id,
                ])->toArray(),
            ];

            // Generate questions
            $questions = $questionGenerator->generateQuestions($lessonData, $count);

            if (empty($questions)) {
                return redirect()
                    ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                    ->with('error', 'Failed to generate valid questions. Please try again.');
            }

            $ttsService = new ElevenLabsTtsService();
            $created = 0;
            $maxSortOrder = $trueFalseGame->questions()->max('sort_order') ?? 0;

            foreach ($questions as $index => $questionData) {
                $audioPath = null;

                // Generate audio if requested
                if ($generateAudio && $ttsService->enabled()) {
                    try {
                        $result = $ttsService->generateAndSaveSentence(
                            $questionData['statement'],
                            "tts/true-false/question_{$lesson->id}_{$trueFalseGame->id}_" . ($index + 1) . ".mp3",
                            null,
                            'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                        );
                        if ($result && isset($result['path'])) {
                            $audioPath = $result['path'];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to generate audio for question: " . $e->getMessage());
                    }
                }

                // Find vocabulary IDs from vocab_words array
                $vocabWords = $questionData['vocab_words'] ?? [];
                $vocabIds = [];
                foreach ($vocabWords as $vocabWord) {
                    $vocab = $lesson->vocabulary->firstWhere('english_word', $vocabWord);
                    if ($vocab) {
                        $vocabIds[] = $vocab->id;
                    }
                }

                // If no vocab IDs found, use first vocab as fallback
                if (empty($vocabIds) && $lesson->vocabulary->isNotEmpty()) {
                    $vocabIds = [$lesson->vocabulary->first()->id];
                }

                $question = TrueFalseQuestion::create([
                    'lesson_id' => $lesson->id,
                    'true_false_game_id' => $trueFalseGame->id,
                    'game_version' => $trueFalseGame->game_version,
                    'statement' => $questionData['statement'],
                    'is_true' => $questionData['is_true'],
                    'explanation' => $questionData['explanation'],
                    'category' => $questionData['category'] ?? null,
                    'audio_path' => $audioPath,
                    'is_approved' => $autoApprove,
                    'is_active' => true,
                    'sort_order' => $maxSortOrder + $index + 1,
                ]);

                // Attach vocabulary items
                if (!empty($vocabIds)) {
                    $question->vocabulary()->attach($vocabIds);
                }

                $created++;
            }

            $message = "Generated {$created} question(s)";
            if (!$autoApprove) {
                $message .= " (pending approval)";
            }

            return redirect()
                ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Question generation failed', [
                'lesson_id' => $lesson->id,
                'game_id' => $trueFalseGame->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
                ->with('error', 'Failed to generate questions: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve questions.
     */
    public function bulkApprove(Request $request, Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $questionIds = $request->input('question_ids');
        if (is_string($questionIds)) {
            $questionIds = json_decode($questionIds, true);
        }

        $request->merge(['question_ids' => $questionIds ?? []]);
        
        $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'exists:true_false_questions,id',
        ]);

        $count = TrueFalseQuestion::whereIn('id', $questionIds)
            ->where('true_false_game_id', $trueFalseGame->id)
            ->update(['is_approved' => true]);

        return redirect()
            ->route('admin.lessons.true-false-games.questions.index', [$lesson, $trueFalseGame])
            ->with('success', $count . ' question(s) approved!');
    }
}
