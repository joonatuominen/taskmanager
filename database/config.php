<?php
/**
 * Database Configuration for Task Manager
 * 
 * This file contains database connection settings and helper functions
 * for the Task Management System.
 */

class DatabaseConfig {
    const DB_CHARSET = 'utf8mb4';
    
    private static $connection = null;
    
    /**
     * Get database configuration based on environment
     */
    private static function getDatabaseConfig() {
        // Detect environment based on hostname or server name
        $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        
        if (strpos($hostname, 'jtuominen.net') !== false) {
            // Production server configuration
            return [
                'host' => 'localhost',
                'database' => 'taskmanager',
                'username' => 'root',
                'password' => 'DC2fUhnmbrO1'
            ];
        } else {
            // Local development configuration
            return [
                'host' => 'localhost',
                'database' => 'taskmanager', 
                'username' => 'phpmyadmin',  // Your local MySQL username
                'password' => 'bzQx@N4z7q!oqsaVtQ*R'  // Your local MySQL password
            ];
        }
    }
    
    /**
     * Get database connection using PDO
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $config = self::getDatabaseConfig();
                
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    $config['host'],
                    $config['database'],
                    self::DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET
                ];
                
                self::$connection = new PDO($dsn, $config['username'], $config['password'], $options);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
        
        return self::$connection;
    }
    
    /**
     * Get current environment info for debugging
     */
    public static function getEnvironmentInfo() {
        $hostname = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $config = self::getDatabaseConfig();
        
        return [
            'hostname' => $hostname,
            'environment' => strpos($hostname, 'jtuominen.net') !== false ? 'production' : 'development',
            'database_host' => $config['host'],
            'database_name' => $config['database'],
            'database_user' => $config['username']
            // Note: password is intentionally not included for security
        ];
    }
    
    /**
     * Execute SQL file
     */
    public static function executeSqlFile($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("SQL file not found: " . $filepath);
        }
        
        $sql = file_get_contents($filepath);
        $connection = self::getConnection();
        
