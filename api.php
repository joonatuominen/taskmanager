<?php
/**
 * Simple REST API for Task Management
 * 
 * This file provides API endpoints for the React frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/database/config.php';

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/taskmanager/api.php';
$path = str_replace($base_path, '', $request_uri);
$path = strtok($path, '?'); // Remove query parameters
$method = $_SERVER['REQUEST_METHOD'];

try {
    $taskDb = new TaskDatabase();
    
    switch ($path) {
        case '/tasks':
            if ($method === 'GET') {
                getTasks($taskDb);
            } elseif ($method === 'POST') {
                createTask($taskDb);
            }
            break;
            
        case '/tasks/today':
            if ($method === 'GET') {
                getTodaysTasks($taskDb);
            }
            break;
            
        case '/tasks/upcoming':
            if ($method === 'GET') {
                getUpcomingTasks($taskDb);
            }
            break;
            
        case '/tasks/overdue':
            if ($method === 'GET') {
                getOverdueTasks($taskDb);
            }
            break;
            
        case '/stats':
            if ($method === 'GET') {
                getStats();
            }
            break;
            
        default:
            if (preg_match('/^\/tasks\/(\d+)$/', $path, $matches)) {
                $taskId = $matches[1];
                if ($method === 'GET') {
                    getTask($taskDb, $taskId);
                } elseif ($method === 'PUT') {
                    updateTask($taskDb, $taskId);
                } elseif ($method === 'DELETE') {
                    deleteTask($taskDb, $taskId);
                }
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Endpoint not found']);
            }
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

/**
 * Get all tasks with optional filtering
 */
function getTasks($taskDb) {
    $filters = [];
    $orderBy = $_GET['orderBy'] ?? 'urgency_score';
    $orderDir = $_GET['orderDir'] ?? 'DESC';
    $limit = $_GET['limit'] ?? null;
    
    // Apply filters from query parameters
    if (isset($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    if (isset($_GET['priority_min'])) {
        $filters['priority_min'] = intval($_GET['priority_min']);
    }
    if (isset($_GET['priority_max'])) {
        $filters['priority_max'] = intval($_GET['priority_max']);
    }
    if (isset($_GET['urgency_status'])) {
        $filters['urgency_status'] = $_GET['urgency_status'];
    }
    if (isset($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    $tasks = $taskDb->getTasks($filters, $orderBy, $orderDir, $limit);
    
    // Format dates for frontend
    foreach ($tasks as &$task) {
        $task['deadline_formatted'] = $task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : null;
        $task['planned_date_formatted'] = $task['planned_date'] ? date('M j, Y H:i', strtotime($task['planned_date'])) : null;
        $task['created_at_formatted'] = date('M j, Y H:i', strtotime($task['created_at']));
        $task['urgency_score'] = round(floatval($task['urgency_score']), 1);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'count' => count($tasks)
    ]);
}

/**
 * Get today's tasks
 */
function getTodaysTasks($taskDb) {
    $tasks = $taskDb->getTodaysTasks();
    
    foreach ($tasks as &$task) {
        $task['deadline_formatted'] = $task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : null;
        $task['planned_date_formatted'] = $task['planned_date'] ? date('M j, Y H:i', strtotime($task['planned_date'])) : null;
        $task['urgency_score'] = round(floatval($task['urgency_score']), 1);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'count' => count($tasks)
    ]);
}

/**
 * Get upcoming tasks
 */
function getUpcomingTasks($taskDb) {
    $tasks = $taskDb->getUpcomingTasks();
    
    foreach ($tasks as &$task) {
        $task['deadline_formatted'] = $task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : null;
        $task['planned_date_formatted'] = $task['planned_date'] ? date('M j, Y H:i', strtotime($task['planned_date'])) : null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'count' => count($tasks)
    ]);
}

/**
 * Get overdue tasks
 */
function getOverdueTasks($taskDb) {
    $tasks = $taskDb->getOverdueTasks();
    
    foreach ($tasks as &$task) {
        $task['deadline_formatted'] = $task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : null;
        $task['planned_date_formatted'] = $task['planned_date'] ? date('M j, Y H:i', strtotime($task['planned_date'])) : null;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $tasks,
        'count' => count($tasks)
    ]);
}

/**
 * Get single task
 */
function getTask($taskDb, $id) {
    $task = $taskDb->getTask($id);
    
    if ($task) {
        $task['deadline_formatted'] = $task['deadline'] ? date('M j, Y H:i', strtotime($task['deadline'])) : null;
        $task['planned_date_formatted'] = $task['planned_date'] ? date('M j, Y H:i', strtotime($task['planned_date'])) : null;
        $task['created_at_formatted'] = date('M j, Y H:i', strtotime($task['created_at']));
        $task['urgency_score'] = round(floatval($task['urgency_score']), 1);
        
        echo json_encode([
            'success' => true,
            'data' => $task
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
    }
}

/**
 * Create new task
 */
function createTask($taskDb) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['title'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title is required']);
        return;
    }
    
    $data = [
        'title' => $input['title'],
        'description' => $input['description'] ?? '',
        'estimated_duration' => $input['estimated_duration'] ?? null,
        'priority' => $input['priority'] ?? 50,
        'deadline' => $input['deadline'] ?? null,
        'planned_date' => $input['planned_date'] ?? null,
        'recurrency_type_id' => $input['recurrency_type_id'] ?? 1
    ];
    
    if ($taskDb->createTask($data)) {
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Task created successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create task']);
    }
}

/**
 * Update task
 */
function updateTask($taskDb, $id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        return;
    }
    
    if ($taskDb->updateTask($id, $input)) {
        echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update task']);
    }
}

/**
 * Delete task
 */
function deleteTask($taskDb, $id) {
    if ($taskDb->deleteTask($id)) {
        echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete task']);
    }
}

/**
 * Get database statistics
 */
function getStats() {
    $stats = DatabaseConfig::getStats();
    
    if ($stats) {
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get statistics']);
    }
}
?>