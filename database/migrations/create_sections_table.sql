-- Migration: Create Sections Table for Schedule Management
-- This table stores semester schedule entries (sections) for courses

CREATE TABLE IF NOT EXISTS `sections` (
    `section_id` INT(11) NOT NULL AUTO_INCREMENT,
    `course_id` INT(11) NOT NULL,
    `doctor_id` INT(11) NOT NULL,
    `section_number` VARCHAR(10) NOT NULL,
    `semester` VARCHAR(20) NOT NULL,
    `academic_year` VARCHAR(10) NOT NULL,
    `room` VARCHAR(50) DEFAULT NULL,
    `time_slot` VARCHAR(100) DEFAULT NULL,
    `day_of_week` VARCHAR(20) DEFAULT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `capacity` INT(11) NOT NULL DEFAULT 30,
    `current_enrollment` INT(11) DEFAULT 0,
    `session_type` VARCHAR(20) DEFAULT 'lecture',
    `status` ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`section_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE CASCADE,
    INDEX `idx_course_id` (`course_id`),
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_semester` (`semester`, `academic_year`),
    INDEX `idx_day_time` (`day_of_week`, `start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

