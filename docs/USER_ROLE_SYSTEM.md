# User & Role Management System

This document describes the new database-backed user and role management system.

## Overview

The application now uses a database-backed user system with role-based access control instead of a single admin password stored in `.env`.

## Features

- **Multiple Users**: Support for multiple admin and teacher accounts
- **Role-Based Access**: Two roles - `admin` and `teacher`
- **User Management**: Full CRUD interface for managing users (admin only)
- **Secure Authentication**: Password hashing, rate limiting, CSRF protection
- **Session Management**: Secure sessions with user information

## Roles

### Admin
- Full access to all admin features
- Can manage users (create, edit, delete)
- Can access all lesson management features
- Can view analytics and reports

### Teacher
- Can access admin dashboard
- Can manage lessons and content
- Cannot manage users
- Cannot access user management interface

## Database Schema

### Users Table
```sql
- id (bigint, primary key)
- name (string)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string, hashed)
- role (enum: 'admin', 'teacher')
- is_active (boolean, default: true)
- remember_token (string, nullable)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) - soft deletes
```

## Setup

### 1. Run Migration

```bash
php artisan migrate
```

This creates the `users` table.

### 2. Seed Initial Admin User

```bash
php artisan db:seed --class=UserSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

The seeder creates an admin user based on environment variables:
- `ADMIN_EMAIL` (default: `admin@talma.digital`)
- `ADMIN_PASSWORD` (default: `admin123`)
- `ADMIN_NAME` (default: `Admin User`)

**Important**: Change the default password immediately after first login!

### 3. Environment Variables (Optional)

You can customize the initial admin user:

```env
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=your-secure-password
ADMIN_NAME=Admin User
```

## Usage

### Login

1. Navigate to `/admin`
2. Enter email and password
3. System authenticates against database
4. Session stores user information

### Managing Users (Admin Only)

1. Navigate to `/admin/users`
2. Click "Create User" to add new users
3. Edit users to change roles, passwords, or status
4. Delete users (cannot delete yourself)

### Creating Users

When creating a user:
- **Name**: Full name of the user
- **Email**: Unique email address (used for login)
- **Password**: Minimum 8 characters
- **Role**: Admin or Teacher
- **Active**: Whether the user can log in

### Editing Users

- Can change name, email, role, and active status
- Password is optional - leave blank to keep current password
- If changing password, must provide confirmation

## Security Features

1. **Password Hashing**: All passwords are hashed using bcrypt
2. **Rate Limiting**: 5 login attempts per 15 minutes per IP
3. **CSRF Protection**: All forms protected
4. **Session Security**: HttpOnly, Secure, SameSite cookies
5. **Role Verification**: Middleware checks user role on each request
6. **Soft Deletes**: Users are soft-deleted, not permanently removed

## API Changes

### Authentication Flow

**Old (env-based):**
- Single password in `.env`
- No user management
- No roles

**New (database-based):**
- Multiple users in database
- Email + password authentication
- Role-based access control
- User management interface

### Session Data

After login, session contains:
- `admin_authenticated`: true
- `admin_user_id`: User ID
- `admin_user_name`: User's name
- `admin_user_role`: User's role ('admin' or 'teacher')

## Migration from Old System

If you're migrating from the old `.env`-based system:

1. **Run migration**: `php artisan migrate`
2. **Seed admin user**: `php artisan db:seed --class=UserSeeder`
3. **Login with seeded credentials**
4. **Change password** via user management interface
5. **Remove old env vars** (optional):
   - `ADMIN_PASSWORD`
   - `ADMIN_PASSWORD_HASH`

## Files Created/Modified

### New Files
- `database/migrations/2025_12_28_095627_create_users_table.php`
- `app/Models/User.php`
- `app/Http/Controllers/Admin/UserController.php`
- `database/seeders/UserSeeder.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/create.blade.php`
- `resources/views/admin/users/edit.blade.php`

### Modified Files
- `app/Http/Controllers/Admin/AdminLoginController.php` - Database authentication
- `app/Http/Middleware/AdminAuth.php` - Role verification
- `resources/views/admin/login.blade.php` - Email/password fields
- `resources/views/layouts/admin.blade.php` - User display, Users link
- `routes/web.php` - User management routes
- `database/seeders/DatabaseSeeder.php` - Added UserSeeder
- `public/css/app.css` - Nav user styling

## Troubleshooting

### Can't Login
- Verify user exists: `php artisan tinker` → `User::all()`
- Check user is active: `User::find(1)->is_active`
- Verify password: `Hash::check('password', User::find(1)->password)`

### User Management Not Visible
- Check you're logged in as admin (not teacher)
- Verify session has `admin_user_role` = 'admin'

### Password Reset
If you need to reset a password:
```php
php artisan tinker
$user = User::where('email', 'user@example.com')->first();
$user->password = Hash::make('new-password');
$user->save();
```

## Future Enhancements

Potential future additions:
- Password reset functionality
- Email verification
- Additional roles (e.g., `student`, `moderator`)
- Permission system (granular permissions per role)
- User activity logging
- Two-factor authentication

