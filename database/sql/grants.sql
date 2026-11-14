-- E-Novaz: pembuatan database dan akun runtime terbatas (dijalankan oleh DBA, bukan oleh aplikasi)
-- Jalankan sebagai root/DBA:  mysql -u root -p < database/sql/grants.sql
-- Ganti password di bawah sebelum dipakai.

CREATE DATABASE IF NOT EXISTS enovaz_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Akun migrasi (DDL) hanya dipakai saat deploy: php artisan migrate
CREATE USER IF NOT EXISTS 'enovaz_migrator'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_MIGRATOR';
GRANT ALL PRIVILEGES ON enovaz_db.* TO 'enovaz_migrator'@'localhost';

-- Akun runtime aplikasi: tanpa hak global, tanpa DDL, tanpa GRANT OPTION
CREATE USER IF NOT EXISTS 'enovaz_app'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_APP';

-- Jalankan bagian di bawah SETELAH php artisan migrate (tabel harus sudah ada).
GRANT SELECT, INSERT, UPDATE ON enovaz_db.users            TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON enovaz_db.products         TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON enovaz_db.orders           TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT         ON enovaz_db.order_items      TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON enovaz_db.digital_accounts TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT, UPDATE ON enovaz_db.payments         TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT         ON enovaz_db.deliveries       TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT         ON enovaz_db.audit_logs       TO 'enovaz_app'@'localhost';
GRANT SELECT, INSERT         ON enovaz_db.balance_ledger   TO 'enovaz_app'@'localhost';
-- Dibutuhkan Laravel untuk membaca status migrasi
GRANT SELECT                 ON enovaz_db.migrations       TO 'enovaz_app'@'localhost';

FLUSH PRIVILEGES;
SHOW GRANTS FOR 'enovaz_app'@'localhost';

-- Bila pernah diberi hak berlebih:
-- REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'enovaz_app'@'localhost';
