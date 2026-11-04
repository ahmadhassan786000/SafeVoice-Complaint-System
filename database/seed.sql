-- SafeVoice seed data. Safe to re-run: INSERT IGNORE + unique keys prevent duplicates.
USE safevoice;

INSERT IGNORE INTO roles (id, role_code, role_name) VALUES
 (1,'STUDENT','Student'),
 (2,'INVESTIGATOR','Investigation Officer'),
 (3,'SUPER_ADMIN','Super Admin');

INSERT IGNORE INTO complaint_categories (category_name, description) VALUES
 ('Bullying','Repeated physical, verbal or social behaviour intended to hurt or intimidate.'),
 ('Harassment','Unwanted behaviour that offends, humiliates or distresses a student.'),
 ('Sexual Harassment','Unwelcome conduct of a sexual nature.'),
 ('Physical Abuse','Physical harm or the threat of physical harm.'),
 ('Threats and Intimidation','Threats, coercion or intimidation directed at a student.'),
 ('Discrimination','Unfair treatment based on gender, religion, ethnicity, disability or background.'),
 ('Unfair Treatment','Biased or unjust treatment by staff or students.'),
 ('Teacher Misconduct','Inappropriate or unprofessional behaviour by a teacher or staff member.'),
 ('Student Misconduct','Harmful or rule-breaking behaviour by another student.'),
 ('Other Safety Concerns','Any other school safety concern not listed above.');

-- Default Super Admin  (login: admin@gmail.com / 12345678)
-- The password is stored as a bcrypt hash. Re-importing never creates a duplicate (unique email).
INSERT IGNORE INTO users (role_id, full_name, email, password, account_status, is_protected)
VALUES (3,'System Administrator','admin@gmail.com',
 '$2y$10$bBKz1MT4RjWvlO1d202PteLRPWZtegy39wMY5CGN1Jbi9X2myIz4m',
 'active',1);

-- Development Investigator (login: officer1@safevoice.local / Officer@12345)
INSERT IGNORE INTO users (role_id, full_name, username, email, phone, password, account_status)
VALUES (2,'Officer One','officer1','officer1@safevoice.local','03000000000',
 '$2y$10$r1v2DJR.NncHbXyqIy6nKuWtSzvOWAdSzSJJajPnUz1uc9MB2jG5y',
 'active');

INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
 ('site_name','SafeVoice'),
 ('contact_email','support@safevoice.local'),
 ('contact_phone','+92 300 0000000'),
 ('emergency_number','15'),
 ('allow_registration','1'),
 ('max_evidence_mb','10');
