-- Blog Management System Database Schema

CREATE DATABASE IF NOT EXISTS blog_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE blog_system;

-- Drop tables if they exist to start clean during setup
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS admin_users;
DROP TABLE IF EXISTS blog_posts;

-- Blog Posts Table
CREATE TABLE blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    short_description VARCHAR(500) NOT NULL,
    category VARCHAR(100) DEFAULT NULL,
    image_path VARCHAR(500) DEFAULT NULL,
    created_date DATETIME NOT NULL,
    INDEX idx_created_date (created_date),
    INDEX idx_category (category),
    FULLTEXT INDEX idx_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin Users Table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts Table (for rate limiting)
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    attempt_time DATETIME NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    INDEX idx_username_time (username, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed Default Admin User: admin / Password@123
INSERT INTO admin_users (username, password_hash, created_at)
VALUES ('admin', '$2b$12$YcFpwpTAILR0e7HKzpf07eUWbbPDCq9bnzBLE.JFybvSUJ0JQGjPy', CURRENT_TIMESTAMP);
