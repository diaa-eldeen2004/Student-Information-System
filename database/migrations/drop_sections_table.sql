-- Migration: Drop sections table and migrate to schedule table
-- This migration removes the old sections table after migrating to schedule

-- Step 1: Drop foreign key constraints from enrollments that reference sections
ALTER TABLE `enrollments` 
DROP FOREIGN KEY IF EXISTS `enrollments_ibfk_2`;

-- Step 2: Drop foreign key constraints from enrollment_requests that reference sections
ALTER TABLE `enrollment_requests`
DROP FOREIGN KEY IF EXISTS `enrollment_requests_ibfk_2`;

-- Step 3: Drop foreign key constraints from assignments that reference sections (if exists)
ALTER TABLE `assignments`
DROP FOREIGN KEY IF EXISTS `assignments_ibfk_2`;

-- Step 4: Drop the sections table
DROP TABLE IF EXISTS `sections`;

