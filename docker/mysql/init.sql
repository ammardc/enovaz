-- Initial database setup for E-Novaz
-- This script runs on first container startup

-- Create additional database if needed (already created by env vars)
-- USE enovaz;

-- Grant privileges following security spec from documentation
-- Create application user with limited privileges (if not using root)

-- For production, use a dedicated app user instead of the main user
-- Uncomment and customize for production:

/*
CREATE USER IF NOT EXISTS 'enovaz_app'@'%' IDENTIFIED BY 'app_secret_password';

-- Users table
GRANT SELECT, INSERT, UPDATE ON enovaz.users TO 'enovaz_app'@'%';

-- Products and variants
GRANT SELECT, INSERT, UPDATE ON enovaz.products TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.product_variants TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.categories TO 'enovaz_app'@'%';

-- Inventory
GRANT SELECT, INSERT, UPDATE ON enovaz.inventory_units TO 'enovaz_app'@'%';

-- Orders
GRANT SELECT, INSERT, UPDATE ON enovaz.orders TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_items TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_status_events TO 'enovaz_app'@'%';

-- Payments
GRANT SELECT, INSERT, UPDATE ON enovaz.payment_invoices TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.payment_events TO 'enovaz_app'@'%';

-- Wallet
GRANT SELECT, INSERT, UPDATE ON enovaz.wallet_accounts TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.wallet_ledger_entries TO 'enovaz_app'@'%';

-- Refunds
GRANT SELECT, INSERT, UPDATE ON enovaz.refunds TO 'enovaz_app'@'%';

-- Audit logs (SELECT, INSERT only - NO UPDATE/DELETE)
GRANT SELECT, INSERT ON enovaz.admin_audit_logs TO 'enovaz_app'@'%';

-- Other tables
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
*/

-- ============================================
-- SECURITY CONFIGURATION
-- ============================================

-- 1. PASSWORD VALIDATION PLUGIN
-- Install password validation (if available)
-- Note: This command may fail on some MariaDB versions
-- but the security configuration below still applies

-- 2. PASSWORD POLICY CONFIGURATION (MariaDB 10.4+)
-- Set password validation policy
SET GLOBAL validate_password_policy = 'MEDIUM';
SET GLOBAL validate_password_length = 14;
SET GLOBAL validate_password_mixed_case_count = 1;
SET GLOBAL validate_password_number_count = 1;
SET GLOBAL validate_password_special_char_count = 1;

-- 3. PASSWORD EXPIRATION POLICY
-- Global password expiration: 90 days
SET GLOBAL default_password_lifetime = 90;

-- 4. CREATE APPLICATION USER WITH STRONG PASSWORD
-- Drop existing user if exists
DROP USER IF EXISTS 'enovaz_app'@'%';

-- Create application user with strong password
-- Password: SecureEnovazApp123!
-- Requirements: 14+ chars, 1 uppercase, 1 digit, 1 special char
CREATE USER 'enovaz_app'@'%' IDENTIFIED BY 'SecureEnovazApp123!';

-- Set password lifetime: 60 days
ALTER USER 'enovaz_app'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;

-- Account lockout: 5 failed attempts, 30 minutes lockout
ALTER USER 'enovaz_app'@'%' FAILED_LOGIN_ATTEMPTS 5 PASSWORD_LOCK_TIME 30;

-- 5. GRANT MINIMAL PRIVILEGES (Least Privilege)
-- Users table
GRANT SELECT, INSERT, UPDATE ON enovaz.users TO 'enovaz_app'@'%';

-- Products and variants
GRANT SELECT, INSERT, UPDATE ON enovaz.products TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.product_variants TO 'enovaz_app'@'%';
GRANT SELECT, INSERT, UPDATE ON enovaz.categories TO 'enovaz_app'@'%';

-- Inventory
GRANT SELECT, INSERT, UPDATE ON enovaz.inventory_units TO 'enovaz_app'@'%';

-- Orders
GRANT SELECT, INSERT, UPDATE ON enovaz.orders TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_items TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.order_status_events TO 'enovaz_app'@'%';

-- Payments
GRANT SELECT, INSERT, UPDATE ON enovaz.payment_invoices TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.payment_events TO 'enovaz_app'@'%';

-- Wallet
GRANT SELECT, INSERT, UPDATE ON enovaz.wallet_accounts TO 'enovaz_app'@'%';
GRANT SELECT, INSERT ON enovaz.wallet_ledger_entries TO 'enovaz_app'@'%';

-- Refunds
GRANT SELECT, INSERT, UPDATE ON enovaz.refunds TO 'enovaz_app'@'%';

-- Audit logs (SELECT, INSERT only - NO UPDATE/DELETE)
GRANT SELECT, INSERT ON enovaz.admin_audit_logs TO 'enovaz_app'@'%';

-- Other tables
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

-- 6. CREATE AUDIT USER (read-only for monitoring)
DROP USER IF EXISTS 'enovaz_audit'@'%';

CREATE USER 'enovaz_audit'@'%' IDENTIFIED BY 'AuditMonitor456!';

-- Grant only SELECT for audit purposes
GRANT SELECT ON enovaz.* TO 'enovaz_audit'@'%';
GRANT SELECT ON mysql.user TO 'enovaz_audit'@'%';

-- Set password lifetime
ALTER USER 'enovaz_audit'@'%' PASSWORD EXPIRE INTERVAL 60 DAY;

-- Flush privileges
FLUSH PRIVILEGES;
