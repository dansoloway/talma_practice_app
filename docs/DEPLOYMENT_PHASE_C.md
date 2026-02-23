# Deploying Phase C (Org-Scoped Admin) to Production

This guide covers deploying the Phase C changes (org-scoped admin URLs, content export) without breaking existing production.

## What Changes (Summary)

- **New tables**: `organizations`, `organization_user`, `organization_course` (additive only; no changes to `courses`, `lessons`, etc.)
- **New behavior**: When logged-in admins visit `/admin`, they’re redirected to org selection or last-used org
- **New URLs**: `/o/{org}/admin/*` for org-scoped admin (analytics, courses)
- **Legacy URLs**: Existing `/admin/*` routes still work (courses, lessons, etc.)
- **Student experience**: No changes to public/student routes

## Pre-Deploy Checklist

- [ ] **1. Content backup (required)**  
  From production, run:
  ```bash
  php artisan content:export --output=/path/to/safe/backup/content-pre-phase-c-$(date +%Y%m%d).json
  ```
  Move the file somewhere safe (outside the deploy directory).

- [ ] **2. Database backup**  
  Take a standard DB backup (e.g. `mysqldump` or your provider’s backup) before migrations.

- [ ] **3. Maintenance window**  
  Decide if you want `php artisan down` during deploy; it’s recommended for production.

## Deployment Steps

```bash
# 1. Put site in maintenance mode (optional but recommended)
php artisan down --retry=60

# 2. Deploy code (your usual process: git pull, composer install, etc.)
git pull origin main
composer install --no-dev --optimize-autoloader

# 3. Run migrations (creates new tables only)
php artisan migrate --force

# 4. Seed the Default org and attach existing courses/users
php artisan db:seed --class=OrganizationSeeder

# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Bring site back up
php artisan up
```

## Post-Deploy Verification

- [ ] Visit `/admin` as an admin → should redirect to org selection or `/o/default/admin`
- [ ] Visit `/o/default/admin/courses` → should list existing courses
- [ ] Compare counts: `content:export` row counts vs. DB counts
- [ ] Student routes (`/`, `/lessons`, `/courses/{slug}`) behave as before

## Rollback (If Needed)

1. **Revert code** to the previous commit.
2. **Migrations**: Phase C migrations only add tables. Rolling them back will drop `organization_course`, `organization_user`, `organizations`. That does **not** delete courses or lessons.
3. **Content**: Use the pre-deploy `content:export` JSON to verify or restore data if anything looks wrong.

## Why This Is Safe

- **Migrations**: Only add new tables; no changes to `courses`, `lessons`, `vocabulary`, etc.
- **OrganizationSeeder**: Adds records only (Default org + pivot rows); does not modify existing data.
- **Routing**: Legacy `/admin/courses`, `/admin/lessons`, etc. remain; only `/admin` (entry) and org-scoped routes are new or changed.
- **Backward compatibility**: Admins who bookmark `/admin/courses` or other legacy routes still reach them. New flow via `/o/default/admin` is additive.
