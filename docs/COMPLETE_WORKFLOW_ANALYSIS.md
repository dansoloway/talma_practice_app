# Complete Workflow Analysis & Implementation Plan

## Your Desired Workflow

```
PDF ingestion script 
  → creates/updates Course + Lessons + Vocab
  → Auto-create games + trigger enrichment (reuse existing)
  → Publish workflow (Option 2: add publish_status)
  → Pedagogy review (move lessons to published)
  → Restricted access + completion tracking (binary)
  → Final pedagogy check
```

---

## Current State Analysis

### ✅ 1. PDF Ingestion Script

**Status:** ❌ NOT BUILT (but planned in gap analysis)

**What Exists:**
- CSV vocabulary import (`VocabularyController::processCsv()`)
- Bulk lesson import (`ImportPracticePalLessons`)
- Enrichment pipeline (translation, TTS, images)
- Game creation (`createGamesForLesson()`)

**What's Needed:**
- PDF parser service (~200-300 LOC)
- Structured data extractor (~150-200 LOC)
- PDF import command (~100-150 LOC)
- Integration with existing enrichment pipeline

**Estimated Effort:** ~600-700 LOC (from gap analysis)

---

### ✅ 2. Auto-Create Games + Trigger Enrichment

**Status:** ✅ EXISTS (100% reusable)

**What Exists:**
- **Translation:** `OpenAiTranslator::translate()`
- **TTS Audio:** `ElevenLabsTtsService::generate()`
- **Images:** `ImageGeneratorService::generateVocabularyImage()`
- **Game Creation:** `VocabularyController::createGamesForLesson()`
  - Matching Game (if 2+ words)
  - Flashcard Game (if 1+ words)
  - Spelling Game (if vocabulary exists)

**Current Flow:**
```php
// In VocabularyController::processCsv()
foreach ($vocabulary as $word) {
    // 1. Create vocabulary record
    $vocab = Vocabulary::create([...]);
    
    // 2. Auto-translate
    $translations = $translator->translate($word);
    $vocab->update($translations);
    
    // 3. Auto-generate audio
    $audioPath = $ttsService->generate($word);
    $vocab->update(['word_audio_path' => $audioPath]);
    
    // 4. Auto-generate image (if enabled)
    if ($imageGenerator->enabled()) {
        $imagePath = $imageGenerator->generateVocabularyImage($word);
        $vocab->update(['image_path' => $imagePath]);
    }
}

// 5. Auto-create games
$this->createGamesForLesson($lesson);
```

**Reusability:** **100%** - Can be called directly from PDF import command

**No Changes Needed** ✅

---

### ⚠️ 3. Publish Workflow (Option 2)

