# Admin User Management System

## Overview
Sistem admin role-based untuk E-Novaz yang memungkinkan admin mengelola users tanpa akses root database.

## Features

### 1. Admin Dashboard
- **URL:** `/admin/dashboard`
- Statistics overview (total users, active users, admin count, locked accounts)
- Recent login activity
- Quick actions (manage users, view locked accounts, view inactive users)

### 2. User Management
- **URL:** `/admin/users`
- List all users with pagination
- Filter by status (active, inactive, locked, admin)
- View user details
- Edit user information
- Delete users (with protection for own account and last admin)

### 3. Password Reset (Manual by Admin)
- Admin can reset user password without email
- Password requirements enforced:
  - Minimum 8 characters
  - Uppercase & lowercase letters
  - Numbers
  - Special characters

### 4. Account Security
- **Failed Login Attempts:** Max 5 attempts
- **Auto-Lock:** Account locked for 30 minutes after 5 failed attempts
- **Admin Unlock:** Admin can manually unlock accounts
- **Account Status:** Admin can activate/deactivate accounts

### 5. Role-Based Access
- **User Role:** Regular customer access
- **Admin Role:** Full user management access
- Middleware protection for admin routes

## Installation & Setup

### 1. Run Migration
```bash
docker-compose exec app php artisan migrate
```

This will add these columns to `users` table:
- `role` (enum: 'user', 'admin')
- `is_active` (boolean)
- `last_login_at` (timestamp)
- `failed_login_attempts` (integer)
- `locked_until` (timestamp)

### 2. Create First Admin Account
```bash
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

**Default Admin Credentials:**
- Email: `admin@enovaz.local`
- Password: `Admin@123456`

⚠️ **IMPORTANT:** Change password after first login!

### 3. Login as Admin
1. Go to: `http://localhost:8000/masuk`
2. Login with admin credentials
3. You'll be redirected to `/admin/dashboard`

## Routes

### Admin Dashboard & User Management
```
GET  /admin/dashboard                      - Admin dashboard
GET  /admin/users                          - List all users
GET  /admin/users/{user}                   - View user details
GET  /admin/users/{user}/edit              - Edit user form
PUT  /admin/users/{user}                   - Update user
POST /admin/users/{user}/reset-password    - Reset user password
POST /admin/users/{user}/unlock            - Unlock user account
POST /admin/users/{user}/toggle-active     - Toggle active status
DELETE /admin/users/{user}                 - Delete user
```

### Middleware
- `admin` - Check if user has admin role
- `auth` - Check if user is authenticated

## User Model Methods

### Security Methods
```php
$user->isAdmin()              // Check if user is admin
$user->isLocked()             // Check if account is locked
$user->incrementFailedAttempts()  // Increment failed login count
$user->resetFailedAttempts()     // Reset counter on successful login
$user->unlock()               // Manually unlock account (admin only)
```

## Admin Use Cases

### 1. User Lupa Password
**Problem:** User lupa password dan tidak bisa akses email.

**Solution:**
1. Admin login ke dashboard
2. Cari user di `/admin/users`
3. Klik "View" pada user tersebut
4. Klik "Reset Password"
5. Input password baru (min 8 char, kompleks)
6. Informasikan password baru ke user

### 2. Akun Mencurigakan
**Problem:** Ada akun yang melakukan aktivitas mencurigakan.

**Solution:**
1. Admin login ke dashboard
2. Cari user di `/admin/users`
3. Klik "View" pada user tersebut
4. Klik "Deactivate User" untuk nonaktifkan sementara
5. Atau klik "Delete User" untuk hapus permanen

### 3. Akun Terkunci (Brute Force)
**Problem:** User salah password 5x dan akun terkunci.

**Solution:**
1. Admin login ke dashboard
2. Lihat "Locked Users" di stats
3. Atau filter di `/admin/users?filter=locked`
4. Klik "View" pada user yang terkunci
5. Klik "Unlock Account"

## Security Features

### 1. Password Policy
- Minimum 8 characters (12 recommended)
- Must contain uppercase letters
- Must contain lowercase letters
- Must contain numbers
- Must contain special characters

### 2. Account Locking
- Auto-lock after 5 failed login attempts
- Lock duration: 30 minutes
- Admin can manually unlock
- Failed attempts counter displayed in user list

### 3. Admin Protection
- Cannot delete own account
- Cannot deactivate own account
- Cannot delete last admin account
- All admin actions are protected by middleware

### 4. Login Redirect
- **Admin users:** Redirected to `/admin/dashboard`
- **Regular users:** Redirected to `/shop` (home)

## Views

### Layout
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/show.blade.php`
- `resources/views/admin/users/edit.blade.php`

### Components
- Stats cards (total users, active, admin, locked)
- User table with filters
- User detail card with actions
- Reset password form (inline)

## Database Schema

### Migration: `2026_11_05_000002_add_role_and_status_to_users.php`

```sql
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN failed_login_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN locked_until TIMESTAMP NULL;
```

## Testing Checklist

- [ ] Admin can login and see dashboard
- [ ] Admin can view all users
- [ ] Admin can edit user information
- [ ] Admin can reset user password
- [ ] Admin can unlock locked account
- [ ] Admin can activate/deactivate user
- [ ] Admin can delete user
- [ ] Admin cannot delete own account
- [ ] Admin cannot delete last admin
- [ ] Failed login attempts increment correctly
- [ ] Account locks after 5 failed attempts
- [ ] Account unlocks after 30 minutes
- [ ] Inactive users cannot login
- [ ] Admin redirects to dashboard after login
- [ ] Regular users redirect to shop after login

## Future Enhancements

1. **Activity Logs:** Track all admin actions
2. **2FA for Admin:** Two-factor authentication
3. **Bulk Actions:** Delete/activate multiple users
4. **Export Users:** Export user list to CSV/Excel
5. **Email Notifications:** Notify users when password is reset
6. **Role Permissions:** Fine-grained permissions per admin
7. **Session Management:** View and revoke active sessions
8. **IP Whitelist:** Restrict admin access by IP

## Support

For issues or questions, contact the development team or refer to Laravel documentation:
- https://laravel.com/docs/authentication
- https://laravel.com/docs/authorization

---

**Version:** 1.0.0  
**Last Updated:** 2026-11-05  
**Author:** Ahyar Nur Ichwan
