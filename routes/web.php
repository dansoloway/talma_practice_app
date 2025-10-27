<?php

use App\Http\Controllers\LessonController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\PromptModelController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\PartController as AdminPartController;
use App\Http\Controllers\Admin\VocabularyController as AdminVocabularyController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\OptionController as AdminOptionController;
use App\Http\Controllers\Admin\FlashcardGameController as AdminFlashcardGameController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('lessons.index');
});

// Lessons
Route::get('/lessons', [LessonController::class, 'index'])->name('lessons.index');
Route::get('/lessons/{slug}', [LessonController::class, 'show'])->name('lessons.show');

// Matching Games (public)
Route::get('/lessons/{lesson}/matching-games/{matching_game}/play', [App\Http\Controllers\Admin\MatchingGameController::class, 'play'])
    ->name('matching-games.play');

// Flashcard Games (public)
Route::get('/lessons/{lesson}/flashcard-games/{flashcard_game}/play', [App\Http\Controllers\Admin\FlashcardGameController::class, 'play'])
    ->name('flashcard-games.play');

// Prompts (JSON API)
Route::get('/prompts/{id}', [PromptController::class, 'show'])->name('prompts.show');
Route::get('/prompts/{promptId}/options/{optionId}/model', [PromptModelController::class, 'show'])
    ->name('prompts.model');

// Responses
Route::post('/responses', [ResponseController::class, 'store'])->name('responses.store');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // Lessons
    Route::resource('lessons', AdminLessonController::class);
    Route::get('lessons/{lesson}/manage', [AdminLessonController::class, 'manage'])
        ->name('lessons.manage');
    Route::post('lessons/{lesson}/update-activity-order', [AdminLessonController::class, 'updateActivityOrder'])
        ->name('lessons.update-activity-order');
    Route::post('lessons/{lesson}/delete-activity', [AdminLessonController::class, 'deleteActivity'])
        ->name('lessons.delete-activity');
    
    // Parts
    Route::get('lessons/{lesson}/parts', [AdminPartController::class, 'index'])
        ->name('lessons.parts.index');
    Route::get('lessons/{lesson}/parts/create', [AdminPartController::class, 'create'])
        ->name('lessons.parts.create');
    Route::post('lessons/{lesson}/parts', [AdminPartController::class, 'store'])
        ->name('lessons.parts.store');
    Route::get('lessons/{lesson}/parts/{part}', [AdminPartController::class, 'show'])
        ->name('lessons.parts.show');
    Route::get('lessons/{lesson}/parts/{part}/edit', [AdminPartController::class, 'edit'])
        ->name('lessons.parts.edit');
    Route::put('lessons/{lesson}/parts/{part}', [AdminPartController::class, 'update'])
        ->name('lessons.parts.update');
    Route::delete('lessons/{lesson}/parts/{part}', [AdminPartController::class, 'destroy'])
        ->name('lessons.parts.destroy');
    
    // Vocabulary
    Route::get('lessons/{lesson}/vocabulary', [AdminVocabularyController::class, 'index'])
        ->name('lessons.vocabulary.index');
    Route::get('lessons/{lesson}/vocabulary/create', [AdminVocabularyController::class, 'create'])
        ->name('lessons.vocabulary.create');
    Route::post('lessons/{lesson}/vocabulary', [AdminVocabularyController::class, 'store'])
        ->name('lessons.vocabulary.store');
    Route::get('lessons/{lesson}/vocabulary/{vocabulary}', [AdminVocabularyController::class, 'show'])
        ->name('lessons.vocabulary.show');
    Route::get('lessons/{lesson}/vocabulary/{vocabulary}/edit', [AdminVocabularyController::class, 'edit'])
        ->name('lessons.vocabulary.edit');
    Route::put('lessons/{lesson}/vocabulary/{vocabulary}', [AdminVocabularyController::class, 'update'])
        ->name('lessons.vocabulary.update');
    Route::delete('lessons/{lesson}/vocabulary/{vocabulary}', [AdminVocabularyController::class, 'destroy'])
        ->name('lessons.vocabulary.destroy');
    Route::get('lessons/{lesson}/vocabulary/csv/upload', [AdminVocabularyController::class, 'csvUpload'])
        ->name('lessons.vocabulary.csv.upload');
    Route::post('lessons/{lesson}/vocabulary/csv/process', [AdminVocabularyController::class, 'processCsv'])
        ->name('lessons.vocabulary.csv.process');
    Route::get('lessons/vocabulary/csv/template', [AdminVocabularyController::class, 'csvTemplate'])
        ->name('lessons.vocabulary.csv.template');
    Route::put('lessons/{lesson}/vocabulary/{vocabulary}/image', [AdminVocabularyController::class, 'updateImage'])
        ->name('lessons.vocabulary.update-image');
    Route::put('lessons/{lesson}/vocabulary/{vocabulary}/remove-image', [AdminVocabularyController::class, 'removeImage'])
        ->name('lessons.vocabulary.remove-image');
    
    // Auto-image finder
    Route::get('lessons/{lesson}/vocabulary/auto-images', [App\Http\Controllers\Admin\AutoImageController::class, 'index'])
        ->name('lessons.vocabulary.auto-images');
    Route::post('lessons/{lesson}/vocabulary/{vocabulary}/find-images', [App\Http\Controllers\Admin\AutoImageController::class, 'findImages'])
        ->name('lessons.vocabulary.find-images');
    Route::post('lessons/{lesson}/vocabulary/{vocabulary}/apply-image', [App\Http\Controllers\Admin\AutoImageController::class, 'applyImage'])
        ->name('lessons.vocabulary.apply-image');
    
    // Matching Games
    Route::resource('lessons.matching-games', App\Http\Controllers\Admin\MatchingGameController::class);
    
    // Flashcard Games
    Route::resource('lessons.flashcard-games', App\Http\Controllers\Admin\FlashcardGameController::class);
    
    // Prompts
    Route::get('lessons/{lesson}/prompts/create', [AdminPromptController::class, 'create'])
        ->name('lessons.prompts.create');
    Route::post('lessons/{lesson}/prompts', [AdminPromptController::class, 'store'])
        ->name('lessons.prompts.store');
    Route::get('lessons/{lesson}/prompts/import', [AdminPromptController::class, 'showImport'])
        ->name('lessons.prompts.import');
    Route::post('lessons/{lesson}/prompts/import', [AdminPromptController::class, 'import'])
        ->name('lessons.prompts.import.store');
    Route::get('prompts/{prompt}', [AdminPromptController::class, 'show'])
        ->name('prompts.show');
    Route::get('prompts/{prompt}/edit', [AdminPromptController::class, 'edit'])
        ->name('prompts.edit');
    Route::put('prompts/{prompt}', [AdminPromptController::class, 'update'])
        ->name('prompts.update');
    Route::delete('prompts/{prompt}', [AdminPromptController::class, 'destroy'])
        ->name('prompts.destroy');
    
    // Options
    Route::get('prompts/{prompt}/options/create', [AdminOptionController::class, 'create'])
        ->name('prompts.options.create');
    Route::post('prompts/{prompt}/options', [AdminOptionController::class, 'store'])
        ->name('prompts.options.store');
    Route::get('options/{option}/edit', [AdminOptionController::class, 'edit'])
        ->name('options.edit');
    Route::put('options/{option}', [AdminOptionController::class, 'update'])
        ->name('options.update');
    Route::delete('options/{option}', [AdminOptionController::class, 'destroy'])
        ->name('options.destroy');
});

