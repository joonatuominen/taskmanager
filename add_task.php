<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Task - Task Manager</title>
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
        
        .container {
            max-width: 800px;
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
            text-align: center;
        }
        
        .form-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .priority-slider {
            position: relative;
        }
        
        .priority-value {
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            position: absolute;
            right: 0;
            top: -35px;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-secondary {
            background: #6c757d;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-plus-circle"></i> Add New Task</h1>
            <p>Create a new task with all the details you need</p>
        </div>

        <div class="form-container">
            <form id="taskForm">
                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-tasks"></i> Task Description *
                    </label>
                    <textarea id="description" name="description" required 
                              placeholder="Describe what needs to be done..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estimated_duration">
                            <i class="fas fa-clock"></i> Estimated Duration (minutes)
                        </label>
                        <input type="number" id="estimated_duration" name="estimated_duration" 
                               min="1" placeholder="60">
                    </div>

                    <div class="form-group priority-slider">
                        <label for="priority">
                            <i class="fas fa-exclamation-triangle"></i> Priority (1 = Most Urgent, 100 = Least Urgent)
                        </label>
                        <div class="priority-value" id="priorityValue">50</div>
                        <input type="range" id="priority" name="priority" 
                               min="1" max="100" value="50" 
                               style="width: 100%; margin-top: 10px;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="deadline">
                            <i class="fas fa-calendar-times"></i> Deadline (Optional)
                        </label>
                        <input type="datetime-local" id="deadline" name="deadline">
                    </div>

                    <div class="form-group">
                        <label for="planned_date">
                            <i class="fas fa-calendar-check"></i> Planned Date (Optional)
                        </label>
                        <input type="datetime-local" id="planned_date" name="planned_date">
                    </div>
                </div>

                <div class="form-group">
                    <label for="recurrency_type_id">
                        <i class="fas fa-redo"></i> Recurrency
                    </label>
                    <select id="recurrency_type_id" name="recurrency_type_id">
                        <option value="1">One-time task</option>
                        <option value="2">Daily</option>
                        <option value="3">Weekly</option>
                        <option value="4">Monthly</option>
                        <option value="5">Yearly</option>
                    </select>
                </div>

                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Tasks
                    </a>
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Update priority value display
        const prioritySlider = document.getElementById('priority');
        const priorityValue = document.getElementById('priorityValue');
        
        prioritySlider.addEventListener('input', function() {
            const value = this.value;
            priorityValue.textContent = value;
            
            // Change color based on priority
            if (value <= 10) {
                priorityValue.style.background = '#dc3545';
                priorityValue.textContent = value + ' (Critical)';
            } else if (value <= 25) {
                priorityValue.style.background = '#fd7e14';
                priorityValue.textContent = value + ' (High)';
            } else if (value <= 50) {
                priorityValue.style.background = '#ffc107';
                priorityValue.textContent = value + ' (Medium)';
            } else if (value <= 75) {
                priorityValue.style.background = '#28a745';
                priorityValue.textContent = value + ' (Low)';
            } else {
                priorityValue.style.background = '#6c757d';
                priorityValue.textContent = value + ' (Very Low)';
            }
        });

        // Handle form submission
        document.getElementById('taskForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            // Convert form data to object
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    data[key] = value;
                }
            }
            
            // Convert numeric fields
            if (data.estimated_duration) {
                data.estimated_duration = parseInt(data.estimated_duration);
            }
            if (data.priority) {
                data.priority = parseInt(data.priority);
            }
            if (data.recurrency_type_id) {
                data.recurrency_type_id = parseInt(data.recurrency_type_id);
            }
            
            try {
                const response = await fetch('/taskmanager/api.php/tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Show success message
                    const successDiv = document.createElement('div');
                    successDiv.className = 'success-message';
                    successDiv.innerHTML = '<i class="fas fa-check-circle"></i> Task created successfully!';
                    
                    const form = document.getElementById('taskForm');
                    form.parentNode.insertBefore(successDiv, form);
                    
                    // Reset form
                    form.reset();
                    prioritySlider.value = 50;
                    priorityValue.textContent = '50';
                    priorityValue.style.background = '#ffc107';
                    
                    // Scroll to top
                    window.scrollTo(0, 0);
                    
                    // Auto redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                    
                } else {
                    throw new Error(result.error || 'Failed to create task');
                }
                
            } catch (error) {
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + error.message;
                
                const form = document.getElementById('taskForm');
                form.parentNode.insertBefore(errorDiv, form);
                
                // Scroll to top
                window.scrollTo(0, 0);
                
                // Remove error message after 5 seconds
                setTimeout(() => {
                    errorDiv.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>