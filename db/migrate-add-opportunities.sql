-- Migration: add opportunities table for managing jobs, skills, scholarships and bursaries.
-- Run this on an existing database that already has the other tables from schema.sql.
CREATE TABLE IF NOT EXISTS opportunities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('job','skill','scholarship','bursary') NOT NULL,
  title VARCHAR(120) NOT NULL,
  title_sw VARCHAR(120) NOT NULL DEFAULT '',
  description TEXT NOT NULL,
  description_sw TEXT NOT NULL DEFAULT '',
  provider VARCHAR(120) NOT NULL DEFAULT '',
  location VARCHAR(80) NOT NULL DEFAULT '',
  deadline DATE NULL,
  link VARCHAR(500) NOT NULL DEFAULT '',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_active_type (active, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
