# 🚀 QUICK SETUP GUIDE - E-NOVAZ ADMIN

Panduan cepat untuk setup E-Novaz dengan kredensial admin Anda sendiri.

---

## 📋 STEP-BY-STEP SETUP

### Step 1: Clone Repository
```bash
git clone https://github.com/ammardc/enovaz.git
cd enovaz
```

### Step 2: Copy Environment File
```bash
cp .env.docker .env
```

### Step 3: **CUSTOMIZE ADMIN CREDENTIALS** ⚠️
Edit file `.env` dan ubah kredensial admin sesuai keinginan:

```env
# Admin Account Configuration
ADMIN_NAME="Nama Anda"
ADMIN_USERNAME=username_anda
ADMIN_EMAIL=email@anda.com
ADMIN_PASSWORD=PasswordKuat@123
```

**IMPORTANT:** 
- Password harus minimal 16 karakter
- Harus mengandung: huruf besar, huruf kecil, angka, dan karakter spesial
- Contoh password kuat: `MyVerySecure@Pass2026`

### Step 4: Start Docker Containers
```bash
docker-compose up -d
```

**Wait for all containers to start:**
```
✓ enovaz-app (PHP 8.2-FPM)
✓ enovaz-db (MariaDB 11.2)
✓ enovaz-nginx (Nginx)
✓ enovaz-redis (Redis 7)
✓ enovaz-queue (Queue Worker)
✓ enovaz-scheduler (Task Scheduler)
✓ enovaz-mailhog (Email Testing)
```

### Step 5: Run Database Migration
```bash
docker-compose exec app php artisan migrate
```

### Step 6: Create Admin Account (with YOUR credentials!)
```bash
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

**Output akan menampilkan:**
```
✅ Admin account created successfully!

📧 Email: email@anda.com
👤 Username: username_anda
🔑 Password: PasswordKuat@123

⚠️  IMPORTANT: Change the password after first login!
```

### Step 7: Seed Sample Data (Optional)
```bash
docker-compose exec app php artisan db:seed
```

This will create:
- 8 sample products (ChatGPT, Gemini, Claude, etc.)
- 1 test user
- Sample categories

---

## 🔐 LOGIN TO ADMIN PANEL

### URL
```
http://localhost:8000/masuk
```

### Credentials
- **Username/Email:** (yang Anda set di .env)
- **Password:** (yang Anda set di .env)

### After Login
You'll be redirected to: `/admin/dashboard`

---

## 📝 DEFAULT CREDENTIALS (if not customized)

Jika Anda tidak mengubah `.env`, default credentials adalah:

```
Email: admin@enovaz.local
Username: admin
Password: AdminSecure@123456
```

**⚠️ WAJIB GANTI PASSWORD SETELAH LOGIN PERTAMA!**

---

## 🎯 ADMIN FEATURES

### Dashboard (`/admin/dashboard`)
- User statistics
- Recent login activity
- Quick actions

### User Management (`/admin/users`)
- View all users
- Edit user info
- Reset password (manual, no email needed!)
- Lock/Unlock accounts
- Activate/Deactivate users
- Delete users

### Security Features
- Failed login counter (max 5 attempts)
- Auto-lock account for 30 minutes
- Manual unlock by admin
- Password complexity enforcement

---

## 🔄 RESET ADMIN PASSWORD

### Method 1: Via Database Seeder (Re-run)
If you want to reset admin account to .env values:

```bash
# Delete old admin first
docker-compose exec app php artisan tinker
>>> User::where('role', 'admin')->delete();
>>> exit

# Create new admin with .env credentials
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

### Method 2: Via Tinker (Manual)
```bash
docker-compose exec app php artisan tinker
```

```php
$admin = User::where('email', 'admin@enovaz.local')->first();
$admin->password = bcrypt('NewPassword@123');
$admin->save();
echo "Password updated!";
exit
```

---

## 📚 ENVIRONMENT VARIABLES

### Admin Configuration
```env
ADMIN_NAME="Admin E-Novaz"          # Full name displayed in admin panel
ADMIN_USERNAME=admin                # Username for login
ADMIN_EMAIL=admin@enovaz.local      # Email for login
ADMIN_PASSWORD=Admin@123456         # Initial password
```

### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=db                          # Docker service name
DB_PORT=3306
DB_DATABASE=enovaz
DB_USERNAME=enovaz
DB_PASSWORD=secret
```

### Application
```env
APP_NAME="E-Novaz"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

---

## 🛠️ TROUBLESHOOTING

### Admin Seeder Says "Admin already exists"
This means an admin account is already created. To create a new one with different credentials:

```bash
# Option 1: Delete existing admin
docker-compose exec app php artisan tinker
>>> User::where('role', 'admin')->delete();
>>> exit

# Then run seeder again
docker-compose exec app php artisan db:seed --class=AdminSeeder
```

### Forgot Admin Password
Use Method 2 in "Reset Admin Password" section above.

### Can't Login
Check:
1. Email/Username correct (check .env values)
2. Password correct (case sensitive!)
3. Account is active: `is_active = 1`
4. No typos in credentials

### Database Connection Error
```bash
# Check if db container is running
docker-compose ps

# Restart if needed
docker-compose restart db app
```

---

## 🎓 TIPS FOR TEAM SETUP

### For Each Team Member:

1. **Clone the repository**
2. **Copy `.env.docker` to `.env`**
3. **Customize ADMIN_* variables with their own credentials**
4. **Run setup steps 4-6**
5. **Each member gets their own admin account!**

### Centralized Admin (One Admin for Team):
1. Setup by one person
2. Share admin credentials securely (use password manager)
3. All team members use same credentials
4. Change password after sharing

### Multiple Admins:
After initial setup, existing admin can:
1. Login to `/admin/dashboard`
2. Go to `/admin/users`
3. Create new user
4. Edit user → Change role to "admin"

---

## 📞 NEED HELP?

### Documentation
- `ADMIN-SYSTEM.md` - Complete admin system documentation
- `ADMIN-QUICKSTART.md` - Feature overview & use cases
- `DOCKER.md` - Docker configuration details

### Common Commands
```bash
# View logs
docker-compose logs app -f

# Restart containers
docker-compose restart

# Stop containers
docker-compose down

# Start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

---

## ✅ CHECKLIST

- [ ] Cloned repository
- [ ] Copied `.env.docker` to `.env`
- [ ] Customized `ADMIN_*` variables in `.env`
- [ ] Started Docker containers
- [ ] Ran database migrations
- [ ] Created admin account with seeder
- [ ] Tested login at http://localhost:8000/masuk
- [ ] Changed password after first login
- [ ] Explored admin dashboard

---

**Setup Time:** ~5 minutes  
**Status:** ✅ Ready to use!  
**Version:** 1.0.0 (Production Ready)

**Happy coding! 🚀**
