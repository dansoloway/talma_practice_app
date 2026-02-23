# Organization, Course & Class Access Design

This document describes the flexible access model for organizations, courses, classes, and roles. It extends the existing [USER_ROLE_SYSTEM.md](USER_ROLE_SYSTEM.md).

## Design Decisions (Confirmed)

1. **Users** can be in **multiple organizations**
2. **Courses** are a shared catalog — many organizations can access the same course
3. **Roles**: Global (admin, teacher) + per-org (org_admin, teacher, student)
4. **Classes**: Teachers create content for specific classes; classes restrict which courses/content students see
5. **Auditing**: Track who granted/revoked access and when
6. **class_teacher**: Yes — teachers are assigned to specific classes they teach
7. **Org access mode**: Organizations can be **open** (fully public — no sign-in required to view) or **restricted** (must sign in; students see only courses assigned to their class)
8. **Course creation**: Global admins create courses anywhere; org_admin/teacher create courses within their org

---

## Entity Hierarchy

```
Organization (school, district, program)
    ├── has many Classes (optional grouping: "7A", "Grade 8 Section 2")
    ├── has many users (via organization_user)
    └── has access to many courses (via organization_course)

User
    ├── global role: admin | teacher (for admin dashboard)
    ├── belongs to many organizations (via organization_user, with per-org role)
    └── optionally belongs to classes (for students)

Course
    ├── shared catalog — not owned by a single org
    └── accessible by many organizations (via organization_course)

Class (within an organization)
    ├── belongs to organization
    ├── has many students (via class_user)
    ├── has many teachers (via class_teacher)
    └── has access to specific courses (via class_course) — "this class sees these courses"

Lesson
    └── belongs to course (unchanged)
```

---

## Roles

### Global Roles (on `users` table)
| Role | Scope | Purpose |
|------|-------|---------|
| `admin` | System-wide | Full access, user management, all orgs |
| `teacher` | System-wide | Can access admin dashboard; content creation; scoped by org/class when implemented |

### Per-Organization Roles (on `organization_user` pivot)
| Role | Purpose |
|------|---------|
| `org_admin` | Manages org: add/remove users, assign courses to org, manage classes |
| `teacher` | Creates content; assigns courses to classes; manages students in their classes |
| `student` | Views courses assigned to their class(es) |

A user can be `teacher` globally (admin access) and `org_admin` in Org A, `teacher` in Org B, `student` in Org C.

---

## Database Schema

### New Tables

```sql
-- Organizations
organizations
    id, name, slug, description, is_active
    access_mode (enum: 'open'|'restricted') -- open = public, no login required; restricted = must be signed in + class assignment
    created_at, updated_at

-- User ↔ Organization (many-to-many, with per-org role)
organization_user
    id, organization_id, user_id, role (org_admin|teacher|student)
    created_at, updated_at
    -- Optional: created_by (audit)

-- Org ↔ Course (which orgs can use which courses)
organization_course
    id, organization_id, course_id
    created_at, updated_at
    -- Optional: assigned_by (audit)
    unique(organization_id, course_id)

-- Classes (grouping within org; e.g. "7A", "Grade 8")
classes
    id, organization_id, name, slug, description, is_active
    created_at, updated_at

-- Student ↔ Class (which students are in which classes)
class_user
    id, class_id, user_id
    created_at, updated_at
    unique(class_id, user_id)

-- Class ↔ Course (which courses this class sees — teacher assigns)
class_course
    id, class_id, course_id
    created_at, updated_at
    -- Optional: assigned_by (audit)
    unique(class_id, course_id)

-- Teacher ↔ Class (which teachers teach which classes)
class_teacher
    id, class_id, user_id
    created_at, updated_at
    unique(class_id, user_id)
```

### Existing Tables (Changes)

```sql
-- users: add nothing if role stays; or add role enum: admin, teacher, student (global default?)
-- courses: add organization_id (nullable) — who "owns" or created it; global admin creates with null; org_admin/teacher create with their org_id
-- lessons: unchanged
```

---

## Access Logic

### "What courses can user X see?" (Student view)

