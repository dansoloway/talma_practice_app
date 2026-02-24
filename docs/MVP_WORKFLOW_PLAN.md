# MVP Workflow Implementation Plan

## Scope: Pilot MVP

**Goal:** Minimal viable implementation of complete workflow with existing admin UI

## 🎯 Key Finding: Authentication Reuse

**✅ EXISTING INFRASTRUCTURE:**
- `users` table with `email`, `password` (hashed), `role` enum
- `User` model extends `Authenticatable` (Laravel standard)
- Custom `AdminLoginController` with email/password authentication
- Password hashing, session management, remember tokens
- Role checking (`isAdmin()`, `isTeacher()`)

**✅ CAN REUSE:**
- Add 'student' to role enum
- Add `isStudent()` method
- Copy `AdminLoginController` pattern → `StudentLoginController`
- Copy `AdminAuth` middleware pattern → `StudentAuth` middleware

**⚡ SAVINGS:** Phase 3 reduced from 1.5 days → **0.5 days** (saves 1 day!)

---

## MVP Components

### 1. PDF Ingestion + Enrichment
- PDF parser → Course/Lesson/Vocab creation
- Auto-enrichment (translation, TTS, images)
- Auto-game creation
- **Status:** New code needed (~600-700 LOC)

### 2. Publish Workflow (Simplified)
- Add `publish_status` field
- Filter published lessons for students
- Minimal admin controls (status dropdown in existing lesson edit page)
- **Status:** New code needed (~150-200 LOC)

### 3. Whitelist Access Control
- Email whitelist per course
- Middleware to check whitelist
- **Status:** New code needed (~200-250 LOC)

### 4. Binary Completion Tracking
- `lesson_completions` table
- Mark-complete action
- **Status:** New code needed (~150-200 LOC)

### 5. Pedagogy Review (Reuse Existing)
- Use existing lesson edit page
- Change status dropdown to include "Ready for Review" → "Published"
- No new dashboard needed
- **Status:** Minimal changes (~50 LOC)

---

## Database Tables

### New Tables

#### 1. `course_email_whitelists`
```php
Schema::create('course_email_whitelists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('course_id')->constrained()->onDelete('cascade');
    $table->string('email')->index();
    $table->timestamps();
    
    $table->unique(['course_id', 'email']);
});
```

**Purpose:** Store whitelisted emails per course

---

#### 2. `lesson_completions`
```php
Schema::create('lesson_completions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Student user
    $table->foreignId('lesson_id')->constrained()->onDelete('cascade');
    $table->timestamp('completed_at');
    $table->timestamps();
    
    $table->unique(['user_id', 'lesson_id']);
    $table->index(['user_id', 'completed_at']);
});
```

**Purpose:** Track binary completion per student per lesson

**Note:** Uses `user_id` (foreign key) instead of email - cleaner and more normalized

---

### Modified Tables

#### 1. `users` (modify enum)
- Add 'student' to role enum
- **Reuses existing:** password field, authentication system

#### 2. `lessons` (add field)
```php
Schema::table('lessons', function (Blueprint $table) {
    $table->string('publish_status', 20)
        ->default('draft')
        ->after('status')
        ->index();
});
```

**Values:** `draft`, `ready_for_review`, `published`

---

#### 3. `vocabulary` (add fields - from PDF gap analysis)
```php
Schema::table('vocabulary', function (Blueprint $table) {
    $table->text('definition')->nullable()->after('arabic_translation');
    $table->text('example_sentence')->nullable()->after('definition');
});
```

---

## Routes

### New Routes

#### Student Authentication (Reuse Existing Auth System)
```php
// Student login (reuses existing users table + password auth)
Route::get('/student/login', [StudentLoginController::class, 'show'])
    ->name('student.login.show');

Route::post('/student/login', [StudentLoginController::class, 'login'])
    ->name('student.login');

Route::post('/student/logout', [StudentLoginController::class, 'logout'])
    ->name('student.logout');
```

#### Completion Tracking
```php
// Mark lesson as completed
Route::post('/lessons/{lesson}/complete', [LessonCompletionController::class, 'markComplete'])
    ->middleware('student.auth')
    ->name('lessons.complete');
```

