-- Migration: Add bio column to doctors table
ALTER TABLE `doctors` ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `department`;

