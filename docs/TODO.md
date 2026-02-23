# TODO / Future Improvements

## Admin Menu

### Current Implementation
- Admin menu is always visible on all student-facing pages (whether logged in or not)
- Menu includes: Analytics dropdown, Lessons dropdown, Users (admin only), AI Costs, Login/Logout
- Links redirect to login page if not authenticated, or to admin pages if logged in
- Mobile menu includes all admin links

### Future Considerations
- [ ] Consider adding visual indicator when not logged in (e.g., different styling)
- [ ] Consider adding tooltip/hint explaining that clicking will prompt login
- [ ] Consider adding "Student View" toggle to switch between student and admin views
- [ ] Consider adding breadcrumb navigation for admin pages
- [ ] Consider adding quick access to frequently used admin functions

## Deployment

### Auto-Deployment Options (Not Implemented)
- [ ] Set up GitHub Actions for automatic deployment on push
- [ ] Set up Laravel Forge for automatic deployment
- [ ] Set up webhook-based deployment script
- [ ] Set up git post-receive hook on production server

Current deployment is manual: `git pull` on production server.

## Organization & Course Migration (Phase C)

- [ ] **Investigate possible lost content**: After implementing org-scoped admin, some existing content may not appear in the Default org. Verify `OrganizationSeeder` has been run and that all courses/lessons are correctly attached. Audit what content existed pre-migration vs. what appears in `/o/default/admin/courses` now. If gaps are found, create a migration or one-off script to recover/reattach content.

### Production deployment

**Deploying Phase C safely**: See [DEPLOYMENT_PHASE_C.md](DEPLOYMENT_PHASE_C.md) for a full checklist (backup, migrate, seed, verify).

### Production upgrade backup

**Before running migrations or upgrades on production**, export all content as a safety backup:

```bash
php artisan content:export
```

This writes a timestamped JSON file to `storage/app/content-export-{date}.json` containing courses, lessons, vocabulary, games (matching, flashcard, spelling, sentence builder, true/false, clause), prompts, options, grammar sets, and pivot tables. Use `--output=/path/to/backup.json` to specify a custom path.

## Other Notes
- Admin menu visibility was simplified to always show on student pages for easier access
- First-time admin login: Default credentials are set via `UserSeeder` (see `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`)
