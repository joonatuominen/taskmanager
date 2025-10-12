-- Helper stored procedures for generating recurring task instances

DELIMITER //

-- Generate daily recurring instances
CREATE PROCEDURE GenerateDailyInstances(
    IN p_task_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_interval INT,
    IN p_recurrency_end_date DATETIME
)
BEGIN
    DECLARE current_date DATE;
    DECLARE max_end_date DATE;
    
    SET current_date = p_start_date;
    SET max_end_date = LEAST(p_end_date, COALESCE(DATE(p_recurrency_end_date), p_end_date));
    
    WHILE current_date <= max_end_date DO
        INSERT IGNORE INTO task_instances (parent_task_id, instance_date)
        VALUES (p_task_id, current_date);
        
        SET current_date = DATE_ADD(current_date, INTERVAL p_interval DAY);
    END WHILE;
END //

-- Generate weekly recurring instances
CREATE PROCEDURE GenerateWeeklyInstances(
    IN p_task_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_interval INT,
    IN p_recurrency_end_date DATETIME
)
BEGIN
    DECLARE current_date DATE;
    DECLARE max_end_date DATE;
    
    SET current_date = p_start_date;
    SET max_end_date = LEAST(p_end_date, COALESCE(DATE(p_recurrency_end_date), p_end_date));
    
    WHILE current_date <= max_end_date DO
        INSERT IGNORE INTO task_instances (parent_task_id, instance_date)
        VALUES (p_task_id, current_date);
        
        SET current_date = DATE_ADD(current_date, INTERVAL (p_interval * 7) DAY);
    END WHILE;
END //

-- Generate monthly recurring instances
CREATE PROCEDURE GenerateMonthlyInstances(
    IN p_task_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_interval INT,
    IN p_recurrency_end_date DATETIME
)
BEGIN
    DECLARE current_date DATE;
    DECLARE max_end_date DATE;
    
    SET current_date = p_start_date;
    SET max_end_date = LEAST(p_end_date, COALESCE(DATE(p_recurrency_end_date), p_end_date));
    
    WHILE current_date <= max_end_date DO
        INSERT IGNORE INTO task_instances (parent_task_id, instance_date)
        VALUES (p_task_id, current_date);
        
        SET current_date = DATE_ADD(current_date, INTERVAL p_interval MONTH);
    END WHILE;
END //

-- Generate yearly recurring instances
CREATE PROCEDURE GenerateYearlyInstances(
    IN p_task_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_interval INT,
    IN p_recurrency_end_date DATETIME
)
BEGIN
    DECLARE current_date DATE;
    DECLARE max_end_date DATE;
    
    SET current_date = p_start_date;
    SET max_end_date = LEAST(p_end_date, COALESCE(DATE(p_recurrency_end_date), p_end_date));
    
    WHILE current_date <= max_end_date DO
        INSERT IGNORE INTO task_instances (parent_task_id, instance_date)
        VALUES (p_task_id, current_date);
        
        SET current_date = DATE_ADD(current_date, INTERVAL p_interval YEAR);
    END WHILE;
END //

-- Function to get task priority label
CREATE FUNCTION GetPriorityLabel(priority_value INT) 
RETURNS VARCHAR(20)
READS SQL DATA
DETERMINISTIC
BEGIN
    CASE 
        WHEN priority_value <= 10 THEN RETURN 'Critical';
        WHEN priority_value <= 25 THEN RETURN 'High';
        WHEN priority_value <= 50 THEN RETURN 'Medium';
        WHEN priority_value <= 75 THEN RETURN 'Low';
        ELSE RETURN 'Very Low';
    END CASE;
END //

