# User & Role Management System

## Roles

### Admin
- Full access to all admin features
- Can manage users (create, edit, delete)
- Role value: `admin`

### Teacher
- Can access admin dashboard
- Can manage lessons and content
- Cannot manage users
- Role value: `teacher`

## Database Schema (users table)

- id, name, email, email_verified_at, password, role (enum: 'admin', 'teacher'), is_active (boolean), remember_token, created_at, updated_at, deleted_at

## Authentication (current - Laravel guard)

- Uses `Auth::guard('admin')` (session driver, users provider)
- Login: email + password + is_active in credentials
- Roles: admin, teacher
- `/admin/users` requires role === 'admin' via admin.only middleware
- Unauthenticated /admin/* redirects to /admin/login (not /login)
