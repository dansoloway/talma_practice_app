# Course Container & Publish State Analysis

## Current Status

### ✅ 1. Course Container - EXISTS

**Status:** Fully implemented

**Location:**
- Model: `app/Models/Course.php`
- Migration: `database/migrations/2026_01_28_155847_create_courses_table.php`
- Controller: `app/Http/Controllers/Admin/CourseController.php`

**Fields:**
- `title` (string)
- `slug` (string, unique)
- `description` (text, nullable)
- `cover_image_path` (string, nullable)
- `sort_order` (integer)
- `is_active` (boolean, default: true)
- `archived_at` (timestamp, nullable)

**Capabilities:**
- ✅ Courses can contain multiple lessons
- ✅ Lessons belong to courses (`course_id` foreign key)
- ✅ Course has `is_active` flag
- ✅ Course has `archived_at` for soft deletion
- ✅ Course CRUD operations in admin panel
- ✅ Course archive/unarchive functionality

**Routes:**
- `GET /admin/courses` - List courses
- `GET /admin/courses/create` - Create course
- `POST /admin/courses` - Store course
- `GET /admin/courses/{course}` - Show course
- `GET /admin/courses/{course}/edit` - Edit course
- `PUT /admin/courses/{course}` - Update course
- `POST /admin/courses/{course}/archive` - Archive course
- `POST /admin/courses/{course}/unarchive` - Unarchive course

**Usage:**
- Students browse courses: `/courses/{slug}`
- Lessons are grouped by course
- Course filtering in admin panel

---

### ⚠️ 2. Publish State Workflow - PARTIALLY EXISTS

**Status:** Field exists but NOT used for publish workflow

**Current Implementation:**

**Database Field:**
- Migration: `database/migrations/2025_12_01_115722_add_tracking_fields_to_lessons_table.php`
- Field: `status` (string, default: 'not_started')
- Location: `lessons.status`

**Current Status Values:**
- `not_started` - Lesson hasn't been started
- `in_progress` - Lesson is being worked on
- `done` - Lesson is complete
- `stuck` - Lesson is blocked

**Current Usage:**
- Used in **Lesson Tracker** (`/admin/lesson-tracker`)
- Internal tracking for content creators
- Assignment tracking (`assigned_to` field)
- Admin notes (`admin_notes` field)

**What's Missing:**

❌ **No publish state machine:**
- No "Draft" state
- No "Ready for Review" state  
- No "Published" state
- No workflow transitions

❌ **No visibility control based on status:**
- Students see lessons based on `is_active` boolean only
- No filtering by publish status
- No review workflow

❌ **No course-level publish state:**
- Course only has `is_active` boolean
- No course publish workflow

---

## Gap Analysis

### What Exists

✅ **Course Container**
- Full CRUD operations
- Archive/unarchive
- Lesson grouping
- Student-facing course pages

✅ **Status Field (Internal Tracking)**
- Status field exists on lessons
- Used for internal content creation tracking
- Assignment tracking
- Admin notes

✅ **Basic Visibility Control**
- `is_active` boolean on lessons
- `is_active` boolean on courses
- `archived_at` for soft deletion

### What's Missing

❌ **Publish State Machine**
- No Draft → Ready for Review → Published workflow
- Status field is used for internal tracking, not publishing

❌ **Review Workflow**
- No way to mark lessons as "ready for review"
- No way for pedagogy team to review before publishing
- No approval/rejection workflow

❌ **Publish State Enforcement**
- Students can see lessons based on `is_active` only
- No protection against showing half-built lessons
- No course-level publish state

❌ **State Transition Logic**
- No validation of state transitions
- No automatic state changes
- No state-based permissions

---

## Recommended Implementation

### Option 1: Enhance Existing Status Field (Minimal Change)

**Approach:** Repurpose `status` field for publish workflow

**Changes Required:**

1. **Update Status Values:**
   ```php
   // Migration: Change default and add constraint
   $table->string('status', 20)->default('draft')->change();
   ```

2. **New Status Values:**
   - `draft` - Lesson is being created/edited
   - `ready_for_review` - Lesson is ready for pedagogy review
   - `published` - Lesson is live and visible to students
   - `archived` - Lesson is archived (or use `archived_at`)

3. **Update Student Visibility Logic:**
   ```php
   // Only show published lessons to students
   $lessons = Lesson::where('status', 'published')
       ->where('is_active', true)
       ->whereNull('archived_at')
       ->get();
   ```

4. **Add Status Transition Methods:**
   ```php
   // app/Models/Lesson.php
   public function markReadyForReview(): bool
   {
       if ($this->status !== 'draft') {
           throw new \Exception('Can only mark draft lessons as ready for review');
       }
       return $this->update(['status' => 'ready_for_review']);
   }
   
   public function publish(): bool
   {
       if ($this->status !== 'ready_for_review') {
           throw new \Exception('Can only publish lessons ready for review');
       }
       return $this->update(['status' => 'published', 'is_active' => true]);
   }
   
   public function unpublish(): bool
   {
       return $this->update(['status' => 'draft', 'is_active' => false]);
   }
   ```

**Pros:**
- Minimal code changes
- Reuses existing field
- Quick to implement

**Cons:**
- Loses internal tracking status
- May need separate field for internal tracking

---

### Option 2: Add Separate Publish Status Field (Recommended)

**Approach:** Add new `publish_status` field, keep `status` for internal tracking

**Changes Required:**

1. **New Migration:**
   ```php
   Schema::table('lessons', function (Blueprint $table) {
       $table->string('publish_status', 20)
           ->default('draft')
           ->after('status');
   });
   ```

