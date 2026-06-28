<?php

use App\Http\Controllers\ActivityEventController;
use App\Http\Controllers\GuidedLessonController;
use App\Http\Controllers\LearnerProfileController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\PromptController;
use App\Http\Controllers\PromptModelController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentChildController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\VoiceSampleController;
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
use App\Http\Controllers\Admin\OrgSelectController;
use App\Http\Controllers\Admin\ClassroomController;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Student Homepage (legacy - uses Default org)
Route::get('/', [StudentController::class, 'index'])->name('student.index');
Route::get('/lessons', [StudentController::class, 'index'])->name('lessons.index');
Route::get('/courses/{course:slug}', [StudentController::class, 'course'])->name('student.course');

// Org-scoped student auth (public — no student.org.access middleware)
Route::prefix('o/{organization}')->name('org.student.')->middleware(['org.context'])->group(function () {
    Route::get('login', [StudentAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [StudentAuthController::class, 'login'])->name('login.submit');
    Route::get('register', [StudentAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [StudentAuthController::class, 'register'])->name('register.submit');
    Route::post('logout', [StudentAuthController::class, 'logout'])->middleware('auth:admin')->name('logout');
});

// Org-scoped student routes (protected when org is restricted)
Route::prefix('o/{organization}')->name('org.student.')->middleware(['org.context', 'student.org.access', 'learner.selected'])->group(function () {
    Route::get('complete-voice-profile', [LearnerProfileController::class, 'showCompleteVoiceProfile'])->name('complete-voice-profile');
    Route::post('complete-voice-profile', [LearnerProfileController::class, 'storeCompleteVoiceProfile'])->name('complete-voice-profile.submit');
    Route::get('select-child', [StudentChildController::class, 'selectChild'])->name('select-child');
    Route::post('select-child', [StudentChildController::class, 'storeSelectedChild'])->name('select-child.submit');
});

Route::prefix('o/{organization}')->name('org.student.')->middleware(['org.context', 'student.org.access', 'learner.selected', 'learner.voice-profile'])->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('courses/{course:slug}', [StudentController::class, 'course'])->name('course');
    Route::get('lessons/{lesson}/guided/vocabulary', [GuidedLessonController::class, 'vocabulary'])->name('guided.vocabulary');
    Route::get('lessons/{slug}', [LessonController::class, 'show'])->name('lesson');
});
Route::get('/grade/{gradeLevel}', [StudentController::class, 'grade'])->name('student.grade'); // Kept for backward compatibility
Route::post('/grade/{gradeLevel}/update-order', [StudentController::class, 'updateLessonOrder'])
    ->middleware('auth:admin')
    ->name('student.grade.update-order');

// Individual Lessons
Route::get('/lessons/{slug}', [LessonController::class, 'show'])->name('lessons.show');
Route::get('/lessons/{lesson}/guided/vocabulary', [GuidedLessonController::class, 'vocabulary'])->name('guided.vocabulary');

// Matching Games (public)
Route::get('/lessons/{lesson}/matching-games/{matching_game}/play', [App\Http\Controllers\Admin\MatchingGameController::class, 'play'])
    ->name('matching-games.play');

// Flashcard Games (public)
Route::get('/lessons/{lesson}/flashcard-games/{flashcard_game}/play', [App\Http\Controllers\Admin\FlashcardGameController::class, 'play'])
    ->name('flashcard-games.play');

// Spelling Games (public)
Route::get('/lessons/{lesson}/spelling-games/{spelling_game}/play', [App\Http\Controllers\Admin\SpellingGameController::class, 'play'])
    ->name('spelling-games.play');

// Sentence Builder Games (public)
Route::get('/lessons/{lesson}/sentence-builder-games/{sentence_builder_game}/play', [App\Http\Controllers\Admin\SentenceBuilderGameController::class, 'play'])
    ->name('sentence-builder-games.play');

// Prompts (JSON API) - Rate limited
Route::get('/prompts/{id}', [PromptController::class, 'show'])
    ->middleware('throttle:100,1')
    ->name('prompts.show');
Route::get('/lessons/{lesson}/prompts/play', [PromptController::class, 'play'])
    ->middleware('throttle:100,1')
    ->name('prompts.play');
Route::get('/lessons/{lesson}/true-false-games/{trueFalseGame}/play', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'play'])
    ->middleware('throttle:100,1')
    ->scopeBindings()
    ->name('true-false-games.play');

// Clause Exercises (public)
Route::get('/lessons/{lesson}/clause-exercises/{clauseExercise}/play', [App\Http\Controllers\Admin\ClauseExerciseController::class, 'play'])
    ->middleware('throttle:100,1')
    ->name('clause-exercises.play');
Route::get('/prompts/{promptId}/options/{optionId}/model', [PromptModelController::class, 'show'])
    ->middleware('throttle:100,1')
    ->name('prompts.model');

