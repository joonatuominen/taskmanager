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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .main-content {
            display: grid;
            gap: 30px;
        }
        
        .main-content.with-sidebar {
            grid-template-columns: 250px 1fr;
        }
        
        .main-content.without-sidebar {
            grid-template-columns: 1fr;
        }
        
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .sidebar h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 1.2rem;
        }
        
        .filter-group {
            margin-bottom: 25px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
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
        
        .tasks-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .tasks-header {
            padding: 25px;
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
            max-height: 600px;
            overflow-y: auto;
        }
        
        .task-item {
            padding: 20px 25px;
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
            .main-content.with-sidebar,
            .main-content.without-sidebar {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .task-content {
                flex-direction: column;
                gap: 15px;
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
            const [stats, setStats] = useState(null);
            const [loading, setLoading] = useState(true);
            const [error, setError] = useState(null);
            const [activeTab, setActiveTab] = useState('all');
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

            // Fetch statistics
            const fetchStats = async () => {
                try {
                    const response = await fetch(`${API_BASE}/stats`);
                    const data = await response.json();
                    
                    if (data.success) {
                        setStats(data.data);
                    }
                } catch (err) {
                    console.error('Failed to fetch stats:', err);
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
                        fetchStats(); // Refresh stats
                    } else {
                        setError(data.error || 'Failed to update task');
                    }
                } catch (err) {
                    setError('Network error: ' + err.message);
                }
            };

            // Handle filter changes
            const handleFilterChange = (key, value) => {
                setFilters(prev => ({
                    ...prev,
                    [key]: value
                }));
            };

            // Effects
            useEffect(() => {
                fetchTasks();
            }, [activeTab, filters]);

            useEffect(() => {
                fetchStats();
            }, []);

            return (
                <div className="app-container">
                    <header className="header">
                        <h1><i className="fas fa-tasks"></i> Task Manager</h1>
                        <p>Organize your tasks efficiently with smart prioritization</p>
                    </header>

                    {stats && <StatsGrid stats={stats} />}

                    <div className={`main-content ${activeTab === 'all' ? 'with-sidebar' : 'without-sidebar'}`}>
                        <Sidebar 
                            filters={filters}
                            onFilterChange={handleFilterChange}
                            activeTab={activeTab}
                        />
                        
                        <TasksContainer
                            tasks={tasks}
                            loading={loading}
                            error={error}
                            activeTab={activeTab}
                            onTabChange={setActiveTab}
                            onCompleteTask={completeTask}
                        />
                    </div>
                </div>
            );
        }

        // Statistics Grid Component
        function StatsGrid({ stats }) {
            return (
                <div className="stats-grid">
                    <div className="stat-card">
                        <div className="stat-number" style={{color: '#667eea'}}>
                            {stats.total_tasks}
                        </div>
                        <div className="stat-label">Total Tasks</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-number" style={{color: '#28a745'}}>
                            {stats.completed_tasks}
                        </div>
                        <div className="stat-label">Completed</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-number" style={{color: '#ffc107'}}>
                            {stats.pending_tasks}
                        </div>
                        <div className="stat-label">Pending</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-number" style={{color: '#dc3545'}}>
                            {stats.overdue_tasks}
                        </div>
                        <div className="stat-label">Overdue</div>
                    </div>
                </div>
            );
        }

        // Sidebar Component
        function Sidebar({ filters, onFilterChange, activeTab }) {
            if (activeTab !== 'all') return null;

            return (
                <div className="sidebar">
                    <h3><i className="fas fa-filter"></i> Filters</h3>
                    
                    <div className="filter-group">
                        <label>Status</label>
                        <select 
                            value={filters.status}
                            onChange={(e) => onFilterChange('status', e.target.value)}
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
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
                        <input 
                            type="number" 
                            placeholder="Min priority (1-100)"
                            min="1" 
                            max="100"
                            value={filters.priority_min}
                            onChange={(e) => onFilterChange('priority_min', e.target.value)}
                        />
                        <input 
                            type="number" 
                            placeholder="Max priority (1-100)"
                            min="1" 
                            max="100"
                            value={filters.priority_max}
                            onChange={(e) => onFilterChange('priority_max', e.target.value)}
                            style={{marginTop: '8px'}}
                        />
                    </div>

                    <div className="filter-group">
                        <label>Search</label>
                        <input 
                            type="text" 
                            placeholder="Search tasks..."
                            value={filters.search}
                            onChange={(e) => onFilterChange('search', e.target.value)}
                        />
                    </div>
                </div>
            );
        }

        // Tasks Container Component
        function TasksContainer({ tasks, loading, error, activeTab, onTabChange, onCompleteTask }) {
            const tabs = [
                { id: 'all', label: 'All Tasks', icon: 'fas fa-list' },
                { id: 'today', label: 'Today', icon: 'fas fa-calendar-day' },
                { id: 'upcoming', label: 'Upcoming', icon: 'fas fa-clock' },
                { id: 'overdue', label: 'Overdue', icon: 'fas fa-exclamation-triangle' }
            ];

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
                                    <i className={tab.icon}></i> {tab.label}
                                </button>
                            ))}
                        </div>
                        <div style={{display: 'flex', alignItems: 'center', gap: '15px'}}>
                            <a href="add_task.php" className="btn btn-primary btn-sm">
                                <i className="fas fa-plus"></i> Add Task
                            </a>
                            <div className="task-count">
                                {tasks.length} tasks
                            </div>
                        </div>
                    </div>

                    <div className="task-list">
                        {loading && (
                            <div className="loading">
                                <i className="fas fa-spinner fa-spin"></i> Loading tasks...
                            </div>
                        )}

                        {error && (
                            <div className="error">
                                <i className="fas fa-exclamation-circle"></i> {error}
                            </div>
                        )}

                        {!loading && !error && tasks.length === 0 && (
                            <div className="empty-state">
                                <i className="fas fa-tasks"></i>
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
                                />
                            ))
                        }
                    </div>
                </div>
            );
        }

        // Task Item Component
        function TaskItem({ task, onComplete }) {
            const [isExpanded, setIsExpanded] = useState(false);
            
            const getPriorityClass = (label) => {
                return `priority-${label.toLowerCase().replace(' ', '-')}`;
            };

            const getStatusClass = (status) => {
                return `status-${status.replace('_', '-')}`;
            };

            const getUrgencyClass = (status) => {
                return `urgency-${status.replace('_', '-')}`;
            };

            const toggleDescription = () => {
                setIsExpanded(!isExpanded);
            };

            const hasDescription = task.description && task.description.trim() !== '';

            return (
                <div className={`task-item ${task.status === 'completed' ? 'completed' : ''}`}>
                    <div className="task-content">
                        <div className="task-main">
                            <div className="task-title" onClick={hasDescription ? toggleDescription : undefined}>
                                {task.title}
                                {hasDescription && (
                                    <button className="task-expand-btn" onClick={toggleDescription}>
                                        <i className={`fas fa-chevron-${isExpanded ? 'up' : 'down'}`}></i>
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
                                    <span><i className="fas fa-clock"></i> {task.estimated_duration} min</span>
                                )}
                                {task.deadline_formatted && (
                                    <span><i className="fas fa-calendar-times"></i> Due: {task.deadline_formatted}</span>
                                )}
                                {task.planned_date_formatted && (
                                    <span><i className="fas fa-calendar-check"></i> Planned: {task.planned_date_formatted}</span>
                                )}
                                {task.urgency_score && (
                                    <span><i className="fas fa-tachometer-alt"></i> Urgency: {task.urgency_score}</span>
                                )}
                            </div>

                            <div className="task-badges">
                                <span className={`badge ${getPriorityClass(task.priority_label)}`}>
                                    {task.priority_label} ({task.priority})
                                </span>
                                <span className={`badge ${getStatusClass(task.status)}`}>
                                    {task.status.replace('_', ' ')}
                                </span>
                                {task.urgency_status && task.urgency_status !== 'normal' && (
                                    <span className={`badge ${getUrgencyClass(task.urgency_status)}`}>
                                        {task.urgency_status.replace('_', ' ')}
                                    </span>
                                )}
                                {task.recurrency_type && task.recurrency_type !== 'none' && (
                                    <span className="badge" style={{background: '#e1f5fe', color: '#0277bd'}}>
                                        <i className="fas fa-redo"></i> {task.recurrency_type}
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="task-actions">
                            {task.status === 'pending' && (
                                <button 
                                    className="btn btn-success btn-sm"
                                    onClick={() => onComplete(task.id)}
                                    title="Mark as completed"
                                >
                                    <i className="fas fa-check"></i> Complete
                                </button>
                            )}
                            {task.status === 'completed' && (
                                <span className="btn btn-success btn-sm" style={{cursor: 'default', opacity: 0.7}}>
                                    <i className="fas fa-check-circle"></i> Completed
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
                        <h2><i class="fas fa-exclamation-triangle"></i> Database Setup Required</h2>
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                        <p style="margin-top: 15px;">
                            <a href="setup.php" class="btn btn-primary">
                                <i class="fas fa-cog"></i> Go to Setup
                            </a>
                        </p>
                    </div>
                </div>
            `;
        <?php endif; ?>
    </script>
</body>
</html>