# 🐳 Enovaz Docker Setup - Quick Start Guide

## ✅ Installation Complete

### Installed Components
- ✅ Docker 29.9.2
- ✅ Docker Compose 5.7.0
- ✅ Docker service enabled & running
- ✅ User added to docker group

---

## 🚀 Usage Commands

### Start Containers
```bash
cd ~/Documents/enovaz-docker
docker-compose up -d
```

### Stop Containers
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f db
```

### Check Container Status
```bash
docker-compose ps
```

### Rebuild Containers
```bash
docker-compose up -d --build
```

---

## 🔧 Laravel Artisan Commands

### Generate APP_KEY
```bash
docker-compose exec app php artisan key:generate
```

### Run Migrations
```bash
docker-compose exec app php artisan migrate
```

### Seed Database
```bash
docker-compose exec app php artisan db:seed
```

### Clear Cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear
```

### Install Composer Dependencies
```bash
docker-compose exec app composer install
```

### Install NPM Dependencies & Build Assets
```bash
docker-compose exec app npm install
docker-compose exec app npm run build
```

---

## 🌐 Access Points

| Service | URL | Description |
|---------|-----|-------------|
| **Web App** | http://localhost:8000 | Main Laravel application |
| **MailHog** | http://localhost:8025 | Email testing UI |
| **Database** | localhost:3306 | MariaDB (user: enovaz, pass: secret) |
| **Redis** | localhost:6379 | Redis cache/queue |

---

## 📦 Container Architecture

```
┌─────────────────────────────────────────────────┐
│  enovaz-nginx (Port 8000)                      │
│  └─ Reverse proxy to PHP-FPM                   │
└─────────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────────┐
│  enovaz-app (PHP-FPM)                          │
│  └─ Laravel application                        │
└─────────────────────────────────────────────────┘
        ↓                    ↓
┌──────────────────┐  ┌──────────────────┐
│  enovaz-db      │  │  enovaz-redis   │
│  (MariaDB)      │  │  (Cache/Queue)  │
└──────────────────┘  └──────────────────┘

┌─────────────────────────────────────────────────┐
│  Background Workers                            │
│  ├─ enovaz-queue (Queue worker)               │
│  ├─ enovaz-scheduler (Cron tasks)             │
│  └─ enovaz-mailhog (Email testing)            │
└─────────────────────────────────────────────────┘
```

---

## 🔍 Troubleshooting

### Container won't start
```bash
# Check logs
docker-compose logs app

# Rebuild
docker-compose up -d --build --force-recreate
```

### Permission issues
```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Database connection failed
```bash
# Check if DB is ready
docker-compose exec db mysql -u enovaz -psecret -e "SHOW DATABASES;"

# Restart DB
docker-compose restart db
```

### Reset everything
```bash
# Stop and remove containers, volumes
docker-compose down -v

# Rebuild and start
docker-compose up -d --build
```

---

## 📝 First-Time Setup Checklist

After containers are running:

1. **Generate APP_KEY**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

2. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

3. **Install dependencies** (if not done in Dockerfile)
   ```bash
   docker-compose exec app composer install
   docker-compose exec app npm install
   docker-compose exec app npm run build
   ```

4. **Set permissions**
   ```bash
   docker-compose exec app chmod -R 775 storage bootstrap/cache
   ```

5. **Test application**
   - Visit: http://localhost:8000
   - Check MailHog: http://localhost:8025

---

## 🔐 Environment Variables

Key variables in `.env`:

```env
# App
APP_NAME="E-Novaz"
APP_ENV=local
APP_KEY=base64:xxxxx  # Generate with artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (Docker)
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=enovaz
DB_USERNAME=enovaz
DB_PASSWORD=secret

# Redis (Docker)
REDIS_HOST=redis
REDIS_PORT=6379

# Mail (Docker - MailHog)
MAIL_HOST=mailhog
MAIL_PORT=1025
```

---

## 🎯 Next Steps

1. Wait for `docker-compose up -d --build` to complete
2. Run first-time setup commands above
3. Access http://localhost:8000
4. Start development!

---

## 📚 Additional Resources

- [Docker Documentation](https://docs.docker.com/)
- [Laravel Documentation](https://laravel.com/docs)
- [Docker Compose Reference](https://docs.docker.com/compose/compose-file/)

---

**Created:** 2026-11-05
**Docker Version:** 29.9.2
**Compose Version:** 5.7.0
