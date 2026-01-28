<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SpellingGame;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class SpellingGameController extends Controller
{
    /**
     * Display a listing of spelling games for a lesson.
     */
    public function index(Lesson $lesson)
    {
        $spellingGames = $lesson->spellingGames()->ordered()->get();
        return view('admin.spelling-games.index', compact('lesson', 'spellingGames'));
    }

    /**
     * Show the form for creating a new spelling game.
     */
    public function create(Lesson $lesson)
    {
        $vocabulary = $lesson->getVocabularyForGames();
        
        return view('admin.spelling-games.create', compact('lesson', 'vocabulary'));
    }

    /**
     * Store a newly created spelling game.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['title'] = $validated['title'] ?: $this->generateDefaultTitle($lesson);
        $validated['is_active'] = true; // Always active
        $validated['sort_order'] = $validated['sort_order'] ?? $lesson->spellingGames()->max('sort_order') + 1;

        SpellingGame::create($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Spelling game created successfully!');
    }

    /**
     * Display the specified spelling game.
     */
    public function show(Lesson $lesson, SpellingGame $spellingGame)
    {
        $vocabularyIds = $spellingGame->vocabulary_ids ?? [];
        $vocabulary = Vocabulary::whereIn('id', $vocabularyIds)->get();
        
        return view('admin.spelling-games.show', compact('lesson', 'spellingGame', 'vocabulary'));
    }

    /**
     * Show the form for editing the specified spelling game.
     */
    public function edit(Lesson $lesson, SpellingGame $spellingGame)
    {
        $vocabulary = $lesson->getVocabularyForGames();
        
        return view('admin.spelling-games.edit', compact('lesson', 'spellingGame', 'vocabulary'));
    }

    /**
     * Update the specified spelling game.
     */
    public function update(Request $request, Lesson $lesson, SpellingGame $spellingGame)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = true; // Always active

        $spellingGame->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Spelling game updated successfully!');
    }

    /**
     * Remove the specified spelling game.
     */
    public function destroy(Lesson $lesson, SpellingGame $spellingGame)
    {
        $spellingGame->delete();

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Spelling game deleted successfully!');
    }

    /**
     * Play the spelling game (student-facing)
     * Only accessible if lesson is active and not archived.
     */
    public function play(Lesson $lesson, SpellingGame $spelling_game, Request $request)
    {
        // Ensure lesson is active and not archived
        if (!$lesson->is_active || $lesson->archived_at) {
            abort(404);
        }

        // Ensure game is active
        if (!$spelling_game->is_active) {
            abort(404);
        }

        // Get vocabulary items directly from the IDs
        $vocabularyIds = $spelling_game->vocabulary_ids ?? [];
        $vocabulary = Vocabulary::whereIn('id', $vocabularyIds)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($vocab) {
                return [
                    'id' => $vocab->id,
                    'english_word' => $vocab->english_word,
                    'image_path' => $vocab->image_url,
                    'word_audio_path' => $vocab->word_audio_url,
                ];
            });

        if ($vocabulary->isEmpty()) {
            return redirect()
                ->route('lessons.show', $lesson->slug)
                ->with('info', 'No vocabulary available for this spelling game.');
        }

        return view('spelling-games.play', compact('lesson', 'spelling_game', 'vocabulary'));
    }

    /**
     * Generate a default title for a spelling game.
     */
    private function generateDefaultTitle(Lesson $lesson): string
    {
        $count = $lesson->spellingGames()->count() + 1;
        return trim($lesson->title . ' Spelling Practice ' . $count);
    }
}
