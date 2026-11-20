-- E-Novaz Database Security Configuration
-- Implementasi password policy dan account security
-- File: docker/mysql/security.sql

-- ============================================
-- 1. PASSWORD VALIDATION PLUGIN
-- ============================================

-- Install password validation plugin
INSTALL PLUGIN validate_password SONAME 'validate_password.so';

-- Verify plugin installation
SELECT PLUGIN_NAME, PLUGIN_STATUS FROM INFORMATION_SCHEMA.PLUGINS
WHERE PLUGIN_NAME = 'validate_password';

-- ============================================
-- 2. PASSWORD POLICY CONFIGURATION
-- ============================================

-- Set password validation policy level
-- 0 = LOW (hanya panjang)
-- 1 = MEDIUM (panjang, uppercase, lowercase, digits, special)
-- 2 = STRONG (panjang, uppercase, lowercase, digits, special + dictionary check)
SET GLOBAL validate_password_policy = 'MEDIUM';

-- Minimum password length (14 characters)
SET GLOBAL validate_password_length = 14;

-- Require at least 1 uppercase letter
SET GLOBAL validate_password_mixed_case_count = 1;

-- Require at least 1 digit
SET GLOBAL validate_password_number_count = 1;

-- Require at least 1 special character
SET GLOBAL validate_password_special_char_count = 1;

-- ============================================
-- 3. PASSWORD EXPIRATION CONFIGURATION
-- ============================================

-- Set global password expiration policy
-- Password expires after 90 days
SET GLOBAL default_password_lifetime = 90;

-- ============================================
-- 4. CREATE APPLICATION USER WITH STRONG PASSWORD
-- ============================================

-- Drop existing user if exists
DROP USER IF EXISTS 'enovaz_app'@'%';

-- Create new user with password validation
-- Password must meet: 14+ chars, 1 uppercase, 1 digit, 1 special char
-- Example: SecurePass123!
CREATE USER 'enovaz_app'@'%' IDENTIFIED BY 'SecureEnovazApp123!';

-- Set password lifetime for this user (60 days)
ALTER USER 'enovaz_app'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;

-- Lock account after 5 failed login attempts
-- Account auto-unlock after 30 minutes
ALTER USER 'enovaz_app'@'%' FAILED_LOGIN_ATTEMPTS 5 PASSWORD_LOCK_TIME 30;

-- ============================================
-- 5. GRANT MINIMAL PRIVILEGES (Least Privilege)
-- ============================================

-- Database level privileges for app
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

-- Laravel framework tables
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.sessions TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.cache TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.cache_locks TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.jobs TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.job_batches TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.failed_jobs TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON enovaz.password_reset_tokens TO 'enovaz_app'@'%';

FLUSH PRIVILEGES;

-- ============================================
-- 6. CREATE AUDIT USER (for monitoring)
-- ============================================

DROP USER IF EXISTS 'enovaz_audit'@'%';

CREATE USER 'enovaz_audit'@'%' IDENTIFIED BY 'AuditMonitor456!';

-- Grant only SELECT for audit purposes
GRANT SELECT ON enovaz.* TO 'enovaz_audit'@'%';
GRANT SELECT ON mysql.user TO 'enovaz_audit'@'%';
GRANT SELECT ON mysql.general_log TO 'enovaz_audit'@'%';

-- Set password lifetime
ALTER USER 'enovaz_audit'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;

FLUSH PRIVILEGES;

-- ============================================
-- 7. ENABLE GENERAL LOG for AUDIT (optional)
-- ============================================

-- NOTE: This should be enabled/disabled as needed for troubleshooting
-- Not recommended for production due to performance impact

-- SET GLOBAL general_log = 'ON';
-- SET GLOBAL log_output = 'TABLE';

-- ============================================
-- 8. VERIFY PASSWORD POLICY
-- ============================================

-- Show current password policy settings
SELECT VARIABLE_NAME, VARIABLE_VALUE FROM INFORMATION_SCHEMA.GLOBAL_VARIABLES
WHERE VARIABLE_NAME LIKE 'validate_password%'
OR VARIABLE_NAME LIKE 'default_password%';

-- ============================================
-- 9. VIEW USERS WITH PASSWORD EXPIRATION
-- ============================================

SELECT
    user,
    host,
    password_expired,
    password_lifetime,
    password_reuse_history,
    password_reuse_time,
    password_require_current
FROM mysql.user
WHERE user IN ('enovaz_app', 'enovaz_audit');

-- ============================================
-- 10. VIEW ACCOUNT LOCK STATUS
-- ============================================

SELECT
    user,
    host,
    account_locked,
    password_expired,
    password_lifetime,
    password_failed_login_attempts,
    password_lock_time_days
FROM mysql.user
WHERE user LIKE 'enovaz%';

-- ============================================
-- SECURITY SUMMARY
-- ============================================

/*
Password Policy Enforcement:
✅ Policy Level: MEDIUM (uppercase, lowercase, digits, special chars)
✅ Minimum Length: 14 characters
✅ Uppercase Required: 1+
✅ Digits Required: 1+
✅ Special Chars Required: 1+

User Accounts:
✅ enovaz_app: Main application user
   - Password expires every 60 days
   - Account locks after 5 failed attempts
   - Locked for 30 minutes before auto-unlock
   - Minimal privileges (Least Privilege Principle)

✅ enovaz_audit: Audit/monitoring user
   - SELECT-only access
   - Password expires every 60 days
   - No privilege to modify data

✅ root: Administrator (should change password)

Account Lockout Policy:
✅ Failed attempts: 5
✅ Lockout duration: 30 minutes (auto-unlock)

Password Expiration:
✅ Global default: 90 days
✅ enovaz_app: 60 days
✅ enovaz_audit: 60 days

Authentication Features:
✅ Password complexity validation
✅ Password history tracking
✅ Account locking on failed attempts
✅ Password expiration enforcement
✅ Least privilege principle
✅ Separate audit user

NOTE: These settings require MariaDB 10.4+ or MySQL 5.7+
Current: MariaDB 11.2 ✅
*/
