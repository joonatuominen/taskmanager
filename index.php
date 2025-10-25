<?php
/**
 * Task Manager - Main Application
 * 
 * This is the main entry point for the Task Management System
 * with PHP backend and React frontend
 */

require_once __DIR__ . '/database/config.php';

// Check if database is set up
$isDatabaseReady = false;
$errorMessage = null;

try {
    $isDatabaseReady = DatabaseConfig::isDatabaseSetup();
    if (!$isDatabaseReady) {
        $errorMessage = "Database is not set up yet. Please run the setup first.";
    }
} catch (Exception $e) {
    $errorMessage = "Database connection failed: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        
        .app-container {
            max-width: 100%;
            margin: 0;
            padding: 15px;
        }
        
        .main-content {
            display: block;
        }
        
        .filters-bar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        
        .add-task-bar {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 2px solid #667eea;
        }
        
        .add-task-grid {
            display: grid;
            grid-template-columns: 2fr 0.8fr 0.8fr 0.6fr 0.6fr 0.8fr 1.2fr auto;
            gap: 8px;
            align-items: end;
        }
        
        .add-task-field {
            margin-bottom: 0;
        }
        
        .add-task-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        .add-task-field input,
        .add-task-field select,
        .add-task-field textarea {
            width: 100%;
            padding: 8px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            transition: border-color 0.2s ease;
            resize: none;
        }
        
        .add-task-field textarea {
            min-height: 40px;
            max-height: 80px;
        }
        
        .add-task-field input:focus,
        .add-task-field select:focus,
        .add-task-field textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .add-task-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .add-task-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        
        .add-task-btn.primary {
            background: #667eea;
            color: white;
        }
        
        .add-task-btn.primary:hover {
            background: #5a6fd8;
        }
        
        .add-task-btn.secondary {
            background: #6c757d;
            color: white;
        }
        
        .add-task-btn.secondary:hover {
            background: #5a6268;
        }
        
        .add-task-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        @media (max-width: 768px) {
            .add-task-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .task-edit-form {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .add-task-buttons {
                flex-direction: row;
                justify-content: space-between;
            }
            
            .add-task-btn {
                flex: 1;
            }
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .filter-group {
            margin-bottom: 0;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .priority-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        
        .tasks-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .tasks-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .tasks-header h2 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .task-count {
            background: #667eea;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .task-list {
            /* Removed scrolling - show all tasks */
        }
        
        .task-item {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }
        
        .task-item:hover {
            background-color: #f8f9fa;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .task-main {
            flex: 1;
        }
        
        .task-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .task-title:hover {
            color: #667eea;
        }
        
        .task-description {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, margin-bottom 0.3s ease, padding 0.3s ease;
            margin-bottom: 0;
            padding: 0;
        }
        
        .task-description.expanded {
            max-height: 200px;
            margin-bottom: 12px;
        }
        
        .task-description.has-content.expanded {
            border-left: 3px solid #e9ecef;
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 12px;
        }
        
        .task-expand-btn {
            background: none;
            border: none;
            color: #667eea;
            font-size: 0.8rem;
            cursor: pointer;
            padding: 2px 0;
            margin-left: 8px;
            transition: color 0.2s ease;
        }
        
        .task-expand-btn:hover {
            color: #5a6fd8;
        }
        
        .task-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .task-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .priority-critical { background: #ffe6e6; color: #dc3545; }
        .priority-high { background: #fff3e0; color: #ff6f00; }
        .priority-medium { background: #fff8e1; color: #f57c00; }
        .priority-low { background: #e8f5e8; color: #2e7d32; }
        .priority-very-low { background: #f5f5f5; color: #757575; }
        
        .status-pending { background: #e3f2fd; color: #1565c0; }
        .status-in-progress { background: #fff3e0; color: #e65100; }
        .status-completed { background: #e8f5e8; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .status-on-hold { background: #f3e5f5; color: #7b1fa2; }
        
        .task-item.completed {
            opacity: 0.8;
            background-color: #f8f9fa;
        }
        
        .task-item.completed .task-title {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .task-item.completed .task-meta {
            color: #adb5bd;
        }
        
        .urgency-overdue { background: #ffebee; color: #c62828; }
        .urgency-due-today { background: #fff3e0; color: #ef6c00; }
        .urgency-due-soon { background: #fff8e1; color: #f57c00; }
        
        .task-actions {
            display: flex;
            gap: 8px;
        }
        
        .task-edit-form {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 0.8fr 0.8fr 2.5fr auto;
            gap: 10px;
            align-items: end;
        }
        
        .edit-field {
            margin-bottom: 0;
        }
        
        .edit-field-row {
            display: contents;
        }
        
        .edit-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        .edit-input, .edit-textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s ease;
        }
        
        .edit-input:focus, .edit-textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .edit-textarea {
            resize: vertical;
            min-height: 60px;
        }
        
        .edit-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .date-input-group {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .date-input-group .edit-input {
            flex: 1;
        }
        
        .calendar-picker {
            width: 40px;
            height: 40px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            cursor: pointer;
            background: #f8f9fa;
            transition: all 0.2s ease;
            position: relative;
            color: transparent;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='%23666' d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 16px 16px;
        }
        
        .calendar-picker::-webkit-calendar-picker-indicator {
            opacity: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            cursor: pointer;
        }
        
        .calendar-picker:hover {
            border-color: #667eea;
            background: #fff;
        }
        
        .calendar-picker:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-sm {
            padding: 5px 8px;
            font-size: 0.75rem;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .task-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .app-container {
                padding: 10px;
            }
            
            .main-content {
                gap: 15px;
            }
            
            .edit-field-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .tasks-header {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .tab-buttons {
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .tab-button {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }
        
        .tab-buttons {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .tab-button {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .tab-button:hover {
            color: #333;
        }
        
        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect } = React;

        // API base URL
        const API_BASE = '/taskmanager/api.php';

        // Task Manager App Component
        function TaskManager() {
            const [tasks, setTasks] = useState([]);
            const [loading, setLoading] = useState(true);
            const [error, setError] = useState(null);
            const [activeTab, setActiveTab] = useState('all');
            const [recurrencyTypes, setRecurrencyTypes] = useState([]);
            const [taskCounts, setTaskCounts] = useState({});
            const [filters, setFilters] = useState({
                status: '',
                priority_min: '',
                priority_max: '',
                urgency_status: '',
                search: ''
            });

            // Fetch tasks based on current tab and filters
            const fetchTasks = async () => {
                setLoading(true);
                setError(null);
                
                try {
                    let url = `${API_BASE}/tasks`;
                    
                    // Handle different tabs
                    switch (activeTab) {
                        case 'today':
                            url = `${API_BASE}/tasks/today`;
                            break;
                        case 'upcoming':
                            url = `${API_BASE}/tasks/upcoming`;
                            break;
                        case 'overdue':
                            url = `${API_BASE}/tasks/overdue`;
                            break;
                        default:
                            // Add filters for 'all' tab
                            const params = new URLSearchParams();
                            Object.entries(filters).forEach(([key, value]) => {
                                if (value) params.append(key, value);
                            });
                            if (params.toString()) {
                                url += '?' + params.toString();
                            }
                    }
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.success) {
                        setTasks(data.data);
                    } else {
                        setError(data.error || 'Failed to fetch tasks');
                    }
                } catch (err) {
                    setError('Network error: ' + err.message);
                } finally {
                    setLoading(false);
                }
            };

            // Complete task
            const completeTask = async (taskId) => {
                try {
                    const response = await fetch(`${API_BASE}/tasks/${taskId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ status: 'completed' })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        fetchTasks(); // Refresh tasks
                        fetchTaskCounts(); // Refresh counts
                    } else {
                        setError(data.error || 'Failed to update task');
                    }
                } catch (err) {
                    setError('Network error: ' + err.message);
                }
            };

            // Update task
            const updateTask = async (taskId, updatedData) => {
                try {
                    const response = await fetch(`${API_BASE}/tasks/${taskId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(updatedData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        fetchTasks(); // Refresh tasks
                        fetchTaskCounts(); // Refresh counts
                        return true;
                    } else {
                        setError(data.error || 'Failed to update task');
                        return false;
                    }
                } catch (err) {
                    setError('Network error: ' + err.message);
                    return false;
                }
            };

            // Handle filter changes
            const handleFilterChange = (key, value) => {
                setFilters(prev => ({
                    ...prev,
                    [key]: value
                }));
            };

            // Fetch recurrency types
            const fetchRecurrencyTypes = async () => {
                try {
                    const response = await fetch(`${API_BASE}/recurrency-types`);
                    const data = await response.json();
                    if (data.success) {
                        setRecurrencyTypes(data.data);
                    }
                } catch (error) {
                    console.error('Failed to fetch recurrency types:', error);
                }
            };

            // Fetch task counts for tabs
            const fetchTaskCounts = async () => {
                try {
                    const counts = {};
                    
                    // Fetch counts for each tab
                    const tabEndpoints = {
                        all: `${API_BASE}/tasks`,
                        today: `${API_BASE}/tasks/today`,
                        upcoming: `${API_BASE}/tasks/upcoming`,
                        overdue: `${API_BASE}/tasks/overdue`
                    };

                    for (const [tab, url] of Object.entries(tabEndpoints)) {
                        try {
                            const response = await fetch(url);
                            const data = await response.json();
                            if (data.success) {
                                counts[tab] = data.data.length;
                            }
                        } catch (err) {
                            console.error(`Failed to fetch ${tab} count:`, err);
                        }
                    }
                    
                    setTaskCounts(counts);
                } catch (error) {
                    console.error('Failed to fetch task counts:', error);
                }
            };

            // Effects
            useEffect(() => {
                fetchTasks();
                fetchTaskCounts();
            }, [activeTab, filters]);

            useEffect(() => {
                fetchRecurrencyTypes();
            }, []);

            return (
                <div className="app-container">
                    <div className="main-content">
                        <AddTaskBar 
                            recurrencyTypes={recurrencyTypes}
                            onTaskAdded={() => {
                                fetchTasks();
                                fetchTaskCounts();
                            }}
                        />
                        
                        {activeTab === 'all' && (
                            <FiltersBar 
                                filters={filters}
                                onFilterChange={handleFilterChange}
                            />
                        )}
                        
                        <TasksContainer
                            tasks={tasks}
                            loading={loading}
                            error={error}
                            activeTab={activeTab}
                            onTabChange={setActiveTab}
                            onCompleteTask={completeTask}
                            onUpdateTask={updateTask}
                            taskCounts={taskCounts}
                            recurrencyTypes={recurrencyTypes}
                        />
                    </div>
                </div>
            );
        }

        // Filters Bar Component
        function FiltersBar({ filters, onFilterChange }) {
            return (
                <div className="filters-bar">
                    <div className="filters-grid">
                        <div className="filter-group">
                            <label>Status</label>
                            <select 
                                value={filters.status}
                                onChange={(e) => onFilterChange('status', e.target.value)}
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div className="filter-group">
                            <label>Urgency</label>
                            <select 
                                value={filters.urgency_status}
                                onChange={(e) => onFilterChange('urgency_status', e.target.value)}
                            >
                                <option value="">All Urgencies</option>
                                <option value="overdue">Overdue</option>
                                <option value="due_today">Due Today</option>
                                <option value="due_soon">Due Soon</option>
                                <option value="normal">Normal</option>
                            </select>
                        </div>

                        <div className="filter-group">
                            <label>Priority Range</label>
                            <div className="priority-inputs">
                                <input 
                                    type="number" 
                                    placeholder="Min (1-100)"
                                    min="1" 
                                    max="100"
                                    value={filters.priority_min}
                                    onChange={(e) => onFilterChange('priority_min', e.target.value)}
                                />
                                <input 
                                    type="number" 
                                    placeholder="Max (1-100)"
                                    min="1" 
                                    max="100"
                                    value={filters.priority_max}
                                    onChange={(e) => onFilterChange('priority_max', e.target.value)}
                                />
                            </div>
                        </div>

                        <div className="filter-group">
                            <label>Search Tasks</label>
                            <input 
                                type="text" 
                                placeholder="Search in title and description..."
                                value={filters.search}
                                onChange={(e) => onFilterChange('search', e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            );
        }

        // Add Task Bar Component
        function AddTaskBar({ recurrencyTypes, onTaskAdded }) {
            const [formData, setFormData] = React.useState({
                title: '',
                description: '',
                deadline: '',
                planned_date: '',
                priority: 50,
                estimated_duration: '',
                recurrency_type_id: 1
            });
            const [isSubmitting, setIsSubmitting] = React.useState(false);

            const formatDateForStorage = (displayString) => {
                if (!displayString) return null;
                try {
                    const match = displayString.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
                    if (match) {
                        const [, day, month, year] = match;
                        return `${year}-${month}-${day}`;
                    }
                    return null;
                } catch (e) {
                    return null;
                }
            };

            const handleInputChange = (field, value) => {
                setFormData(prev => ({ ...prev, [field]: value }));
            };

            const handleDateChange = (field, value) => {
                if (value) {
                    // Convert from date format to dd.mm.YYYY
                    const date = new Date(value);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const formatted = `${day}.${month}.${year}`;
                    setFormData(prev => ({ ...prev, [field]: formatted }));
                }
            };

            const convertToDateLocal = (ddmmyyyyString) => {
                if (!ddmmyyyyString) return '';
                try {
                    const match = ddmmyyyyString.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
                    if (match) {
                        const [, day, month, year] = match;
                        return `${year}-${month}-${day}`;
                    }
                    return '';
                } catch (e) {
                    return '';
                }
            };

            const handleSubmit = async (e) => {
                e.preventDefault();
                if (!formData.title.trim()) return;

                setIsSubmitting(true);
                try {
                    const dataToSend = {
                        ...formData,
                        deadline: formatDateForStorage(formData.deadline),
                        planned_date: formatDateForStorage(formData.planned_date),
                        priority: parseInt(formData.priority) || 50,
                        estimated_duration: parseInt(formData.estimated_duration) || null,
                        recurrency_type_id: formData.recurrency_type_id ? parseInt(formData.recurrency_type_id) : 1
                    };

                    const response = await fetch('/taskmanager/api.php/tasks', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(dataToSend)
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Reset form
                        setFormData({
                            title: '',
                            description: '',
                            deadline: '',
                            planned_date: '',
                            priority: 50,
                            estimated_duration: '',
                            recurrency_type_id: 1
                        });
                        onTaskAdded();
                    } else {
                        alert('Error: ' + (result.error || 'Failed to create task'));
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                } finally {
                    setIsSubmitting(false);
                }
            };

            return (
                <div className="add-task-bar">
                    <h3 style={{ marginBottom: '20px' }}>Add New Task</h3>
                    <form onSubmit={handleSubmit}>
                        <div className="add-task-grid">
                            <div className="add-task-field">
                                <label>Title *</label>
                                <input
                                    type="text"
                                    placeholder="Enter task title..."
                                    value={formData.title}
                                    onChange={(e) => handleInputChange('title', e.target.value)}
                                    required
                                />
                            </div>

                            <div className="add-task-field">
                                <label>Deadline</label>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                    <input
                                        type="text"
                                        placeholder="dd.mm.YYYY"
                                        value={formData.deadline}
                                        onChange={(e) => handleInputChange('deadline', e.target.value)}
                                        style={{ width: '85px', minWidth: '85px' }}
                                    />
                                    <input
                                        type="date"
                                        value={convertToDateLocal(formData.deadline)}
                                        onChange={(e) => handleDateChange('deadline', e.target.value)}
                                        style={{ width: '30px', minWidth: '30px', padding: '3px' }}
                                        title="Use date picker"
                                    />
                                </div>
                            </div>

                            <div className="add-task-field">
                                <label>Planned Date</label>
                                <div style={{ display: 'flex', gap: '5px' }}>
                                    <input
                                        type="text"
                                        placeholder="dd.mm.YYYY"
                                        value={formData.planned_date}
                                        onChange={(e) => handleInputChange('planned_date', e.target.value)}
                                        style={{ width: '85px', minWidth: '85px' }}
                                    />
                                    <input
                                        type="date"
                                        value={convertToDateLocal(formData.planned_date)}
                                        onChange={(e) => handleDateChange('planned_date', e.target.value)}
                                        style={{ width: '30px', minWidth: '30px', padding: '3px' }}
                                        title="Use date picker"
                                    />
                                </div>
                            </div>

                            <div className="add-task-field">
                                <label>Priority</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="100"
                                    placeholder="1-100"
                                    value={formData.priority}
                                    onChange={(e) => handleInputChange('priority', e.target.value)}
                                />
                            </div>

                            <div className="add-task-field">
                                <label>Duration</label>
                                <input
                                    type="number"
                                    min="1"
                                    placeholder="Minutes"
                                    value={formData.estimated_duration}
                                    onChange={(e) => handleInputChange('estimated_duration', e.target.value)}
                                />
                            </div>

                            <div className="add-task-field">
                                <label>Recurring</label>
                                <select
                                    value={formData.recurrency_type_id}
                                    onChange={(e) => handleInputChange('recurrency_type_id', e.target.value)}
                                >
                                    <option value="1">No Recurrence</option>
                                    {recurrencyTypes.map(type => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="add-task-field">
                                <label>Description</label>
                                <input
                                    type="text"
                                    placeholder="Add description (optional)..."
                                    value={formData.description}
                                    onChange={(e) => handleInputChange('description', e.target.value)}
                                />
                            </div>

                            <div className="add-task-buttons">
                                <button 
                                    type="submit" 
                                    disabled={isSubmitting || !formData.title.trim()}
                                    className="add-task-btn primary"
                                >
                                    {isSubmitting ? 'Creating...' : 'Create'}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            );
        };

        // Tasks Container Component
        function TasksContainer({ tasks, loading, error, activeTab, onTabChange, onCompleteTask, onUpdateTask, taskCounts, recurrencyTypes }) {
            const tabs = [
                { id: 'all', label: 'All Tasks' },
                { id: 'today', label: 'Today' },
                { id: 'upcoming', label: 'Upcoming' },
                { id: 'overdue', label: 'Overdue' }
            ];

            const getTabLabel = (tab) => {
                const count = taskCounts && taskCounts[tab.id] !== undefined ? taskCounts[tab.id] : '';
                return count !== '' ? `${tab.label} (${count})` : tab.label;
            };

            return (
                <div className="tasks-container">
                    <div className="tasks-header">
                        <div className="tab-buttons">
                            {tabs.map(tab => (
                                <button
                                    key={tab.id}
                                    className={`tab-button ${activeTab === tab.id ? 'active' : ''}`}
                                    onClick={() => onTabChange(tab.id)}
                                >
                                    {getTabLabel(tab)}
                                </button>
                            ))}
                        </div>
                        <div style={{display: 'flex', alignItems: 'center', gap: '15px'}}>
                        </div>
                    </div>

                    <div className="task-list">
                        {loading && (
                            <div className="loading">
                                Loading tasks...
                            </div>
                        )}

                        {error && (
                            <div className="error">
                                {error}
                            </div>
                        )}

                        {!loading && !error && tasks.length === 0 && (
                            <div className="empty-state">
                                <h3>No tasks found</h3>
                                <p>No tasks match your current filters.</p>
                            </div>
                        )}

                        {!loading && !error && tasks.length > 0 && 
                            tasks.map(task => (
                                <TaskItem 
                                    key={task.id} 
                                    task={task} 
                                    onComplete={onCompleteTask}
                                    onUpdate={onUpdateTask}
                                    recurrencyTypes={recurrencyTypes}
                                />
                            ))
                        }
                    </div>
                </div>
            );
        }

        // Task Item Component
        function TaskItem({ task, onComplete, onUpdate, recurrencyTypes }) {
            const [isExpanded, setIsExpanded] = useState(false);
            const [isEditing, setIsEditing] = useState(false);

            // Format functions for date conversion
            const formatDateForDisplay = (dateString) => {
                if (!dateString) return '';
                try {
                    const date = new Date(dateString);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    return `${day}.${month}.${year}`;
                } catch (e) {
                    return '';
                }
            };

            const formatDateForStorage = (displayString) => {
                if (!displayString) return '';
                try {
                    // Parse dd.mm.YYYY format
                    const match = displayString.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);
                    if (match) {
                        const [, day, month, year] = match;
                        return `${year}-${month}-${day}`;
                    }
                    return '';
                } catch (e) {
                    return '';
                }
            };

            const [editData, setEditData] = useState({
                title: task.title,
                description: task.description || '',
                priority: task.priority,
                estimated_duration: task.estimated_duration || '',
                deadline: task.deadline || '',
                planned_date: task.planned_date || '',
                recurrency_type_id: task.recurrency_type_id && task.recurrency_type_id !== 0 ? task.recurrency_type_id : 1
            });
            
            // Update editData when task changes (e.g., after a task update)
            React.useEffect(() => {
                setEditData({
                    title: task.title,
                    description: task.description || '',
                    priority: task.priority,
                    estimated_duration: task.estimated_duration || '',
                    deadline: task.deadline || '',
                    planned_date: task.planned_date || '',
                    recurrency_type_id: task.recurrency_type_id && task.recurrency_type_id !== 0 ? task.recurrency_type_id : 1
                });
            }, [task]);
            
            const getPriorityClass = (label) => {
                if (!label) return 'priority-medium'; // fallback for missing priority_label
                return `priority-${label.toLowerCase().replace(' ', '-')}`;
            };

            const getStatusClass = (status) => {
                if (!status) return 'status-pending'; // fallback for missing status
                return `status-${status.replace('_', '-')}`;
            };

            const getUrgencyClass = (status) => {
                if (!status) return 'urgency-normal'; // fallback for missing urgency_status
                return `urgency-${status.replace('_', '-')}`;
            };

            const toggleDescription = () => {
                setIsExpanded(!isExpanded);
            };

            const handleEditClick = () => {
                setIsEditing(true);
            };

            const handleCancelEdit = () => {
                setIsEditing(false);
                // Reset edit data to original values
                setEditData({
                    title: task.title,
                    description: task.description || '',
                    priority: task.priority,
                    estimated_duration: task.estimated_duration || '',
                    deadline: task.deadline || '',
                    planned_date: task.planned_date || '',
                    recurrency_type_id: task.recurrency_type_id && task.recurrency_type_id !== 0 ? task.recurrency_type_id : 1
                });
            };

            const handleSaveEdit = async () => {
                if (!editData.title.trim()) {
                    alert('Title is required');
                    return;
                }

                const success = await onUpdate(task.id, {
                    title: editData.title,
                    description: editData.description,
                    priority: parseInt(editData.priority),
                    estimated_duration: editData.estimated_duration ? parseInt(editData.estimated_duration) : null,
                    deadline: editData.deadline || null,
                    planned_date: editData.planned_date || null,
                    recurrency_type_id: editData.recurrency_type_id ? parseInt(editData.recurrency_type_id) : 1
                });

                if (success) {
                    setIsEditing(false);
                }
            };

            const handleInputChange = (field, value) => {
                setEditData(prev => ({
                    ...prev,
                    [field]: value
                }));
            };

            const handleDateChange = (field, value) => {
                if (value) {
                    // Convert from date format to dd.mm.YYYY for display
                    const date = new Date(value);
                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();
                    const formatted = `${day}.${month}.${year}`;
                    // Store as storage format (YYYY-MM-DD)
                    setEditData(prev => ({
                        ...prev,
                        [field]: formatDateForStorage(formatted)
                    }));
                }
            };

            const hasDescription = task.description && task.description.trim() !== '';

            return (
                <div className={`task-item ${task.status === 'completed' ? 'completed' : ''}`}>
                    <div className="task-content">
                        {isEditing ? (
                            // Edit Mode
                            <div className="task-edit-form">
                                <div className="edit-field">
                                    <label>Title *</label>
                                    <input 
                                        type="text" 
                                        value={editData.title}
                                        onChange={(e) => handleInputChange('title', e.target.value)}
                                        className="edit-input"
                                        placeholder="Task title"
                                    />
                                </div>
                                
                                <div className="edit-field">
                                    <label>Deadline</label>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <input 
                                            type="text"
                                            value={editData.deadline ? formatDateForDisplay(editData.deadline) : ''}
                                            onChange={(e) => handleInputChange('deadline', formatDateForStorage(e.target.value))}
                                            className="edit-input"
                                            placeholder="dd.mm.YYYY"
                                            style={{ flex: 1 }}
                                        />
                                        <input
                                            type="date"
                                            value={editData.deadline ? editData.deadline.slice(0, 10) : ''}
                                            onChange={(e) => handleDateChange('deadline', e.target.value)}
                                            style={{ 
                                                width: '40px', 
                                                minWidth: '40px',
                                                color: 'transparent',
                                                border: 'none',
                                                background: 'transparent',
                                                cursor: 'pointer'
                                            }}
                                            title="Use date picker"
                                        />
                                    </div>
                                </div>
                                
                                <div className="edit-field">
                                    <label>Planned Date</label>
                                    <div style={{ display: 'flex', gap: '10px' }}>
                                        <input 
                                            type="text"
                                            value={editData.planned_date ? formatDateForDisplay(editData.planned_date) : ''}
                                            onChange={(e) => handleInputChange('planned_date', formatDateForStorage(e.target.value))}
                                            className="edit-input"
                                            placeholder="dd.mm.YYYY"
                                            style={{ flex: 1 }}
                                        />
                                        <input
                                            type="date"
                                            value={editData.planned_date ? editData.planned_date.slice(0, 10) : ''}
                                            onChange={(e) => handleDateChange('planned_date', e.target.value)}
                                            style={{ 
                                                width: '40px', 
                                                minWidth: '40px',
                                                color: 'transparent',
                                                border: 'none',
                                                background: 'transparent',
                                                cursor: 'pointer'
                                            }}
                                            title="Use date picker"
                                        />
                                    </div>
                                </div>
                                
                                <div className="edit-field">
                                    <label>Priority</label>
                                    <input 
                                        type="number" 
                                        min="1" 
                                        max="100"
                                        value={editData.priority}
                                        onChange={(e) => handleInputChange('priority', e.target.value)}
                                        className="edit-input"
                                        placeholder="1-100"
                                    />
                                </div>
                                
                                <div className="edit-field">
                                    <label>Duration</label>
                                    <input 
                                        type="number" 
                                        min="1"
                                        value={editData.estimated_duration}
                                        onChange={(e) => handleInputChange('estimated_duration', e.target.value)}
                                        className="edit-input"
                                        placeholder="Minutes"
                                    />
                                </div>
                                
                                <div className="edit-field">
                                    <label>Description</label>
                                    <input 
                                        type="text"
                                        value={editData.description}
                                        onChange={(e) => handleInputChange('description', e.target.value)}
                                        className="edit-input"
                                        placeholder="Description"
                                    />
                                </div>
                                
                                <div className="edit-field">
                                    <label>Recurring</label>
                                    <select
                                        value={editData.recurrency_type_id}
                                        onChange={(e) => handleInputChange('recurrency_type_id', e.target.value)}
                                        className="edit-input"
                                    >
                                        <option value="1">No Recurrence</option>
                                        {recurrencyTypes.map(type => (
                                            <option key={type.id} value={type.id}>
                                                {type.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                
                                <div className="edit-actions">
                                    <button className="btn btn-success btn-sm" onClick={handleSaveEdit}>
                                        Save
                                    </button>
                                    <button className="btn btn-secondary btn-sm" onClick={handleCancelEdit}>
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        ) : (
                            // View Mode
                            <div className="task-main">
                                <div className="task-title" onClick={hasDescription ? toggleDescription : undefined}>
                                    {task.title}
                                    {hasDescription && (
                                        <button className="task-expand-btn" onClick={toggleDescription}>
                                            {isExpanded ? 'Hide Details' : 'Show Details'}
                                        </button>
                                    )}
                                </div>
                                
                                {hasDescription && (
                                    <div className={`task-description has-content ${isExpanded ? 'expanded' : ''}`}>
                                        {task.description}
                                    </div>
                                )}
                                
                                <div className="task-meta">
                                    {task.estimated_duration && (
                                        <span>{task.estimated_duration} min</span>
                                    )}
                                    {task.deadline_formatted && (
                                        <span>Due: {formatDateForDisplay(task.deadline)}</span>
                                    )}
                                    {task.planned_date_formatted && (
                                        <span>Planned: {formatDateForDisplay(task.planned_date)}</span>
                                    )}
                                    {task.urgency_score && (
                                        <span>Urgency: {task.urgency_score}</span>
                                    )}
                                </div>

                                <div className="task-badges">
                                    <span className={`badge ${getPriorityClass(task.priority_label)}`}>
                                        {task.priority_label} ({task.priority})
                                    </span>
                                    <span className={`badge ${getStatusClass(task.status)}`}>
                                        {task.status ? task.status.replace('_', ' ') : 'pending'}
                                    </span>
                                    {task.urgency_status && task.urgency_status !== 'normal' && (
                                        <span className={`badge ${getUrgencyClass(task.urgency_status)}`}>
                                            {task.urgency_status.replace('_', ' ')}
                                        </span>
                                    )}
                                    {task.recurrency_type && task.recurrency_type !== 'none' && (
                                        <span className="badge" style={{background: '#e1f5fe', color: '#0277bd'}}>
                                            {task.recurrency_type}
                                        </span>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="task-actions">
                            {!isEditing && task.status === 'pending' && (
                                <>
                                    <button 
                                        className="btn btn-primary btn-sm"
                                        onClick={handleEditClick}
                                        title="Edit task"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        className="btn btn-success btn-sm"
                                        onClick={() => onComplete(task.id)}
                                        title="Mark as completed"
                                    >
                                        Complete
                                    </button>
                                </>
                            )}
                            {!isEditing && task.status === 'completed' && (
                                <span className="btn btn-success btn-sm" style={{cursor: 'default', opacity: 0.7}}>
                                    Completed
                                </span>
                            )}
                        </div>
                    </div>
                </div>
            );
        }

        // Check if database is ready and render appropriate content
        <?php if ($isDatabaseReady): ?>
            ReactDOM.render(<TaskManager />, document.getElementById('root'));
        <?php else: ?>
            document.getElementById('root').innerHTML = `
                <div class="app-container">
                    <div class="error">
                        <h2>Database Setup Required</h2>
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                        <p style="margin-top: 15px;">
                            <a href="setup.php" class="btn btn-primary">
                                Go to Setup
                            </a>
                        </p>
                    </div>
                </div>
            `;
        <?php endif; ?>
    </script>
</body>
</html>