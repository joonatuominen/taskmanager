<?php
/**
 * Database Setup Script for Task Manager
 * Run this script once to set up the database on your remote server
 */

// Database configuration - UPDATE THESE VALUES FOR YOUR REMOTE SERVER
$db_host = 'localhost';  // Your database host
$db_user = 'root';  // Your MySQL username  
$db_pass = 'DC2fUhnmbrO1';  // Your MySQL password
$db_name = 'taskmanager';

echo "<h2>Task Manager Database Setup</h2>\n";

try {
    // Connect to MySQL (without selecting database first)
    $dsn = "mysql:host=$db_host;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p>✅ Connected to MySQL server successfully!</p>\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '$db_name' created/verified!</p>\n";
    
    // Select the database
    $pdo->exec("USE `$db_name`");
    
    // List of SQL files to execute in order
    $sql_files = [
        'database/schema.sql',
        'database/simple_views.sql', 
        'database/procedures.sql',
        'database/add_title_migration.sql'
    ];
    
    echo "<p>📁 Current directory: " . __DIR__ . "</p>\n";
    echo "<p>📋 Looking for SQL files...</p>\n";
    
    foreach ($sql_files as $file) {
        $file_path = __DIR__ . '/' . $file;
        if (file_exists($file_path)) {
            echo "<p>📋 Executing $file...</p>\n";
            
            $sql = file_get_contents($file_path);
            
            // Handle procedures.sql differently (contains stored procedures)
            if (strpos($file, 'procedures.sql') !== false) {
                // For procedures, we need to execute the entire file at once
                // Skip stored procedures for now as they're optional for basic functionality
                echo "<p>⚠️ Skipping stored procedures (optional for basic functionality)</p>\n";
                continue;
            }
            
            // Remove stored procedures from the SQL content for basic setup
            // Split into sections and filter out procedure definitions
            $sql_clean = '';
            $lines = explode("\n", $sql);
            $skip_procedure = false;
            
            foreach ($lines as $line) {
                $trimmed = trim($line);
                
                // Start skipping when we hit a CREATE PROCEDURE
                if (preg_match('/^CREATE\s+PROCEDURE/i', $trimmed)) {
                    $skip_procedure = true;
                    echo "<p style='margin-left: 20px; color: orange;'>⚠️ Skipping stored procedure: " . substr($trimmed, 0, 50) . "...</p>\n";
                    continue;
                }
                
                // Stop skipping when we hit END and then a delimiter
                if ($skip_procedure && preg_match('/^END\s*;?\s*$/i', $trimmed)) {
                    $skip_procedure = false;
                    continue;
                }
                
                // If we're not skipping, add the line to clean SQL
                if (!$skip_procedure) {
                    $sql_clean .= $line . "\n";
                }
            }
            
            // Now split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql_clean)));
            
            $statement_count = 0;
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^\/\*/', $statement)) {
                    $statement_count++;
                    try {
                        $pdo->exec($statement);
                        echo "<p style='margin-left: 20px; color: green;'>✓ Statement $statement_count executed</p>\n";
                    } catch (PDOException $e) {
                        // Show the actual statement that failed for debugging
                        $short_statement = substr(trim($statement), 0, 100) . (strlen(trim($statement)) > 100 ? '...' : '');
                        
                        // Ignore errors for statements that might already exist
                        if (strpos($e->getMessage(), 'already exists') !== false || 
                            strpos($e->getMessage(), 'Duplicate') !== false) {
                            echo "<p style='margin-left: 20px; color: orange;'>⚠️ Statement $statement_count already exists (skipping): $short_statement</p>\n";
                        } else {
                            echo "<p style='margin-left: 20px; color: red;'>❌ Error in statement $statement_count: " . $e->getMessage() . "</p>\n";
                            echo "<p style='margin-left: 40px; color: red;'>Statement: $short_statement</p>\n";
                            throw $e; // Re-throw to stop execution on real errors
                        }
                    }
                }
            }
            echo "<p>📊 Executed $statement_count statements from $file</p>\n";
            echo "<p>✅ $file executed successfully!</p>\n";
        } else {
            echo "<p>⚠️ Warning: $file not found, skipping...</p>\n";
        }
    }
    
    echo "<h3>🎉 Database setup completed successfully!</h3>\n";
    echo "<p><strong>Note:</strong> Stored procedures were skipped but are not required for basic functionality.</p>\n";
    echo "<p><strong>Next steps:</strong></p>\n";
    echo "<ol>\n";
    echo "<li>Update database credentials in <code>database/config.php</code></li>\n";
    echo "<li>Delete this setup file for security: <code>setup_database.php</code></li>\n";
    echo "<li>Test your application</li>\n";
    echo "<li>(Optional) If you need recurring tasks, manually import <code>database/procedures.sql</code> via phpMyAdmin</li>\n";
    echo "</ol>\n";
    
} catch (PDOException $e) {
    echo "<p>❌ <strong>Database Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . " <strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "<p><strong>Common solutions:</strong></p>\n";
    echo "<ul>\n";
    echo "<li>Check your database credentials</li>\n";
    echo "<li>Make sure MySQL is running</li>\n";
    echo "<li>Verify your user has CREATE DATABASE privileges</li>\n";
    echo "<li>Check if the database files exist in the database/ folder</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<p>❌ <strong>General Error:</strong> " . $e->getMessage() . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . " <strong>Line:</strong> " . $e->getLine() . "</p>\n";
}
?>