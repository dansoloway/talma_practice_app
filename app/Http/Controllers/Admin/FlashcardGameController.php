<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\Part;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class FlashcardGameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Lesson $lesson)
    {
        $flashcardGames = $lesson->flashcardGames()->orderBy('sort_order')->get();
        return view('admin.flashcard-games.index', compact('lesson', 'flashcardGames'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Lesson $lesson)
    {
        $vocabulary = $lesson->vocabulary()->where('is_active', true)->orderBy('sort_order')->get();
        
        return view('admin.flashcard-games.create', compact('lesson', 'vocabulary'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'cards_per_game' => 'integer|min:1|max:50',
        ]);

        $validated['lesson_id'] = $lesson->id;
        
        // Always include all game types - no need for admin to choose
        $validated['game_types'] = ['image_to_word', 'image_to_audio', 'audio_to_image', 'audio_to_word'];
        
        // Default to all vocabulary words if none are selected
        if (empty($validated['vocabulary_ids'])) {
            $validated['vocabulary_ids'] = $lesson->vocabulary()->pluck('id')->toArray();
        }
        
        // Default to active
        $validated['is_active'] = $request->input('is_active', '1') == '1';

        $flashcardGame = FlashcardGame::create($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Flashcard game created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Lesson $lesson, FlashcardGame $flashcardGame)
    {
        $flashcardGame->load(['vocabulary', 'part']);
        return view('admin.flashcard-games.show', compact('lesson', 'flashcardGame'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lesson $lesson, FlashcardGame $flashcardGame)
    {
        $vocabulary = $lesson->vocabulary()->where('is_active', true)->orderBy('sort_order')->get();
        
        return view('admin.flashcard-games.edit', compact('lesson', 'flashcardGame', 'vocabulary'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Lesson $lesson, FlashcardGame $flashcardGame)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'cards_per_game' => 'integer|min:1|max:50',
        ]);

        // Always include all game types - no need for admin to choose
        $validated['game_types'] = ['image_to_word', 'image_to_audio', 'audio_to_image', 'audio_to_word'];
        
        // Handle checkbox properly - convert to boolean
        $validated['is_active'] = $request->input('is_active') == '1';

        $flashcardGame->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Flashcard game updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lesson $lesson, FlashcardGame $flashcardGame)
    {
        $flashcardGame->delete();

        return redirect()
            ->route('admin.lessons.flashcard-games.index', $lesson)
            ->with('success', 'Flashcard game deleted successfully!');
    }

    /**
     * Play the flashcard game
     */
    public function play(Lesson $lesson, FlashcardGame $flashcardGame, Request $request)
    {
        // Get vocabulary items
        $vocabularyIds = $flashcardGame->vocabulary_ids ?? [];
        $vocabulary = Vocabulary::whereIn('id', $vocabularyIds)->get();
        
        // Get the display mode (default to first available mode)
        $mode = $request->get('mode');
        if (!$mode) {
            $availableModes = $this->getAvailableModes($vocabulary);
            $mode = array_key_first($availableModes) ?: 'image';
        }
        
        // Generate game data based on the selected mode
        $gameData = $this->generateGameData($flashcardGame, $vocabulary, $mode);
        
        return view('flashcard-games.play', compact('lesson', 'flashcardGame', 'gameData', 'mode'));
    }

    private function generateGameData(FlashcardGame $flashcardGame, $vocabulary, $mode = 'image')
    {
        $gameTypes = $flashcardGame->game_types;
        $cardsPerGame = $flashcardGame->cards_per_game;
        
        // Filter vocabulary based on what's available for the selected mode
        $availableVocab = $this->filterVocabularyForMode($vocabulary, $mode);
        
        // Select random vocabulary items
        $selectedVocab = $availableVocab->shuffle()->take($cardsPerGame);
        
        $cards = [];
        
        foreach ($selectedVocab as $vocab) {
            $cards[] = [
                'id' => $vocab->id,
                'english_word' => $vocab->english_word,
                'hebrew_translation' => $vocab->hebrew_translation,
                'arabic_translation' => $vocab->arabic_translation,
                'image_path' => $vocab->image_path ? asset('storage/' . $vocab->image_path) : null,
                'audio_path' => $vocab->word_audio_path ? asset('storage/' . $vocab->word_audio_path) : null,
            ];
        }

        return [
            'cards' => $cards,
            'game_types' => $gameTypes,
            'cards_per_game' => $cardsPerGame,
            'mode' => $mode,
            'available_modes' => $this->getAvailableModes($vocabulary),
        ];
    }

    /**
     * Filter vocabulary based on what's available for the selected mode
     */
    private function filterVocabularyForMode($vocabulary, $mode)
    {
        return $vocabulary->filter(function ($vocab) use ($mode) {
            switch ($mode) {
                case 'hebrew':
                    return !empty($vocab->hebrew_translation);
                case 'arabic':
                    return !empty($vocab->arabic_translation);
                case 'image':
                default:
                    return !empty($vocab->image_path);
            }
        });
    }

    /**
     * Get available modes based on vocabulary data
     */
    private function getAvailableModes($vocabulary)
    {
        $modes = [];
        
        if ($vocabulary->whereNotNull('image_path')->count() > 0) {
            $modes['image'] = 'Images';
        }
        if ($vocabulary->whereNotNull('hebrew_translation')->count() > 0) {
            $modes['hebrew'] = 'Hebrew';
        }
        if ($vocabulary->whereNotNull('arabic_translation')->count() > 0) {
            $modes['arabic'] = 'Arabic';
        }
        
        return $modes;
    }
}
