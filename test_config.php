<?php
/**
 * Database Configuration Test
 * Use this to verify your database configuration is working correctly
 */

require_once __DIR__ . '/database/config.php';

echo "<h2>🔧 Database Configuration Test</h2>\n";

try {
    // Show environment info
    $envInfo = DatabaseConfig::getEnvironmentInfo();
    
    echo "<h3>📍 Environment Detection</h3>\n";
    echo "<table border='1' cellpadding='8' cellspacing='0'>\n";
    echo "<tr><th>Setting</th><th>Value</th></tr>\n";
    echo "<tr><td>Hostname</td><td>" . htmlspecialchars($envInfo['hostname']) . "</td></tr>\n";
    echo "<tr><td>Environment</td><td><strong>" . ucfirst($envInfo['environment']) . "</strong></td></tr>\n";
    echo "<tr><td>Database Host</td><td>" . htmlspecialchars($envInfo['database_host']) . "</td></tr>\n";
    echo "<tr><td>Database Name</td><td>" . htmlspecialchars($envInfo['database_name']) . "</td></tr>\n";
    echo "<tr><td>Database User</td><td>" . htmlspecialchars($envInfo['database_user']) . "</td></tr>\n";
    echo "</table>\n";
    
    // Test connection
    echo "<h3>🔌 Connection Test</h3>\n";
    
    if (DatabaseConfig::testConnection()) {
        echo "<p style='color: green; font-weight: bold;'>✅ Database connection successful!</p>\n";
        
        // Get detailed database status
        echo "<h3>🔍 Checking Database Status...</h3>\n";
        try {
            $dbStatus = DatabaseConfig::getDatabaseStatus();
            echo "<p style='color: blue;'>✅ Status check completed successfully.</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Status check failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            $dbStatus = ['setup_complete' => false, 'error' => $e->getMessage()];
        }
        
        if ($dbStatus['setup_complete']) {
            echo "<p style='color: green;'>✅ Database tables are set up correctly.</p>\n";
            
            // Show some stats if available
            $stats = DatabaseConfig::getStats();
            if ($stats) {
                echo "<h3>📊 Quick Statistics</h3>\n";
                echo "<p>Total Tasks: <strong>" . $stats['total_tasks'] . "</strong></p>\n";
                echo "<p>Pending Tasks: <strong>" . $stats['pending_tasks'] . "</strong></p>\n";
            }
        } else {
            echo "<p style='color: orange;'>⚠️ Database setup incomplete.</p>\n";
            
            if (isset($dbStatus['error'])) {
                echo "<p style='color: red;'><strong>Database Error:</strong> " . htmlspecialchars($dbStatus['error']) . "</p>\n";
            } else {
                echo "<h4>📋 Database Status Details:</h4>\n";
                echo "<p><strong>All Tables Found:</strong> " . (empty($dbStatus['all_tables']) ? 'None' : implode(', ', $dbStatus['all_tables'])) . "</p>\n";
                echo "<p><strong>Required Tables Present:</strong> " . (empty($dbStatus['required_tables']) ? 'None' : implode(', ', $dbStatus['required_tables'])) . "</p>\n";
                echo "<p><strong>Missing Tables:</strong> " . (empty($dbStatus['missing_tables']) ? 'None' : implode(', ', $dbStatus['missing_tables'])) . "</p>\n";
            }
            
            echo "<p><a href='setup.php'>🔧 Run Database Setup</a></p>\n";
        }
        
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Database connection failed!</p>\n";
        echo "<p>Please check your database credentials in the configuration.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>If connection fails, check your database credentials</li>\n";
echo "<li>If tables are missing, run <a href='setup.php'>setup.php</a></li>\n";
echo "<li>If everything works, go to <a href='index.php'>Task Manager</a></li>\n";
echo "</ul>\n";
?>