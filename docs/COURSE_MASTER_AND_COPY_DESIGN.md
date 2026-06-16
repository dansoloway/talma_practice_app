# Course Root vs Copy Design

## Overview

Two distinct ways to use a course across organizations:

1. **Sync (Root)** – One canonical course in the Root realm; global admins edit it; can be attached to many orgs with different view permissions; no file duplication.

2. **Copy (Branch)** – Full clone of course + all content + duplicate assets; the copy belongs to the target org and can diverge; org admins can edit their copy.

---

## 1. Root Realm

### Concept
The Root organization is a **system-level organization**, not a normal tenant organization. It represents the canonical course ownership at the top of the architecture—the origin of shared content. It is architecturally above tenant organizations.

- Root courses live in this context: they are the canonical, system-wide versions.
- Only **global admins** (role `admin`) can create or edit courses in the Root organization.
- Teachers / org admins cannot edit Root courses.
- Root is hidden from standard organization selection for non-global admins.

### Behavior
- A Root course can be **attached** to any tenant org via `organization_course`.
- **Attachment does not transfer ownership.** The course record remains owned by Root. Tenant organizations receive access via the pivot table only.
- Each org gets its own pivot row: `is_org_wide` can differ (e.g. org-wide in Org A, class-only in Org B).
- No file duplication: all orgs reference the same course and assets.
- Edits to the Root course propagate to all orgs using it.

### Removing from an org
- Detach the course from that org (`organization_course` row).
- Root course and its data stay in the Root organization.

---

## 2. Copy (Branch)

### Concept
- Create a full clone of a course (from Root or from another org).
- Clone: Course → Lessons → Parts → Vocabulary, Prompts, Options → MatchingGames, FlashcardGames, SpellingGames, SentenceBuilderGames, TrueFalseGames, ClauseExercises, etc.
- **Duplicate assets**: copy image files, audio files to new paths (or a new storage subtree).
- The new course is attached only to the **target org** (not to Root).
- Org admins can edit their copy; it can diverge from the original.

### Behavior
- Two separate course records; changes in one do not affect the other.
- Assets are duplicated so the copy is self-contained and can be edited safely.

---

## 3. Data Model

### Organizations
- The Root organization is seeded during system initialization.
- **Only one Root organization may ever exist.**
- Root cannot be deleted.
- Root cannot be converted into a tenant organization.
- Use `organizations.is_root` boolean flag to identify it explicitly.
- Root uses slug `root` (or `__root`).
- Root is hidden from normal org selection for non-global admins.

### Courses
- No changes are required to the courses table for Phase 1.
- Root courses: attached to Root organization (and optionally to tenant orgs).
- Copied courses: attached only to the target org(s), not to Root.

### organization_course
- Already supports per-org `is_org_wide`.
- Root organization rows: the canonical attachment.
- Tenant org rows: "this org can use this course" (sync case).

---

## 4. Admin Flows

### Global admin

**Create Root course**
- In Root organization context: Create Course (same as today, but in Root org).
- Course is stored in Root organization.

**Attach Root course to org (sync)**
- In target org (e.g. We Speak): "Add from Root" → pick a Root course → attach with desired `is_org_wide`.
- Same course, no copy; all orgs share content.

**Remove Root course from org**
- In that org's course list: "Remove from this org" → detach only.

**Copy course to org (branch)**
- In target org: "Copy from Root" (or "Copy from another org") → pick source course → clone course + related data + duplicate assets → attach clone to target org only.

### Org admin / teacher
- Can create org-owned courses (today's behavior when creating in their org).
- Can copy from Root into their org (branch).
- Cannot edit Root courses; can edit their org's copies.

---

## 5. Access Control

| Action                         | Global Admin | Org Admin / Teacher |
|--------------------------------|-------------|----------------------|
| Create/edit course in Root     | ✓           | ✗                    |
| Create course in own org       | ✓           | ✓                    |
| Attach Root course to org      | ✓           | ✓ (own org only?)   |
| Remove Root from org           | ✓           | ✓ (own org only?)   |
| Copy course to org             | ✓           | ✓ (own org only?)   |
| Edit org-owned (copied) course | ✓           | ✓                    |

*Optional:* restrict "Add from Root" / "Copy" to global admins only if you want tighter control.

---

## 6. Asset Duplication (Copy Only)

### Storage structure
- **Root-owned assets:** `storage/root/courses/{course_id}/...`
- **Tenant-owned copied assets:** `storage/orgs/{org_id}/courses/{course_id}/...`

Asset storage mirrors data ownership boundaries. This enables safe org deletion, prevents cross-org asset leakage, and makes long-term S3 or storage policy isolation cleaner.

### Copy operations (Root → Org copy)
- **Images**: Copy `vocabulary.image_path`, `options.image_path`, etc. to tenant-owned paths.
- **Audio**: Copy `vocabulary.word_audio_path`, `options.sentence_audio_path`, prompt audio, etc.
- Update all cloned records to point at the new paths.

### Sync (attach Root course)
- No duplication; all orgs use the same file paths (root storage).

---

## 7. Implementation Phases

### Phase 1: Root organization + Share
- Create Root organization (migration or seeder during system init).
- Restrict editing of Root organization courses to global admins.
- Add "Add from Root" to org course management: list Root courses, attach chosen one to current org.
- Add "Remove from this org" to detach.

### Phase 2: Copy
- Implement `CourseClone` (or similar) service: clone course and all relations.
- Implement asset duplication for Root → Org copy.
- Add "Copy from Root" (and optionally "Copy from another org") to org course management.
- Attach clone only to target org.

---

## 8. Open Questions

1. **Root visibility** – Should Root be treated as completely invisible to non-global admins, or visible but read-only?
2. **Migrating existing default courses** – Should current TALMA Community Resources courses be moved into Root and then re-attached to default?
3. **Copy source** – Allow copy from any org's course, or only from Root?

---

## 9. Recommendations

**Source tracking:** Add `courses.source_course_id` (nullable FK to `courses.id`) for copied courses to preserve lineage. This enables attribution, analytics, divergence tracking, and potential future re-sync capabilities. Optional for Phase 1/2.
