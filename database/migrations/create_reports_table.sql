-- Migration: Create Reports Table
-- This table stores generated reports and analytics

CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `type` ENUM('academic', 'attendance', 'financial', 'system', 'other') NOT NULL DEFAULT 'other',
    `period` VARCHAR(50) DEFAULT 'on_demand',
    `status` ENUM('completed', 'generating', 'scheduled', 'failed') NOT NULL DEFAULT 'generating',
    `file_path` VARCHAR(500) DEFAULT NULL,
    `parameters` TEXT DEFAULT NULL COMMENT 'JSON string of report parameters',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_period` (`period`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

