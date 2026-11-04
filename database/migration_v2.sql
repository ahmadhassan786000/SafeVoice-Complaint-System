-- SafeVoice migration v2  (for an EXISTING database - keeps all current data)
-- Import in phpMyAdmin with the `safevoice` database selected. Safe to run more than once.
USE safevoice;

-- Default Super Admin: admin@gmail.com / 12345678 (bcrypt hash), created only if it does not exist yet.
INSERT INTO users (role_id, full_name, email, password, account_status, must_change_password, is_protected)
SELECT 3, 'System Administrator', 'admin@gmail.com',
       '$2y$10$bBKz1MT4RjWvlO1d202PteLRPWZtegy39wMY5CGN1Jbi9X2myIz4m', 'active', 1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@gmail.com');

-- No table or column changes are needed for v2: the new password rule (8+ characters),
-- mandatory student phone number and the UI changes are all handled in PHP.
-- Optional: students registered earlier without a phone number are asked for one when they edit their profile.
