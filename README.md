# 🎯 Task Manager

A modern task management system built with PHP backend and React frontend, featuring smart prioritization and comprehensive task organization.

## ✨ Features

### Core Task Management

- ✅ **Task Description** - Rich text descriptions for detailed task information
- ✅ **Estimated Duration** - Time tracking in minutes for better planning
- ✅ **Smart Priority System** - 1-100 scale with automatic urgency calculation
- ✅ **Flexible Deadlines** - Optional deadlines with overdue tracking
- ✅ **Planned Dates** - Schedule when you plan to work on tasks
- ✅ **Recurrency Support** - Daily, weekly, monthly, yearly, or one-time tasks

### Smart Features

- 🎯 **Automatic Urgency Scoring** - Combines priority, deadlines, and planned dates
- 📊 **Multiple Views** - All tasks, today's tasks, upcoming, and overdue
- 🔍 **Advanced Filtering** - Filter by status, priority range, urgency, and search
- 📈 **Real-time Statistics** - Dashboard with task completion metrics
- 🎨 **Color-coded Priorities** - Visual priority indicators (Critical to Very Low)
- 📱 **Responsive Design** - Works perfectly on desktop and mobile

### Task Organization

- 🏷️ **Status Tracking** - Pending, In Progress, Completed, Cancelled, On Hold
- 🔄 **Recurring Tasks** - Automated task repetition with flexible patterns
- 📅 **Today's Focus** - Special view for today's planned and due tasks
- ⚠️ **Overdue Alerts** - Automatic identification of past-due items

## 🚀 Quick Start

### 1. Database Setup

```bash
# Run the automated setup script
./database/setup.sh

# Or set up manually:
# 1. Create MySQL database 'taskmanager'
# 2. Import database/schema.sql
# 3. Import database/simple_views.sql
```

### 2. Configuration

Edit `database/config.php` with your MySQL credentials:

```php
const DB_HOST = 'localhost';
const DB_NAME = 'taskmanager';
const DB_USER = 'your_username';
const DB_PASS = 'your_password';
```

### 3. Access the Application

- **Main App**: `http://localhost/taskmanager/`
- **Add Tasks**: `http://localhost/taskmanager/add_task.php`
- **Setup Page**: `http://localhost/taskmanager/setup.php`

### 4. Add Sample Data (Optional)

```bash
php add_sample_tasks.php
```

## 📁 File Structure

```
taskmanager/
├── index.php              # Main React-based task interface
├── api.php                 # REST API endpoints
├── add_task.php           # Task creation form
├── setup.php              # Database setup and testing
├── add_sample_tasks.php   # Sample data generator
├── database/
│   ├── config.php         # Database configuration and helpers
│   ├── schema.sql         # Main database structure
│   ├── procedures.sql     # Advanced stored procedures
│   ├── simple_views.sql   # Simplified database views
│   └── setup.sh          # Automated setup script
├── DATABASE.md            # Comprehensive database documentation
└── README.md             # This file
```

## 🔌 API Endpoints

### Tasks

- `GET /api.php/tasks` - Get all tasks with filtering
- `GET /api.php/tasks/today` - Get today's tasks
- `GET /api.php/tasks/upcoming` - Get upcoming tasks (next 7 days)
- `GET /api.php/tasks/overdue` - Get overdue tasks
- `GET /api.php/tasks/{id}` - Get specific task
- `POST /api.php/tasks` - Create new task
- `PUT /api.php/tasks/{id}` - Update task
- `DELETE /api.php/tasks/{id}` - Delete task

### Statistics

- `GET /api.php/stats` - Get task statistics

### Query Parameters (for GET /tasks)

- `status` - Filter by status (pending, in_progress, completed, etc.)
- `priority_min` / `priority_max` - Priority range filtering
- `urgency_status` - Filter by urgency (overdue, due_today, due_soon, normal)
- `search` - Text search in task descriptions
- `orderBy` - Sort field (urgency_score, priority, deadline, etc.)
- `orderDir` - Sort direction (ASC, DESC)
- `limit` - Limit number of results

## 🎨 User Interface

### Main Dashboard

- **Smart Statistics** - Total, completed, pending, and overdue task counts
- **Tabbed Views** - Easy switching between All, Today, Upcoming, and Overdue
- **Advanced Filters** - Sidebar with status, priority, urgency, and search filters
- **Task Cards** - Rich task display with priority badges and action buttons

### Task Display Features

