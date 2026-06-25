<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\GuardsRestrictedCourseAccess;
use App\Http\Controllers\Controller;
use App\Models\FlashcardGame;
use App\Models\Lesson;
use App\Models\Part;
use App\Models\Vocabulary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FlashcardGameController extends Controller
{
    use GuardsRestrictedCourseAccess;
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
        $vocabulary = $lesson->getVocabularyForGames();
        
        return view('admin.flashcard-games.create', compact('lesson', 'vocabulary'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'vocabulary_ids' => 'required|array|min:1',
            'vocabulary_ids.*' => 'exists:vocabulary,id',
            'cards_per_game' => 'integer|min:1|max:50',
        ]);

        $validated['lesson_id'] = $lesson->id;
        $validated['title'] = $validated['title'] ?: $this->generateDefaultTitle($lesson);
        
        // Determine game types based on selected vocabulary assets
        $selectedIds = $validated['vocabulary_ids'] ?? [];
        $missingImages = Vocabulary::whereIn('id', $selectedIds)
            ->where(function($q){ $q->whereNull('image_path')->orWhere('image_path', ''); })
            ->count();
        $missingAudio = Vocabulary::whereIn('id', $selectedIds)
            ->whereNull('word_audio_path')
            ->count();
        
        if ($missingImages > 0 && $missingAudio > 0) {
            // If some selected words lack both images and audio, disable those game types
            $validated['game_types'] = [];
            session()->flash('warning', "Some selected vocabulary items are missing images ({$missingImages}) and audio ({$missingAudio}). Game types were disabled.");
        } elseif ($missingImages > 0) {
            // If some selected words lack images, enable only audio-based games
            $validated['game_types'] = ['audio_to_word'];
            session()->flash('warning', "Some selected vocabulary items are missing images ({$missingImages}). Image-based game types were disabled.");
        } elseif ($missingAudio > 0) {
            // If some selected words lack audio, enable only image-based games
            $validated['game_types'] = ['image_to_word'];
            session()->flash('warning', "Some selected vocabulary items are missing audio ({$missingAudio}). Audio-based game types were disabled.");
        } else {
            // All assets available, enable simple image-only and audio-only game types
            $validated['game_types'] = ['image_to_word', 'audio_to_word'];
        }
        
        // Default to all vocabulary words if none are selected
        if (empty($validated['vocabulary_ids'])) {
            $validated['vocabulary_ids'] = $lesson->getVocabularyForGames()->pluck('id')->toArray();
        }
        
        // Always active
        $validated['is_active'] = true;

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
        $vocabulary = $lesson->getVocabularyForGames();
        
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

        // Determine game types based on selected vocabulary assets
        $selectedIds = $validated['vocabulary_ids'] ?? [];
        $missingImages = Vocabulary::whereIn('id', $selectedIds)
            ->where(function($q){ $q->whereNull('image_path')->orWhere('image_path', ''); })
            ->count();
        $missingAudio = Vocabulary::whereIn('id', $selectedIds)
            ->whereNull('word_audio_path')
            ->count();
        
        if ($missingImages > 0 && $missingAudio > 0) {
            // If some selected words lack both images and audio, disable those game types
            $validated['game_types'] = [];
            session()->flash('warning', "Some selected vocabulary items are missing images ({$missingImages}) and audio ({$missingAudio}). Game types were disabled.");
        } elseif ($missingImages > 0) {
            // If some selected words lack images, enable only audio-based games
            $validated['game_types'] = ['audio_to_word'];
            session()->flash('warning', "Some selected vocabulary items are missing images ({$missingImages}). Image-based game types were disabled.");
        } elseif ($missingAudio > 0) {
            // If some selected words lack audio, enable only image-based games
            $validated['game_types'] = ['image_to_word'];
            session()->flash('warning', "Some selected vocabulary items are missing audio ({$missingAudio}). Audio-based game types were disabled.");
        } else {
            // All assets available, enable simple image-only and audio-only game types
            $validated['game_types'] = ['image_to_word', 'audio_to_word'];
        }
        
        // Always active
        $validated['is_active'] = true;

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
            ->route('admin.lessons.manage', $lesson)
            ->with('success', 'Flashcard game deleted successfully!');
    }

    /**
     * Play the flashcard game
     * Only accessible if lesson is active and not archived.
     */
    public function play(Lesson $lesson, FlashcardGame $flashcardGame, Request $request)
    {
        $gate = $this->ensureLegacyCourseAccess($lesson);
        if ($gate instanceof RedirectResponse) {
            return $gate;
        }

        // Ensure lesson is active and not archived
        if (!$lesson->is_active || $lesson->archived_at) {
            abort(404);
        }

        // Ensure game is active
        if (!$flashcardGame->is_active) {
            abort(404);
        }

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
        $availableModes = $this->getAvailableModes($vocabulary);
        $gameTypes = $this->determineAvailableGameTypes($vocabulary);
        if (empty($gameTypes)) {
            $gameTypes = array_keys(FlashcardGame::getGameTypes());
        }
        $cardsPerGame = max(1, (int) $flashcardGame->cards_per_game);
        
        // Filter vocabulary based on what's available for the selected mode
        $availableVocab = $this->filterVocabularyForMode($vocabulary, $mode);
        
        // Select random vocabulary items
        $selectedVocab = $availableVocab->shuffle()->take($cardsPerGame);
        if ($selectedVocab->isEmpty()) {
            $selectedVocab = $availableVocab;
        }

        $cardsPerGame = max(1, $selectedVocab->count());
        
        $cards = [];
        
        foreach ($selectedVocab as $vocab) {
            $cards[] = [
                'id' => $vocab->id,
                'english_word' => $vocab->english_word,
                'hebrew_translation' => $vocab->hebrew_translation,
                'arabic_translation' => $vocab->arabic_translation,
                'image_path' => $this->buildPublicAssetUrl($vocab->image_path),
                'audio_path' => $this->buildPublicAssetUrl($vocab->word_audio_path),
            ];
        }

        return [
            'cards' => $cards,
            'game_types' => $gameTypes,
            'cards_per_game' => $cardsPerGame,
            'mode' => $mode,
            'available_modes' => $availableModes,
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

    private function determineAvailableGameTypes($vocabulary): array
    {
        $types = [];

        // Only enable simple image-only and audio-only modes
        if ($vocabulary->whereNotNull('image_path')->count() > 0) {
            $types[] = 'image_to_word';
        }

        if ($vocabulary->whereNotNull('word_audio_path')->count() > 0) {
            $types[] = 'audio_to_word';
        }

        return array_values(array_unique($types));
    }

    private function buildPublicAssetUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalized = ltrim($path, '/');
        $normalized = preg_replace('#^storage/#', '', $normalized);

        return asset('storage/' . $normalized);
    }

    private function generateDefaultTitle(Lesson $lesson): string
    {
        $count = $lesson->flashcardGames()->count() + 1;
        return trim(sprintf('%s Flashcards %d', $lesson->title, $count));
    }
}