#### Whitelist Management (Admin)
```php
// Course whitelist management
Route::get('/admin/courses/{course}/whitelist', [CourseWhitelistController::class, 'index'])
    ->middleware('admin.auth')
    ->name('admin.courses.whitelist.index');

Route::post('/admin/courses/{course}/whitelist', [CourseWhitelistController::class, 'store'])
    ->middleware('admin.auth')
    ->name('admin.courses.whitelist.store');

Route::delete('/admin/courses/{course}/whitelist/{whitelist}', [CourseWhitelistController::class, 'destroy'])
    ->middleware('admin.auth')
    ->name('admin.courses.whitelist.destroy');

Route::post('/admin/courses/{course}/whitelist/bulk', [CourseWhitelistController::class, 'bulkStore'])
    ->middleware('admin.auth')
    ->name('admin.courses.whitelist.bulk');
```

---

### Modified Routes

#### Student Routes (add middleware)
```php
// Existing student routes - add middleware
Route::get('/', [StudentController::class, 'index'])
    ->middleware('student.auth')
    ->name('student.index');

Route::get('/courses/{course:slug}', [StudentController::class, 'course'])
    ->middleware('student.auth')
    ->name('student.course');

Route::get('/lessons/{slug}', [LessonController::class, 'show'])
    ->middleware('student.auth')
    ->name('lessons.show');
```

---

## Middleware

### New Middleware

#### 1. `StudentAuth` Middleware
**File:** `app/Http/Middleware/StudentAuth.php`

**Purpose:** Check if student is authenticated (reuses existing auth pattern)

**Logic:** (Similar to `AdminAuth` middleware)
```php
public function handle(Request $request, Closure $next)
{
    // Check if student is authenticated via session
    if (session('student_authenticated')) {
        $userId = session('student_user_id');
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->is_active && $user->isStudent()) {
                return $next($request);
            }
        }
        session()->forget(['student_authenticated', 'student_user_id', 'student_user_email']);
    }
    
    // Check remember token cookie (reuse pattern)
    $rememberToken = $request->cookie('student_remember_token');
    if ($rememberToken) {
        $user = User::where('remember_token', hash('sha256', $rememberToken))
            ->where('is_active', true)
            ->where('role', 'student')
            ->first();
        
        if ($user) {
            $request->session()->regenerate();
            session([
                'student_authenticated' => true,
                'student_user_id' => $user->id,
                'student_user_email' => $user->email,
            ]);
            return $next($request);
        }
    }
    
    return redirect()->route('student.login.show');
}
```

---

#### 2. `CheckCourseAccess` Middleware
**File:** `app/Http/Middleware/CheckCourseAccess.php`

**Purpose:** Verify student email is whitelisted for course

**Logic:**
```php
public function handle(Request $request, Closure $next, Course $course)
{
    // Get authenticated student (already checked by StudentAuth middleware)
    $userId = session('student_user_id');
    $user = User::find($userId);
    
    if (!$user || !$user->isStudent()) {
        return redirect()->route('student.login.show');
    }
    
    // Check if student email is whitelisted for this course
    $isWhitelisted = CourseEmailWhitelist::where('course_id', $course->id)
        ->where('email', $user->email)
        ->exists();
    
    if (!$isWhitelisted) {
        abort(403, 'You do not have access to this course.');
    }
    
    return $next($request);
}
```

**Usage:** Apply to course and lesson routes

---

## Controllers

### New Controllers

#### 1. `StudentLoginController`
**File:** `app/Http/Controllers/StudentLoginController.php`

**Methods:**
- `show()` - Show login form (reuse pattern from `AdminLoginController`)
- `login(Request $request)` - Authenticate email/password, verify whitelist, set session
  - Validate email/password
  - Find user with `role = 'student'`
  - Verify password
  - Check email is whitelisted for at least one course
  - Set session (`student_authenticated`, `student_user_id`, `student_user_email`)
  - Handle remember token (optional)
- `logout()` - Clear session and remember token

**Reuses:** Password authentication pattern from `AdminLoginController`
**Additional:** Check email is whitelisted for at least one course (after auth)

**Estimated LOC:** ~100-120 lines (reusing existing patterns)

---

#### 2. `CourseWhitelistController`
**File:** `app/Http/Controllers/Admin/CourseWhitelistController.php`

**Methods:**
- `index(Course $course)` - Show whitelist for course
- `store(Request $request, Course $course)` - Add email to whitelist
- `bulkStore(Request $request, Course $course)` - Bulk add emails (CSV)
- `destroy(Course $course, CourseEmailWhitelist $whitelist)` - Remove email

