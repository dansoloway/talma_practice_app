<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\TrueFalseQuestion;
use App\Models\Vocabulary;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TrueFalseQuestionController extends Controller
{
    /**
     * Display a listing of questions for a lesson.
     */
    public function index(Lesson $lesson, Request $request)
    {
        // Get all questions, optionally filter by version
        $query = $lesson->trueFalseQuestions()->with('vocabulary');
        
        $filterVersion = $request->get('filter_version');
        if ($filterVersion && in_array($filterVersion, ['easy', 'medium', 'hard'])) {
            $query->forVersion($filterVersion);
        }
        
        $questions = $query->orderBy('is_approved')
            ->orderBy('sort_order')
            ->get();
        
        $pendingCount = $questions->where('is_approved', false)->count();
        $approvedCount = $questions->where('is_approved', true)->where('is_active', true)->count();
        
        // Get counts for each version
        $versionCounts = [];
        foreach (['easy', 'medium', 'hard'] as $version) {
            $versionCounts[$version] = [
                'total' => $lesson->trueFalseQuestions()->forVersion($version)->count(),
                'approved' => $lesson->trueFalseQuestions()->forVersion($version)->where('is_approved', true)->where('is_active', true)->count(),
                'pending' => $lesson->trueFalseQuestions()->forVersion($version)->where('is_approved', false)->count(),
            ];
        }
        
        return view('admin.true-false-questions.index', compact('lesson', 'questions', 'pendingCount', 'approvedCount', 'filterVersion', 'versionCounts'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Lesson $lesson, Request $request)
    {
        $gameVersion = $request->get('version', 'easy');
        if (!in_array($gameVersion, ['easy', 'medium', 'hard'])) {
            $gameVersion = 'easy';
        }

        $vocabulary = $lesson->vocabulary()->active()->ordered()->get();
        
        return view('admin.true-false-questions.create', compact('lesson', 'gameVersion', 'vocabulary'));
    }

    /**
     * Store a newly created question.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'statement' => 'required|string|max:500',
            'is_true' => 'required|boolean',
            'explanation' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'game_version' => 'required|in:easy,medium,hard',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['is_approved'] = $request->boolean('is_approved', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? $lesson->trueFalseQuestions()
            ->forVersion($validated['game_version'])
            ->max('sort_order') + 1;

        $question = TrueFalseQuestion::create($validated);
        
        // Attach vocabulary items
        $question->vocabulary()->attach($validated['vocabulary_ids']);

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'True/False question created successfully!');
    }

    /**
     * Display the specified question.
     */
    public function show(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->load('vocabulary');
        return view('admin.true-false-questions.show', compact('lesson', 'trueFalseQuestion'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->load('vocabulary');
        $vocabulary = $lesson->vocabulary()->active()->ordered()->get();
        $selectedVocabIds = $trueFalseQuestion->vocabulary->pluck('id')->toArray();
        
        return view('admin.true-false-questions.edit', compact('lesson', 'trueFalseQuestion', 'vocabulary', 'selectedVocabIds'));
    }

    /**
     * Update the specified question.
     */
    public function update(Request $request, Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $validated = $request->validate([
            'statement' => 'required|string|max:500',
            'is_true' => 'required|boolean',
            'explanation' => 'required|string|max:1000',
            'category' => 'nullable|string|max:100',
            'game_version' => 'required|in:easy,medium,hard',
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
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'Question updated successfully!');
    }

    /**
     * Remove the specified question.
     */
    public function destroy(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->delete();

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'Question deleted successfully!');
    }

    /**
     * Approve a question.
     */
    public function approve(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->update(['is_approved' => true]);

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'Question approved!');
    }

    /**
     * Reject a question.
     */
    public function reject(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        $trueFalseQuestion->update(['is_approved' => false]);

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'Question rejected.');
    }

    /**
     * Generate questions using AI.
     */
    public function generate(Lesson $lesson, Request $request)
    {
        $count = (int) $request->input('count', 6);
        $gameVersion = $request->input('game_version', 'easy');
        $generateAudio = $request->boolean('generate_audio', false);
        $autoApprove = $request->boolean('auto_approve', false);

        // Validate inputs
        if ($count < 5 || $count > 8) {
            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'Count must be between 5 and 8');
        }

        if (!in_array($gameVersion, ['easy', 'medium', 'hard'])) {
            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'Invalid game version');
        }

        // Check if OpenAI is configured
        $questionGenerator = app(OpenAiQuestionGenerator::class);
        if (!$questionGenerator->enabled()) {
            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }

        try {
            // Load lesson vocabulary
            $lesson->load('vocabulary');
            
            if ($lesson->vocabulary->isEmpty()) {
                return redirect()
                    ->route('admin.lessons.true-false-questions.index', $lesson)
                    ->with('error', 'This lesson has no vocabulary. Add vocabulary first.');
            }

            $lessonData = [
                'title' => $lesson->title,
                'game_version' => $gameVersion,
                'vocabulary' => $lesson->vocabulary->map(fn($v) => [
                    'english_word' => $v->english_word,
                    'id' => $v->id,
                ])->toArray(),
            ];

            // Generate questions
            $questions = $questionGenerator->generateQuestions($lessonData, $count);

            if (empty($questions)) {
                return redirect()
                    ->route('admin.lessons.true-false-questions.index', $lesson)
                    ->with('error', 'Failed to generate valid questions. Please try again.');
            }

            $ttsService = new ElevenLabsTtsService();
            $created = 0;
            $maxSortOrder = $lesson->trueFalseQuestions()
                ->forVersion($gameVersion)
                ->max('sort_order') ?? 0;

            foreach ($questions as $index => $questionData) {
                $audioPath = null;

                // Generate audio if requested
                if ($generateAudio && $ttsService->enabled()) {
                    try {
                        $result = $ttsService->generateAndSaveSentence(
                            $questionData['statement'],
                            "tts/true-false/question_{$lesson->id}_{$gameVersion}_" . ($index + 1) . ".mp3",
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
                    'game_version' => $gameVersion,
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
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Question generation failed', [
                'lesson_id' => $lesson->id,
                'game_version' => $gameVersion,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'Failed to generate questions: ' . $e->getMessage());
        }
    }

    /**
     * Bulk approve questions.
     */
    public function bulkApprove(Request $request, Lesson $lesson)
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
            ->where('lesson_id', $lesson->id)
            ->update(['is_approved' => true]);

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', $count . ' question(s) approved!');
    }

    /**
     * Play the True/False game (student-facing)
     */
    public function play(Lesson $lesson, Request $request)
    {
        // Ensure lesson is active and not archived
        if (!$lesson->is_active || $lesson->archived_at) {
            abort(404);
        }

        $gameVersion = $request->get('version', 'easy');
        
        if (!in_array($gameVersion, ['easy', 'medium', 'hard'])) {
            $gameVersion = 'easy';
        }

        // Get approved, active questions for this lesson and version
        $questions = $lesson->trueFalseQuestions()
            ->forVersion($gameVersion)
            ->where('is_approved', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()
                ->route('lessons.show', $lesson->slug)
                ->with('info', "No {$gameVersion} level True/False questions available for this lesson yet.");
        }

        // Check which versions are available
        $availableVersions = [];
        foreach (['easy', 'medium', 'hard'] as $version) {
            $count = $lesson->trueFalseQuestions()
                ->forVersion($version)
                ->where('is_approved', true)
                ->where('is_active', true)
                ->count();
            if ($count > 0) {
                $availableVersions[] = $version;
            }
        }

        $questions = $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'statement' => $question->statement,
                'is_true' => $question->is_true,
                'explanation' => $question->explanation,
                'category' => $question->category,
                'audio_path' => $question->audio_path ? asset($question->audio_path) : null,
            ];
        });

        return view('true-false-games.play', compact('lesson', 'questions', 'gameVersion', 'availableVersions'));
    }
}
