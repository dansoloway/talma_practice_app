<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\TrueFalseQuestion;
use App\Services\QuestionGeneration\OpenAiQuestionGenerator;
use App\Services\Tts\ElevenLabsTtsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TrueFalseQuestionController extends Controller
{
    /**
     * Display a listing of questions for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $questions = $lesson->trueFalseQuestions()
            ->orderBy('is_approved')
            ->orderBy('sort_order')
            ->get();
        
        $pendingCount = $questions->where('is_approved', false)->count();
        $approvedCount = $questions->where('is_approved', true)->where('is_active', true)->count();
        
        return view('admin.true-false-questions.index', compact('lesson', 'questions', 'pendingCount', 'approvedCount'));
    }

    /**
     * Show the form for creating a new question.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.true-false-questions.create', compact('lesson'));
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
            'category' => 'nullable|string|max:50',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['is_approved'] = $request->boolean('is_approved', true);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? $lesson->trueFalseQuestions()->max('sort_order') + 1;

        TrueFalseQuestion::create($validated);

        return redirect()
            ->route('admin.lessons.true-false-questions.index', $lesson)
            ->with('success', 'True/False question created successfully!');
    }

    /**
     * Display the specified question.
     */
    public function show(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        return view('admin.true-false-questions.show', compact('lesson', 'trueFalseQuestion'));
    }

    /**
     * Show the form for editing the specified question.
     */
    public function edit(Lesson $lesson, TrueFalseQuestion $trueFalseQuestion)
    {
        return view('admin.true-false-questions.edit', compact('lesson', 'trueFalseQuestion'));
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
            'category' => 'nullable|string|max:50',
            'is_approved' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_approved'] = $request->boolean('is_approved', $trueFalseQuestion->is_approved);
        $validated['is_active'] = $request->boolean('is_active', $trueFalseQuestion->is_active);

        $trueFalseQuestion->update($validated);

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
        $generateAudio = $request->boolean('generate_audio', false);
        $autoApprove = $request->boolean('auto_approve', false);

        // Validate count
        if ($count < 5 || $count > 8) {
            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'Count must be between 5 and 8');
        }

        // Check if OpenAI is configured
        $questionGenerator = new OpenAiQuestionGenerator();
        if (!$questionGenerator->enabled()) {
            return redirect()
                ->route('admin.lessons.true-false-questions.index', $lesson)
                ->with('error', 'OpenAI API key not configured. Set OPENAI_API_KEY in .env');
        }

        try {
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
            $questions = $questionGenerator->generateQuestions($lessonData, $count);

            $ttsService = new ElevenLabsTtsService();
            $created = 0;

            foreach ($questions as $index => $questionData) {
                $audioPath = null;

                // Generate audio if requested
                if ($generateAudio && $ttsService->enabled()) {
                    try {
                        $result = $ttsService->generateAndSaveSentence(
                            $questionData['statement'],
                            "tts/true-false/question_{$lesson->id}_" . ($index + 1) . ".mp3",
                            null,
                            'EXAVITQu4vr4xnSDxMaL' // Rachel voice
                        );
                        if ($result && isset($result['path'])) {
                            $audioPath = $result['path'];
                        }
                    } catch (\Exception $e) {
                        // Continue without audio if generation fails
                        \Illuminate\Support\Facades\Log::warning("Failed to generate audio for question: " . $e->getMessage());
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
                    'sort_order' => $lesson->trueFalseQuestions()->max('sort_order') + $index + 1,
                ]);

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
        // Handle JSON string from JavaScript
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
}
