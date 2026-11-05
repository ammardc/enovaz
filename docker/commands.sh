#!/bin/bash
# Makefile alternative untuk E-Novaz Docker
# Usage: bash docker/commands.sh <command>

set -e

COMMAND=${1:-help}

case $COMMAND in
  # Setup & Installation
  setup)
    echo "🚀 Running initial setup..."
    bash docker/setup.sh
    ;;
  
  install)
    echo "📦 Installing dependencies..."
    docker-compose exec app composer install
    docker-compose exec app npm ci
    ;;
  
  # Container Management
  up)
    echo "▶️  Starting containers..."
    docker-compose up -d
    ;;
  
  down)
    echo "⏹️  Stopping containers..."
    docker-compose down
    ;;
  
  restart)
    echo "🔄 Restarting containers..."
    docker-compose restart
    ;;
  
  rebuild)
    echo "🔨 Rebuilding containers..."
    docker-compose down
    docker-compose build --no-cache
    docker-compose up -d
    ;;
  
  logs)
    docker-compose logs -f ${2:-app}
    ;;
  
  # Application Commands
  shell)
    docker-compose exec app bash
    ;;
  
  artisan)
    shift
    docker-compose exec app php artisan "$@"
    ;;
  
  test)
    echo "🧪 Running tests..."
    docker-compose exec app php artisan test
    ;;
  
  migrate)
    echo "🗄️  Running migrations..."
    docker-compose exec app php artisan migrate --force
    ;;
  
  migrate-fresh)
    echo "⚠️  Fresh migration (destructive!)..."
    read -p "Are you sure? (yes/no): " confirm
    if [ "$confirm" = "yes" ]; then
      docker-compose exec app php artisan migrate:fresh --seed
    else
      echo "Cancelled."
    fi
    ;;
  
  seed)
    echo "🌱 Seeding database..."
    docker-compose exec app php artisan db:seed
    ;;
  
  # Cache Management
  cache-clear)
    echo "🧹 Clearing all caches..."
    docker-compose exec app php artisan cache:clear
    docker-compose exec app php artisan config:clear
    docker-compose exec app php artisan route:clear
    docker-compose exec app php artisan view:clear
    ;;
  
  optimize)
    echo "⚡ Optimizing application..."
    docker-compose exec app php artisan config:cache
    docker-compose exec app php artisan route:cache
    docker-compose exec app php artisan view:cache
    docker-compose exec app php artisan event:cache
    ;;
  
  # Frontend
  npm)
    shift
    docker-compose exec app npm "$@"
    ;;
  
  build)
    echo "🎨 Building frontend assets..."
    docker-compose exec app npm run build
    ;;
  
  dev)
    echo "🔥 Starting Vite dev server..."
    docker-compose exec app npm run dev
    ;;
  
  # Database
  db)
    echo "🗄️  Connecting to database..."
    docker-compose exec db mysql -u enovaz -psecret enovaz
    ;;
  
  backup)
    BACKUP_FILE="backup-$(date +%Y%m%d-%H%M%S).sql"
    echo "💾 Creating backup: $BACKUP_FILE"
    docker-compose exec -T db mysqldump -u enovaz -psecret enovaz > "$BACKUP_FILE"
    echo "✓ Backup saved: $BACKUP_FILE"
    ;;
  
  restore)
    if [ -z "$2" ]; then
      echo "❌ Usage: $0 restore <backup-file.sql>"
      exit 1
    fi
    echo "📥 Restoring from: $2"
    docker-compose exec -T db mysql -u enovaz -psecret enovaz < "$2"
    echo "✓ Database restored"
    ;;
  
  # User Management
  owner)
    if [ -z "$2" ]; then
      echo "❌ Usage: $0 owner <email>"
      exit 1
    fi
    echo "👤 Creating owner: $2"
    docker-compose exec app php artisan store:owner "$2"
    ;;
  
  # Monitoring
  ps)
    docker-compose ps
    ;;
  
  stats)
    docker stats --no-stream
    ;;
  
  # Help
  help|*)
    echo "E-Novaz Docker Commands"
    echo ""
    echo "Setup & Installation:"
    echo "  setup              Run initial setup"
    echo "  install            Install dependencies"
    echo ""
    echo "Container Management:"
    echo "  up                 Start containers"
    echo "  down               Stop containers"
    echo "  restart            Restart containers"
    echo "  rebuild            Rebuild containers"
    echo "  logs [service]     View logs (default: app)"
    echo "  ps                 Show container status"
    echo "  stats              Show resource usage"
    echo ""
    echo "Application:"
    echo "  shell              Enter app container"
    echo "  artisan [cmd]      Run artisan command"
    echo "  test               Run tests"
    echo "  migrate            Run migrations"
    echo "  migrate-fresh      Fresh migration (destructive)"
    echo "  seed               Seed database"
    echo "  owner <email>      Create owner account"
    echo ""
    echo "Cache:"
    echo "  cache-clear        Clear all caches"
    echo "  optimize           Optimize for production"
    echo ""
    echo "Frontend:"
    echo "  npm [cmd]          Run npm command"
    echo "  build              Build frontend assets"
    echo "  dev                Start Vite dev server"
    echo ""
    echo "Database:"
    echo "  db                 Connect to database"
    echo "  backup             Create database backup"
    echo "  restore <file>     Restore from backup"
    echo ""
    echo "Examples:"
    echo "  bash docker/commands.sh setup"
    echo "  bash docker/commands.sh artisan migrate"
    echo "  bash docker/commands.sh npm run build"
    echo ""
    ;;
esac
