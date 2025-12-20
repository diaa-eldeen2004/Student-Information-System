-- Migration: Update Reports Table to Support File Storage
-- This migration adds a file_data column to store actual file content instead of just file paths

-- Add file_data column to store file content (BLOB)
ALTER TABLE `reports` 
ADD COLUMN `file_data` LONGBLOB DEFAULT NULL COMMENT 'Stored file content' AFTER `file_path`,
ADD COLUMN `file_name` VARCHAR(255) DEFAULT NULL COMMENT 'Original file name' AFTER `file_data`,
ADD COLUMN `file_type` VARCHAR(100) DEFAULT NULL COMMENT 'MIME type of the file' AFTER `file_name`,
ADD COLUMN `file_size` INT(11) DEFAULT NULL COMMENT 'File size in bytes' AFTER `file_type`;

-- Add index for file_name for faster lookups
ALTER TABLE `reports` 
ADD INDEX `idx_file_name` (`file_name`);

