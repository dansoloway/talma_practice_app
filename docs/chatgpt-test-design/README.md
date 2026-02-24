# ChatGPT Test Design Context

This folder contains source files and documentation copied for use in prompts to ChatGPT (or similar) when asking it to design automated tests for the TALMA Practice Pal app.

## Contents

| File | Description |
|------|-------------|
| `routes-web.php` | Route definitions – public vs admin, middleware used |
| `config-auth.php` | Auth guards (web, admin), providers |
| `AdminLoginController.php` | Login, logout, rate limiting, session handling |
| `EnsureAdminAccess.php` | Middleware: active + canAccessAdmin |
| `EnsureUserIsAdmin.php` | Middleware: role === 'admin' |
| `User.php` | User model – roles, is_active, canAccessAdmin |
| `bootstrap-app-auth-excerpt.php` | Auth exception redirect, middleware aliases |
| `USER_ROLE_SYSTEM.md` | Role and schema overview |

## Test Focus Areas

1. **Admin authentication** – login with `Auth::guard('admin')`, logout, is_active in credentials, session invalidation
2. **Middleware** – auth:admin, EnsureAdminAccess, EnsureUserIsAdmin
3. **Route protection** – unauthenticated → /admin/login; /admin/users → 403 for teachers
4. **User model** – is_active, canAccessAdmin(), role checks

## Tech Stack

- Laravel 12, PHP 8.2
- PHPUnit (in composer.json; no tests/ or phpunit.xml yet)
- Session auth with dedicated `admin` guard