**Estimated LOC:** ~150-200 lines

---

#### 3. `LessonCompletionController`
**File:** `app/Http/Controllers/LessonCompletionController.php`

**Methods:**
- `markComplete(Request $request, Lesson $lesson)` - Mark lesson as completed
  - Get authenticated user from session
  - Create or update `LessonCompletion` record
  - Return success response

**Estimated LOC:** ~30-50 lines

---

### Modified Controllers

#### 4. `StudentController` (modify)
**Changes:**
- Get authenticated user from session (`student_user_id`)
- Filter courses by whitelist (only show courses student has access to)
- Show completion status for lessons (check `LessonCompletion` table)
- Load completions for current user

**Estimated LOC changes:** ~50-80 lines

---

#### 5. `LessonController` (modify)
**Changes:**
- Filter by `publish_status = 'published'`
- Get authenticated user from session
- Show completion status (check `LessonCompletion` for current user)
- Add "Mark Complete" button (if not completed)

**Estimated LOC changes:** ~30-50 lines

---

#### 6. `Admin/LessonController` (modify)
**Changes:**
- Add `publish_status` dropdown in edit form
- Add "Mark Ready for Review" button (if draft)
- Add "Publish" button (if ready_for_review)

**Estimated LOC changes:** ~50-80 lines

---

## Models

### New Models

#### 1. `CourseEmailWhitelist`
**File:** `app/Models/CourseEmailWhitelist.php`

**Relationships:**
- `belongsTo(Course::class)`

**Note:** This links emails to courses. When student logs in, we check if their email is whitelisted for any course.

**Estimated LOC:** ~30-40 lines

---

#### 2. `LessonCompletion`
**File:** `app/Models/LessonCompletion.php`

**Relationships:**
- `belongsTo(User::class)` - Student user
- `belongsTo(Lesson::class)`

**Scopes:**
- `scopeForUser($query, $userId)`
- `scopeCompleted($query)`

**Estimated LOC:** ~40-50 lines

---

### Modified Models

#### 3. `User` (add method)
**Changes:**
- Add `isStudent()` method
- Update role enum to include 'student'

**Estimated LOC changes:** ~5-10 lines

#### 4. `Lesson` (add methods)
**Changes:**
- Add `publish_status` to fillable
- Add scopes: `published()`, `readyForReview()`, `draft()`
- Add methods: `markReadyForReview()`, `publish()`, `isPublished()`
- Add relationship: `completions()`

**Estimated LOC changes:** ~50-70 lines

---

#### 5. `Course` (add relationship)
**Changes:**
- Add relationship: `emailWhitelists()`
- Add method: `isEmailWhitelisted($email)`

**Estimated LOC changes:** ~20-30 lines

---

## Views

### New Views

#### 1. Student Login
**File:** `resources/views/student/login.blade.php`

**Content:**
- Email + password form (reuse pattern from admin login)
- "Login" button
- Error message display

**Reuses:** Pattern from `resources/views/admin/login.blade.php`

**Estimated LOC:** ~50-60 lines (similar to admin login)

---

#### 2. Course Whitelist Management
**File:** `resources/views/admin/courses/whitelist.blade.php`

**Content:**
- List of whitelisted emails
- Add email form
- Bulk upload (CSV) option
- Remove email buttons

**Estimated LOC:** ~100-150 lines

---

### Modified Views

#### 3. Lesson Edit Form
**File:** `resources/views/admin/lessons/edit.blade.php`

**Changes:**
- Add `publish_status` dropdown
- Add "Mark Ready for Review" button (conditional)
- Add "Publish" button (conditional)

**Estimated LOC changes:** ~30-50 lines

---

#### 4. Student Course Page
**File:** `resources/views/student/course.blade.php`

**Changes:**
- Show completion status (✓ or empty)
- Add "Mark Complete" button if not completed

**Estimated LOC changes:** ~30-50 lines

---

#### 5. Lesson Show Page
**File:** `resources/views/lessons/show.blade.php`

**Changes:**
- Show completion status
- Add "Mark Complete" button if not completed

**Estimated LOC changes:** ~20-30 lines

---

## Effort Estimate (Days)

