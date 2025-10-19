-- Migration: Add title field to tasks table
-- This script adds a title field and reorganizes the task structure

USE taskmanager;

-- Add title column to tasks table
ALTER TABLE tasks 
ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT '' 
AFTER id;

-- Update existing tasks to have a title based on description
-- Take first 100 characters of description as title
UPDATE tasks 
SET title = CASE 
    WHEN LENGTH(description) <= 100 THEN description
    ELSE CONCAT(LEFT(description, 97), '...')
END
WHERE title = '';

-- Make title required (remove default after data migration)
ALTER TABLE tasks 
MODIFY COLUMN title VARCHAR(255) NOT NULL;

-- Update description comment to reflect new purpose
ALTER TABLE tasks 
MODIFY COLUMN description TEXT COMMENT 'Detailed notes and explanation for the task';

-- Add index for title for faster searching
ALTER TABLE tasks 
ADD INDEX idx_title (title);

-- Show the updated structure
DESCRIBE tasks;