- **Priority Color Coding** - Visual indicators for task importance
- **Smart Badges** - Status, urgency, and recurrency indicators
- **Time Information** - Estimated duration, deadlines, and planned dates
- **Quick Actions** - One-click task completion and status updates

### Add Task Form

- **Intuitive Form** - Clean, user-friendly task creation interface
- **Smart Priority Slider** - Visual priority selection with labels
- **Date/Time Pickers** - Easy deadline and planned date selection
- **Recurrency Options** - Simple recurring task setup

## 💡 Usage Examples

### Creating a High-Priority Task

1. Click "Add Task" button
2. Enter description: "Prepare quarterly presentation"
3. Set priority to 15 (High priority)
4. Set deadline for tomorrow
5. Choose "One-time task" for recurrency
6. Click "Create Task"

### Filtering Tasks

- **View only overdue tasks**: Select "Overdue" tab
- **Find urgent tasks**: Set priority filter 1-25
- **Search tasks**: Use the search box in sidebar
- **Today's focus**: Click "Today" tab for today's planned tasks

### Managing Recurring Tasks

- **Daily standup**: Create with "Daily" recurrency
- **Weekly reports**: Create with "Weekly" recurrency
- **Monthly reviews**: Create with "Monthly" recurrency

## 🛠️ Technical Details

### Backend (PHP)

- **PDO Database Layer** - Secure, prepared statement-based database access
- **RESTful API** - Clean API design with proper HTTP methods
- **Error Handling** - Comprehensive error catching and logging
- **Database Views** - Optimized queries with pre-calculated urgency scores

### Frontend (React)

- **Modern React** - Using React 18 with hooks
- **Responsive Design** - Mobile-first CSS with grid layouts
- **Real-time Updates** - Live statistics and task state management
- **User Experience** - Smooth transitions and interactive elements

### Database Design

- **Normalized Structure** - Efficient relational database design
- **Smart Indexing** - Performance-optimized queries
- **Flexible Recurrency** - Sophisticated recurring task system
- **Calculated Fields** - Automatic urgency scoring and priority labels

## 🔧 Customization

### Adding New Priority Levels

Modify the priority calculation in `database/simple_views.sql`:

```sql
CASE
    WHEN t.priority <= 5 THEN 'Urgent'
    WHEN t.priority <= 15 THEN 'Critical'
    -- Add your custom levels here
```

### Extending the API

Add new endpoints in `api.php`:

```php
case '/tasks/my-custom-endpoint':
    if ($method === 'GET') {
        myCustomFunction($taskDb);
    }
    break;
```

### Customizing the UI

- Modify CSS variables in `index.php` for color schemes
- Add new filter options in the Sidebar component
- Create custom task card layouts in TaskItem component

## 📊 Database Schema Highlights

### Core Tables

- **tasks** - Main task storage with all features
- **recurrency_types** - Recurrence pattern definitions
- **task_instances** - Individual occurrences of recurring tasks
- **categories** - Task categorization (ready for future enhancement)

### Smart Views

- **task_dashboard** - Main view with calculated urgency scores
- **todays_tasks** - Today's focus with smart prioritization
- **upcoming_tasks** - Next 7 days planning view
- **overdue_tasks** - Past-due items with days overdue
- **task_statistics** - Real-time dashboard metrics

## 🚀 Future Enhancements

### Planned Features

- 👥 **Multi-user Support** - User authentication and task ownership
- 📎 **File Attachments** - Document and image attachments to tasks
- 🔗 **Task Dependencies** - Link tasks with prerequisite relationships
- ⏱️ **Time Tracking** - Built-in timer with actual vs estimated duration
- 📧 **Notifications** - Email/SMS reminders for deadlines
- 📱 **Mobile App** - Native iOS/Android applications
- 📈 **Analytics** - Productivity insights and reporting
- 🎨 **Themes** - Dark mode and custom color schemes

### Easy Extensions

- **Categories/Tags** - Database already supports task categorization
- **Comments** - Add task comments and notes
- **Subtasks** - Break down complex tasks into smaller items
- **Templates** - Reusable task templates for common workflows

## 🤝 Contributing

This is a personal project, but contributions are welcome! Areas for improvement:

- UI/UX enhancements
- Performance optimizations
- New filtering options
- Mobile responsiveness improvements
- Additional API endpoints

## 📄 License

This project is open source and available under the MIT License.

---

**Built with ❤️ using PHP, React, and MySQL**
