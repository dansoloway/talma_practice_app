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
            'title' => 'nullable|string|max:255',
            'vocabulary_ids' => 'required|array|min:2',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'grid_size' => 'required|integer|min:2|max:8',
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
        $validated['title'] = $validated['title'] ?: $this->generateDefaultTitle($lesson);
        $validated['is_active'] = true; // Always active
        
        // Note: Parts are no longer used, activities belong directly to lessons

        MatchingGame::create($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
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
            'grid_size' => 'required|integer|min:2|max:8',
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

        $validated['title'] = $validated['title'] ?? $matchingGame->title;
        $validated['is_active'] = true; // Always active

        $matchingGame->update($validated);

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Matching game updated successfully!');
    }

    public function destroy(Lesson $lesson, MatchingGame $matchingGame)
    {
        $matchingGame->delete();

        return redirect()
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Matching game deleted successfully!');
    }

    /**
     * Play the matching game (student-facing)
     * Only accessible if lesson is active and not archived.
     */
    public function play(Lesson $lesson, MatchingGame $matching_game, Request $request)
    {
        // Ensure lesson is active and not archived
        if (!$lesson->is_active || $lesson->archived_at) {
            abort(404);
        }

        // Ensure game is active
        if (!$matching_game->is_active) {
            abort(404);
        }

        // Get vocabulary items directly from the IDs
        $vocabularyIds = $matching_game->vocabulary_ids ?? [];
        $vocabulary = Vocabulary::whereIn('id', $vocabularyIds)->get();
        
        // Get the matching mode (default to first available mode)
        $mode = $request->get('mode');
        if (!$mode) {
            $availableModes = $this->getAvailableModes($vocabulary);
            $mode = array_key_first($availableModes) ?: 'image';
        }
        
        // Generate game data based on the selected mode
        $gameData = $this->generateGameData($matching_game, $vocabulary, $mode);
        
        return view('matching-games.play', compact('lesson', 'matching_game', 'gameData', 'mode'));
    }

    /**
     * Generate the game grid with shuffled cards
     */
    private function generateGameData(MatchingGame $matchingGame, $vocabulary, $mode = 'image')
    {
        $gridSize = $matchingGame->grid_size;
        $totalCards = $gridSize * $gridSize;
        $pairs = $totalCards / 2;

        // Filter vocabulary based on what's available for the selected mode
        $availableVocab = $this->filterVocabularyForMode($vocabulary, $mode);
        
        // Take only the number of pairs we need
        $selectedVocab = $availableVocab->take($pairs);
        
        $cards = [];
        
        // Create pairs based on the selected mode
        foreach ($selectedVocab as $vocab) {
            // English word card (always the same)
            $cards[] = [
                'id' => 'word_' . $vocab->id,
                'type' => 'word',
                'content' => $vocab->english_word,
                'word' => $vocab->english_word,
                'vocab_id' => $vocab->id,
                'audio_path' => $vocab->word_audio_url,
            ];
            
            // Matching card based on mode
            $matchingCard = $this->createMatchingCard($vocab, $mode);
            if ($matchingCard) {
                $cards[] = $matchingCard;
            }
        }

        // Shuffle the cards
        shuffle($cards);

        return [
            'cards' => $cards,
            'grid_size' => $gridSize,
            'total_cards' => $totalCards,
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
                case 'audio':
                    return !empty($vocab->word_audio_path);
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
     * Create a matching card based on the mode
     */
    private function createMatchingCard($vocab, $mode)
    {
        switch ($mode) {
            case 'audio':
                // In audio mode, create an audio card (to match with word card)
                return [
                    'id' => 'audio_' . $vocab->id,
                    'type' => 'audio',
                    'content' => null, // No visual content for audio cards
                    'word' => $vocab->english_word,
                    'vocab_id' => $vocab->id,
                    'audio_path' => $vocab->word_audio_url,
                ];
            case 'hebrew':
                return [
                    'id' => 'hebrew_' . $vocab->id,
                    'type' => 'hebrew',
                    'content' => $vocab->hebrew_translation,
                    'word' => $vocab->english_word,
                    'vocab_id' => $vocab->id,
                    'audio_path' => null, // No audio for translations
                ];
            case 'arabic':
                return [
                    'id' => 'arabic_' . $vocab->id,
                    'type' => 'arabic',
                    'content' => $vocab->arabic_translation,
                    'word' => $vocab->english_word,
                    'vocab_id' => $vocab->id,
                    'audio_path' => null, // No audio for translations
                ];
            case 'image':
            default:
                return [
                    'id' => 'img_' . $vocab->id,
                    'type' => 'image',
                    'content' => $vocab->image_path ? asset('storage/' . $vocab->image_path) : null,
                    'word' => $vocab->english_word,
                    'vocab_id' => $vocab->id,
                    'audio_path' => null, // No audio for images
                ];
        }
    }

    /**
     * Get available modes based on vocabulary data
     */
    private function getAvailableModes($vocabulary)
    {
        $modes = [];
        
        if ($vocabulary->whereNotNull('word_audio_path')->count() > 0) {
            $modes['audio'] = 'Audio';
        }
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
    private function generateDefaultTitle(Lesson $lesson): string
    {
        $count = $lesson->matchingGames()->count() + 1;
        return trim(sprintf('%s Matching Game %d', $lesson->title, $count));
    }
}