Depends on **org access_mode**:

**Open org** (`access_mode = 'open'`):
- **No authentication required** — content is fully public
- Anyone (signed in or not) can view all courses in `organization_course` for that org
- Use case: free/open programs where all content is freely available

**Restricted org** (`access_mode = 'restricted'`):
1. User must be **signed in**
2. Get user's classes: `user->classes`
3. For each class, get assigned courses: `class->classCourses->course`
4. Union, dedupe → courses the user can access
5. If user has no classes → sees nothing
- Use case: locked-down schools where teachers control exactly what each class sees

### "What courses can teacher T manage/assign?" (Teacher view)

1. Get orgs where user is `teacher` or `org_admin`: `organization_user` where role in (teacher, org_admin)
2. Get courses available to those orgs: `organization_course` where organization_id in (user's orgs)
3. Teacher can assign those courses to classes they teach (or any class in their org if org_admin)

### "What classes can teacher T manage?"

- If `org_admin` in org: all classes in that org
- If `teacher` in org: only classes they're assigned to via `class_teacher`

### "What can org_admin do?"

- Add/remove users from org (with role)
- Add/remove courses from org (`organization_course`)
- Create/edit/archive classes
- Assign courses to classes (`class_course`)
- Assign students to classes (`class_user`)
- Assign teachers to classes (`class_teacher`)

---

## Auditing

### Pivot Tables with Audit Fields

```sql
organization_user
    created_by (user_id, nullable)
    created_at, updated_at

organization_course
    assigned_by (user_id, nullable)
    assigned_at
    created_at, updated_at

class_user
    assigned_by (user_id, nullable)
    assigned_at
    created_at, updated_at

class_course
    assigned_by (user_id, nullable)
    assigned_at
    created_at, updated_at
```

### Audit Log Table (Optional, for full history)

```sql
access_audit_log
    id, action (granted|revoked|changed)
    entity_type (organization_user|organization_course|class_user|class_course)
    entity_id (or organization_id, user_id, etc.)
    performed_by (user_id)
    old_values (json, nullable)
    new_values (json, nullable)
    created_at
```

Use Laravel model events or a dedicated service to write to `access_audit_log` when memberships or assignments change.

---

## Migration Path (From Current State)

### Phase 1: Add Organizations (no access restriction yet)
- Add `organizations`, `organization_user`, `organization_course`
- Create "Default" org, attach all existing courses to it
- Existing users stay as admin/teacher; no org membership required for admin access

### Phase 2: Add Classes
- Add `classes`, `class_user`, `class_course`
- Optionally create a default class per org and assign existing students (if any)

### Phase 3: Enforce Access
- Add middleware: for student routes, check class membership → course access
- Teacher routes: scope by org membership

### Phase 4: Admin UI
- Org CRUD, user-org assignment, course-org assignment
- Class CRUD, student-class assignment, course-class assignment
- Audit log viewer (optional)

---

## Course Creation

| Who | Scope |
|-----|-------|
| Global admin | Create courses anywhere; `organization_id` = null or any org |
| org_admin / teacher | Create courses **within their org** only; `organization_id` = their org |

When org_admin/teacher creates a course, it's automatically added to `organization_course` for their org (or stored via `courses.organization_id`). Lessons stay in courses; courses are assigned to orgs/classes.

---

## Summary

| Concept | Purpose |
|---------|---------|
| **Organization** | School, district, program — top-level tenant |
| **organization_user** | User membership + per-org role (org_admin, teacher, student) |
| **organization_course** | Which orgs can use which courses (shared catalog) |
| **Class** | Group within org (e.g. "7A") — teacher assigns courses to it |
| **class_user** | Which students are in which class |
| **class_course** | Which courses a class can see |
| **class_teacher** | Which teachers teach which classes |
| **org access_mode** | `open` = public, no login; `restricted` = must sign in + class assignment |
| **courses.organization_id** | Creator's org (null = global); org_admin/teacher create within their org |
| **Audit fields** | Who assigned what, when |

This gives you: multi-org, shared course catalog, per-class course assignment, open vs restricted orgs, and room for full auditing.
