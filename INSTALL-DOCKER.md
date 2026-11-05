# Panduan Install Docker di Arch Linux

## Step 1: Install Docker

```bash
# Salin script ke home directory
cd ~
cp /tmp/opencode/install-docker-arch.sh .

# Jalankan dengan sudo
sudo bash install-docker-arch.sh
```

Output yang diharapkan:
```
================================
Docker Installation for Arch Linux
================================
[1/6] Updating system...
[2/6] Installing Docker...
[3/6] Starting Docker service...
[4/6] Adding user to docker group...
[5/6] Verifying Docker installation...

✅ Docker installed successfully!
================================

⚠️  IMPORTANT: You need to LOG OUT and LOG IN again
    for group changes to take effect.

After re-login, test with: docker run hello-world
```

## Step 2: Logout & Login

```bash
# Logout dari sesi saat ini
logout

# Atau restart display manager
sudo systemctl restart sddm  # Untuk KDE
# sudo systemctl restart gdm   # Untuk GNOME
# sudo systemctl restart lightdm  # Untuk XFCE/LXDE
```

## Step 3: Verify Installation

Setelah login kembali:

```bash
# Test Docker tanpa sudo
docker run hello-world

# Check versions
docker --version
docker-compose --version
```

## Step 4: Setup E-Novaz

```bash
# Masuk ke directory project
cd ~/Documents/enovaz-docker

# Run setup script
bash docker/setup.sh
```

Setup akan:
- ✅ Generate APP_KEY dan INVENTORY_KEY
- ✅ Start semua containers (app, db, redis, queue, scheduler, nginx, mailhog)
- ✅ Run migrations dan seeder
- ✅ Build frontend assets

## Step 5: Akses Aplikasi

Setelah setup selesai:

- **Web App**: http://localhost:8000
- **MailHog** (untuk test email): http://localhost:8025
- **Database**: localhost:3306
  - Database: `enovaz`
  - Username: `enovaz`
  - Password: `secret`

## Step 6: Create Owner Account

```bash
# Daftar akun baru di http://localhost:8000/daftar
# Cek email di MailHog: http://localhost:8025
# Klik link verifikasi

# Promote ke owner
cd ~/Documents/enovaz-docker
bash docker/commands.sh owner your@email.com

# Login dan akses /admin untuk setup TOTP
```

## Troubleshooting

### Docker command not found setelah install
**Solusi**: Pastikan sudah logout dan login kembali

### Permission denied saat run docker
**Solusi**: 
```bash
# Check apakah sudah di group docker
groups

# Jika belum ada "docker", tambahkan manual
sudo usermod -aG docker $USER
# Logout dan login kembali
```

### Port 8000 already in use
**Solusi**: Ubah port di `docker-compose.yml`:
```yaml
nginx:
  ports:
    - "8001:80"  # Ganti 8000 ke 8001
```

### Database connection error
**Solusi**:
```bash
# Wait lebih lama untuk database startup
docker-compose restart db
sleep 10
bash docker/commands.sh migrate
```

## Next Steps

Setelah environment berjalan:

1. ✅ Baca dokumentasi lengkap: [DOCKER.md](DOCKER.md)
2. ✅ Setup Casaku credentials untuk payment testing
3. ✅ Setup Google OAuth (optional)
4. ✅ Konfigurasi SMTP untuk production email
5. ✅ Setup backup automation

## Useful Commands

```bash
cd ~/Documents/enovaz-docker

# Helper commands
bash docker/commands.sh help

# Quick commands
bash docker/commands.sh up          # Start
bash docker/commands.sh down        # Stop
bash docker/commands.sh logs        # View logs
bash docker/commands.sh shell       # Enter container
bash docker/commands.sh test        # Run tests
bash docker/commands.sh artisan migrate
bash docker/commands.sh npm run build
```

## Selesai! 🎉

Environment Docker E-Novaz sudah siap untuk development!
