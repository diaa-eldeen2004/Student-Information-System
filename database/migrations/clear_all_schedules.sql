-- Clear all schedules from the schedule table
-- WARNING: This will delete ALL schedule records permanently

-- Option 1: TRUNCATE (faster, resets AUTO_INCREMENT)
TRUNCATE TABLE `schedule`;

-- Option 2: DELETE (if you want to keep AUTO_INCREMENT value)
-- DELETE FROM `schedule`;

-- Verify the table is empty
SELECT COUNT(*) as remaining_schedules FROM `schedule`;

