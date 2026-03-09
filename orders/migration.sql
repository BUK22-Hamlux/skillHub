-- ============================================================
-- SkillHub – Phase 3: Marketplace + Order Management
-- Run this after skillhub.sql and services/migration.sql
-- ============================================================

USE skillhub;

CREATE TABLE IF NOT EXISTS orders (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    service_id      INT UNSIGNED    NOT NULL,
    client_id       INT UNSIGNED    NOT NULL,
    freelancer_id   INT UNSIGNED    NOT NULL,

    -- Snapshot of service at time of order (price may change later)
    service_title   VARCHAR(120)    NOT NULL,
    amount          DECIMAL(10,2)   NOT NULL,
    delivery_days   TINYINT UNSIGNED NOT NULL,

    requirements    TEXT            NULL DEFAULT NULL,   -- client notes/brief

    status          ENUM(
                        'pending',      -- placed, awaiting freelancer acceptance
                        'accepted',     -- freelancer accepted
                        'in_progress',  -- work started
                        'completed',    -- freelancer marked done
                        'cancelled'     -- either party cancelled
                    ) NOT NULL DEFAULT 'pending',

    -- Timestamps per status change
    accepted_at     DATETIME        NULL DEFAULT NULL,
    completed_at    DATETIME        NULL DEFAULT NULL,
    cancelled_at    DATETIME        NULL DEFAULT NULL,

    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                             ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_ord_client     (client_id),
    INDEX idx_ord_freelancer (freelancer_id),
    INDEX idx_ord_service    (service_id),
    INDEX idx_ord_status     (status),

    CONSTRAINT fk_ord_service    FOREIGN KEY (service_id)    REFERENCES services(id)  ON DELETE RESTRICT,
    CONSTRAINT fk_ord_client     FOREIGN KEY (client_id)     REFERENCES users(id)     ON DELETE RESTRICT,
    CONSTRAINT fk_ord_freelancer FOREIGN KEY (freelancer_id) REFERENCES users(id)     ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