2. **New Status Values:**
   - `draft` - Lesson is being created/edited
   - `ready_for_review` - Ready for pedagogy review
   - `published` - Live and visible to students

3. **Update Model:**
   ```php
   // app/Models/Lesson.php
   protected $fillable = [
       // ... existing fields
       'publish_status',
   ];
   
   // Scopes
   public function scopePublished($query)
   {
       return $query->where('publish_status', 'published')
           ->where('is_active', true)
           ->whereNull('archived_at');
   }
   
   public function scopeReadyForReview($query)
   {
       return $query->where('publish_status', 'ready_for_review');
   }
   
   public function scopeDraft($query)
   {
       return $query->where('publish_status', 'draft');
   }
   
   // Methods
   public function markReadyForReview(): bool
   {
       if ($this->publish_status !== 'draft') {
           throw new \Exception('Can only mark draft lessons as ready for review');
       }
       return $this->update(['publish_status' => 'ready_for_review']);
   }
   
   public function publish(): bool
   {
       if ($this->publish_status !== 'ready_for_review') {
           throw new \Exception('Can only publish lessons ready for review');
       }
       return $this->update(['publish_status' => 'published', 'is_active' => true]);
   }
   
   public function unpublish(): bool
   {
       return $this->update(['publish_status' => 'draft', 'is_active' => false]);
   }
   
   public function isPublished(): bool
   {
       return $this->publish_status === 'published' 
           && $this->is_active 
           && is_null($this->archived_at);
   }
   ```

4. **Update Student Controller:**
   ```php
   // app/Http/Controllers/LessonController.php
   public function show($slug)
   {
       $lesson = Lesson::where('slug', $slug)
           ->published() // Only show published lessons
           ->firstOrFail();
       // ...
   }
   ```

5. **Add Admin UI:**
   - Status dropdown in lesson edit page
   - "Mark Ready for Review" button
   - "Publish" button (only visible for ready_for_review)
   - "Unpublish" button (only visible for published)

**Pros:**
- Keeps internal tracking separate
- Clear separation of concerns
- More flexible for future enhancements

**Cons:**
- Requires migration
- More code changes

---

### Option 3: Course-Level Publish State

**Approach:** Add publish status to Course model as well

**Changes Required:**

1. **Add to Course Model:**
   ```php
   // Migration
   Schema::table('courses', function (Blueprint $table) {
       $table->string('publish_status', 20)->default('draft')->after('is_active');
   });
   
   // Model methods
   public function publish(): bool
   {
       // Only publish if all lessons are published
       $unpublishedLessons = $this->lessons()
           ->where('publish_status', '!=', 'published')
           ->count();
       
       if ($unpublishedLessons > 0) {
           throw new \Exception('Cannot publish course with unpublished lessons');
       }
       
       return $this->update(['publish_status' => 'published', 'is_active' => true]);
   }
   ```

2. **Update Student Visibility:**
   ```php
   // Only show published courses
   $courses = Course::where('publish_status', 'published')
       ->where('is_active', true)
       ->whereNull('archived_at')
       ->get();
   ```

**Pros:**
- Course-level control
- Can prevent showing courses with incomplete lessons

**Cons:**
- More complex logic
- May be overkill if lesson-level is sufficient

---

## Recommended Implementation Plan

### Phase 1: Add Publish Status to Lessons (Option 2)

1. **Create Migration**
   - Add `publish_status` field to `lessons` table
   - Default: 'draft'
   - Set existing lessons to 'published' (if they're currently active)

2. **Update Lesson Model**
   - Add `publish_status` to fillable
   - Add scopes: `published()`, `readyForReview()`, `draft()`
   - Add methods: `markReadyForReview()`, `publish()`, `unpublish()`, `isPublished()`

3. **Update Student Controller**
   - Filter lessons by `publish_status = 'published'`
   - Only show published lessons to students

4. **Update Admin UI**
   - Add publish status dropdown in lesson edit page
   - Add "Mark Ready for Review" button
   - Add "Publish" button (with permission check)
   - Show publish status in lesson list

5. **Add Permissions** (Optional)
   - Only pedagogy team can publish
   - Content creators can mark ready for review

### Phase 2: Course-Level Publish (Optional)

- Add publish status to courses
- Add course publish workflow
- Prevent showing courses with unpublished lessons

---

## Code Estimate

### Option 1 (Enhance Existing Status)
- Migration: ~20 LOC
- Model updates: ~50 LOC
- Controller updates: ~30 LOC
- UI updates: ~100 LOC
- **Total: ~200 LOC**

### Option 2 (Separate Publish Status) - Recommended
- Migration: ~20 LOC
- Model updates: ~80 LOC
- Controller updates: ~50 LOC
- UI updates: ~150 LOC
- **Total: ~300 LOC**

### Option 3 (Course-Level)
- Additional migration: ~20 LOC
- Course model updates: ~50 LOC
- Additional controller logic: ~50 LOC
- **Additional: ~120 LOC**

---

## Summary

### ✅ What Exists
- **Course Container:** Fully implemented
- **Status Field:** Exists but used for internal tracking
- **Basic Visibility:** `is_active` boolean

### ❌ What's Missing
- **Publish State Machine:** Draft → Ready for Review → Published
- **Review Workflow:** No way for pedagogy to review before publishing
- **State-Based Visibility:** Students see lessons based on `is_active` only

### 🎯 Recommendation
**Implement Option 2:** Add separate `publish_status` field to lessons
- Keeps internal tracking separate
- Clear publish workflow
- ~300 LOC to implement
- Can add course-level later if needed
