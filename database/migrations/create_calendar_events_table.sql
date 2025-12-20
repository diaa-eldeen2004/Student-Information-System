-- Migration: Create Calendar Events Table
-- This table stores calendar events, exams, and other scheduled activities

CREATE TABLE IF NOT EXISTS `calendar_events` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `event_type` ENUM('exam', 'meeting', 'holiday', 'deadline', 'other') NOT NULL DEFAULT 'other',
    `status` ENUM('active', 'cancelled', 'completed') NOT NULL DEFAULT 'active',
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `course_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_start_date` (`start_date`),
    INDEX `idx_department` (`department`),
    INDEX `idx_course_id` (`course_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

