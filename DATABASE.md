# Task Management System - Database Design Documentation

## 📋 Overview

This document describes the MySQL database schema designed for your PHP + React task management application. The database is designed to handle all your specified requirements with room for future enhancements.

## 🎯 Requirements Addressed

✅ **Task Description** - Text field for detailed task descriptions  
✅ **Estimated Duration** - Duration in minutes with flexibility for different time formats  
✅ **Recurrency Information** - Comprehensive system supporting daily, weekly, monthly, yearly, and custom patterns  
✅ **Optional Deadline** - Flexible deadline system with urgency calculations  
✅ **Planned Date** - When you plan to work on the task (separate from deadline)  
✅ **Priority Status** - Numeric scale from 1-100 (1 = most urgent, 100 = least urgent)

## 🗄️ Database Schema

### Core Tables

#### 1. `tasks` - Main Task Storage

```sql
CREATE TABLE tasks (
    id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT NOT NULL,
    estimated_duration INT DEFAULT NULL,              -- Duration in minutes
    priority INT DEFAULT 50 CHECK (priority >= 1 AND priority <= 100),
    deadline DATETIME DEFAULT NULL,                   -- Optional deadline
    planned_date DATETIME DEFAULT NULL,               -- Optional planned work date
    recurrency_type_id INT DEFAULT 1,                -- Links to recurrency_types
    recurrency_interval INT DEFAULT 1,               -- For custom intervals
    recurrency_end_date DATETIME DEFAULT NULL,       -- When recurrency stops
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'on_hold') DEFAULT 'pending',
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Key Features:**

- Priority scale: 1 (urgent) to 100 (low priority)
- Separate deadline and planned_date for flexible scheduling
- Comprehensive status tracking
- Automatic timestamp management

#### 2. `recurrency_types` - Recurrence Patterns

```sql
CREATE TABLE recurrency_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Pre-loaded Types:**

- `none` - One-time task
- `daily` - Every day
- `weekly` - Every week
- `monthly` - Every month
- `yearly` - Every year
- `custom` - Custom intervals

#### 3. `task_instances` - Recurring Task Instances

```sql
CREATE TABLE task_instances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_task_id INT NOT NULL,
    instance_date DATETIME NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'skipped') DEFAULT 'pending',
    actual_duration INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Purpose:** Manages individual instances of recurring tasks, allowing each occurrence to have its own status and completion tracking.

### Enhancement Tables

#### 4. `categories` - Task Organization

```sql
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) DEFAULT '#007bff',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 5. `task_categories` - Many-to-Many Relationship

```sql
CREATE TABLE task_categories (
    task_id INT,
    category_id INT,
    PRIMARY KEY (task_id, category_id)
);
```

#### 6. `task_attachments` - File Management

```sql
CREATE TABLE task_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    upload_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 7. `task_comments` - Task Notes

```sql
CREATE TABLE task_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    task_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 📊 Smart Views for Easy Querying

### 1. `task_dashboard` - Main Task View

Combines task data with calculated urgency scores and priority labels:

```sql
CREATE VIEW task_dashboard AS
SELECT
    t.id,
    t.description,
    t.estimated_duration,
    t.priority,
    GetPriorityLabel(t.priority) as priority_label,
    t.deadline,
    t.planned_date,
    t.status,
    rt.name as recurrency_type,
    CalculateUrgencyScore(t.priority, t.deadline, t.planned_date) as urgency_score,
    -- ... urgency status calculation
FROM tasks t
LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
WHERE t.status IN ('pending', 'in_progress');
```

### 2. `todays_tasks` - Today's Focus

Shows tasks due today, planned for today, or overdue:

```sql
CREATE VIEW todays_tasks AS
SELECT
    t.id,
    t.description,
    t.priority,
    GetPriorityLabel(t.priority) as priority_label,
    t.estimated_duration,
    CalculateUrgencyScore(t.priority, t.deadline, t.planned_date) as urgency_score,
    -- ... day status calculation
FROM tasks t
WHERE (
    (DATE(t.deadline) = CURDATE()) OR
    (DATE(t.planned_date) = CURDATE()) OR
    (t.deadline IS NOT NULL AND t.deadline < NOW())
)
AND t.status IN ('pending', 'in_progress')
ORDER BY urgency_score DESC;
```

### 3. `upcoming_tasks` - Next 7 Days

### 4. `overdue_tasks` - Past Due Items

### 5. `task_statistics` - Dashboard Metrics

## 🤖 Smart Functions & Procedures

