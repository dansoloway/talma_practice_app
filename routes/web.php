<?php

use App\Http\Controllers\ActivityEventController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\PromptModelController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\PartController as AdminPartController;
use App\Http\Controllers\Admin\VocabularyController as AdminVocabularyController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\OptionController as AdminOptionController;
use App\Http\Controllers\Admin\FlashcardGameController as AdminFlashcardGameController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Student Homepage
Route::get('/', [StudentController::class, 'index'])->name('student.index');
Route::get('/lessons', [StudentController::class, 'index'])->name('lessons.index');
Route::get('/grade/{gradeLevel}', [StudentController::class, 'grade'])->name('student.grade');

// Individual Lessons
Route::get('/lessons/{slug}', [LessonController::class, 'show'])->name('lessons.show');

// Matching Games (public)
Route::get('/lessons/{lesson}/matching-games/{matching_game}/play', [App\Http\Controllers\Admin\MatchingGameController::class, 'play'])
    ->name('matching-games.play');

// Flashcard Games (public)
Route::get('/lessons/{lesson}/flashcard-games/{flashcard_game}/play', [App\Http\Controllers\Admin\FlashcardGameController::class, 'play'])
    ->name('flashcard-games.play');

// Prompts (JSON API)
Route::get('/prompts/{id}', [PromptController::class, 'show'])->name('prompts.show');
Route::get('/lessons/{lesson}/prompts/play', [PromptController::class, 'play'])->name('prompts.play');
Route::get('/prompts/{promptId}/options/{optionId}/model', [PromptModelController::class, 'show'])
    ->name('prompts.model');

// Responses
Route::post('/responses', [ResponseController::class, 'store'])->name('responses.store');
Route::post('/activity-events', [ActivityEventController::class, 'store'])->name('activity-events.store');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Admin login route (redirects to lessons management)
Route::get('/admin', function () {
    return redirect()->route('admin.lessons.index');
})->name('admin.dashboard')->middleware('admin.auth');

Route::post('/admin/login', function (Request $request) {
    $password = $request->input('admin_password');
    $correctPassword = env('ADMIN_PASSWORD', 'admin123');
    
    if ($password === $correctPassword) {
        session(['admin_authenticated' => true]);
        return redirect()->route('admin.dashboard');
    } else {
        return redirect()->route('admin.dashboard')->with('error', 'Incorrect password. Please try again.');
    }
})->name('admin.login');

Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    
    // Logout
    Route::post('/logout', function () {
        session()->forget('admin_authenticated');
        return response()->json(['success' => true]);
    })->name('logout');
    
    // Lessons
    Route::resource('lessons', AdminLessonController::class);
    Route::get('lessons/{lesson}/manage', [AdminLessonController::class, 'manage'])
        ->name('lessons.manage');
    Route::post('lessons/{lesson}/update-activity-order', [AdminLessonController::class, 'updateActivityOrder'])
        ->name('lessons.update-activity-order');
    Route::post('lessons/{lesson}/delete-activity', [AdminLessonController::class, 'deleteActivity'])
        ->name('lessons.delete-activity');
    
    // Archive functionality
    Route::post('lessons/{lesson}/archive', [AdminLessonController::class, 'archive'])
        ->name('lessons.archive');
    Route::post('lessons/{lesson}/unarchive', [AdminLessonController::class, 'unarchive'])
        ->name('lessons.unarchive');
    Route::get('lessons-archived', [AdminLessonController::class, 'archived'])
        ->name('lessons.archived');
    
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
    Route::post('lessons/{lesson}/vocabulary/generate-tts', [AdminVocabularyController::class, 'generateTts'])
        ->name('lessons.vocabulary.generate-tts');
    Route::get('vocabulary/tts-logs', [AdminVocabularyController::class, 'viewLogs'])
        ->name('vocabulary.tts-logs');
    
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
    Route::get('lessons/{lesson}/prompts', [AdminPromptController::class, 'index'])
        ->name('lessons.prompts.index');
    Route::get('lessons/{lesson}/prompts/create', [AdminPromptController::class, 'create'])
        ->name('lessons.prompts.create');
    Route::post('lessons/{lesson}/prompts', [AdminPromptController::class, 'store'])
        ->name('lessons.prompts.store');
    Route::get('lessons/{lesson}/prompts/import', [AdminPromptController::class, 'showImport'])
        ->name('lessons.prompts.import');
    Route::post('lessons/{lesson}/prompts/preview', [AdminPromptController::class, 'previewCsv'])
        ->name('lessons.prompts.preview');
    Route::post('lessons/{lesson}/prompts/confirm-import', [AdminPromptController::class, 'confirmImport'])
        ->name('lessons.prompts.confirm-import');
    Route::get('lessons/prompts/csv/template', [AdminPromptController::class, 'csvTemplate'])
        ->name('lessons.prompts.csv.template');
    Route::post('lessons/{lesson}/prompts/generate-word-tts', [AdminPromptController::class, 'generateWordTts'])
        ->name('lessons.prompts.generate-word-tts');
    Route::post('lessons/{lesson}/prompts/generate-sentence-tts', [AdminPromptController::class, 'generateSentenceTts'])
        ->name('lessons.prompts.generate-sentence-tts');
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

