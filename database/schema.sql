-- Task Management System Database Schema
-- Created: October 12, 2025

CREATE DATABASE IF NOT EXISTS taskmanager 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE taskmanager;

-- Table for storing task recurrency patterns
CREATE TABLE recurrency_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default recurrency types
INSERT INTO recurrency_types (name, description) VALUES
('none', 'One-time task, no recurrence'),
('daily', 'Repeats every day'),
('weekly', 'Repeats every week'),
('monthly', 'Repeats every month'),
('yearly', 'Repeats every year'),
('custom', 'Custom recurrence pattern');

-- Main tasks table
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT NOT NULL,
    estimated_duration INT DEFAULT NULL COMMENT 'Duration in minutes',
    priority INT DEFAULT 50 CHECK (priority >= 1 AND priority <= 100) COMMENT 'Priority from 1 (urgent) to 100 (low)',
    deadline DATETIME DEFAULT NULL COMMENT 'Optional deadline for the task',
    planned_date DATETIME DEFAULT NULL COMMENT 'Planned date to work on the task',
    
    -- Recurrency information
    recurrency_type_id INT DEFAULT 1,
    recurrency_interval INT DEFAULT 1 COMMENT 'For custom recurrency (e.g., every 2 weeks)',
    recurrency_end_date DATETIME DEFAULT NULL COMMENT 'When recurrency should stop',
    
    -- Status and metadata
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'pending',
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    FOREIGN KEY (recurrency_type_id) REFERENCES recurrency_types(id) ON DELETE SET NULL,
    
    -- Indexes for better performance
    INDEX idx_priority (priority),
    INDEX idx_deadline (deadline),
    INDEX idx_planned_date (planned_date),
    INDEX idx_status (status),
    INDEX idx_recurrency_type (recurrency_type_id),
    INDEX idx_created_at (created_at)
);

-- Table for storing task instances (for recurring tasks)
CREATE TABLE task_instances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_task_id INT NOT NULL,
    instance_date DATETIME NOT NULL COMMENT 'Specific date for this instance',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'skipped') DEFAULT 'pending',
    actual_duration INT DEFAULT NULL COMMENT 'Actual time spent in minutes',
    notes TEXT DEFAULT NULL COMMENT 'Notes specific to this instance',
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (parent_task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    
    -- Ensure no duplicate instances for the same task on the same date
    UNIQUE KEY unique_task_instance (parent_task_id, instance_date),
    
    INDEX idx_instance_date (instance_date),
    INDEX idx_status (status),
    INDEX idx_parent_task (parent_task_id)
);

-- Table for task categories/tags (optional enhancement)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#007bff' COMMENT 'Hex color code for UI',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Junction table for task-category relationship (many-to-many)
CREATE TABLE task_categories (
    task_id INT,
    category_id INT,
    PRIMARY KEY (task_id, category_id),
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Table for task attachments/files (optional enhancement)
CREATE TABLE task_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    upload_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id)
);

-- Table for task comments/notes (optional enhancement)
CREATE TABLE task_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    INDEX idx_task_id (task_id),
    INDEX idx_created_at (created_at)
);

-- Views for easier querying

-- View for active tasks with recurrency information
CREATE VIEW active_tasks AS
SELECT 
    t.id,
    t.description,
    t.estimated_duration,
    t.priority,
    t.deadline,
    t.planned_date,
    t.status,
    rt.name as recurrency_type,
    t.recurrency_interval,
    t.recurrency_end_date,
    t.created_at,
    t.updated_at,
    CASE 
        WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 'overdue'
        WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 'due_soon'
        ELSE 'normal'
    END as urgency_status
FROM tasks t
LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
WHERE t.status IN ('pending', 'in_progress');

-- View for upcoming tasks (next 7 days)
CREATE VIEW upcoming_tasks AS
SELECT 
    t.id,
    t.description,
    t.priority,
    t.planned_date,
    t.deadline,
    t.estimated_duration,
    rt.name as recurrency_type
