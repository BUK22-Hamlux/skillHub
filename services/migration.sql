-- ============================================================
-- SkillHub – Phase 2: Service Management
-- Migration: add to existing skillhub database
-- ============================================================

USE skillhub;

-- ------------------------------------------------------------
-- Table: service_categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS service_categories (
    id        TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name      VARCHAR(80)      NOT NULL UNIQUE,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO service_categories (name) VALUES
  ('Web Development'),
  ('Mobile Development'),
  ('UI / UX Design'),
  ('Graphic Design'),
  ('Copywriting & Content'),
  ('SEO & Digital Marketing'),
  ('Video & Animation'),
  ('Data & Analytics'),
  ('DevOps & Cloud'),
  ('Other');

-- ------------------------------------------------------------
-- Table: services
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS services (
    id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    freelancer_id   INT UNSIGNED      NOT NULL,
    category_id     TINYINT UNSIGNED  NULL     DEFAULT NULL,
    title           VARCHAR(120)      NOT NULL,
    description     TEXT              NOT NULL,
    price           DECIMAL(10,2)     NOT NULL,
    delivery_days   TINYINT UNSIGNED  NOT NULL DEFAULT 1,
    image_path      VARCHAR(255)      NULL     DEFAULT NULL,
    status          ENUM('active','paused','deleted') NOT NULL DEFAULT 'active',
    created_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP
                                               ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_svc_freelancer (freelancer_id),
    INDEX idx_svc_status     (status),
    CONSTRAINT fk_svc_freelancer FOREIGN KEY (freelancer_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_svc_category FOREIGN KEY (category_id)
        REFERENCES service_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
