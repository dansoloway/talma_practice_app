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

## Other Notes
- Admin menu visibility was simplified to always show on student pages for easier access
- First-time admin login: Default credentials are set via `UserSeeder` (see `ADMIN_EMAIL` and `ADMIN_PASSWORD` in `.env`)
