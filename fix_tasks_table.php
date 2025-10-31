<?php
/**
 * Simple Tasks Table Creation Test
 */

require_once __DIR__ . '/database/config.php';

echo "<h2>🔧 Manual Tasks Table Creation</h2>\n";

try {
    $connection = DatabaseConfig::getConnection();
    
    echo "<h3>Current Tables:</h3>\n";
    $result = $connection->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Existing tables: " . implode(', ', $tables) . "</p>\n";
    
    echo "<h3>Checking recurrency_types table:</h3>\n";
    $result = $connection->query("SELECT COUNT(*) as count FROM recurrency_types");
    $count = $result->fetch()['count'];
    echo "<p>recurrency_types has " . $count . " records</p>\n";
    
    echo "<h3>Creating tasks table (simplified version):</h3>\n";
    
    // First, try creating without foreign key
    $simpleSql = "
    CREATE TABLE IF NOT EXISTS tasks (
        id INT PRIMARY KEY AUTO_INCREMENT,
        description TEXT NOT NULL,
        estimated_duration INT DEFAULT NULL,
        priority INT DEFAULT 50,
        deadline DATETIME DEFAULT NULL,
        planned_date DATETIME DEFAULT NULL,
        recurrency_type_id INT DEFAULT 1,
        status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'pending',
        completed_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    try {
        $connection->exec($simpleSql);
        echo "<p style='color: green;'>✅ Basic tasks table created</p>\n";
        
        // Now try to add the foreign key constraint
        echo "<h3>Adding foreign key constraint:</h3>\n";
        $fkSql = "ALTER TABLE tasks ADD CONSTRAINT fk_recurrency_type 
                  FOREIGN KEY (recurrency_type_id) REFERENCES recurrency_types(id) ON DELETE SET NULL";
        
        try {
            $connection->exec($fkSql);
            echo "<p style='color: green;'>✅ Foreign key constraint added</p>\n";
        } catch (PDOException $e) {
            echo "<p style='color: orange;'>⚠️ Foreign key constraint failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            echo "<p>This is not critical - the table will work without it.</p>\n";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Failed to create tasks table: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "<h3>Final Check:</h3>\n";
    $result = $connection->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Final tables: " . implode(', ', $tables) . "</p>\n";
    
    if (in_array('tasks', $tables)) {
        echo "<p style='color: green; font-size: 18px;'>🎉 Tasks table now exists!</p>\n";
        echo "<p><a href='test_config.php'>Test Configuration</a> | <a href='index.php'>Go to Task Manager</a></p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>