FROM tasks t
LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
WHERE (
    (t.planned_date IS NOT NULL AND t.planned_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY))
    OR 
    (t.deadline IS NOT NULL AND t.deadline BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY))
)
AND t.status IN ('pending', 'in_progress')
ORDER BY 
    CASE WHEN t.deadline IS NOT NULL THEN t.deadline ELSE t.planned_date END ASC,
    t.priority ASC;

-- View for overdue tasks
CREATE VIEW overdue_tasks AS
SELECT 
    t.id,
    t.description,
    t.priority,
    t.deadline,
    t.planned_date,
    t.estimated_duration,
    DATEDIFF(NOW(), COALESCE(t.deadline, t.planned_date)) as days_overdue
FROM tasks t
WHERE (
    (t.deadline IS NOT NULL AND t.deadline < NOW())
    OR 
    (t.deadline IS NULL AND t.planned_date IS NOT NULL AND t.planned_date < NOW())
)
AND t.status IN ('pending', 'in_progress')
ORDER BY days_overdue DESC, t.priority ASC;

-- Stored procedure for creating recurring task instances
DELIMITER //

CREATE PROCEDURE GenerateRecurringInstances(
    IN start_date DATE,
    IN end_date DATE
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE task_id INT;
    DECLARE recurrency_type VARCHAR(50);
    DECLARE recurrency_interval INT;
    DECLARE task_planned_date DATETIME;
    DECLARE recurrency_end_date DATETIME;
    
    DECLARE task_cursor CURSOR FOR 
        SELECT t.id, rt.name, t.recurrency_interval, t.planned_date, t.recurrency_end_date
        FROM tasks t
        JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
        WHERE rt.name != 'none' AND t.status = 'pending';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN task_cursor;
    
    task_loop: LOOP
        FETCH task_cursor INTO task_id, recurrency_type, recurrency_interval, task_planned_date, recurrency_end_date;
        
        IF done THEN
            LEAVE task_loop;
        END IF;
        
        -- Generate instances based on recurrency type
        CASE recurrency_type
            WHEN 'daily' THEN
                CALL GenerateDailyInstances(task_id, start_date, end_date, recurrency_interval, recurrency_end_date);
            WHEN 'weekly' THEN
                CALL GenerateWeeklyInstances(task_id, start_date, end_date, recurrency_interval, recurrency_end_date);
            WHEN 'monthly' THEN
                CALL GenerateMonthlyInstances(task_id, start_date, end_date, recurrency_interval, recurrency_end_date);
            WHEN 'yearly' THEN
                CALL GenerateYearlyInstances(task_id, start_date, end_date, recurrency_interval, recurrency_end_date);
        END CASE;
        
    END LOOP;
    
    CLOSE task_cursor;
END //

DELIMITER ;

-- Sample data for testing
INSERT INTO categories (name, color, description) VALUES
('Work', '#dc3545', 'Work-related tasks'),
('Personal', '#28a745', 'Personal tasks and errands'),
('Health', '#17a2b8', 'Health and fitness related'),
('Learning', '#ffc107', 'Educational and skill development'),
('Maintenance', '#6c757d', 'Regular maintenance tasks');

-- Sample tasks
INSERT INTO tasks (description, estimated_duration, priority, deadline, planned_date, recurrency_type_id) VALUES
('Complete project proposal', 120, 10, '2025-10-15 17:00:00', '2025-10-14 09:00:00', 1),
('Daily exercise routine', 45, 30, NULL, '2025-10-13 07:00:00', 2),
('Weekly team meeting', 60, 40, NULL, '2025-10-14 10:00:00', 3),
('Monthly budget review', 90, 25, '2025-10-31 23:59:59', '2025-10-28 14:00:00', 4),
('Buy groceries', 30, 50, NULL, '2025-10-13 16:00:00', 1),
('Code review for new feature', 90, 20, '2025-10-16 12:00:00', '2025-10-15 10:00:00', 1);