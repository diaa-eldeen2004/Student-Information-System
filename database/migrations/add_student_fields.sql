-- Migration: Add major, minor, midterm_cardinality, and final_cardinality to students table
ALTER TABLE `students` ADD COLUMN `major` VARCHAR(100) DEFAULT NULL AFTER `admission_date`;
ALTER TABLE `students` ADD COLUMN `minor` VARCHAR(100) DEFAULT NULL AFTER `major`;
ALTER TABLE `students` ADD COLUMN `midterm_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for midterm quiz access' AFTER `minor`;
ALTER TABLE `students` ADD COLUMN `final_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for final quiz access' AFTER `midterm_cardinality`;
ALTER TABLE `students` MODIFY COLUMN `midterm_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for midterm quiz access';
ALTER TABLE `students` MODIFY COLUMN `final_cardinality` VARCHAR(255) DEFAULT NULL COMMENT 'Password for final quiz access';
CREATE INDEX `idx_major` ON `students` (`major`);
