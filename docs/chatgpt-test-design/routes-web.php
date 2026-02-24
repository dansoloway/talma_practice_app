<?php

use App\Http\Controllers\ActivityEventController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\PromptModelController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController as AdminLessonController;
use App\Http\Controllers\Admin\LessonTrackerController;
use App\Http\Controllers\Admin\PartController as AdminPartController;
use App\Http\Controllers\Admin\VocabularyController as AdminVocabularyController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\OptionController as AdminOptionController;
use App\Http\Controllers\Admin\FlashcardGameController as AdminFlashcardGameController;
use App\Http\Controllers\Admin\GrammarConceptController;
use App\Http\Controllers\Admin\OpenAiUsageController;
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
Route::get('/courses/{course:slug}', [StudentController::class, 'course'])->name('student.course');
Route::get('/grade/{gradeLevel}', [StudentController::class, 'grade'])->name('student.grade'); // Kept for backward compatibility
Route::post('/grade/{gradeLevel}/update-order', [StudentController::class, 'updateLessonOrder'])
    ->middleware('auth:admin')
    ->name('student.grade.update-order');

// Individual Lessons
Route::get('/lessons/{slug}', [LessonController::class, 'show'])->name('lessons.show');

// ... (remaining routes abbreviated - full file in routes/web.php)

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Admin login routes (PUBLIC - no auth required)
Route::get('/admin', [AdminLoginController::class, 'show'])->name('admin.dashboard');
Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login.show');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login');

// Admin password reset routes (PUBLIC)
Route::get('/admin/password/forgot', ...)->name('admin.password.forgot');
Route::post('/admin/password/email', ...)->name('admin.password.email');
Route::get('/admin/password/reset/{token}', ...)->name('admin.password.reset');
Route::post('/admin/password/reset', ...)->name('admin.password.update');

// Admin protected routes - require auth:admin + admin.access
Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.access'])->group(function () {
    Route::get('analytics', ...)->name('analytics');
    Route::get('session-length', ...)->name('session-length');
    Route::get('openai-usage', ...)->name('openai-usage');
    Route::get('lesson-tracker', ...)->name('lesson-tracker');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->middleware('admin.only'); // admin.only = role must be 'admin'
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
    Route::resource('courses', CourseController::class);
    Route::resource('lessons', AdminLessonController::class);
    // ... many more admin routes
});
