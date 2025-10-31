<?php
/**
 * Database Setup and Test Script
 * 
 * This script helps you set up and test your Task Manager database
 */

require_once __DIR__ . '/database/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - Database Setup</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status {
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .priority-critical { color: #dc3545; font-weight: bold; }
        .priority-high { color: #fd7e14; font-weight: bold; }
        .priority-medium { color: #ffc107; }
        .priority-low { color: #28a745; }
        .priority-very-low { color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Task Manager - Database Setup</h1>
        
        <?php
        $action = $_GET['action'] ?? 'status';
        
        // Handle actions
        if ($action === 'setup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            echo '<div class="info">Setting up database...</div>';
            
            if (DatabaseConfig::setupDatabase()) {
                echo '<div class="success">✅ Database setup completed successfully!</div>';
            } else {
                echo '<div class="error">❌ Database setup failed. Please check the error logs.</div>';
            }
        }
        
        // Test database connection
        echo '<h2>Database Connection Status</h2>';
        
        if (DatabaseConfig::testConnection()) {
            echo '<div class="success">✅ Database connection successful!</div>';
            
            if (DatabaseConfig::isDatabaseSetup()) {
                echo '<div class="success">✅ Database tables are set up and ready to use.</div>';
                
                // Show statistics
                $stats = DatabaseConfig::getStats();
                if ($stats) {
                    echo '<h3>📊 Database Statistics</h3>';
                    echo '<table>';
                    echo '<tr><th>Metric</th><th>Value</th></tr>';
                    echo '<tr><td>Total Tasks</td><td>' . $stats['total_tasks'] . '</td></tr>';
                    echo '<tr><td>Pending Tasks</td><td>' . $stats['pending_tasks'] . '</td></tr>';
                    echo '<tr><td>In Progress Tasks</td><td>' . $stats['in_progress_tasks'] . '</td></tr>';
                    echo '<tr><td>Completed Tasks</td><td>' . $stats['completed_tasks'] . '</td></tr>';
                    echo '<tr><td>Overdue Tasks</td><td>' . $stats['overdue_tasks'] . '</td></tr>';
                    echo '<tr><td>Average Priority</td><td>' . round($stats['avg_priority'], 1) . '</td></tr>';
                    echo '<tr><td>Average Estimated Duration</td><td>' . round($stats['avg_estimated_duration'] ?? 0, 0) . ' minutes</td></tr>';
                    echo '</table>';
                }
                
                // Show sample tasks
                try {
                    $taskDb = new TaskDatabase();
                    $tasks = $taskDb->getTasks([], 'urgency_score', 'DESC', 10);
                    
                    if (!empty($tasks)) {
                        echo '<h3>📋 Sample Tasks (Top 10 by Urgency)</h3>';
                        echo '<table>';
                        echo '<tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Deadline</th><th>Urgency Score</th></tr>';
                        
                        foreach ($tasks as $task) {
                            $priorityClass = '';
                            switch ($task['priority_label']) {
                                case 'Critical': $priorityClass = 'priority-critical'; break;
                                case 'High': $priorityClass = 'priority-high'; break;
                                case 'Medium': $priorityClass = 'priority-medium'; break;
                                case 'Low': $priorityClass = 'priority-low'; break;
                                case 'Very Low': $priorityClass = 'priority-very-low'; break;
                            }
                            
                            echo '<tr>';
                            echo '<td>' . $task['id'] . '</td>';
                            echo '<td>' . htmlspecialchars($task['title']) . '</td>';
                            echo '<td class="' . $priorityClass . '">' . $task['priority'] . ' (' . $task['priority_label'] . ')</td>';
                            echo '<td>' . ucwords(str_replace('_', ' ', $task['status'])) . '</td>';
                            echo '<td>' . ($task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : '-') . '</td>';
                            echo '<td>' . round($task['urgency_score'], 1) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    
                    // Show today's tasks
                    $todayTasks = $taskDb->getTodaysTasks();
                    if (!empty($todayTasks)) {
                        echo '<h3>📅 Today\'s Tasks</h3>';
                        echo '<table>';
                        echo '<tr><th>Description</th><th>Priority</th><th>Status</th><th>Urgency Score</th></tr>';
                        
                        foreach ($todayTasks as $task) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($task['title']) . '</td>';
                            echo '<td>' . $task['priority'] . ' (' . $task['priority_label'] . ')</td>';
                            echo '<td>' . ucwords(str_replace('_', ' ', $task['day_status'])) . '</td>';
                            echo '<td>' . round($task['urgency_score'], 1) . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="error">Error loading tasks: ' . $e->getMessage() . '</div>';
                }
                
            } else {
                echo '<div class="warning">⚠️ Database tables are not set up yet.</div>';
                echo '<form method="post" action="?action=setup">';
                echo '<button type="submit" class="btn btn-success">🚀 Set Up Database</button>';
                echo '</form>';
            }
            
        } else {
            echo '<div class="error">❌ Cannot connect to database. Please check your configuration in <code>database/config.php</code></div>';
        }
        ?>
        
        <h2>🛠️ Database Configuration</h2>
        <p>Current database configuration:</p>
        <?php 
        $envInfo = DatabaseConfig::getEnvironmentInfo();
        ?>
        <pre>Environment: <?php echo ucfirst($envInfo['environment']); ?>
Hostname: <?php echo $envInfo['hostname']; ?>
Host: <?php echo $envInfo['database_host']; ?>
Database: <?php echo $envInfo['database_name']; ?>
Username: <?php echo $envInfo['database_user']; ?>
Charset: <?php echo DatabaseConfig::DB_CHARSET; ?></pre>
        
        <p><strong>Configuration is automatic:</strong> Uses different settings for localhost vs jtuominen.net</p>
        
        <h2>📖 Database Schema Overview</h2>
        <p>Your task management database includes the following key tables:</p>
        
        <h3>Core Tables:</h3>
        <ul>
            <li><strong>tasks</strong> - Main task storage with all your requirements</li>
            <li><strong>recurrency_types</strong> - Defines recurrence patterns (daily, weekly, monthly, etc.)</li>
            <li><strong>task_instances</strong> - Individual instances of recurring tasks</li>
            <li><strong>categories</strong> - Task categories/tags for organization</li>
            <li><strong>task_categories</strong> - Many-to-many relationship between tasks and categories</li>
        </ul>
        
        <h3>Key Features:</h3>
        <ul>
            <li>✅ Task description, estimated duration, priority (1-100)</li>
            <li>✅ Optional deadline and planned date</li>
            <li>✅ Comprehensive recurrency support (daily, weekly, monthly, yearly, custom)</li>
            <li>✅ Automatic urgency score calculation</li>
            <li>✅ Pre-built views for different task views (today's, upcoming, overdue)</li>
            <li>✅ Stored procedures for generating recurring task instances</li>
            <li>✅ Support for task categories and attachments</li>
        </ul>
        
        <h3>Useful Database Views:</h3>
        <ul>
            <li><code>task_dashboard</code> - Main task view with calculated urgency scores</li>
            <li><code>todays_tasks</code> - Tasks due or planned for today</li>
            <li><code>upcoming_tasks</code> - Tasks coming up in the next 7 days</li>
            <li><code>overdue_tasks</code> - Past due tasks</li>
            <li><code>task_statistics</code> - Overall task statistics</li>
        </ul>
        
        <h2>🚀 Next Steps</h2>
        <ol>
            <li><strong>Verify Database Setup:</strong> Make sure the database connection is working and tables are created</li>
            <li><strong>Update Configuration:</strong> Modify <code>database/config.php</code> with your MySQL credentials</li>
            <li><strong>Build PHP API:</strong> Create REST API endpoints for task CRUD operations</li>
            <li><strong>Develop React UI:</strong> Build the frontend interface for task management</li>
            <li><strong>Add Authentication:</strong> Implement user authentication if needed</li>
        </ol>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <p><small>Task Manager Database Setup - Ready for PHP + React development</small></p>
        </div>
    </div>
</body>
</html>