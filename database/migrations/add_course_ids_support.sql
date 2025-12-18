-- Migration: Add course_ids JSON column to support multiple courses in one schedule entry
-- This allows storing multiple courses that are scheduled together with the same schedule_id

ALTER TABLE `schedule` 
ADD COLUMN `course_ids` JSON NULL AFTER `course_id`;

-- Note: course_id will remain for backward compatibility and will store the first course_id
-- course_ids will store an array of all course IDs: [1, 2, 3]

