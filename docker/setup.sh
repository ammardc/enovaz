#!/bin/bash
# Setup script untuk E-Novaz dengan Docker
# Run: bash docker/setup.sh

set -e

echo "================================"
echo "E-Novaz Docker Setup"
echo "================================"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker tidak ditemukan!"
    echo "Jalankan: sudo bash install-docker-arch.sh"
    exit 1
fi

echo "✓ Docker ditemukan: $(docker --version)"

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose tidak ditemukan!"
    echo "Jalankan: sudo pacman -S docker-compose"
    exit 1
fi

echo "✓ Docker Compose ditemukan: $(docker-compose --version)"
echo ""

# Create .env file from .env.docker if .env doesn't exist
if [ ! -f .env ]; then
    echo "[1/7] Membuat file .env..."
    cp .env.docker .env
    echo "✓ File .env dibuat dari .env.docker"
else
    echo "[1/7] File .env sudah ada, skip..."
fi

# Generate APP_KEY
echo "[2/7] Generate APP_KEY..."
if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY=" .env; then
    # Build temporary container to generate key
    docker-compose run --rm --no-deps app php artisan key:generate --force
    echo "✓ APP_KEY generated"
else
    echo "✓ APP_KEY sudah ada"
fi

# Generate INVENTORY_KEY
echo "[3/7] Generate INVENTORY_KEY..."
if grep -q "INVENTORY_KEY=$" .env || ! grep -q "INVENTORY_KEY=" .env; then
    INVENTORY_KEY=$(openssl rand -base64 32 2>/dev/null || dd if=/dev/urandom bs=32 count=1 2>/dev/null | base64)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s|INVENTORY_KEY=.*|INVENTORY_KEY=$INVENTORY_KEY|" .env
    else
        sed -i "s|INVENTORY_KEY=.*|INVENTORY_KEY=$INVENTORY_KEY|" .env
    fi
    echo "✓ INVENTORY_KEY generated"
else
    echo "✓ INVENTORY_KEY sudah ada"
fi

# Start Docker containers
echo "[4/7] Starting Docker containers..."
docker-compose up -d
echo "✓ Containers started"

# Wait for database to be ready
echo "[5/7] Waiting for database..."
sleep 10

# Run migrations
echo "[6/7] Running migrations..."
docker-compose exec -T app php artisan migrate --force --seed
echo "✓ Database migrated and seeded"

# Build frontend assets (if not already built)
echo "[7/7] Building frontend assets..."
if [ ! -d "public/build" ]; then
    docker-compose exec -T app npm run build
    echo "✓ Frontend assets built"
else
    echo "✓ Frontend assets already built"
fi

echo ""
echo "================================"
echo "✅ Setup Complete!"
echo "================================"
echo ""
echo "🌐 Application: http://localhost:8000"
echo "📧 MailHog:     http://localhost:8025"
echo "🗄️  Database:   localhost:3306"
echo "   - Database:  enovaz"
echo "   - Username:  enovaz"
echo "   - Password:  secret"
echo ""
echo "📝 Useful commands:"
echo "   docker-compose up -d        # Start containers"
echo "   docker-compose down         # Stop containers"
echo "   docker-compose logs -f app  # View app logs"
echo "   docker-compose exec app bash # Enter container"
echo ""
echo "👤 Create first owner:"
echo "   docker-compose exec app php artisan store:owner your@email.com"
echo ""
