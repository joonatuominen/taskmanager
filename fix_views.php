<?php
/**
 * Fix Missing Views and Title Column
 */

require_once __DIR__ . '/database/config.php';

echo "<h2>🔧 Fixing Missing Views and Title Column</h2>\n";

try {
    $connection = DatabaseConfig::getConnection();
    
    echo "<h3>Step 1: Check current tasks table structure</h3>\n";
    $result = $connection->query("DESCRIBE tasks");
    $columns = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Current columns: " . implode(', ', $columns) . "</p>\n";
    
    $hasTitle = in_array('title', $columns);
    echo "<p>Has title column: " . ($hasTitle ? 'Yes' : 'No') . "</p>\n";
    
    if (!$hasTitle) {
        echo "<h3>Step 2: Adding title column</h3>\n";
        try {
            $connection->exec("ALTER TABLE tasks ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT '' AFTER id");
            echo "<p style='color: green;'>✅ Title column added</p>\n";
            
            // Update existing tasks if any exist
            $connection->exec("UPDATE tasks SET title = CASE 
                WHEN LENGTH(description) <= 100 THEN description
                ELSE CONCAT(LEFT(description, 97), '...')
            END WHERE title = ''");
            echo "<p style='color: green;'>✅ Existing tasks updated with titles</p>\n";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error adding title column: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ Title column already exists</p>\n";
    }
    
    echo "<h3>Step 3: Creating task_dashboard view</h3>\n";
    
    $dashboardView = "
    CREATE OR REPLACE VIEW task_dashboard AS
    SELECT 
        t.id,
        t.title,
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
            WHEN t.deadline IS NOT NULL AND t.deadline < NOW() THEN 'Overdue'
            WHEN t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE() THEN 'Due Today'
            WHEN t.deadline IS NOT NULL AND t.deadline <= DATE_ADD(NOW(), INTERVAL 3 DAY) THEN 'Due Soon'
            WHEN t.planned_date IS NOT NULL AND DATE(t.planned_date) = CURDATE() THEN 'Planned Today'
            ELSE 'Normal'
        END as urgency_status,
        t.created_at,
        t.updated_at,
        t.completed_at,
        -- Time calculations
        CASE 
            WHEN t.completed_at IS NOT NULL THEN 
                TIMESTAMPDIFF(MINUTE, t.created_at, t.completed_at)
            ELSE NULL
        END as completion_time_minutes
    FROM tasks t
    LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
    WHERE t.status != 'cancelled'
    ";
    
    try {
        $connection->exec($dashboardView);
        echo "<p style='color: green;'>✅ task_dashboard view created</p>\n";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error creating task_dashboard view: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "<h3>Step 4: Creating additional helpful views</h3>\n";
    
    // Create a simple active tasks view
    $activeTasksView = "
    CREATE OR REPLACE VIEW active_tasks AS
    SELECT * FROM task_dashboard 
    WHERE status IN ('pending', 'in_progress')
    ORDER BY urgency_score DESC, priority ASC, deadline ASC
    ";
    
    try {
        $connection->exec($activeTasksView);
        echo "<p style='color: green;'>✅ active_tasks view created</p>\n";
    } catch (PDOException $e) {
        echo "<p style='color: orange;'>⚠️ Error creating active_tasks view: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "<h3>Step 5: Final verification</h3>\n";
    
    // Test the view
    try {
        $result = $connection->query("SELECT COUNT(*) as count FROM task_dashboard");
        $count = $result->fetch()['count'];
        echo "<p style='color: green;'>✅ task_dashboard view works! Found " . $count . " tasks.</p>\n";
        
        echo "<p style='color: green; font-size: 18px;'>🎉 Database setup is now complete!</p>\n";
        echo "<p><strong>You can now:</strong></p>\n";
        echo "<ul>\n";
        echo "<li><a href='test_config.php'>Test Configuration</a></li>\n";
        echo "<li><a href='index.php'>Go to Task Manager</a></li>\n";
        echo "</ul>\n";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error testing task_dashboard view: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>