-- Function to calculate task urgency score
CREATE FUNCTION CalculateUrgencyScore(
    priority_value INT,
    deadline_date DATETIME,
    planned_date DATETIME
) 
RETURNS DECIMAL(5,2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE urgency_score DECIMAL(5,2) DEFAULT 0;
    DECLARE days_to_deadline INT;
    DECLARE days_to_planned INT;
    
    -- Base score from priority (inverted - lower priority number = higher urgency)
    SET urgency_score = (101 - priority_value);
    
    -- Add urgency based on deadline
    IF deadline_date IS NOT NULL THEN
        SET days_to_deadline = DATEDIFF(deadline_date, NOW());
        CASE 
            WHEN days_to_deadline < 0 THEN SET urgency_score = urgency_score + 50; -- Overdue
            WHEN days_to_deadline = 0 THEN SET urgency_score = urgency_score + 40; -- Due today
            WHEN days_to_deadline = 1 THEN SET urgency_score = urgency_score + 30; -- Due tomorrow
            WHEN days_to_deadline <= 3 THEN SET urgency_score = urgency_score + 20; -- Due in 2-3 days
            WHEN days_to_deadline <= 7 THEN SET urgency_score = urgency_score + 10; -- Due this week
        END CASE;
    END IF;
    
    -- Add urgency based on planned date
    IF planned_date IS NOT NULL THEN
        SET days_to_planned = DATEDIFF(planned_date, NOW());
        CASE 
            WHEN days_to_planned < 0 THEN SET urgency_score = urgency_score + 20; -- Past planned date
            WHEN days_to_planned = 0 THEN SET urgency_score = urgency_score + 15; -- Planned for today
            WHEN days_to_planned = 1 THEN SET urgency_score = urgency_score + 10; -- Planned for tomorrow
            WHEN days_to_planned <= 3 THEN SET urgency_score = urgency_score + 5;  -- Planned soon
        END CASE;
    END IF;
    
    RETURN urgency_score;
END //

DELIMITER ;

-- Additional useful views

-- View for task dashboard with calculated urgency scores
CREATE VIEW task_dashboard AS
SELECT 
    t.id,
    t.description,
    t.estimated_duration,
    t.priority,
    GetPriorityLabel(t.priority) as priority_label,
    t.deadline,
    t.planned_date,
    t.status,
    rt.name as recurrency_type,
    CalculateUrgencyScore(t.priority, t.deadline, t.planned_date) as urgency_score,
    CASE 
        WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 'overdue'
        WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR) THEN 'due_today'
        WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 72 HOUR) THEN 'due_soon'
        WHEN t.planned_date IS NOT NULL AND t.planned_date < NOW() THEN 'past_planned'
        WHEN t.planned_date IS NOT NULL AND DATE(t.planned_date) = CURDATE() THEN 'planned_today'
        ELSE 'normal'
    END as urgency_status,
    t.created_at,
    t.updated_at
FROM tasks t
LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
WHERE t.status IN ('pending', 'in_progress');

-- View for task statistics
CREATE VIEW task_statistics AS
SELECT 
    COUNT(*) as total_tasks,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_tasks,
    COUNT(CASE WHEN deadline IS NOT NULL AND deadline < NOW() AND status IN ('pending', 'in_progress') THEN 1 END) as overdue_tasks,
    AVG(priority) as avg_priority,
    AVG(estimated_duration) as avg_estimated_duration,
    AVG(CASE WHEN completed_at IS NOT NULL AND created_at IS NOT NULL 
             THEN TIMESTAMPDIFF(HOUR, created_at, completed_at) END) as avg_completion_time_hours
FROM tasks;

-- View for today's tasks
CREATE VIEW todays_tasks AS
SELECT 
    t.id,
    t.description,
    t.priority,
    GetPriorityLabel(t.priority) as priority_label,
    t.estimated_duration,
    t.deadline,
    t.planned_date,
    t.status,
    CalculateUrgencyScore(t.priority, t.deadline, t.planned_date) as urgency_score,
    CASE 
        WHEN t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE() THEN 'due_today'
        WHEN t.planned_date IS NOT NULL AND DATE(t.planned_date) = CURDATE() THEN 'planned_today'
        WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 'overdue'
        ELSE 'normal'
    END as day_status
FROM tasks t
WHERE (
    (DATE(t.deadline) = CURDATE()) OR 
    (DATE(t.planned_date) = CURDATE()) OR
    (t.deadline IS NOT NULL AND t.deadline < NOW())
)
AND t.status IN ('pending', 'in_progress')
ORDER BY urgency_score DESC, t.priority ASC;