### Phase 1: Database & Models (1 day)
- Create migrations (2 tables, 2 field additions)
- Create models (`CourseEmailWhitelist`, `LessonCompletion`)
- Update models (`Lesson`, `Course`)
- **Total: 1 day**

---

### Phase 2: Publish Workflow (1 day)
- Add `publish_status` field and migration
- Add model scopes and methods
- Update student controller to filter published
- Update admin lesson edit form
- Add status buttons
- **Total: 1 day**

---

### Phase 3: Whitelist Access Control (0.5 days) ⚡ REDUCED
- ✅ **REUSE:** Existing `users` table with `password` field
- ✅ **REUSE:** Existing `User` model (extends `Authenticatable`)
- ✅ **REUSE:** Existing password hashing (`Hash::check()`)
- Add 'student' to role enum in users table migration
- Add `isStudent()` method to User model
- Create `StudentAuth` middleware (similar to `AdminAuth`)
- Create `CheckCourseAccess` middleware
- Create `StudentLoginController` (reuse pattern from `AdminLoginController`)
- Create student login view (reuse pattern from admin login)
- Create `CourseWhitelistController` (admin only)
- Create whitelist management views
- Update routes with middleware
- **Total: 0.5 days** (much smaller - reusing existing auth!)

---

### Phase 4: Completion Tracking (1 day)
- Create `LessonCompletionController`
- Create `lesson_completions` table migration
- Add completion status to student views
- Add "Mark Complete" action
- Update student controller to show completion status
- **Total: 1 day**

---

### Phase 5: PDF Ingestion (2 days)
- Create PDF parser service
- Create structured data extractor
- Create PDF import command
- Integrate with enrichment pipeline
- Test with sample PDFs
- **Total: 2 days**

---

### Phase 6: Testing & Integration (1 day)
- End-to-end testing
- Fix bugs
- Integration testing
- Documentation updates
- **Total: 1 day**

---

## Total Effort Estimate

| Phase | Component | Days |
|-------|-----------|------|
| Phase 1 | Database & Models | 1 |
| Phase 2 | Publish Workflow | 1 |
| Phase 3 | Whitelist Access Control | 0.5 ⚡ |
| Phase 4 | Completion Tracking | 1 |
| Phase 5 | PDF Ingestion | 2 |
| Phase 6 | Testing & Integration | 1 |
| **Total** | | **6.5 days** |

**Rounded:** **7 days** (1 week)

---

## Summary

### Database Tables (New)
1. `course_email_whitelists` - Email whitelist per course
2. `lesson_completions` - Binary completion tracking

### Database Tables (Modified)
1. `lessons` - Add `publish_status` field
2. `vocabulary` - Add `definition`, `example_sentence` fields

### Routes (New)
- `/student/login` - Student email login
- `/student/logout` - Student logout
- `/lessons/{lesson}/complete` - Mark lesson complete
- `/admin/courses/{course}/whitelist` - Whitelist management (CRUD)

### Routes (Modified)
- All student routes - Add `student.auth` middleware
- Course/lesson routes - Add `CheckCourseAccess` middleware

### Middleware (New)
1. `StudentAuth` - Check student email in session
2. `CheckCourseAccess` - Verify email whitelisted for course

### Controllers (New)
1. `StudentAuthController` - Student login/logout
2. `CourseWhitelistController` - Whitelist management
3. `LessonCompletionController` - Mark complete action

### Controllers (Modified)
1. `StudentController` - Filter by whitelist, show completion
2. `LessonController` - Filter published, show completion
3. `Admin/LessonController` - Add publish status controls

### Models (New)
1. `CourseEmailWhitelist`
2. `LessonCompletion`

### Models (Modified)
1. `Lesson` - Add publish_status methods/scopes
2. `Course` - Add whitelist relationship

### Views (New)
1. `student/login.blade.php` - Simple email login
2. `admin/courses/whitelist.blade.php` - Whitelist management

### Views (Modified)
1. `admin/lessons/edit.blade.php` - Add publish status controls
2. `student/course.blade.php` - Show completion status
3. `lessons/show.blade.php` - Show completion, mark complete button

---

## Implementation Checklist

### Database
- [ ] Modify `users` table: Add 'student' to role enum
- [ ] Create `course_email_whitelists` migration
- [ ] Create `lesson_completions` migration
- [ ] Add `publish_status` to `lessons` migration
- [ ] Add `definition`, `example_sentence` to `vocabulary` migration
- [ ] Run migrations

