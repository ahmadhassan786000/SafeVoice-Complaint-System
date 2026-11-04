-- SafeVoice schema (MySQL 5.7+/MariaDB 10.4+, InnoDB, utf8mb4)
CREATE DATABASE IF NOT EXISTS safevoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE safevoice;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS system_settings, complaint_assignments,
  complaint_status_history, statements, investigations, notifications, complaint_messages,
  complaint_evidence, complaint_student_details, complaints, category_assignments,
  complaint_categories, users, roles;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE roles (
  id TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_code VARCHAR(20) NOT NULL UNIQUE,      -- STUDENT | INVESTIGATOR | SUPER_ADMIN
  role_name VARCHAR(50) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  role_id TINYINT UNSIGNED NOT NULL,
  student_id VARCHAR(20) NULL UNIQUE,         -- only for students, e.g. SV-2026-00001
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(25) NULL,
  password VARCHAR(255) NOT NULL,             -- password_hash()
  profile_image VARCHAR(255) NULL,
  account_status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  is_protected TINYINT(1) NOT NULL DEFAULT 0, -- initial Super Admin cannot be deleted
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role_id),
  INDEX idx_users_status (account_status),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE complaint_categories (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(500) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE category_assignments (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  officer_user_id INT UNSIGNED NOT NULL,
  assigned_by_admin_id INT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_category (category_id),       -- ONE active officer per category
  INDEX idx_ca_officer (officer_user_id),
  CONSTRAINT fk_ca_category FOREIGN KEY (category_id) REFERENCES complaint_categories(id),
  CONSTRAINT fk_ca_officer  FOREIGN KEY (officer_user_id) REFERENCES users(id),
  CONSTRAINT fk_ca_admin    FOREIGN KEY (assigned_by_admin_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE complaints (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_code VARCHAR(25) NOT NULL UNIQUE,  -- e.g. SV-C-2026-000001
  student_user_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  reported_person_type VARCHAR(50) NOT NULL,
  reported_person_name VARCHAR(100) NULL,
  incident_date DATE NOT NULL,
  incident_location VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  witness_information TEXT NULL,
  status ENUM('Pending','Under Review','Under Investigation','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  assigned_officer_id INT UNSIGNED NULL,       -- NULL = Unassigned (internal only)
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_c_student (student_user_id),
  INDEX idx_c_officer (assigned_officer_id),
  INDEX idx_c_category (category_id),
  INDEX idx_c_status (status),
  CONSTRAINT fk_c_student  FOREIGN KEY (student_user_id) REFERENCES users(id),
  CONSTRAINT fk_c_category FOREIGN KEY (category_id) REFERENCES complaint_categories(id),
  CONSTRAINT fk_c_officer  FOREIGN KEY (assigned_officer_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE complaint_student_details (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL UNIQUE,
  school_name VARCHAR(150) NOT NULL,
  school_roll_number VARCHAR(50) NOT NULL,
  class_name VARCHAR(50) NOT NULL,
  gender ENUM('Male','Female','Prefer not to say') NOT NULL,
  INDEX idx_csd_school (school_name),
  CONSTRAINT fk_csd_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE complaint_evidence (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL,
  uploaded_by_user_id INT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,             -- original name (display only)
  file_path VARCHAR(255) NOT NULL,             -- random stored name
  file_type VARCHAR(100) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ce_complaint (complaint_id),
  CONSTRAINT fk_ce_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_ce_user FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE complaint_messages (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL,
  sender_user_id INT UNSIGNED NOT NULL,        -- real internal sender
  sender_role ENUM('STUDENT','INVESTIGATOR') NOT NULL,
  message TEXT NOT NULL,
  is_info_request TINYINT(1) NOT NULL DEFAULT 0,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cm_complaint (complaint_id, created_at),
  CONSTRAINT fk_cm_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_cm_sender FOREIGN KEY (sender_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (                   -- students only
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  recipient_user_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(500) NOT NULL,
  notification_type VARCHAR(40) NOT NULL,
  related_complaint_id INT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_n_recipient (recipient_user_id, is_read),
  CONSTRAINT fk_n_user FOREIGN KEY (recipient_user_id) REFERENCES users(id),
  CONSTRAINT fk_n_complaint FOREIGN KEY (related_complaint_id) REFERENCES complaints(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE investigations (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL UNIQUE,
  officer_user_id INT UNSIGNED NOT NULL,
  investigation_summary TEXT NULL,
  investigation_notes TEXT NULL,
  findings ENUM('Substantiated','Not Substantiated','Inconclusive','Referred') NULL, -- separate from workflow status
  final_report TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_inv_officer FOREIGN KEY (officer_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE statements (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  investigation_id INT UNSIGNED NOT NULL,
  statement_details TEXT NOT NULL,
  recorded_by_user_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_st_inv FOREIGN KEY (investigation_id) REFERENCES investigations(id) ON DELETE CASCADE,
  CONSTRAINT fk_st_user FOREIGN KEY (recorded_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE complaint_status_history (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL,
  old_status VARCHAR(30) NULL,
  new_status VARCHAR(30) NOT NULL,
  changed_by_user_id INT UNSIGNED NOT NULL,
  public_description VARCHAR(255) NOT NULL,    -- safe to show to students
  internal_notes TEXT NULL,                    -- NEVER shown to students
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sh_complaint (complaint_id, created_at),
  CONSTRAINT fk_sh_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_sh_user FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE complaint_assignments (
  id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  complaint_id INT UNSIGNED NOT NULL,
  previous_officer_id INT UNSIGNED NULL,
  new_officer_id INT UNSIGNED NULL,
  assigned_by_admin_id INT UNSIGNED NULL,      -- NULL = automatic category routing
  reassignment_reason VARCHAR(255) NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_asg_complaint (complaint_id),
  CONSTRAINT fk_asg_complaint FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
  CONSTRAINT fk_asg_prev FOREIGN KEY (previous_officer_id) REFERENCES users(id),
  CONSTRAINT fk_asg_new FOREIGN KEY (new_officer_id) REFERENCES users(id),
  CONSTRAINT fk_asg_admin FOREIGN KEY (assigned_by_admin_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Extra table: System Settings page
CREATE TABLE system_settings (
  setting_key VARCHAR(60) PRIMARY KEY,
  setting_value VARCHAR(500) NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
