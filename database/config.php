<?php
/**
 * Database Configuration for Task Manager
 * 
 * This file contains database connection settings and helper functions
 * for the Task Management System.
 */

class DatabaseConfig {
    // Database connection parameters
    const DB_HOST = 'localhost';
    const DB_NAME = 'taskmanager';
    const DB_USER = 'phpmyadmin'; // Change this to your MySQL username
    const DB_PASS = 'bzQx@N4z7q!oqsaVtQ*R';     // Change this to your MySQL password
    const DB_CHARSET = 'utf8mb4';
    
    private static $connection = null;
    
    /**
     * Get database connection using PDO
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    self::DB_HOST,
                    self::DB_NAME,
                    self::DB_CHARSET
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . self::DB_CHARSET
                ];
                
                self::$connection = new PDO($dsn, self::DB_USER, self::DB_PASS, $options);
                
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                throw new Exception("Database connection failed. Please check your configuration.");
            }
        }
        
        return self::$connection;
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
            // Split SQL file by delimiter and execute each statement
            $statements = explode(';', $sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $connection->exec($statement);
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
            return false;
        }
    }
    
    /**
     * Setup database from schema files
     */
    public static function setupDatabase() {
        try {
            // First, execute the main schema
            self::executeSqlFile(__DIR__ . '/schema.sql');
            
            // Then, execute the procedures
            self::executeSqlFile(__DIR__ . '/procedures.sql');
            
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
        $sql = "SELECT * FROM task_dashboard WHERE 1=1";
        
        // If no status filter is specified or only active statuses, filter out completed tasks by default
        if (empty($filters['status']) || (!$useAllTasks && !in_array($filters['status'], ['completed', 'cancelled', 'on_hold']))) {
            $sql .= " AND status IN ('pending', 'in_progress')";
        }
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['priority_min'])) {
            $sql .= " AND priority >= :priority_min";
            $params['priority_min'] = $filters['priority_min'];
        }
        
        if (!empty($filters['priority_max'])) {
            $sql .= " AND priority <= :priority_max";
            $params['priority_max'] = $filters['priority_max'];
        }
        
        if (!empty($filters['urgency_status'])) {
            $sql .= " AND urgency_status = :urgency_status";
            $params['urgency_status'] = $filters['urgency_status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE :search OR description LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Apply ordering - overdue tasks should always come first
        $allowedOrderBy = ['urgency_score', 'priority', 'deadline', 'planned_date', 'created_at', 'title', 'description'];
        if (in_array($orderBy, $allowedOrderBy)) {
            $orderDir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
            // Always prioritize overdue tasks first, then sort by the requested field
            $sql .= " ORDER BY 
                CASE WHEN urgency_status = 'overdue' THEN 0 ELSE 1 END ASC,
                {$orderBy} {$orderDir}";
        } else {
            // Default ordering with overdue priority
            $sql .= " ORDER BY 
                CASE WHEN urgency_status = 'overdue' THEN 0 ELSE 1 END ASC,
                urgency_score DESC";
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
        $fields = [];
        $params = ['id' => $id];
        
        $allowedFields = ['title', 'description', 'estimated_duration', 'priority', 'deadline', 'planned_date', 'status', 'recurrency_type_id'];
        
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE tasks SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute($params);
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
        $stmt = $this->connection->prepare("SELECT * FROM task_dashboard WHERE id = :id");
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