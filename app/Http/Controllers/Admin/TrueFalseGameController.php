<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Http\Controllers\Concerns\ProvidesGuidedFlowContext;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\TrueFalseGame;
use App\Services\Tts\TrueFalseQuestionTtsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrueFalseGameController extends Controller
{
    use GuardsRestrictedCourseAccess;
    use ProvidesGuidedFlowContext;

    public function __construct(
        private TrueFalseQuestionTtsService $questionTts
    ) {}
    /**
     * Display a listing of True/False games for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $games = $lesson->trueFalseGames()->ordered()->get();
        return view('admin.true-false-games.index', compact('lesson', 'games'));
    }

    /**
     * Show the form for creating a new game.
     */
    public function create(Lesson $lesson)
    {
        return view('admin.true-false-games.create', compact('lesson'));
    }

    /**
     * Store a newly created game.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'game_version' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['title'] = $validated['title'] ?: $this->generateDefaultTitle($lesson, $validated['game_version']);
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? $lesson->trueFalseGames()->max('sort_order') + 1;

        $game = TrueFalseGame::create($validated);

        return redirect()
            ->route('admin.lessons.true-false-games.show', [$lesson, $game])
            ->with('success', 'True/False game created successfully!');
    }

    /**
     * Display the specified game and its questions.
     */
    public function show(Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $trueFalseGame->load(['questions.vocabulary']);
        $questions = $trueFalseGame->questions()->ordered()->get();

        $missingAudioCount = $questions->whereNull('audio_path')->count();
        $audioBackfilled = 0;
        if ($missingAudioCount > 0) {
            $audioBackfilled = $this->questionTts->ensureForGame($questions->whereNull('audio_path'));
            if ($audioBackfilled > 0) {
                $questions = $trueFalseGame->questions()->ordered()->get();
            }
        }
        
        $pendingCount = $questions->where('is_approved', false)->count();
        $approvedCount = $questions->where('is_approved', true)->where('is_active', true)->count();
        
        return view('admin.true-false-games.show', compact('lesson', 'trueFalseGame', 'questions', 'pendingCount', 'approvedCount', 'audioBackfilled'));
    }

    /**
     * Show the form for editing the specified game.
     */
    public function edit(Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        return view('admin.true-false-games.edit', compact('lesson', 'trueFalseGame'));
    }

    /**
     * Update the specified game.
     */
    public function update(Request $request, Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'game_version' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active', $trueFalseGame->is_active);

        $trueFalseGame->update($validated);

        return redirect()
            ->route('admin.lessons.true-false-games.show', [$lesson, $trueFalseGame])
            ->with('success', 'Game updated successfully!');
    }

    /**
     * Remove the specified game.
     */
    public function destroy(Lesson $lesson, TrueFalseGame $trueFalseGame)
    {
        $trueFalseGame->delete();

        return redirect()
            ->route('admin.lessons.true-false-games.index', $lesson)
            ->with('success', 'Game deleted successfully!');
    }

    /**
     * Play the True/False game (student-facing)
     */
    public function play(Lesson $lesson, TrueFalseGame $trueFalseGame, Request $request)
    {
        $gate = $this->ensureLegacyCourseAccess($lesson);
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        // Ensure the game belongs to this lesson
        if ($trueFalseGame->lesson_id !== $lesson->id) {
            abort(404);
        }
        
        // Ensure lesson and game are active
        if (!$lesson->is_active || $lesson->archived_at || !$trueFalseGame->is_active) {
            abort(404);
        }

        // Get approved, active questions for this game
        $questions = $trueFalseGame->questions()
            ->where('is_approved', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($questions->isEmpty()) {
            return redirect()
                ->route('lessons.show', $lesson->slug)
                ->with('info', 'No questions available for this True/False game yet.');
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

        return view('true-false-games.play', array_merge(
            compact('lesson', 'trueFalseGame', 'questions'),
            $this->guidedFlowViewData($request, $lesson, 'true_false', $trueFalseGame->id)
        ));
    }

    /**
     * Generate default title for a game.
     */
    protected function generateDefaultTitle(Lesson $lesson, string $gameVersion): string
    {
        $versionLabel = ucfirst($gameVersion);
        $count = $lesson->trueFalseGames()->forVersion($gameVersion)->count();
        return "True/False Game ({$versionLabel})" . ($count > 0 ? " " . ($count + 1) : "");
    }
}
