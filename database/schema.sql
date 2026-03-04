-- FILE: database/schema.sql
-- Converclick CRM — Schema completo MySQL/MariaDB
-- Compatible con MySQL 5.7+ y MariaDB 10.3+
-- Ejecutar en orden

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- Tabla: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150)     NOT NULL,
  `email`         VARCHAR(255)     NOT NULL,
  `password`      VARCHAR(255)     NOT NULL,
  `role`          ENUM('admin','vendedor') NOT NULL DEFAULT 'vendedor',
  `is_active`     TINYINT(1)       NOT NULL DEFAULT 1,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: leads
-- ============================================================
CREATE TABLE IF NOT EXISTS `leads` (
  `id`                    INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`                  VARCHAR(200)     NOT NULL,
  `email`                 VARCHAR(255)     DEFAULT NULL,
  `phone`                 VARCHAR(30)      DEFAULT NULL,
  -- Empresa
  `company_name`          VARCHAR(200)     DEFAULT NULL,
  `company_industry`      VARCHAR(150)     DEFAULT NULL,
  `company_size`          VARCHAR(50)      DEFAULT NULL,
  -- Fuente
  `source`                ENUM('mautic_form','whatsapp','manual') NOT NULL DEFAULT 'manual',
  `source_detail`         VARCHAR(255)     DEFAULT NULL,
  `source_timestamp`      DATETIME         DEFAULT NULL,
  -- Asignación
  `assigned_to`           INT UNSIGNED     DEFAULT NULL,
  -- Calificación (BANT simplificado)
  `has_budget`            TINYINT(1)       NOT NULL DEFAULT 0,
  `has_deadline`          TINYINT(1)       NOT NULL DEFAULT 0,
  `has_active_problem`    TINYINT(1)       NOT NULL DEFAULT 0,
  `decision_maker`        TINYINT(1)       NOT NULL DEFAULT 0,
  -- Gestión comercial
  `context_notes`         TEXT             DEFAULT NULL,
  `next_step`             VARCHAR(500)     DEFAULT NULL,
  `next_step_date`        DATETIME         DEFAULT NULL,
  -- Conflicto de deduplicación
  `conflict_flag`         TINYINT(1)       NOT NULL DEFAULT 0,
  `conflict_detail`       TEXT             DEFAULT NULL,
  -- Mautic
  `mautic_contact_id`     INT UNSIGNED     DEFAULT NULL,
  -- Auditoría
  `created_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  -- Unique nullable: MySQL ignora NULL en UNIQUE
  UNIQUE KEY `uq_leads_email` (`email`),
  UNIQUE KEY `uq_leads_phone` (`phone`),
  KEY `idx_leads_source`       (`source`),
  KEY `idx_leads_assigned`     (`assigned_to`),
  KEY `idx_leads_next_step`    (`next_step_date`),
  KEY `idx_leads_created`      (`created_at`),
  CONSTRAINT `fk_leads_user` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: lead_activities (timeline / auditoría)
-- ============================================================
CREATE TABLE IF NOT EXISTS `lead_activities` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `lead_id`     INT UNSIGNED     NOT NULL,
  `user_id`     INT UNSIGNED     DEFAULT NULL,
  `type`        VARCHAR(50)      NOT NULL,
  -- Tipos sugeridos: created, updated, source_added, assigned, qualification_changed,
  --                  next_step_set, conflict_flagged, mautic_received, whatsapp_received
  `description` TEXT             NOT NULL,
  `metadata`    JSON             DEFAULT NULL,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activities_lead`    (`lead_id`),
  KEY `idx_activities_created` (`created_at`),
  CONSTRAINT `fk_activities_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_activities_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: settings (clave-valor por instalación)
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `key`         VARCHAR(100)     NOT NULL,
  `value`       TEXT             DEFAULT NULL,
  `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: integrations (configuración de integraciones)
-- ============================================================
CREATE TABLE IF NOT EXISTS `integrations` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `type`        VARCHAR(50)      NOT NULL,
  -- Tipos: mautic, uazapi
  `config`      JSON             NOT NULL DEFAULT ('{}'),
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  `updated_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_integrations_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla: integration_logs (debug de webhooks/integraciones)
-- ============================================================
CREATE TABLE IF NOT EXISTS `integration_logs` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `source`      VARCHAR(50)      NOT NULL,
  -- Valores: mautic, uazapi
  `direction`   ENUM('in','out') NOT NULL DEFAULT 'in',
  `event_type`  VARCHAR(100)     DEFAULT NULL,
  `payload`     MEDIUMTEXT       DEFAULT NULL,
  `status`      ENUM('ok','error','conflict','duplicate') NOT NULL DEFAULT 'ok',
  `message`     TEXT             DEFAULT NULL,
  `ip_address`  VARCHAR(45)      DEFAULT NULL,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_source`  (`source`),
  KEY `idx_logs_status`  (`status`),
  KEY `idx_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