### Priority Labeling Function

```sql
CREATE FUNCTION GetPriorityLabel(priority_value INT)
RETURNS VARCHAR(20)
BEGIN
    CASE
        WHEN priority_value <= 10 THEN RETURN 'Critical';
        WHEN priority_value <= 25 THEN RETURN 'High';
        WHEN priority_value <= 50 THEN RETURN 'Medium';
        WHEN priority_value <= 75 THEN RETURN 'Low';
        ELSE RETURN 'Very Low';
    END CASE;
END
```

### Urgency Score Calculation

The `CalculateUrgencyScore()` function considers:

- Base priority (inverted: lower number = higher urgency)
- Days until deadline (overdue gets highest boost)
- Days until planned date
- Combined scoring for smart task prioritization

### Recurring Task Generation

Stored procedures automatically generate task instances:

- `GenerateRecurringInstances()` - Main procedure
- `GenerateDailyInstances()` - Daily recurrence
- `GenerateWeeklyInstances()` - Weekly recurrence
- `GenerateMonthlyInstances()` - Monthly recurrence
- `GenerateYearlyInstances()` - Yearly recurrence

## 🔍 Common Query Patterns

### Get Tasks by Priority Range

```php
$taskDb = new TaskDatabase();
$urgentTasks = $taskDb->getTasks([
    'priority_min' => 1,
    'priority_max' => 25
], 'urgency_score', 'DESC');
```

### Get Today's Tasks

```php
$todayTasks = $taskDb->getTodaysTasks();
```

### Filter by Status and Search

```php
$tasks = $taskDb->getTasks([
    'status' => 'pending',
    'search' => 'project',
    'urgency_status' => 'overdue'
], 'deadline', 'ASC');
```

## 🚀 API Integration Ready

The `TaskDatabase` class provides methods for:

- ✅ `getTasks()` - Flexible filtering and sorting
- ✅ `createTask()` - New task creation
- ✅ `updateTask()` - Task modifications
- ✅ `deleteTask()` - Task removal
- ✅ `completeTask()` - Mark as completed
- ✅ `getTodaysTasks()` - Today's focus
- ✅ `getUpcomingTasks()` - Weekly preview
- ✅ `getOverdueTasks()` - Priority items

## 📱 Perfect for React Frontend

The database design supports common UI patterns:

- **Dashboard View**: `task_dashboard` with urgency scores
- **Today's View**: `todays_tasks` for daily focus
- **Calendar View**: Planned dates and deadlines
- **Priority Matrix**: Priority-based filtering
- **Search & Filter**: Text search and multi-criteria filtering
- **Categories**: Color-coded task organization

## 🔧 Setup Instructions

1. **Run Database Setup:**

   ```bash
   cd /var/www/html/taskmanager
   ./database/setup.sh
   ```

2. **Update Configuration:**
   Edit `database/config.php` with your MySQL credentials

3. **Test Setup:**
   Visit `http://localhost/taskmanager/setup.php`

4. **Start Development:**
   Use the `TaskDatabase` class for all database operations

## 🎨 UI Filtering & Sorting Options

### Filtering Options

- **Status**: pending, in_progress, completed, cancelled, on_hold
- **Priority Range**: 1-100 with smart labels
- **Urgency Status**: overdue, due_today, due_soon, normal
- **Date Ranges**: deadline, planned_date
- **Text Search**: description content
- **Categories**: tag-based filtering

### Sorting Options

- **Urgency Score**: Smart calculated priority
- **Priority**: Manual 1-100 scale
- **Deadline**: Time-sensitive sorting
- **Planned Date**: Schedule-based sorting
- **Created Date**: Chronological sorting
- **Description**: Alphabetical sorting

## 💡 Future Enhancement Ideas

- **User Management**: Multi-user support with user_id foreign keys
- **Subtasks**: Parent-child task relationships
- **Time Tracking**: Start/stop timers with actual duration logging
- **Dependencies**: Task dependency chains
- **Templates**: Reusable task templates
- **Notifications**: Deadline and reminder system
- **Analytics**: Performance metrics and reporting

## 🏆 Benefits of This Design

1. **Scalable**: Handles growing task complexity
2. **Flexible**: Supports various task management methodologies
3. **Performance**: Optimized indexes and views
4. **Maintainable**: Clean separation of concerns
5. **Feature-Rich**: Ready for advanced UI components
6. **Standards-Compliant**: Follows database best practices

Your database is now ready to power a professional-grade task management application with PHP backend and React frontend!
