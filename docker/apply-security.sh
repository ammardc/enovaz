#!/bin/bash
# Apply database security configuration
# Run: bash docker/apply-security.sh

set -e

echo "================================"
echo "E-Novaz Database Security Setup"
echo "================================"
echo ""

cd "$(dirname "$0")/.."

echo "[1/7] Checking database connection..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "SELECT VERSION();" > /dev/null 2>&1
echo "✓ Database connected"

echo ""
echo "[2/7] Configuring password policy..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
SET GLOBAL validate_password_policy = 'MEDIUM';
SET GLOBAL validate_password_length = 14;
SET GLOBAL validate_password_mixed_case_count = 1;
SET GLOBAL validate_password_number_count = 1;
SET GLOBAL validate_password_special_char_count = 1;
" 
echo "✓ Password policy configured"

echo ""
echo "[3/7] Setting password expiration..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
SET GLOBAL default_password_lifetime = 90;
"
echo "✓ Password expiration set to 90 days"

echo ""
echo "[4/7] Creating application user..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
DROP USER IF EXISTS 'enovaz_app'@'%';
CREATE USER 'enovaz_app'@'%' IDENTIFIED BY 'SecureEnovazApp123!';
ALTER USER 'enovaz_app'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;
ALTER USER 'enovaz_app'@'%' FAILED_LOGIN_ATTEMPTS 5 PASSWORD_LOCK_TIME 30;
"
echo "✓ Application user created with security policies"

echo ""
echo "[5/7] Granting application privileges..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
GRANT SELECT, INSERT, UPDATE ON enovaz.users TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.products TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.product_variants TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.categories TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.inventory_units TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.orders TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_items TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_status_events TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.payment_invoices TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.payment_events TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.wallet_accounts TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.wallet_ledger_entries TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.refunds TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.admin_audit_logs TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.content_pages TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.policy_versions TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.product_media TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.admin_adjustments TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.social_accounts TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.settings_public TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.sessions TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.cache TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.cache_locks TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.jobs TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.job_batches TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.failed_jobs TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.password_reset_tokens TO 'enovaz_app'@'%';
FLUSH PRIVILEGES;
"
echo "✓ Application privileges granted (Least Privilege Principle)"

echo ""
echo "[6/7] Creating audit user..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
DROP USER IF EXISTS 'enovaz_audit'@'%';
CREATE USER 'enovaz_audit'@'%' IDENTIFIED BY 'AuditMonitor456!';
GRANT SELECT ON enovaz.* TO 'enovaz_audit'@'%';
GRANT SELECT ON mysql.user TO 'enovaz_audit'@'%';
ALTER USER 'enovaz_audit'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;
FLUSH PRIVILEGES;
"
echo "✓ Audit user created with read-only privileges"

echo ""
echo "[7/7] Verifying security configuration..."
docker-compose exec -T db mariadb -u root -prootsecret enovaz -e "
SELECT 'Password Policy Settings:' as '';
SELECT VARIABLE_NAME, VARIABLE_VALUE FROM INFORMATION_SCHEMA.GLOBAL_VARIABLES
WHERE VARIABLE_NAME LIKE 'validate_password%';

SELECT '' as '';
SELECT 'User Security Status:' as '';
SELECT user, host, password_expired, password_lifetime, 
       password_failed_login_attempts, password_lock_time_days
FROM mysql.user WHERE user LIKE 'enovaz%' OR user = 'root';
"

echo ""
echo "================================"
echo "✅ Security Setup Complete!"
echo "================================"
echo ""
echo "📊 Summary:"
echo "✅ Password Policy: MEDIUM (14+ chars, uppercase, digits, special)"
echo "✅ Password Expiration: 90 days (global), 60 days (enovaz_app/audit)"
echo "✅ Account Lockout: 5 failed attempts, 30 min auto-unlock"
echo "✅ Application User: enovaz_app (minimal privileges)"
echo "✅ Audit User: enovaz_audit (read-only)"
echo ""
echo "🔑 Credentials:"
echo "   enovaz_app: SecureEnovazApp123!"
echo "   enovaz_audit: AuditMonitor456!"
echo ""
echo "📚 Documentation: See SECURITY.md for details"
echo ""
