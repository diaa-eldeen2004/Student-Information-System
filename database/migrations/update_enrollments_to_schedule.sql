-- Migration: Update enrollments table to use schedule_id instead of section_id
-- This migration updates the enrollments table to reference the new schedule table

-- Step 1: Add schedule_id column if it doesn't exist
ALTER TABLE `enrollments` 
ADD COLUMN IF NOT EXISTS `schedule_id` INT(11) NULL AFTER `section_id`;

-- Step 2: Copy data from section_id to schedule_id (mapping sections to schedule)
-- Note: This assumes schedule_id values match section_id values initially
-- If you have existing data, you may need to manually map them
UPDATE `enrollments` e
INNER JOIN `schedule` s ON e.section_id = s.schedule_id
SET e.schedule_id = s.schedule_id
WHERE e.section_id IS NOT NULL;

-- Step 3: Make schedule_id NOT NULL after data migration
ALTER TABLE `enrollments` 
MODIFY COLUMN `schedule_id` INT(11) NOT NULL;

-- Step 4: Add foreign key constraint for schedule_id
ALTER TABLE `enrollments`
ADD CONSTRAINT `fk_enrollments_schedule` 
FOREIGN KEY (`schedule_id`) REFERENCES `schedule`(`schedule_id`) ON DELETE CASCADE;

-- Step 5: Update unique constraint to use schedule_id
ALTER TABLE `enrollments`
DROP INDEX IF EXISTS `unique_enrollment`,
ADD UNIQUE KEY `unique_enrollment` (`student_id`, `schedule_id`);

-- Step 6: Add index for schedule_id
ALTER TABLE `enrollments`
ADD INDEX IF NOT EXISTS `idx_schedule_id` (`schedule_id`);

-- Step 7: Drop old section_id column and foreign key (optional - uncomment when ready)
-- ALTER TABLE `enrollments`
-- DROP FOREIGN KEY IF EXISTS `enrollments_ibfk_2`,
-- DROP COLUMN `section_id`;

