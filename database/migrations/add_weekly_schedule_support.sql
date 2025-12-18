-- Migration: Add weekly schedule support to schedule table
-- This allows storing a whole week's schedule in one entry

ALTER TABLE `schedule` 
ADD COLUMN `weekly_schedule` JSON NULL AFTER `day_of_week`,
ADD COLUMN `is_weekly` TINYINT(1) DEFAULT 0 AFTER `weekly_schedule`;

-- Update index to support weekly schedules
ALTER TABLE `schedule` 
DROP INDEX IF EXISTS `idx_day_time`,
ADD INDEX `idx_is_weekly` (`is_weekly`);

-- Example weekly_schedule JSON structure:
-- {
--   "Monday": {"start_time": "09:00:00", "end_time": "10:30:00"},
--   "Wednesday": {"start_time": "09:00:00", "end_time": "10:30:00"},
--   "Friday": {"start_time": "09:00:00", "end_time": "10:30:00"}
-- }

