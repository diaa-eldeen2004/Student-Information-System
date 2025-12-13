# Fix IT Officer Duplicate Key Error

## Problem
When creating the second IT officer, you get this error:
```
Failed to create IT officer: IT Officer creation failed (PDO): SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '0' for key 'PRIMARY'
```

## Root Cause
The `it_officers` table's AUTO_INCREMENT value is set incorrectly, causing MySQL to try inserting `it_id = 0` instead of auto-generating a new ID.

## Solution

### Option 1: Quick SQL Fix (Recommended)
Run this SQL command in your MySQL database:

```sql
-- Get the maximum it_id and set AUTO_INCREMENT to max + 1
SET @max_id = (SELECT COALESCE(MAX(it_id), 0) FROM it_officers);
SET @sql = CONCAT('ALTER TABLE it_officers AUTO_INCREMENT = ', @max_id + 1);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
```

Or manually:
1. Check the current max ID:
   ```sql
   SELECT MAX(it_id) FROM it_officers;
   ```
2. Set AUTO_INCREMENT to max + 1 (replace X with max_id + 1):
   ```sql
   ALTER TABLE it_officers AUTO_INCREMENT = X;
   ```

### Option 2: Use the Fix Script
Run the SQL script located at:
```
database/fix_it_officers_table.sql
```

### Option 3: Automatic Fix (After Code Update)
The code has been updated to automatically detect and fix this issue. If you encounter the error again:
1. The system will automatically try to fix AUTO_INCREMENT
2. You'll see a warning message asking you to try again
3. Simply retry creating the IT officer

## Verification
After running the fix, verify it worked:
```sql
SHOW TABLE STATUS LIKE 'it_officers';
```
Check that the `Auto_increment` value is greater than the maximum `it_id` in the table.

## Prevention
The updated code now:
- Better handles AUTO_INCREMENT issues
- Automatically detects duplicate key errors
- Attempts to fix AUTO_INCREMENT when the error occurs
- Provides better error messages

## Files Modified
1. `app/models/ItOfficer.php` - Added `fixAutoIncrement()` method and improved error handling
2. `app/controllers/Admin.php` - Added automatic AUTO_INCREMENT fix detection
3. `database/fix_it_officers_table.sql` - Manual fix script

## Next Steps
1. Run the SQL fix (Option 1) to fix the current issue
2. Try creating a new IT officer - it should work now
3. The code updates will prevent this issue from happening again

