<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Http\Controllers\Controller;
use App\Models\ClauseExercise;
use App\Models\Lesson;
use App\Models\GrammarSet;
use App\Services\AI\ClauseExerciseGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClauseExerciseController extends Controller
{
    use GuardsRestrictedCourseAccess;

    protected ClauseExerciseGenerator $generator;

    public function __construct(ClauseExerciseGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Show form to create a new clause exercise.
     */
    public function create(Lesson $lesson)
    {
        // Get all available grammar sets, not just associated ones
        $grammarSets = GrammarSet::with('grammarConcepts')->orderBy('title')->get();
        
        // Generate default title based on existing exercises
        $existingCount = ClauseExercise::where('lesson_id', $lesson->id)->count();
        $defaultTitle = 'Fill in the Blanks ' . ($existingCount + 1);
        
        return view('admin.clause-exercises.create', compact('lesson', 'grammarSets', 'defaultTitle'));
    }

    /**
     * Generate and store a new clause exercise.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'grammar_set_id' => 'nullable|exists:grammar_sets,id',
            'model' => 'nullable|string|in:gpt-4o-mini,gpt-4o',
        ]);

        // Auto-generate title if it matches the old pattern (includes grammar set name) or is empty
        // Check if title starts with "Fill in the Blanks:" (old pattern with grammar set) or is empty
        if (empty($validated['title']) || strpos($validated['title'], 'Fill in the Blanks:') === 0) {
            $existingCount = ClauseExercise::where('lesson_id', $lesson->id)->count();
            $validated['title'] = 'Fill in the Blanks ' . ($existingCount + 1);
        }

        try {
            // Get grammar set if provided, otherwise null (generateExercise handles null)
            $grammarSet = null;
            if (!empty($validated['grammar_set_id'])) {
                $grammarSet = GrammarSet::findOrFail($validated['grammar_set_id']);
            }

            // Generate exercise using AI
            try {
                $exerciseData = $this->generator->generateExercise(
                    $lesson,
                    $grammarSet,
                    $validated['topic'] ?? null,
                    $validated['model'] ?? null
                );

                if (!$exerciseData) {
                    return redirect()
                        ->route('admin.lessons.clause-exercises.create', $lesson)
                        ->with('error', 'Failed to generate exercise. The AI may not have returned a valid response. Please try again.');
                }
            } catch (\Exception $genException) {
                Log::error('Error generating clause exercise', [
                    'lesson_id' => $lesson->id,
                    'error' => $genException->getMessage(),
                    'trace' => $genException->getTraceAsString(),
                ]);

                return redirect()
                    ->route('admin.lessons.clause-exercises.create', $lesson)
                    ->with('error', 'Error generating exercise: ' . $genException->getMessage());
            }

            // New format: use blanks as single source of truth
            $paragraphText = $exerciseData['paragraph_text'];
            $blanks = $exerciseData['blanks'] ?? [];
            
            // Validate tokens exist
            preg_match_all('/\{\{(blank_\d+)\}\}/', $paragraphText, $matches);
            $tokens = $matches[1] ?? [];
            if (empty($tokens)) {
                throw new \Exception("Validation failed: Paragraph must contain at least 1 {{blank_id}} token.");
            }

            // Create the exercise with new format
            $exercise = ClauseExercise::create([
                'lesson_id' => $lesson->id,
                'grammar_set_id' => $grammarSet?->id,
                'title' => $validated['title'],
                'topic' => $validated['topic'] ?? null,
                'paragraph_text' => $paragraphText,
                'blanks' => $blanks, // New single source of truth
                // Keep old fields for backward compatibility (derived from blanks)
                'correct_answers' => $this->deriveCorrectAnswers($blanks),
                'blank_positions' => [], // No longer needed with token-based system
                'blank_metadata' => $this->deriveBlankMetadata($blanks),
                'difficulty_level' => 'medium',
                'is_active' => true,
                'sort_order' => ClauseExercise::where('lesson_id', $lesson->id)->max('sort_order') + 1 ?? 0,
            ]);

            return redirect()
                ->route('admin.lessons.manage', $lesson)
                ->with('success', "Clause exercise '{$exercise->title}' created successfully!");
        } catch (\Exception $e) {
            Log::error('Error creating clause exercise', [
                'lesson_id' => $lesson->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lessons.clause-exercises.create', $lesson)
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Show the edit form.
     */
    public function edit(Lesson $lesson, ClauseExercise $clauseExercise)
    {
        // Get all available grammar sets, not just associated ones
        $grammarSets = GrammarSet::with('grammarConcepts')->orderBy('title')->get();
        return view('admin.clause-exercises.edit', compact('lesson', 'clauseExercise', 'grammarSets'));
    }

    /**
     * Update the clause exercise.
     */
    public function update(Request $request, Lesson $lesson, ClauseExercise $clauseExercise)
    {
        // Check if this is a regenerate request
        if ($request->has('regenerate')) {
            return $this->regenerate($request, $lesson, $clauseExercise);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'paragraph_text' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $clauseExercise->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Clause exercise updated successfully!');
    }

    /**
     * Regenerate the clause exercise using AI.
     */
    public function regenerate(Request $request, Lesson $lesson, ClauseExercise $clauseExercise)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'topic' => 'nullable|string|max:255',
            'grammar_set_id' => 'required|exists:grammar_sets,id',
            'model' => 'nullable|string|in:gpt-4o-mini,gpt-4o',
        ]);

        try {
            $grammarSet = GrammarSet::findOrFail($validated['grammar_set_id']);

            // Generate exercise using AI
            try {
                $exerciseData = $this->generator->generateExercise(
                    $lesson,
                    $grammarSet,
                    $validated['topic'] ?? null,
                    $validated['model'] ?? null
                );

                if (!$exerciseData) {
                    return redirect()
                        ->route('admin.lessons.clause-exercises.edit', [$lesson, $clauseExercise])
                        ->with('error', 'Failed to regenerate exercise. The AI may not have returned a valid response. Please try again.');
                }
            } catch (\Exception $genException) {
                Log::error('Error regenerating clause exercise', [
                    'lesson_id' => $lesson->id,
                    'exercise_id' => $clauseExercise->id,
                    'error' => $genException->getMessage(),
                    'trace' => $genException->getTraceAsString(),
                ]);

                return redirect()
                    ->route('admin.lessons.clause-exercises.edit', [$lesson, $clauseExercise])
                    ->with('error', 'Error regenerating exercise: ' . $genException->getMessage())
                    ->withInput($request->only(['title', 'topic', 'grammar_set_id', 'model']));
            }

            // New format: use blanks as single source of truth
            $paragraphText = $exerciseData['paragraph_text'];
            $blanks = $exerciseData['blanks'] ?? [];
            
            // Validate tokens exist
            preg_match_all('/\{\{(blank_\d+)\}\}/', $paragraphText, $matches);
            $tokens = $matches[1] ?? [];
            if (empty($tokens)) {
                throw new \Exception("Validation failed: Paragraph must contain at least 1 {{blank_id}} token.");
            }

            // Update the exercise with new generated data
            $clauseExercise->update([
                'title' => $validated['title'],
                'topic' => $validated['topic'] ?? null,
                'grammar_set_id' => $grammarSet?->id,
                'paragraph_text' => $paragraphText,
                'blanks' => $blanks, // New single source of truth
                // Keep old fields for backward compatibility
                'correct_answers' => $this->deriveCorrectAnswers($blanks),
                'blank_positions' => [],
                'blank_metadata' => $this->deriveBlankMetadata($blanks),
            ]);

            return redirect()
                ->route('admin.lessons.clause-exercises.edit', [$lesson, $clauseExercise])
                ->with('success', 'Clause exercise regenerated successfully!');
        } catch (\Exception $e) {
            Log::error('Error regenerating clause exercise', [
                'lesson_id' => $lesson->id,
                'exercise_id' => $clauseExercise->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('admin.lessons.clause-exercises.edit', [$lesson, $clauseExercise])
                ->with('error', 'Error regenerating exercise: ' . $e->getMessage())
                ->withInput($request->only(['title', 'topic', 'grammar_set_id', 'model']));
        }
    }

    /**
     * Delete the clause exercise.
     */
    public function destroy(Lesson $lesson, ClauseExercise $clauseExercise)
    {
        $clauseExercise->delete();

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Clause exercise deleted successfully!');
    }

    /**
     * Show the clause exercise for students to play.
     */
    public function play(Lesson $lesson, ClauseExercise $clauseExercise)
    {
        $gate = $this->ensureLegacyCourseAccess($lesson);
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        // Verify exercise belongs to lesson
        if ($clauseExercise->lesson_id !== $lesson->id) {
            abort(404);
        }
        
        // Verify exercise is active
        if (!$clauseExercise->is_active) {
            abort(404);
        }
        
        // Load vocabulary needed for vocab blanks in this exercise
        $blankMetadata = $clauseExercise->blank_metadata ?? [];
        $vocabularyIds = [];
        
        foreach ($blankMetadata as $blankId => $metadata) {
            if (($metadata['type'] ?? '') === 'vocab') {
                $vocabularyIds[] = $metadata['correct_answer'] ?? null;
                // Also include distractors
                if (isset($metadata['distractors']) && is_array($metadata['distractors'])) {
                    $vocabularyIds = array_merge($vocabularyIds, $metadata['distractors']);
                }
            }
        }
        
        $vocabularyIds = array_filter(array_unique($vocabularyIds));
        $vocabulary = $lesson->vocabulary()
            ->whereIn('id', $vocabularyIds)
            ->active()
            ->get()
            ->keyBy('id');

        return view('clause-exercises.play', compact('lesson', 'clauseExercise', 'vocabulary'));
    }

    /**
     * Derive correct_answers from blanks (for backward compatibility)
     */
    protected function deriveCorrectAnswers(array $blanks): array
    {
        $correctAnswers = [];
        foreach ($blanks as $blankId => $blank) {
            if (($blank['type'] ?? '') === 'vocab') {
                $correctAnswers[$blankId] = $blank['correct']['vocab_id'] ?? $blank['correct']['text'] ?? '';
            } else {
                $correctAnswers[$blankId] = $blank['correct']['text'] ?? '';
            }
        }
        return $correctAnswers;
    }

    /**
     * Derive blank_metadata from blanks (for backward compatibility)
     */
    protected function deriveBlankMetadata(array $blanks): array
    {
        $metadata = [];
        foreach ($blanks as $blankId => $blank) {
            $meta = [
                'type' => $blank['type'] ?? 'vocab',
            ];
            
            if ($blank['type'] === 'vocab') {
                $meta['correct_answer'] = $blank['correct']['vocab_id'] ?? null;
                $meta['distractors'] = array_map(function($dist) {
                    return $dist['vocab_id'] ?? null;
                }, $blank['distractors'] ?? []);
            } else {
                $meta['correct_answer'] = $blank['correct']['text'] ?? '';
                $meta['distractors'] = array_column($blank['distractors'] ?? [], 'text');
                $meta['grammar_concept_id'] = $blank['grammar_concept_id'] ?? null;
                $meta['grammar_concept'] = $blank['grammar_concept'] ?? '';
            }
            
            $metadata[$blankId] = $meta;
        }
        return $metadata;
    }
}
