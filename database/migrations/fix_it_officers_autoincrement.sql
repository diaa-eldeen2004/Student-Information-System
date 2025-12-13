-- Fix AUTO_INCREMENT for it_officers table
-- This migration fixes the issue where it_id AUTO_INCREMENT is not working correctly
-- 
-- NOTE: This sets AUTO_INCREMENT to 1000 as a safe starting point
-- The direct fix method (Fix AUTO_INCREMENT Now button) is preferred as it calculates max + 1
-- If you have more than 1000 IT officers, use the direct fix method instead

-- Set AUTO_INCREMENT to a safe value (1000)
-- This ensures new records will get unique IDs
-- For a dynamic fix, use the "Fix AUTO_INCREMENT Now" button which calculates max(it_id) + 1
ALTER TABLE it_officers AUTO_INCREMENT = 1000