        try {
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
                    error_log("Skipping stored procedure: " . substr($trimmed, 0, 50) . "...");
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
            
            // Split SQL file by delimiter and execute each statement
            $statements = explode(';', $sql_clean);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^\/\*/', $statement)) {
                    try {
                        $connection->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore errors for statements that might already exist
                        if (strpos($e->getMessage(), 'already exists') === false && 
                            strpos($e->getMessage(), 'Duplicate') === false) {
                            // Re-throw if it's not an "already exists" error
                            throw $e;
                        }
                        // Otherwise, silently continue (table/view already exists)
                    }
                }
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Error executing SQL file: " . $e->getMessage());
            throw new Exception("Error executing SQL file: " . $e->getMessage());
        }
    }
    
    /**
     * Check if database exists and tables are created
     */
    public static function isDatabaseSetup() {
        try {
            $connection = self::getConnection();
            $stmt = $connection->query("SHOW TABLES LIKE 'tasks'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("isDatabaseSetup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get detailed database status for debugging
     */
    public static function getDatabaseStatus() {
        try {
            $connection = self::getConnection();
            
            // Get all tables
            $stmt = $connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check for required tables
            $requiredTables = ['tasks', 'recurrency_types'];
            $status = [
                'connection' => true,
                'all_tables' => $tables,
                'required_tables' => [],
                'missing_tables' => [],
                'setup_complete' => true
            ];
            
            foreach ($requiredTables as $table) {
                if (in_array($table, $tables)) {
                    $status['required_tables'][] = $table;
                } else {
                    $status['missing_tables'][] = $table;
                    $status['setup_complete'] = false;
                }
            }
            
            return $status;
            
        } catch (Exception $e) {
            return [
                'connection' => false,
                'error' => $e->getMessage(),
                'setup_complete' => false
            ];
        }
    }
    
    /**
     * Setup database from schema files
     */
    public static function setupDatabase() {
        try {
            // First, execute the main schema (skip stored procedures for now)
            self::executeSqlFile(__DIR__ . '/schema.sql');
            
            // Execute simple views if they exist
            if (file_exists(__DIR__ . '/simple_views.sql')) {
                self::executeSqlFile(__DIR__ . '/simple_views.sql');
            }
            
            // Execute migrations if they exist
            if (file_exists(__DIR__ . '/add_title_migration.sql')) {
                self::executeSqlFile(__DIR__ . '/add_title_migration.sql');
            }
            
            // Skip procedures for now as they need special handling
            // self::executeSqlFile(__DIR__ . '/procedures.sql');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Database setup failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get database statistics
     */
    public static function getStats() {
        try {
            $connection = self::getConnection();
            $stmt = $connection->query("SELECT * FROM task_statistics");
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Test database connection
     */
    public static function testConnection() {
        try {
            $connection = self::getConnection();
            $stmt = $connection->query("SELECT 1");
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Task Management Database Helper Class
 */
class TaskDatabase {
    private $connection;
    
    public function __construct() {
        $this->connection = DatabaseConfig::getConnection();
    }
    
    /**
     * Get all active tasks with filtering and sorting options
     */
    public function getTasks($filters = [], $orderBy = 'urgency_score', $orderDir = 'DESC', $limit = null) {
        // Use task_dashboard for all tasks, or active_tasks for non-completed tasks
        $useAllTasks = !empty($filters['status']) && in_array($filters['status'], ['completed', 'cancelled', 'on_hold']);
        $sql = "SELECT td.*, t.recurrency_type_id FROM task_dashboard td 
                LEFT JOIN tasks t ON td.id = t.id 
                WHERE 1=1";
        
        // If no status filter is specified or only active statuses, filter out completed tasks by default
        if (empty($filters['status']) || (!$useAllTasks && !in_array($filters['status'], ['completed', 'cancelled', 'on_hold']))) {
            $sql .= " AND td.status IN ('pending', 'in_progress')";
        }
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND td.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['priority_min'])) {
            $sql .= " AND td.priority >= :priority_min";
            $params['priority_min'] = $filters['priority_min'];
        }
        
        if (!empty($filters['priority_max'])) {
            $sql .= " AND td.priority <= :priority_max";
            $params['priority_max'] = $filters['priority_max'];
        }
        
        if (!empty($filters['urgency_status'])) {
            $sql .= " AND td.urgency_status = :urgency_status";
            $params['urgency_status'] = $filters['urgency_status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (td.title LIKE :search OR td.description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Apply ordering - overdue tasks should always come first
        $allowedOrderBy = ['urgency_score', 'priority', 'deadline', 'planned_date', 'created_at', 'title', 'description'];
        if (in_array($orderBy, $allowedOrderBy)) {
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            // Always prioritize overdue tasks first, then sort by the requested field
            $sql .= " ORDER BY 
                CASE WHEN td.urgency_status = 'overdue' THEN 0 ELSE 1 END ASC,
                td.{$orderBy} {$orderDir}";
        } else {
            // Default ordering with overdue priority
            $sql .= " ORDER BY 
                CASE WHEN td.urgency_status = 'overdue' THEN 0 ELSE 1 END ASC,
                td.urgency_score DESC";
        }
        
        // Apply limit
        if ($limit && is_numeric($limit)) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get today's tasks
     */
    public function getTodaysTasks() {
        $stmt = $this->connection->query("SELECT * FROM todays_tasks ORDER BY 
            CASE WHEN day_status = 'overdue' THEN 0 ELSE 1 END ASC,
            urgency_score DESC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get upcoming tasks (next 7 days)
     */
    public function getUpcomingTasks() {
        $stmt = $this->connection->query("SELECT * FROM upcoming_tasks ORDER BY 
            CASE WHEN urgency_status = 'overdue' THEN 0 ELSE 1 END ASC,
            CASE WHEN deadline IS NOT NULL THEN deadline ELSE planned_date END ASC,
            priority ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * Get overdue tasks
     */
    public function getOverdueTasks() {
        $stmt = $this->connection->query("SELECT * FROM overdue_tasks ORDER BY 
            days_overdue DESC, 
            priority ASC");
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new task
     */
    public function createTask($data) {
        $sql = "INSERT INTO tasks (title, description, estimated_duration, priority, deadline, planned_date, recurrency_type_id) 
                VALUES (:title, :description, :estimated_duration, :priority, :deadline, :planned_date, :recurrency_type_id)";
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'estimated_duration' => $data['estimated_duration'] ?? null,
            'priority' => $data['priority'] ?? 50,
            'deadline' => $data['deadline'] ?? null,
            'planned_date' => $data['planned_date'] ?? null,
            'recurrency_type_id' => $data['recurrency_type_id'] ?? 1
        ]);
    }
    
    /**
     * Update a task
     */
    public function updateTask($id, $data) {
        // First, get the current task data to check if it's being completed and is recurring
        $stmt = $this->connection->prepare("SELECT * FROM tasks WHERE id = ?");
        $stmt->execute([$id]);
        $currentTask = $stmt->fetch();
        
        if (!$currentTask) {
            return false;
        }
        
        // Check if task is being completed and is recurring
        $isBeingCompleted = isset($data['status']) && $data['status'] === 'completed' && $currentTask['status'] !== 'completed';
        $isRecurring = $currentTask['recurrency_type_id'] && $currentTask['recurrency_type_id'] != 1; // 1 is 'none'
        
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['title', 'description', 'estimated_duration', 'priority', 'deadline', 'planned_date', 'status', 'recurrency_type_id'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
                
                // Set completed_at timestamp when marking as completed
                if ($field === 'status' && $data[$field] === 'completed') {
                    $fields[] = "completed_at = NOW()";
                }
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->execute($params);
        
        // If task was completed and is recurring, create the next instance
        if ($result && $isBeingCompleted && $isRecurring) {
            $this->createNextRecurringTask($currentTask);
        }
        
        return $result;
    }
    
    /**
     * Create the next instance of a recurring task
     */
    private function createNextRecurringTask($task) {
        // Calculate next dates based on recurrency type
        $nextDeadline = null;
        $nextPlannedDate = null;
        
        // Get recurrency type name
        $stmt = $this->connection->prepare("SELECT name FROM recurrency_types WHERE id = ?");
        $stmt->execute([$task['recurrency_type_id']]);
        $recurrencyType = $stmt->fetchColumn();
        
        if (!$recurrencyType || $recurrencyType === 'none') {
            return false;
        }
        
        // Calculate next deadline if original task had one
        if ($task['deadline']) {
            $nextDeadline = $this->calculateNextDate($task['deadline'], $recurrencyType, $task['recurrency_interval'] ?? 1);
        }
        
        // Calculate next planned date if original task had one
        if ($task['planned_date']) {
            $nextPlannedDate = $this->calculateNextDate($task['planned_date'], $recurrencyType, $task['recurrency_interval'] ?? 1);
        }
        
        // Check if recurrency should end
        if ($task['recurrency_end_date'] && 
            (($nextDeadline && $nextDeadline > $task['recurrency_end_date']) || 
             ($nextPlannedDate && $nextPlannedDate > $task['recurrency_end_date']))) {
            return false; // Don't create next task if past end date
        }
        
        // Create new task data
        $newTaskData = [
            'title' => $task['title'],
            'description' => $task['description'],
            'estimated_duration' => $task['estimated_duration'],
            'priority' => $task['priority'],
            'deadline' => $nextDeadline,
            'planned_date' => $nextPlannedDate,
            'recurrency_type_id' => $task['recurrency_type_id']
        ];
        
        return $this->createTask($newTaskData);
    }
    
    /**
     * Calculate next date based on recurrency type
     */
    private function calculateNextDate($dateString, $recurrencyType, $interval = 1) {
        $date = new DateTime($dateString);
        
        switch ($recurrencyType) {
            case 'daily':
                $date->modify("+{$interval} day");
                break;
            case 'weekly':
                $date->modify("+{$interval} week");
                break;
            case 'monthly':
                $date->modify("+{$interval} month");
                break;
            case 'yearly':
                $date->modify("+{$interval} year");
                break;
            default:
                return null;
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Delete a task
     */
    public function deleteTask($id) {
        $stmt = $this->connection->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Get task by ID
     */
    public function getTask($id) {
        $stmt = $this->connection->prepare("SELECT td.*, t.recurrency_type_id FROM task_dashboard td 
                                           LEFT JOIN tasks t ON td.id = t.id 
                                           WHERE td.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get recurrency types
     */
    public function getRecurrencyTypes() {
        $stmt = $this->connection->query("SELECT * FROM recurrency_types ORDER BY id");
        return $stmt->fetchAll();
    }
    
    /**
     * Mark task as completed
     */
    public function completeTask($id) {
        $sql = "UPDATE tasks SET status = 'completed', completed_at = NOW() WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Generate recurring task instances for a date range
     */
    public function generateRecurringInstances($startDate, $endDate) {
        $stmt = $this->connection->prepare("CALL GenerateRecurringInstances(?, ?)");
        return $stmt->execute([$startDate, $endDate]);
    }
}
?>