# E-Novaz Docker Documentation

## 🐳 Docker Setup untuk Development & Production

### Prerequisites

**Arch Linux:**
```bash
sudo bash install-docker-arch.sh
# Logout dan login kembali untuk apply group changes
```

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install -y docker.io docker-compose
sudo usermod -aG docker $USER
# Logout dan login kembali
```

---

## 🚀 Quick Start

### 1. Clone Repository
```bash
git clone https://github.com/ammardc/enovaz.git
cd enovaz
```

### 2. Setup Environment
```bash
# Copy environment file
cp .env.docker .env

# Edit .env dan sesuaikan:
# - CASAKU_LICENSE_KEY (untuk production)
# - CASAKU_WEBHOOK_SECRET
# - CASAKU_QR_ID
# - GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET (optional)
# - TELEGRAM_URL (optional)
```

### 3. Run Setup Script
```bash
bash docker/setup.sh
```

Setup script akan otomatis:
- Generate APP_KEY
- Generate INVENTORY_KEY
- Start semua containers
- Run database migrations dan seeder
- Build frontend assets

### 4. Akses Aplikasi
- **Web App**: http://localhost:8000
- **MailHog** (Email testing): http://localhost:8025
- **Database**: localhost:3306
  - Database: `enovaz`
  - User: `enovaz`
  - Password: `secret`

---

## 📦 Docker Services

### Containers yang Dijalankan:

| Service | Container Name | Port | Keterangan |
|---------|---------------|------|------------|
| PHP-FPM + Nginx | enovaz-app | - | Main application |
| Nginx Gateway | enovaz-nginx | 8000 | Web server |
| MariaDB 11.2 | enovaz-db | 3306 | Database |
| Redis 7 | enovaz-redis | 6379 | Cache, Queue, Session |
| Queue Worker | enovaz-queue | - | Background jobs |
| Scheduler | enovaz-scheduler | - | Cron jobs |
| MailHog | enovaz-mailhog | 1025, 8025 | Email testing |

---

## 🛠️ Common Commands

### Container Management
```bash
# Start semua containers
docker-compose up -d

# Stop semua containers
docker-compose down

# Stop dan hapus volumes (⚠️ data akan hilang!)
docker-compose down -v

# Restart specific service
docker-compose restart app

# View logs
docker-compose logs -f app
docker-compose logs -f queue
docker-compose logs -f db

# View all logs
docker-compose logs -f
```

### Application Commands
```bash
# Masuk ke container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan queue:work
docker-compose exec app php artisan cache:clear

# Create owner account
docker-compose exec app php artisan store:owner your@email.com

# Run tests
docker-compose exec app php artisan test

# Install new Composer package
docker-compose exec app composer require package/name

# Install new NPM package
docker-compose exec app npm install package-name
docker-compose exec app npm run build
```

### Database Management
```bash
# Access database shell
docker-compose exec db mysql -u enovaz -psecret enovaz

# Backup database
docker-compose exec db mysqldump -u enovaz -psecret enovaz > backup.sql

# Restore database
docker-compose exec -T db mysql -u enovaz -psecret enovaz < backup.sql

# Reset database (⚠️ destructive!)
docker-compose exec app php artisan migrate:fresh --seed
```

---

## 🔧 Development Workflow

### Hot Reload untuk Frontend (Vite)
```bash
# Di terminal pertama, start containers
docker-compose up -d

# Di terminal kedua, run Vite dev server
docker-compose exec app npm run dev

# Akses: http://localhost:8000 (dengan HMR)
```

### Debugging dengan Xdebug (Optional)
Edit `Dockerfile` dan tambahkan:
```dockerfile
RUN pecl install xdebug && docker-php-ext-enable xdebug
```

Tambahkan ke `docker-compose.yml` di service `app`:
```yaml
environment:
  - XDEBUG_MODE=debug
  - XDEBUG_CONFIG=client_host=host.docker.internal
```

---

## 🚢 Production Deployment

### 1. Update Environment Variables
```bash
cp .env.docker .env.production
# Edit .env.production:
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
PAYMENTS_ENABLED=true
CASAKU_LIVE_VERIFIED=true
# ... dst
```

### 2. Build Production Image
```bash
docker build -t enovaz:production .
```

### 3. Run dengan Production Settings
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

### 4. Optimizations
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan event:cache
```

### 5. Setup Backup Automation
```bash
# Tambahkan ke crontab host:
0 2 * * * docker-compose exec -T db mysqldump -u enovaz -p$DB_PASSWORD enovaz | gzip > /backup/enovaz-$(date +\%F).sql.gz
```

---

## 🔐 Security Checklist untuk Production

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `DB_PASSWORD` dan `DB_ROOT_PASSWORD`
- [ ] Generate strong `INVENTORY_KEY`
- [ ] Setup `CASAKU_WEBHOOK_SECRET`
- [ ] Configure SSL/TLS (reverse proxy seperti Traefik/Nginx)
- [ ] Limit exposed ports (jangan expose 3306, 6379 ke public)
- [ ] Enable firewall (ufw/iptables)
- [ ] Setup monitoring (Sentry, Grafana)
- [ ] Regular backups
- [ ] Update containers regularly

---

## 🐛 Troubleshooting

### Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 755 /var/www/html/storage
```

### Database Connection Error
```bash
# Check if database is ready
docker-compose exec db mysql -u enovaz -psecret -e "SELECT 1;"

# Restart database
docker-compose restart db

# Wait longer and retry migration
sleep 20 && docker-compose exec app php artisan migrate
```

### Port Already in Use
```bash
# Check what's using port 8000
sudo lsof -i :8000

# Kill process or change port in docker-compose.yml:
# ports:
#   - "8001:80"  # Change 8000 to 8001
```

### Queue Not Processing
```bash
# Check queue worker logs
docker-compose logs -f queue

# Restart queue worker
docker-compose restart queue

# Manually process queue
docker-compose exec app php artisan queue:work --once
```

### Assets Not Loading
```bash
# Rebuild assets
docker-compose exec app npm run build

# Clear cache
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
```

---

## 📚 Additional Resources

- **Laravel 12 Docs**: https://laravel.com/docs/12.x
- **Docker Docs**: https://docs.docker.com
- **MariaDB Docs**: https://mariadb.com/kb/en/documentation/
- **Redis Docs**: https://redis.io/documentation
- **Casaku API**: https://casaku.id/docs

---

## 🤝 Contributing

### Git Workflow dengan Docker
```bash
# Pull latest changes
git pull origin main

# Rebuild containers if Dockerfile changed
docker-compose build

# Restart dengan new image
docker-compose up -d

# Run new migrations
docker-compose exec app php artisan migrate

# Run tests before commit
docker-compose exec app php artisan test
```

### Pre-commit Checklist
- [ ] Code passes tests: `docker-compose exec app php artisan test`
- [ ] Code formatted: `docker-compose exec app ./vendor/bin/pint`
- [ ] No secrets in .env committed
- [ ] Frontend builds: `docker-compose exec app npm run build`
- [ ] Migration runs clean on fresh database

---

## 📞 Support

Untuk pertanyaan atau issues:
1. Check logs: `docker-compose logs -f`
2. Check GitHub Issues: https://github.com/ammardc/enovaz/issues
3. Contact team via Telegram (jika sudah dikonfigurasi)

---

**Last Updated**: 2026-11-05
**Version**: 1.0.0
