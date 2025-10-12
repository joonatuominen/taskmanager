-- Simple views for Task Manager (without complex procedures)
USE taskmanager;

-- Drop existing views if they exist
DROP VIEW IF EXISTS task_dashboard;
DROP VIEW IF EXISTS todays_tasks;
DROP VIEW IF EXISTS upcoming_tasks;
DROP VIEW IF EXISTS overdue_tasks;
DROP VIEW IF EXISTS active_tasks;
DROP VIEW IF EXISTS task_statistics;

-- View for task dashboard with calculated urgency scores
CREATE VIEW task_dashboard AS
SELECT 
    t.id,
    t.description,
    t.estimated_duration,
    t.priority,
    CASE 
        WHEN t.priority <= 10 THEN 'Critical'
        WHEN t.priority <= 25 THEN 'High'
        WHEN t.priority <= 50 THEN 'Medium'
        WHEN t.priority <= 75 THEN 'Low'
        ELSE 'Very Low'
    END as priority_label,
    t.deadline,
    t.planned_date,
    t.status,
    rt.name as recurrency_type,
    t.recurrency_interval,
    t.recurrency_end_date,
    -- Simple urgency score calculation
    (101 - t.priority + 
     CASE 
         WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 50
         WHEN t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE() THEN 40
         WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 30
         WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 20
         WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY) THEN 10
         ELSE 0
     END +
     CASE 
         WHEN t.planned_date IS NOT NULL AND t.planned_date < NOW() THEN 20
         WHEN t.planned_date IS NOT NULL AND DATE(t.planned_date) = CURDATE() THEN 15
         WHEN t.planned_date IS NOT NULL AND t.planned_date <= DATE_ADD(NOW(), INTERVAL 1 DAY) THEN 10
         WHEN t.planned_date IS NOT NULL AND t.planned_date <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 5
         ELSE 0
     END) as urgency_score,
    CASE 
        WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 'overdue'
        WHEN t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE() THEN 'due_today'
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

-- View for today's tasks
CREATE VIEW todays_tasks AS
SELECT 
    t.id,
    t.description,
    t.priority,
    CASE 
        WHEN t.priority <= 10 THEN 'Critical'
        WHEN t.priority <= 25 THEN 'High'
        WHEN t.priority <= 50 THEN 'Medium'
        WHEN t.priority <= 75 THEN 'Low'
        ELSE 'Very Low'
    END as priority_label,
    t.estimated_duration,
    t.deadline,
    t.planned_date,
    t.status,
    -- Simple urgency score calculation
    (101 - t.priority + 
     CASE 
         WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 50
         WHEN t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE() THEN 40
         ELSE 0
     END +
     CASE 
         WHEN t.planned_date IS NOT NULL AND DATE(t.planned_date) = CURDATE() THEN 15
         ELSE 0
     END) as urgency_score,
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