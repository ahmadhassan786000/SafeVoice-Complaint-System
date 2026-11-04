-- SafeVoice migration (for an EXISTING database). Keeps all complaints, users, messages, evidence and history.
-- Import in phpMyAdmin with the `safevoice` database selected. Safe to run more than once.
-- Do NOT import schema.sql on an existing database: it recreates (empties) every table.
USE safevoice;

-- 1. Remove Audit Logs
DROP TABLE IF EXISTS audit_logs;

-- 2. Remove Login Attempts (no lockout feature any more)
DROP TABLE IF EXISTS login_attempts;

-- 3. Remove the mandatory first-login password change column (only if it still exists)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password');
SET @sql := IF(@col > 0, 'ALTER TABLE users DROP COLUMN must_change_password', 'SELECT ''must_change_password already removed''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Default Super Admin (admin@gmail.com / 12345678, bcrypt hash) - created only if it does not exist yet
INSERT INTO users (role_id, full_name, email, password, account_status, is_protected)
SELECT 3, 'System Administrator', 'admin@gmail.com',
       '$2y$10$bBKz1MT4RjWvlO1d202PteLRPWZtegy39wMY5CGN1Jbi9X2myIz4m', 'active', 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@gmail.com');