// Responses - Rate limited
Route::post('/responses', [ResponseController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('responses.store');
Route::post('/voice-samples', [VoiceSampleController::class, 'store'])
    ->middleware(['auth:admin', 'throttle:60,1'])
    ->name('voice-samples.store');
Route::post('/activity-events', [ActivityEventController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('activity-events.store');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Admin login routes
Route::get('/admin', [AdminLoginController::class, 'show'])->name('admin.dashboard');
Route::get('/admin/login', [AdminLoginController::class, 'show'])->name('admin.login.show');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login');

// Admin password reset routes
Route::get('/admin/password/forgot', [\App\Http\Controllers\Admin\PasswordResetController::class, 'showForgotPasswordForm'])->name('admin.password.forgot');
Route::post('/admin/password/email', [\App\Http\Controllers\Admin\PasswordResetController::class, 'sendResetLinkEmail'])->name('admin.password.email');
Route::get('/admin/password/reset/{token}', [\App\Http\Controllers\Admin\PasswordResetController::class, 'showResetForm'])->name('admin.password.reset');
Route::post('/admin/password/reset', [\App\Http\Controllers\Admin\PasswordResetController::class, 'reset'])->name('admin.password.update');

// Org-scoped admin routes: /o/{org}/admin/*
Route::prefix('o/{organization}')->name('org.admin.')->middleware(['auth:admin', 'admin.access', 'org.context', 'org.member'])->group(function () {
    Route::get('admin', fn () => redirect()->route('org.admin.analytics', ['organization' => request()->route('organization')]))->name('dashboard');
    Route::get('admin/analytics', [DashboardController::class, 'index'])->name('analytics');
    Route::post('admin/courses/{course}/archive', [CourseController::class, 'archive'])->name('courses.archive');
    Route::post('admin/courses/{course}/unarchive', [CourseController::class, 'unarchive'])->name('courses.unarchive');
    Route::post('admin/courses/{course}/toggle-org-wide', [CourseController::class, 'toggleOrgWide'])->name('courses.toggle-org-wide');
    Route::get('admin/courses/add-from-root', [CourseController::class, 'addFromRoot'])->name('courses.add-from-root')->middleware('admin.only');
    Route::post('admin/courses/attach-from-root', [CourseController::class, 'attachFromRoot'])->name('courses.attach-from-root')->middleware('admin.only');
    Route::post('admin/courses/{course}/detach-from-org', [CourseController::class, 'detachFromOrg'])->name('courses.detach-from-org')->middleware('admin.only');
    Route::resource('admin/courses', CourseController::class)->names([
        'index' => 'courses.index',
        'create' => 'courses.create',
        'store' => 'courses.store',
        'show' => 'courses.show',
        'edit' => 'courses.edit',
        'update' => 'courses.update',
        'destroy' => 'courses.destroy',
    ]);
    Route::resource('admin/classrooms', ClassroomController::class)->names([
        'index' => 'classrooms.index',
        'create' => 'classrooms.create',
        'store' => 'classrooms.store',
        'show' => 'classrooms.show',
        'edit' => 'classrooms.edit',
        'update' => 'classrooms.update',
        'destroy' => 'classrooms.destroy',
    ])->parameters(['classrooms' => 'classroom']);
    Route::post('admin/classrooms/{classroom}/sync-students', [ClassroomController::class, 'syncStudents'])->name('classrooms.sync-students');
    Route::post('admin/classrooms/{classroom}/sync-teachers', [ClassroomController::class, 'syncTeachers'])->name('classrooms.sync-teachers');
    Route::post('admin/classrooms/{classroom}/sync-courses', [ClassroomController::class, 'syncCourses'])->name('classrooms.sync-courses');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin.access'])->group(function () {
    // Org selection (before entering org context)
    Route::get('org/select', [OrgSelectController::class, 'index'])->name('org.select');
    Route::post('org/select', [OrgSelectController::class, 'store'])->name('org.select.store');

    // Analytics routes
    Route::get('analytics', [DashboardController::class, 'index'])->name('analytics');
    Route::get('session-length', [DashboardController::class, 'sessionLengthDashboard'])->name('session-length');
    Route::get('session-length/day-breakdown', [DashboardController::class, 'getDayActivityBreakdown'])->name('session-length.day-breakdown');
    Route::get('openai-usage', [OpenAiUsageController::class, 'index'])->name('openai-usage');
    
    // Lesson Tracker
    Route::get('lesson-tracker', [LessonTrackerController::class, 'index'])->name('lesson-tracker');
    Route::put('lesson-tracker/{lesson}', [LessonTrackerController::class, 'update'])->name('lesson-tracker.update');
    
    // User Management (admin only) - includes teachers and admins
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class)->middleware('admin.only');

    // Organization Management (admin only)
    Route::resource('organizations', \App\Http\Controllers\Admin\OrganizationController::class)->middleware('admin.only')->except(['show', 'destroy']);

    // Terms and Conditions (admin only)
    Route::get('terms-and-conditions', [\App\Http\Controllers\Admin\TermsAndConditionsController::class, 'index'])
        ->middleware('admin.only')->name('terms-and-conditions.index');
    Route::get('terms-and-conditions/{termsAndCondition}/edit', [\App\Http\Controllers\Admin\TermsAndConditionsController::class, 'edit'])
        ->middleware('admin.only')->name('terms-and-conditions.edit');
    Route::put('terms-and-conditions/{termsAndCondition}', [\App\Http\Controllers\Admin\TermsAndConditionsController::class, 'update'])
        ->middleware('admin.only')->name('terms-and-conditions.update');
    
    // Logout
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
    
    // Courses
    Route::resource('courses', CourseController::class);
    Route::post('courses/{course}/archive', [CourseController::class, 'archive'])
        ->name('courses.archive');
    Route::post('courses/{course}/unarchive', [CourseController::class, 'unarchive'])
        ->name('courses.unarchive');
    
    // Lessons
    Route::resource('lessons', AdminLessonController::class);
    Route::get('lessons/{lesson}/manage', [AdminLessonController::class, 'manage'])
        ->name('lessons.manage');
    Route::post('lessons/get-vocabulary-for-review', [AdminLessonController::class, 'getVocabularyForReview'])
        ->name('lessons.get-vocabulary-for-review');
    Route::post('lessons/{lesson}/update-activity-order', [AdminLessonController::class, 'updateActivityOrder'])
        ->name('lessons.update-activity-order');
    Route::post('lessons/{lesson}/delete-activity', [AdminLessonController::class, 'deleteActivity'])
        ->name('lessons.delete-activity');
    Route::post('lessons/combine', [AdminLessonController::class, 'combine'])
        ->name('lessons.combine');
    
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
    Route::post('lessons/{lesson}/vocabulary/bulk', [AdminVocabularyController::class, 'bulkStore'])
        ->name('lessons.vocabulary.bulk-store');
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
    Route::post('lessons/{lesson}/vocabulary/{vocabulary}/generate-image', [AdminVocabularyController::class, 'generateImage'])
        ->name('lessons.vocabulary.generate-image');
    Route::post('lessons/{lesson}/vocabulary/generate-tts', [AdminVocabularyController::class, 'generateTts'])
        ->name('lessons.vocabulary.generate-tts');
    
    // Grammar Sets for Lessons
    Route::post('lessons/{lesson}/grammar-sets/attach', [AdminLessonController::class, 'attachGrammarSet'])
        ->name('lessons.grammar-sets.attach');
    Route::delete('lessons/{lesson}/grammar-sets/{grammarSet}/detach', [AdminLessonController::class, 'detachGrammarSet'])
        ->name('lessons.grammar-sets.detach');
    
    // Cover image routes
    Route::put('lessons/{lesson}/cover-image', [AdminLessonController::class, 'updateCoverImage'])
        ->name('lessons.update-cover-image');
    Route::put('lessons/{lesson}/cover-image/remove', [AdminLessonController::class, 'removeCoverImage'])
        ->name('lessons.remove-cover-image');
    
    // Clause Exercises
    Route::get('lessons/{lesson}/clause-exercises/create', [\App\Http\Controllers\Admin\ClauseExerciseController::class, 'create'])
        ->name('lessons.clause-exercises.create');
    Route::post('lessons/{lesson}/clause-exercises', [\App\Http\Controllers\Admin\ClauseExerciseController::class, 'store'])
        ->name('lessons.clause-exercises.store');
    Route::get('lessons/{lesson}/clause-exercises/{clauseExercise}/edit', [\App\Http\Controllers\Admin\ClauseExerciseController::class, 'edit'])
        ->name('lessons.clause-exercises.edit');
    Route::put('lessons/{lesson}/clause-exercises/{clauseExercise}', [\App\Http\Controllers\Admin\ClauseExerciseController::class, 'update'])
        ->name('lessons.clause-exercises.update');
    Route::delete('lessons/{lesson}/clause-exercises/{clauseExercise}', [\App\Http\Controllers\Admin\ClauseExerciseController::class, 'destroy'])
        ->name('lessons.clause-exercises.destroy');
    
    // Grammar Sets (formerly Grammar Concepts)
    Route::get('grammar-concepts', [GrammarConceptController::class, 'index'])
        ->name('grammar-concepts.index');
    Route::get('grammar-concepts/csv/upload', [GrammarConceptController::class, 'csvUpload'])
        ->name('grammar-concepts.csv.upload');
    Route::post('grammar-concepts/csv/process', [GrammarConceptController::class, 'processCsv'])
        ->name('grammar-concepts.csv.process');
    Route::get('grammar-concepts/{grammarConcept}/edit', [GrammarConceptController::class, 'edit'])
        ->name('grammar-concepts.edit');
    Route::put('grammar-concepts/{grammarConcept}', [GrammarConceptController::class, 'update'])
        ->name('grammar-concepts.update');
    Route::delete('grammar-concepts/{grammarConcept}', [GrammarConceptController::class, 'destroy'])
        ->name('grammar-concepts.destroy');
    
    // Grammar Set management
    Route::get('grammar-sets/{grammarSet}/edit', [GrammarConceptController::class, 'editSet'])
        ->name('grammar-sets.edit');
    Route::put('grammar-sets/{grammarSet}', [GrammarConceptController::class, 'updateSet'])
        ->name('grammar-sets.update');
    Route::post('lessons/{lesson}/vocabulary/{vocabulary}/generate-tts', [AdminVocabularyController::class, 'generateSingleTts'])
        ->name('lessons.vocabulary.generate-single-tts');
    Route::post('lessons/{lesson}/vocabulary/generate-images', [AdminVocabularyController::class, 'generateImages'])
        ->name('lessons.vocabulary.generate-images');
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
    
    // Spelling Games
    Route::resource('lessons.spelling-games', App\Http\Controllers\Admin\SpellingGameController::class);
    
    // Sentence Builder Games
    Route::resource('lessons.sentence-builder-games', App\Http\Controllers\Admin\SentenceBuilderGameController::class);
    Route::post('lessons/{lesson}/sentence-builder-games/{sentence_builder_game}/generate', [App\Http\Controllers\Admin\SentenceBuilderGameController::class, 'generate'])
        ->name('lessons.sentence-builder-games.generate');
    Route::post('lessons/{lesson}/sentence-builder-games/{sentence_builder_game}/questions', [App\Http\Controllers\Admin\SentenceBuilderGameController::class, 'storeQuestion'])
        ->name('lessons.sentence-builder-games.store-question');
    Route::put('lessons/{lesson}/sentence-builder-games/{sentence_builder_game}/questions/{question}', [App\Http\Controllers\Admin\SentenceBuilderGameController::class, 'updateQuestion'])
        ->name('lessons.sentence-builder-games.update-question');
    Route::delete('lessons/{lesson}/sentence-builder-games/{sentence_builder_game}/questions/{question}', [App\Http\Controllers\Admin\SentenceBuilderGameController::class, 'deleteQuestion'])
        ->name('lessons.sentence-builder-games.delete-question');
    
    // True/False Games
    Route::get('lessons/{lesson}/true-false-games', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'index'])
        ->name('lessons.true-false-games.index');
    Route::get('lessons/{lesson}/true-false-games/create', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'create'])
        ->name('lessons.true-false-games.create');
    Route::post('lessons/{lesson}/true-false-games', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'store'])
        ->name('lessons.true-false-games.store');
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'show'])
        ->name('lessons.true-false-games.show');
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}/edit', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'edit'])
        ->name('lessons.true-false-games.edit');
    Route::put('lessons/{lesson}/true-false-games/{trueFalseGame}', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'update'])
        ->name('lessons.true-false-games.update');
    Route::delete('lessons/{lesson}/true-false-games/{trueFalseGame}', [App\Http\Controllers\Admin\TrueFalseGameController::class, 'destroy'])
        ->name('lessons.true-false-games.destroy');
    
    // True/False Questions (within a game)
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}/questions', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'index'])
        ->name('lessons.true-false-games.questions.index');
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/create', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'create'])
        ->name('lessons.true-false-games.questions.create');
    Route::post('lessons/{lesson}/true-false-games/{trueFalseGame}/questions', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'store'])
        ->name('lessons.true-false-games.questions.store');
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'show'])
        ->name('lessons.true-false-games.questions.show');
    Route::get('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}/edit', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'edit'])
        ->name('lessons.true-false-games.questions.edit');
    Route::put('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'update'])
        ->name('lessons.true-false-games.questions.update');
    Route::delete('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'destroy'])
        ->name('lessons.true-false-games.questions.destroy');
    Route::post('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}/approve', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'approve'])
        ->name('lessons.true-false-games.questions.approve');
    Route::post('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/{trueFalseQuestion}/reject', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'reject'])
        ->name('lessons.true-false-games.questions.reject');
    Route::post('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/bulk-approve', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'bulkApprove'])
        ->name('lessons.true-false-games.questions.bulk-approve');
    Route::post('lessons/{lesson}/true-false-games/{trueFalseGame}/questions/generate', [App\Http\Controllers\Admin\TrueFalseQuestionController::class, 'generate'])
        ->name('lessons.true-false-games.questions.generate');
    
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

