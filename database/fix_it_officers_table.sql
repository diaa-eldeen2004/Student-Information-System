-- Quick fix for it_officers AUTO_INCREMENT issue
-- Run this SQL script to fix the duplicate primary key error

-- Step 1: Check current AUTO_INCREMENT value
-- SHOW TABLE STATUS LIKE 'it_officers';

-- Step 2: Get the maximum it_id currently in the table
SELECT MAX(it_id) as max_id FROM it_officers;

-- Step 3: Fix AUTO_INCREMENT to be higher than the max ID
-- Replace 'X' with the max_id value from Step 2 + 1
-- Example: If max_id is 1, set AUTO_INCREMENT to 2
ALTER TABLE it_officers AUTO_INCREMENT = 2;

-- OR: Automatically set it to max + 1
SET @max_id = (SELECT COALESCE(MAX(it_id), 0) FROM it_officers);
SET @sql = CONCAT('ALTER TABLE it_officers AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 4: Verify the fix
SHOW TABLE STATUS LIKE 'it_officers';

-- Step 5: Test by trying to insert a new record (should work now)
-- INSERT INTO it_officers (user_id) VALUES (1); -- Replace 1 with a valid user_id

