#!/usr/bin/env bash
# Helper perintah Docker E-Novaz (versi final).
# Usage: bash docker/commands.sh <command>

set -euo pipefail
cd "$(dirname "$0")/.."

COMPOSE="docker compose"
COMMAND="${1:-help}"

get_env() {
  grep -E "^${1}=" .env | head -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

case "$COMMAND" in
  setup)
    bash docker/setup.sh
    ;;

  install)
    $COMPOSE exec app composer install
    $COMPOSE exec app npm ci
    ;;

  up)        $COMPOSE up -d ;;
  down)      $COMPOSE down ;;
  restart)   $COMPOSE restart ;;
  rebuild)
    $COMPOSE down
    $COMPOSE build --no-cache
    $COMPOSE up -d
    ;;

  logs)      $COMPOSE logs -f "${2:-app}" ;;
  shell)     $COMPOSE exec app sh ;;
  artisan)   shift; $COMPOSE exec app php artisan "$@" ;;
  test)
    $COMPOSE exec app php artisan test
    $COMPOSE exec app sh -c "chmod -R a+rwX storage bootstrap/cache"
    ;;
  migrate)   $COMPOSE exec -T -e DB_USERNAME="$(get_env DB_MIGRATOR_USERNAME)" -e DB_PASSWORD="$(get_env DB_MIGRATOR_PASSWORD)" app php artisan migrate --force ;;
  migrate-fresh)
    echo "⚠️  Menghapus seluruh data lalu migrate + seed."
    read -r -p "Ketik YA untuk lanjut: " confirm
    if [ "$confirm" = "YA" ]; then
      $COMPOSE exec -T -e DB_USERNAME="$(get_env DB_MIGRATOR_USERNAME)" -e DB_PASSWORD="$(get_env DB_MIGRATOR_PASSWORD)" app php artisan migrate:fresh --force --seed
    else
      echo "Dibatalkan."
    fi
    ;;
  seed)
    $COMPOSE exec -T -e DB_USERNAME="$(get_env DB_MIGRATOR_USERNAME)" -e DB_PASSWORD="$(get_env DB_MIGRATOR_PASSWORD)" app php artisan db:seed
    ;;

  cache-clear)
    $COMPOSE exec app php artisan optimize:clear
    ;;

  npm)       shift; $COMPOSE exec app npm "$@" ;;
  build)     $COMPOSE exec app npm run build ;;
  dev)       $COMPOSE exec app npm run dev ;;

  db)        $COMPOSE exec db mariadb -u"$(get_env DB_USERNAME)" -p"$(get_env DB_PASSWORD)" "$(get_env DB_DATABASE)" ;;
  backup)
    BACKUP_FILE="backup-$(date +%Y%m%d-%H%M%S).sql"
    echo "💾 Membuat backup: $BACKUP_FILE"
    $COMPOSE exec -T db mariadb-dump -u"$(get_env DB_MIGRATOR_USERNAME)" -p"$(get_env DB_MIGRATOR_PASSWORD)" "$(get_env DB_DATABASE)" > "$BACKUP_FILE"
    echo "✓ Tersimpan: $BACKUP_FILE"
    ;;
  restore)
    if [ -z "${2:-}" ]; then
      echo "❌ Usage: $0 restore <backup-file.sql>"
      exit 1
    fi
    $COMPOSE exec -T db mariadb -u"$(get_env DB_MIGRATOR_USERNAME)" -p"$(get_env DB_MIGRATOR_PASSWORD)" "$(get_env DB_DATABASE)" < "$2"
    echo "✓ Database dipulihkan"
    ;;

  ps)        $COMPOSE ps ;;
  stats)     docker stats --no-stream ;;

  help|*)
    echo "E-Novaz Docker Commands"
    echo ""
    echo "  setup              Setup awal / perbarui (aman diulang)"
    echo "  install            composer install + npm ci"
    echo "  up | down | restart | rebuild"
    echo "  logs [service]     Lihat log (default: app)"
    echo "  shell              Masuk container app"
    echo "  artisan [cmd]      Jalankan artisan"
    echo "  test               php artisan test"
    echo "  migrate            Migrasi (akun migrator)"
    echo "  migrate-fresh      Hapus data + migrate + seed"
    echo "  seed               Seed database"
    echo "  cache-clear        Bersihkan cache"
    echo "  npm [cmd] | build | dev"
    echo "  db                 Masuk MariaDB (akun app)"
    echo "  backup             Dump database ke backup-*.sql"
    echo "  restore <file>     Pulihkan dump"
    echo "  ps | stats"
    echo ""
    ;;
esac
