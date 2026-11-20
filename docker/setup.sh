#!/usr/bin/env bash
# Setup E-Novaz (versi final) dengan Docker.
# Jalankan dari root project: bash docker/setup.sh
#
# Aman dijalankan berulang: tidak menghapus database atau data yang sudah ada.

set -euo pipefail

cd "$(dirname "$0")/.."

COMPOSE="docker compose"

get_env() {
  # Ambil nilai dari .env tanpa tanda kutip pembungkus
  local key="$1"
  grep -E "^${key}=" .env | head -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//'
}

set_env() {
  # Ganti/isi satu baris KEY=value di .env
  local key="$1" value="$2"
  if grep -qE "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    printf '%s=%s\n' "$key" "$value" >> .env
  fi
}

random_base64() {
  openssl rand -base64 32 | tr -d '\n'
}

random_password() {
  # Huruf + angka, aman untuk .env dan SQL
  printf 'Ev%s%s' "$(openssl rand -hex 6)" "$(openssl rand -hex 4)"
}

echo "================================"
echo "E-Novaz Docker Setup (versi final)"
echo "================================"

# 1. File .env
if [ ! -f .env ]; then
  cp .env.docker .env
  echo "[1/9] .env dibuat dari .env.docker"
else
  echo "[1/9] .env sudah ada, dipakai apa adanya"
fi

# 2. Kunci aplikasi (hanya bila masih kosong)
if [ -z "$(get_env APP_KEY)" ]; then
  set_env APP_KEY "base64:$(random_base64)"
  echo "[2/9] APP_KEY dibuat"
else
  echo "[2/9] APP_KEY sudah ada"
fi

if [ -z "$(get_env CREDENTIAL_KEY)" ]; then
  set_env CREDENTIAL_KEY "$(random_base64)"
  echo "      CREDENTIAL_KEY dibuat (simpan cadangannya!)"
else
  echo "      CREDENTIAL_KEY sudah ada"
fi

if [ -z "$(get_env ADMIN_PASSWORD)" ]; then
  set_env ADMIN_PASSWORD "$(random_password)"
  echo "      ADMIN_PASSWORD dibuat"
fi

DB_ROOT_PASSWORD="$(get_env DB_ROOT_PASSWORD)"
DB_USERNAME="$(get_env DB_USERNAME)"
DB_PASSWORD="$(get_env DB_PASSWORD)"
DB_MIGRATOR_USERNAME="$(get_env DB_MIGRATOR_USERNAME)"
DB_MIGRATOR_PASSWORD="$(get_env DB_MIGRATOR_PASSWORD)"
DB_DATABASE="$(get_env DB_DATABASE)"
ADMIN_USERNAME="$(get_env ADMIN_USERNAME)"
ADMIN_PASSWORD="$(get_env ADMIN_PASSWORD)"

# 3. Build & jalankan container
echo "[3/9] Build & start container..."
$COMPOSE up -d --build

# 4. Tunggu database siap
echo "[4/9] Menunggu MariaDB siap..."
for i in $(seq 1 60); do
  if $COMPOSE exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" -e 'SELECT 1' >/dev/null 2>&1; then
    break
  fi
  sleep 2
  if [ "$i" -eq 60 ]; then
    echo "MariaDB tidak siap. Cek: $COMPOSE logs db"
    exit 1
  fi
done
echo "      MariaDB siap"

# 5. Dependensi PHP + aset frontend
echo "[5/9] composer install..."
$COMPOSE exec -T app composer install --no-interaction --prefer-dist

echo "      npm ci & build aset..."
$COMPOSE exec -T app npm ci
$COMPOSE exec -T app npm run build

# 6. Buat database + akun DB (idempoten)
echo "[6/9] Menyiapkan database & akun..."
$COMPOSE exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_MIGRATOR_USERNAME}'@'%' IDENTIFIED BY '${DB_MIGRATOR_PASSWORD}';
ALTER USER '${DB_MIGRATOR_USERNAME}'@'%' IDENTIFIED BY '${DB_MIGRATOR_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_MIGRATOR_USERNAME}'@'%';
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
FLUSH PRIVILEGES;
SQL

# 7. Migrasi + seed memakai akun migrator (DDL)
echo "[7/9] Migrasi & seed (akun migrator)..."
$COMPOSE exec -T app php artisan optimize:clear
$COMPOSE exec -T \
  -e DB_USERNAME="$DB_MIGRATOR_USERNAME" \
  -e DB_PASSWORD="$DB_MIGRATOR_PASSWORD" \
  app php artisan migrate --force --seed

# 8. Hak akses minimal untuk akun runtime aplikasi
echo "[8/9] Menerapkan GRANT minimal untuk ${DB_USERNAME}..."
$COMPOSE exec -T db mariadb -uroot -p"$DB_ROOT_PASSWORD" <<SQL
GRANT SELECT, INSERT, UPDATE ON \`${DB_DATABASE}\`.users            TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT, UPDATE ON \`${DB_DATABASE}\`.products         TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT, UPDATE ON \`${DB_DATABASE}\`.orders           TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT         ON \`${DB_DATABASE}\`.order_items      TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT, UPDATE ON \`${DB_DATABASE}\`.digital_accounts TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT, UPDATE ON \`${DB_DATABASE}\`.payments         TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT         ON \`${DB_DATABASE}\`.deliveries       TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT         ON \`${DB_DATABASE}\`.audit_logs       TO '${DB_USERNAME}'@'%';
GRANT SELECT, INSERT         ON \`${DB_DATABASE}\`.balance_ledger   TO '${DB_USERNAME}'@'%';
GRANT SELECT                 ON \`${DB_DATABASE}\`.migrations       TO '${DB_USERNAME}'@'%';
FLUSH PRIVILEGES;
SQL

# 9. Storage link & permission
echo "[9/9] Storage link & permission..."
$COMPOSE exec -T app php artisan storage:link || true
$COMPOSE exec -T app sh -c "chmod -R a+rwX storage bootstrap/cache"

echo ""
echo "================================"
echo "Selesai."
echo "  URL    : http://localhost:8000"
echo "  Admin  : ${ADMIN_USERNAME} / ${ADMIN_PASSWORD}"
echo "  MailHog: http://localhost:8025"
echo ""
echo "Perintah lain: bash docker/commands.sh help"
echo "================================"
