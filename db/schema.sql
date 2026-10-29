CREATE TABLE cases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(12) NOT NULL UNIQUE,
  category VARCHAR(40) NOT NULL, constituency VARCHAR(40) NOT NULL, ward VARCHAR(40) NOT NULL,
  description TEXT NOT NULL,
  urgency ENUM('low','moderate','urgent') NOT NULL DEFAULT 'moderate',
  status ENUM('submitted','under_review','verified','intervention_assigned','active_support','monitoring','resolved') NOT NULL DEFAULT 'submitted',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE contacts (
  case_id BIGINT UNSIGNED PRIMARY KEY, method ENUM('phone','email') NOT NULL,
  value_enc TEXT NOT NULL, safe_to_call TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL,
  author ENUM('staff','reporter') NOT NULL, body_enc TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Rate limits: hashed IP (salt changes daily), kept in memory only, wiped on restart.
CREATE TABLE throttle (
  h CHAR(64) NOT NULL, b VARCHAR(12) NOT NULL, at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY (h,b)
) ENGINE=MEMORY;
ALTER TABLE cases ADD COLUMN kind ENUM('concern','support') NOT NULL DEFAULT 'concern' AFTER code;
-- Staff: created by hand with bin/create-staff.php. No public sign-up. Two-factor codes are required.
CREATE TABLE staff (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(40) NOT NULL UNIQUE, pass_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','coordinator','liaison') NOT NULL, constituency VARCHAR(40) NULL, totp_enc TEXT NOT NULL,
  last_totp BIGINT NOT NULL DEFAULT 0, active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Who did what to which case. No IP addresses are stored.
CREATE TABLE audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, staff_id INT UNSIGNED NULL, action VARCHAR(40) NOT NULL,
  case_id BIGINT UNSIGNED NULL, note VARCHAR(200) NULL, at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE mentors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, focus VARCHAR(80) NOT NULL, constituency VARCHAR(40) NOT NULL,
  contact_enc TEXT NULL, active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Referrals need two different people: one requests, another approves. An admin then records the referral.
CREATE TABLE escalations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, case_id BIGINT UNSIGNED NOT NULL, agency VARCHAR(60) NOT NULL, reason_enc TEXT NOT NULL,
  status ENUM('requested','approved','rejected','referred') NOT NULL DEFAULT 'requested',
  requested_by INT UNSIGNED NOT NULL, decided_by INT UNSIGNED NULL, reference VARCHAR(80) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
