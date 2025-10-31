<?php
/**
 * Detailed Database Setup with Enhanced Logging
 */

require_once __DIR__ . '/database/config.php';

echo "<h2>🔧 Detailed Database Setup</h2>\n";

try {
    echo "<h3>📋 Step 1: Testing Connection</h3>\n";
    if (DatabaseConfig::testConnection()) {
        echo "<p style='color: green;'>✅ Connection successful</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Connection failed</p>\n";
        exit;
    }
    
    echo "<h3>📋 Step 2: Current Database Status</h3>\n";
    $statusBefore = DatabaseConfig::getDatabaseStatus();
    echo "<p><strong>Tables found:</strong> " . implode(', ', $statusBefore['all_tables']) . "</p>\n";
    echo "<p><strong>Missing tables:</strong> " . implode(', ', $statusBefore['missing_tables']) . "</p>\n";
    
    echo "<h3>📋 Step 3: Manual Table Creation</h3>\n";
    
    // Let's create the tasks table manually to see what happens
    $connection = DatabaseConfig::getConnection();
    
    echo "<h4>Creating tasks table...</h4>\n";
    $tasksSql = "
    CREATE TABLE IF NOT EXISTS tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        description TEXT NOT NULL,
        estimated_duration INT DEFAULT NULL COMMENT 'Duration in minutes',
        priority INT DEFAULT 50 COMMENT 'Priority from 1 (urgent) to 100 (low)',
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
    )";
    
    try {
        $connection->exec($tasksSql);
        echo "<p style='color: green;'>✅ Tasks table created successfully</p>\n";
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Error creating tasks table: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "<h3>📋 Step 4: Final Status Check</h3>\n";
    $statusAfter = DatabaseConfig::getDatabaseStatus();
    echo "<p><strong>Tables found:</strong> " . implode(', ', $statusAfter['all_tables']) . "</p>\n";
    echo "<p><strong>Missing tables:</strong> " . implode(', ', $statusAfter['missing_tables']) . "</p>\n";
    
    if ($statusAfter['setup_complete']) {
        echo "<p style='color: green; font-size: 18px;'>🎉 Database setup is now complete!</p>\n";
        echo "<p><a href='index.php'>Go to Task Manager</a></p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Setup still incomplete. Check the errors above.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><a href='test_config.php'>← Back to Configuration Test</a></p>\n";
?>