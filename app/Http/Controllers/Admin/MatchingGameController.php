<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\MatchingGame;
use App\Models\Part;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class MatchingGameController extends Controller
{
    public function index(Lesson $lesson)
    {
        $matchingGames = $lesson->matchingGames()->ordered()->get();
        return view('admin.matching-games.index', compact('lesson', 'matchingGames'));
    }

    public function create(Lesson $lesson)
    {
        $parts = $lesson->parts()->active()->ordered()->get();
        $vocabulary = $lesson->vocabulary()->active()->ordered()->get();
        
        return view('admin.matching-games.create', compact('lesson', 'parts', 'vocabulary'));
    }

    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'part_id' => 'nullable|exists:parts,id',
            'title' => 'required|string|max:255',
            'vocabulary_ids' => 'required|array|min:2',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'grid_size' => 'required|integer|min:4|max:8',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Ensure we have enough vocabulary for the grid
        $vocabCount = count($validated['vocabulary_ids']);
        $requiredPairs = ($validated['grid_size'] * $validated['grid_size']) / 2;
        
        if ($vocabCount < $requiredPairs) {
            return back()->withErrors([
                'vocabulary_ids' => "You need at least {$requiredPairs} vocabulary items for a {$validated['grid_size']}x{$validated['grid_size']} grid."
            ])->withInput();
        }

        $validated['lesson_id'] = $lesson->id;
        $validated['vocabulary_ids'] = array_slice($validated['vocabulary_ids'], 0, $requiredPairs);

        MatchingGame::create($validated);

        return redirect()
            ->route('admin.lessons.matching-games.index', $lesson)
            ->with('success', 'Matching game created successfully!');
    }

    public function show(Lesson $lesson, MatchingGame $matchingGame)
    {
        $matchingGame->load(['vocabulary']);
        return view('admin.matching-games.show', compact('lesson', 'matchingGame'));
    }

    public function edit(Lesson $lesson, MatchingGame $matchingGame)
    {
        $parts = $lesson->parts()->active()->ordered()->get();
        $vocabulary = $lesson->vocabulary()->active()->ordered()->get();
        
        return view('admin.matching-games.edit', compact('lesson', 'matchingGame', 'parts', 'vocabulary'));
    }

    public function update(Request $request, Lesson $lesson, MatchingGame $matchingGame)
    {
        $validated = $request->validate([
            'part_id' => 'nullable|exists:parts,id',
            'title' => 'required|string|max:255',
            'vocabulary_ids' => 'required|array|min:2',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'grid_size' => 'required|integer|min:4|max:8',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        // Ensure we have enough vocabulary for the grid
        $vocabCount = count($validated['vocabulary_ids']);
        $requiredPairs = ($validated['grid_size'] * $validated['grid_size']) / 2;
        
        if ($vocabCount < $requiredPairs) {
            return back()->withErrors([
                'vocabulary_ids' => "You need at least {$requiredPairs} vocabulary items for a {$validated['grid_size']}x{$validated['grid_size']} grid."
            ])->withInput();
        }

        $validated['vocabulary_ids'] = array_slice($validated['vocabulary_ids'], 0, $requiredPairs);

        $matchingGame->update($validated);

        return redirect()
            ->route('admin.lessons.matching-games.index', $lesson)
            ->with('success', 'Matching game updated successfully!');
    }

    public function destroy(Lesson $lesson, MatchingGame $matchingGame)
    {
        $matchingGame->delete();

        return redirect()
            ->route('admin.lessons.matching-games.index', $lesson)
            ->with('success', 'Matching game deleted successfully!');
    }

    /**
     * Play the matching game (student-facing)
     */
    public function play(Lesson $lesson, MatchingGame $matching_game)
    {
        // Get vocabulary items directly from the IDs
        $vocabularyIds = $matching_game->vocabulary_ids ?? [];
        $vocabulary = Vocabulary::whereIn('id', $vocabularyIds)->get();
        
        // Generate game data
        $gameData = $this->generateGameData($matching_game, $vocabulary);
        
        return view('matching-games.play', compact('lesson', 'matching_game', 'gameData'));
    }

    /**
     * Generate the game grid with shuffled cards
     */
    private function generateGameData(MatchingGame $matchingGame, $vocabulary)
    {
        $gridSize = $matchingGame->grid_size;
        $totalCards = $gridSize * $gridSize;
        $pairs = $totalCards / 2;

        // Take only the number of pairs we need
        $selectedVocab = $vocabulary->take($pairs);
        
        $cards = [];
        
        // Create pairs: one image card, one word card for each vocabulary item
        foreach ($selectedVocab as $vocab) {
            // Image card
            $cards[] = [
                'id' => 'img_' . $vocab->id,
                'type' => 'image',
                'content' => $vocab->image_path ? asset('storage/' . $vocab->image_path) : null,
                'word' => $vocab->english_word,
                'vocab_id' => $vocab->id,
                'audio_path' => $vocab->word_audio_path ? asset('storage/' . $vocab->word_audio_path) : null,
            ];
            
            // Word card
            $cards[] = [
                'id' => 'word_' . $vocab->id,
                'type' => 'word',
                'content' => $vocab->english_word,
                'word' => $vocab->english_word,
                'vocab_id' => $vocab->id,
                'audio_path' => $vocab->word_audio_path ? asset('storage/' . $vocab->word_audio_path) : null,
            ];
        }

        // Shuffle the cards
        shuffle($cards);

        // Debug logging
        \Log::info('Matching Game Debug', [
            'grid_size' => $gridSize,
            'total_cards' => $totalCards,
            'pairs' => $pairs,
            'vocabulary_count' => $vocabulary->count(),
            'selected_vocab_count' => $selectedVocab->count(),
            'cards_count' => count($cards),
            'first_card' => $cards[0] ?? 'none'
        ]);

        return [
            'cards' => $cards,
            'grid_size' => $gridSize,
            'total_cards' => $totalCards,
        ];
    }
}