### Models
- [ ] Update `User` model (add `isStudent()` method)
- [ ] Create `CourseEmailWhitelist` model
- [ ] Create `LessonCompletion` model
- [ ] Update `Lesson` model (publish_status methods)
- [ ] Update `Course` model (whitelist relationship)

### Middleware
- [ ] Create `StudentAuth` middleware
- [ ] Create `CheckCourseAccess` middleware
- [ ] Register middleware in `bootstrap/app.php`

### Controllers
- [ ] Create `StudentLoginController` (reuse pattern from `AdminLoginController`)
- [ ] Create `CourseWhitelistController` (admin only)
- [ ] Create `LessonCompletionController`
- [ ] Update `StudentController` (whitelist filtering, completion)
- [ ] Update `LessonController` (published filter, completion)
- [ ] Update `Admin/LessonController` (publish status controls)

### Routes
- [ ] Add student auth routes
- [ ] Add completion route
- [ ] Add whitelist management routes
- [ ] Add middleware to existing student routes

### Views
- [ ] Create student login view
- [ ] Create whitelist management view
- [ ] Update lesson edit form
- [ ] Update student course page
- [ ] Update lesson show page

### PDF Ingestion (Separate Phase)
- [ ] Create PDF parser service
- [ ] Create structured data extractor
- [ ] Create PDF import command
- [ ] Integrate with enrichment pipeline

---

## Authentication System Analysis

### ✅ Existing Infrastructure (REUSE THIS!)

**What Exists:**
- ✅ `users` table with `email`, `password` (hashed), `role` enum
- ✅ `User` model extends `Authenticatable` (Laravel standard)
- ✅ Password hashing via `Hash::check()` and `Hash::make()`
- ✅ Custom authentication controller (`AdminLoginController`)
- ✅ Session-based authentication
- ✅ Remember token support
- ✅ Rate limiting on login
- ✅ Role checking (`isAdmin()`, `isTeacher()`, `canAccessAdmin()`)

**What We Need to Add:**
- Add 'student' to role enum
- Add `isStudent()` method to User model
- Create `StudentLoginController` (copy pattern from `AdminLoginController`)
- Create `StudentAuth` middleware (copy pattern from `AdminAuth`)
- Add whitelist check after authentication

**Savings:** ~0.5-1 day (no need to build auth from scratch!)

---

## Notes

### Simplifications for MVP

1. **Reuse Existing Authentication**
   - Students use same `users` table with email + password
   - Students have `role = 'student'`
   - Email must be in whitelist for at least one course
   - Reuses existing password hashing and session management

2. **No New Review Dashboard**
   - Use existing lesson edit page
   - Add status dropdown and buttons
   - Pedagogy reviews by editing lesson and changing status

3. **No Reject Workflow**
   - If lesson needs changes, pedagogy changes status back to "draft"
   - Content creator fixes and resubmits

4. **No Comments/Notifications**
   - Use `admin_notes` field on lesson for communication
   - No notification system

5. **No Student Dashboard**
   - Students see courses/lessons on existing pages
   - Completion status shown inline

6. **No Analytics Review**
   - Use existing analytics dashboard
   - No special post-publish review interface

---

## Risk Mitigation

### Potential Issues

1. **Email Validation**
   - Ensure emails are normalized (lowercase, trimmed)
   - Handle email typos gracefully

2. **Session Management**
   - Sessions expire after inactivity
   - Clear session on logout

3. **Whitelist Management**
   - Bulk import should handle duplicates
   - Validate email format

4. **Completion Tracking**
   - Prevent duplicate completions (unique constraint)
   - Handle edge cases (lesson deleted, etc.)

---

## Future Enhancements (Post-MVP)

1. Password authentication for students
2. Student dashboard with progress overview
3. Review dashboard with queue
4. Comments/notifications system
5. Advanced completion tracking (per activity)
6. Course enrollment system
7. Single sign-on integration

---

**Total MVP Effort: 7 days (1 week)** ⚡

**Reduced from 8 days because:**
- ✅ Reusing existing `users` table with password authentication
- ✅ Reusing existing `User` model (extends `Authenticatable`)
- ✅ Reusing authentication patterns from `AdminLoginController`
- ✅ No need to build authentication from scratch