**Status:** ⚠️ PARTIALLY EXISTS (field exists, workflow doesn't)

**What Exists:**
- `status` field on lessons (used for internal tracking)
- `is_active` boolean (basic visibility control)

**What's Missing:**
- `publish_status` field (draft → ready_for_review → published)
- State transition methods
- UI for status changes
- Student visibility filtering by publish_status

**Estimated Effort:** ~300 LOC (from publish status analysis)

**Required Changes:**
1. Migration: Add `publish_status` field
2. Model: Add scopes and transition methods
3. Controller: Filter by publish_status for students
4. UI: Add status dropdown and buttons

---

### ❌ 4. Pedagogy Review

**Status:** ❌ NOT IMPLEMENTED

**What Exists:**
- User roles: `admin` and `teacher`
- Admin authentication
- Lesson edit UI

**What's Missing:**
- Review queue/dashboard for pedagogy team
- "Mark Ready for Review" workflow
- "Approve/Reject" workflow
- Review comments/notes
- Notification system

**Estimated Effort:** ~400-500 LOC

**Required Components:**
1. Review dashboard (`/admin/reviews`)
2. Review queue (lessons with `publish_status = 'ready_for_review'`)
3. Approve/reject actions
4. Review notes/comments
5. Permissions (only pedagogy can publish)

---

### ❌ 5. Restricted Access + Completion Tracking (Binary)

**Status:** ⚠️ PARTIALLY EXISTS

**What Exists:**
- ✅ Activity tracking (`ActivityEvent` model)
  - Tracks activity start/completion
  - Tracks by `session_id` (anonymous)
  - Status: `started`, `completed`
- ✅ Response tracking (`Response` model)
  - Tracks student answers to prompts
  - Tracks by `session_id` (anonymous)
- ✅ Session tracking (`session_id` UUID)
  - Anonymous sessions via cookie
  - No user authentication

**What's Missing:**
- ❌ **Student authentication** (no login system)
- ❌ **Binary lesson completion** (no "lesson completed" flag)
- ❌ **Access control** (lessons are currently public)
- ❌ **User accounts** for students
- ❌ **Completion tracking per student**

**Current State:**
- Lessons are **public** (anyone can access)
- Tracking is **anonymous** (via session_id cookie)
- No way to restrict access to specific students
- No way to track completion per student

**Estimated Effort:** ~800-1000 LOC

**Required Components:**
1. Student authentication system
2. Student user model/table
3. Access control (which students can access which lessons)
4. Binary completion tracking (lesson_completions table)
5. Student dashboard (show completed lessons)
6. Access restrictions middleware

---

### ❌ 6. Final Pedagogy Check

**Status:** ❌ NOT IMPLEMENTED

**What's Missing:**
- Post-publish review process
- Quality checks after students use lessons
- Feedback collection from students
- Analytics review by pedagogy team
- Lesson improvement workflow

**Estimated Effort:** ~300-400 LOC

**Required Components:**
1. Post-publish review dashboard
2. Student feedback collection
3. Analytics review interface
4. Lesson improvement workflow
5. Version control (if lessons need updates)

---

## Complete Workflow Implementation Plan

### Phase 1: PDF Ingestion + Enrichment (Week 1-2)

**Goal:** Import PDFs and auto-create enriched lessons

**Tasks:**
1. ✅ Build PDF parser service
2. ✅ Build structured data extractor
3. ✅ Build PDF import command
4. ✅ Integrate with existing enrichment pipeline
5. ✅ Test with sample PDFs

**Deliverables:**
- `app/Services/PdfImport/PdfParserService.php`
- `app/Services/PdfImport/StructuredDataExtractor.php`
- `app/Console/Commands/ImportPdfLessons.php`
- Database migration for `definition` and `example_sentence`

**Estimated Effort:** ~600-700 LOC

---

### Phase 2: Publish Workflow (Week 2-3)

**Goal:** Implement Draft → Ready for Review → Published workflow

**Tasks:**
1. ✅ Add `publish_status` field to lessons
2. ✅ Add model methods (scopes, transitions)
3. ✅ Update student controller (filter by published)
4. ✅ Add UI for status changes
5. ✅ Add "Mark Ready for Review" button

**Deliverables:**
- Migration: `add_publish_status_to_lessons_table.php`
- Model updates: `Lesson::published()`, `markReadyForReview()`, `publish()`
- Controller updates: Filter published lessons
- UI: Status dropdown and buttons

**Estimated Effort:** ~300 LOC

---

### Phase 3: Pedagogy Review (Week 3-4)

**Goal:** Enable pedagogy team to review and approve lessons

**Tasks:**
1. ✅ Create review dashboard
2. ✅ Show lessons ready for review
3. ✅ Add approve/reject actions
4. ✅ Add review notes/comments
5. ✅ Add permissions (only pedagogy can publish)

**Deliverables:**
- `app/Http/Controllers/Admin/ReviewController.php`
- Review dashboard view
- Approve/reject workflow
- Review notes system

**Estimated Effort:** ~400-500 LOC

---

### Phase 4: Restricted Access + Completion Tracking (Week 4-6)

**Goal:** Add student authentication and binary completion tracking

**Tasks:**
1. ✅ Create student authentication system
2. ✅ Create student user model/table
3. ✅ Add access control (which students see which lessons)
4. ✅ Add binary completion tracking
5. ✅ Create student dashboard
6. ✅ Add completion status display

**Deliverables:**
- Student authentication system
- `app/Models/Student.php` (or extend User model)
- `app/Http/Controllers/StudentAuthController.php`
- `database/migrations/create_lesson_completions_table.php`
- Student dashboard with completion status
- Access control middleware

**Estimated Effort:** ~800-1000 LOC

**Key Decisions:**
- **Option A:** Separate `students` table
- **Option B:** Extend `users` table with `role = 'student'`
- **Recommendation:** Option B (simpler, reuse existing auth)

**Access Control Options:**
- **Option A:** Course-level access (student enrolled in course)
- **Option B:** Lesson-level access (explicit lesson assignments)
- **Option C:** Grade-level access (student sees all lessons for their grade)
- **Recommendation:** Start with Option C (simplest), add Option A/B later

---

### Phase 5: Final Pedagogy Check (Week 6-7)

**Goal:** Post-publish review and quality checks

**Tasks:**
1. ✅ Create post-publish review dashboard
2. ✅ Add student feedback collection
3. ✅ Add analytics review interface
4. ✅ Add lesson improvement workflow
5. ✅ Add quality metrics

**Deliverables:**
- Post-publish review dashboard
- Student feedback system
- Analytics review interface
- Lesson improvement workflow

**Estimated Effort:** ~300-400 LOC

---

## Complete Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    PDF Import Command                       │
│              ImportPdfLessons::handle()                    │
└───────────────────────┬───────────────────────────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   PDF Parser                  │
        │   → Extract Units/Groups       │
        │   → Extract Vocabulary Tables  │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   For each Word Group:        │
        │   1. Create/Update Course     │
        │   2. Create/Update Lesson     │
        │   3. Create Vocabulary        │
        │      (with definitions/       │
        │       example sentences)       │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Auto-Enrichment Pipeline    │
        │   (REUSE EXISTING)            │
        │   - Translation                │
        │   - TTS Audio                 │
        │   - Images                    │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Auto-Create Games           │
        │   (REUSE EXISTING)            │
        │   - Matching                  │
        │   - Flashcard                 │
        │   - Spelling                  │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Set publish_status =        │
        │   'draft'                      │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Content Creator:            │
        │   Mark Ready for Review       │
        │   → publish_status =           │
        │     'ready_for_review'         │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Pedagogy Review Dashboard   │
        │   - Review lessons            │
        │   - Add notes                 │
        │   - Approve/Reject            │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Pedagogy: Approve           │
        │   → publish_status =          │
        │     'published'                │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Student Access Control      │
        │   - Authentication            │
        │   - Course/Lesson access      │
        │   - Completion tracking       │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Students Use Lessons        │
        │   - Track completion          │
        │   - Collect feedback          │
        └───────────────┬───────────────┘
                        │
                        ▼
        ┌───────────────────────────────┐
        │   Final Pedagogy Check        │
        │   - Review analytics          │
        │   - Review feedback           │
        │   - Make improvements        │
        └───────────────────────────────┘
```

---

## Code Estimate Summary

| Phase | Component | LOC | Status |
|-------|-----------|-----|--------|
| **Phase 1** | PDF Ingestion | 600-700 | ❌ Not Built |
| **Phase 2** | Publish Workflow | 300 | ⚠️ Partial |
| **Phase 3** | Pedagogy Review | 400-500 | ❌ Not Built |
| **Phase 4** | Access Control + Completion | 800-1000 | ⚠️ Partial |
| **Phase 5** | Final Pedagogy Check | 300-400 | ❌ Not Built |
| **Total** | | **2,400-2,900 LOC** | |

**Reused Code:** ~1,500 LOC (enrichment, games, etc.)

**Total New Code:** ~2,400-2,900 LOC  
**Total Reused Code:** ~1,500 LOC  
**Reuse Ratio:** ~35% of functionality already exists

---

## Critical Decisions Needed

### 1. Student Authentication

**Question:** How do students authenticate?

**Options:**
- **A:** Simple username/password (students create accounts)
- **B:** Teacher assigns accounts (bulk import)
- **C:** Single sign-on (Google Classroom, etc.)
- **D:** Anonymous with optional accounts

**Recommendation:** Start with Option B (teacher assigns), add Option C later

---

### 2. Access Control Model

**Question:** How do we control which students see which lessons?

**Options:**
- **A:** Course enrollment (student enrolled in course → sees all lessons)
- **B:** Lesson assignments (explicit lesson → student mapping)
- **C:** Grade-level access (student sees all lessons for their grade)
- **D:** Open access (all published lessons visible to all students)

**Recommendation:** Start with Option C (simplest), add Option A later

---

### 3. Completion Tracking Granularity

**Question:** What counts as "completed"?

**Options:**
- **A:** Binary per lesson (student completed lesson = true/false)
- **B:** Per activity (student completed each activity)
- **C:** Per vocabulary word (student practiced each word)
- **D:** Score-based (student achieved X% score)

**Recommendation:** Start with Option A (binary per lesson), add Option B later

---

### 4. Review Workflow

**Question:** What happens when pedagogy rejects a lesson?

**Options:**
- **A:** Reject → back to draft (content creator fixes)
- **B:** Reject → add comments → content creator fixes → resubmit
- **C:** Reject → archive lesson
- **D:** Reject → request changes → content creator updates → auto-resubmit

**Recommendation:** Option B (reject with comments, resubmit workflow)

---

## Implementation Priority

### Must Have (MVP)
1. ✅ PDF ingestion + enrichment
2. ✅ Publish workflow (draft → ready_for_review → published)
3. ✅ Basic pedagogy review (approve/reject)
4. ✅ Student authentication (simple)
5. ✅ Binary completion tracking

### Nice to Have (Phase 2)
1. ⚠️ Advanced access control (course enrollment)
2. ⚠️ Student feedback collection
3. ⚠️ Post-publish analytics review
4. ⚠️ Lesson improvement workflow

### Future Enhancements
1. 🔮 Single sign-on integration
2. 🔮 Advanced completion tracking (per activity)
3. 🔮 Version control for lessons
4. 🔮 Automated quality checks

---

## Summary

### ✅ What Works Well

1. **Enrichment Pipeline** - Fully reusable (translation, TTS, images)
2. **Game Creation** - Fully reusable (matching, flashcard, spelling)
3. **Course Container** - Already exists
4. **Activity Tracking** - Exists (anonymous sessions)

### ⚠️ What Needs Work

1. **PDF Ingestion** - Need to build (~600-700 LOC)
2. **Publish Workflow** - Need to build (~300 LOC)
3. **Pedagogy Review** - Need to build (~400-500 LOC)
4. **Student Authentication** - Need to build (~400-500 LOC)
5. **Access Control** - Need to build (~200-300 LOC)
6. **Completion Tracking** - Need to build (~200-300 LOC)
7. **Final Pedagogy Check** - Need to build (~300-400 LOC)

### 🎯 Overall Assessment

**Your workflow is achievable and well-designed!**

- ✅ Reuses ~35% of existing code (enrichment, games)
- ✅ Clear separation of concerns
- ✅ Logical workflow progression
- ⚠️ Requires ~2,400-2,900 LOC of new code
- ⚠️ Estimated timeline: 6-7 weeks

**Recommendation:** Start with MVP (Phases 1-4), add Phase 5 later based on feedback.
