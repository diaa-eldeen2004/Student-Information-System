-- Complete Migration: Create schedule table and remove sections table
-- Run this migration to fully migrate from sections to schedule

-- Step 1: Create schedule table
CREATE TABLE IF NOT EXISTS `schedule` (
    `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
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
    PRIMARY KEY (`schedule_id`),
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`course_id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`doctor_id`) ON DELETE CASCADE,
    INDEX `idx_course_id` (`course_id`),
    INDEX `idx_doctor_id` (`doctor_id`),
    INDEX `idx_semester` (`semester`, `academic_year`),
    INDEX `idx_day_time` (`day_of_week`, `start_time`, `end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Migrate data from sections to schedule (if sections table exists and has data)
-- Note: This assumes schedule_id will match section_id values
INSERT INTO `schedule` (
    `schedule_id`, `course_id`, `doctor_id`, `section_number`, `semester`, `academic_year`,
    `room`, `time_slot`, `day_of_week`, `start_time`, `end_time`, `capacity`, 
    `current_enrollment`, `session_type`, `status`, `created_at`, `updated_at`
)
SELECT 
    `section_id`, `course_id`, `doctor_id`, `section_number`, `semester`, `academic_year`,
    `room`, `time_slot`, `day_of_week`, `start_time`, `end_time`, `capacity`,
    `current_enrollment`, 
    COALESCE(`session_type`, 'lecture') as `session_type`,
    `status`, `created_at`, `updated_at`
FROM `sections`
WHERE NOT EXISTS (
    SELECT 1 FROM `schedule` WHERE `schedule`.`schedule_id` = `sections`.`section_id`
);

-- Step 3: Drop foreign key constraints that reference sections table
-- Note: MySQL doesn't support IF EXISTS for DROP FOREIGN KEY, so we need to check first
-- This will be handled by the PHP migration script

-- Step 4: Drop sections table
DROP TABLE IF EXISTS `sections`;

