-- ============================================================
-- SkillHub – Freelance Marketplace System
-- Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS skillhub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE skillhub;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    full_name    VARCHAR(120)    NOT NULL,
    email        VARCHAR(180)    NOT NULL UNIQUE,
    password     VARCHAR(255)    NOT NULL,
    role         ENUM('client','freelancer') NOT NULL DEFAULT 'client',
    avatar       VARCHAR(255)    NULL DEFAULT NULL,
    bio          TEXT            NULL DEFAULT NULL,
    is_active    TINYINT(1)      NOT NULL DEFAULT 1,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_email  (email),
    INDEX idx_role   (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: freelancer_profiles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS freelancer_profiles (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED  NOT NULL UNIQUE,
    headline        VARCHAR(200)  NULL DEFAULT NULL,
    skills          TEXT          NULL DEFAULT NULL,   -- comma-separated tags
    hourly_rate     DECIMAL(8,2)  NULL DEFAULT NULL,
    availability    ENUM('available','busy','unavailable') NOT NULL DEFAULT 'available',
    portfolio_url   VARCHAR(255)  NULL DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_fp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: client_profiles
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS client_profiles (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED  NOT NULL UNIQUE,
    company_name    VARCHAR(150)  NULL DEFAULT NULL,
    website         VARCHAR(255)  NULL DEFAULT NULL,
    industry        VARCHAR(100)  NULL DEFAULT NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_cp_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: sessions_log  (optional audit trail)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions_log (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED  NOT NULL,
    ip_address  VARCHAR(45)   NOT NULL,
    user_agent  VARCHAR(255)  NULL DEFAULT NULL,
    action      ENUM('login','logout') NOT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_sl_user (user_id),
    CONSTRAINT fk_sl_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Seed: demo accounts  (password = "Password1!")
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password, role) VALUES
(
    'Alice Johnson',
    'client@skillhub.test',
    '$2y$12$YKpGNzM8HvJQlX5lPO.FuOoW0CGjsZN3K1tU7XQW9eTmTJrPJeOzC',
    'client'
),
(
    'Bob Martinez',
    'freelancer@skillhub.test',
    '$2y$12$YKpGNzM8HvJQlX5lPO.FuOoW0CGjsZN3K1tU7XQW9eTmTJrPJeOzC',
    'freelancer'
);

INSERT INTO client_profiles (user_id, company_name, industry) VALUES
(1, 'Alice Co.', 'Technology');

INSERT INTO freelancer_profiles (user_id, headline, skills, hourly_rate, availability) VALUES
(2, 'Full-Stack Developer & UI Designer', 'PHP,JavaScript,MySQL,CSS', 45.00, 'available');
