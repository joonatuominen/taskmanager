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
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .date-input-group {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .date-input-group input[type="text"] {
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
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='%23666' d='M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM2 2a1 1 0 0 0-1 1v1h14V3a1 1 0 0 0-1-1H2zm13 3H1v9a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V5z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: center;
            background-size: 16px 16px;
        }
        
        .calendar-picker::-webkit-calendar-picker-indicator {
            opacity: 0;
            width: 100%;
            height: 100%;
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
                    <label for="title">
                        <i class="fas fa-heading"></i> Task Title *
                    </label>
                    <input type="text" id="title" name="title" required 
                           placeholder="Enter a short, descriptive title for your task..."
                           maxlength="255">
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-sticky-note"></i> Detailed Notes & Description
                    </label>
                    <textarea id="description" name="description" 
                              placeholder="Add detailed notes, steps, links, or any additional information about this task..."></textarea>
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
                            <i class="fas fa-calendar-times"></i> Deadline (Optional) - dd.mm.YYYY HH:mm
                        </label>
                        <div class="date-input-group">
                            <input type="text" id="deadline" name="deadline" 
                                   placeholder="21.10.2025 14:30" 
                                   pattern="\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}">
                            <input type="datetime-local" id="deadline_picker" 
                                   title="Use calendar picker"
                                   class="calendar-picker">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="planned_date">
                            <i class="fas fa-calendar-check"></i> Planned Date (Optional) - dd.mm.YYYY HH:mm
                        </label>
                        <div class="date-input-group">
                            <input type="text" id="planned_date" name="planned_date" 
                                   placeholder="21.10.2025 14:30" 
                                   pattern="\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}">
                            <input type="datetime-local" id="planned_date_picker" 
                                   title="Use calendar picker"
                                   class="calendar-picker">
                        </div>
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

        // Date format conversion function
        function formatDateForStorage(displayString) {
            if (!displayString) return null;
            try {
                // Parse dd.mm.YYYY HH:mm format
                const match = displayString.match(/^(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2})$/);
                if (match) {
                    const [, day, month, year, hours, minutes] = match;
                    return `${year}-${month}-${day} ${hours}:${minutes}:00`;
                }
                return null;
            } catch (e) {
                return null;
            }
        }

        // Add input validation for date fields
        function validateDateFormat(input) {
            const value = input.value;
            if (value && !value.match(/^\d{2}\.\d{2}\.\d{4} \d{2}:\d{2}$/)) {
                input.setCustomValidity('Please use format: dd.mm.YYYY HH:mm (e.g., 21.10.2025 14:30)');
            } else {
                input.setCustomValidity('');
            }
        }

        // Add event listeners for date validation
        document.getElementById('deadline').addEventListener('input', function() {
            validateDateFormat(this);
        });
        
        document.getElementById('planned_date').addEventListener('input', function() {
            validateDateFormat(this);
        });

        // Connect calendar pickers to text inputs
        function formatDateForDisplay(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${day}.${month}.${year} ${hours}:${minutes}`;
            } catch (e) {
                return '';
            }
        }

        // Calendar picker event listeners
        document.getElementById('deadline_picker').addEventListener('change', function() {
            console.log('Deadline picker changed:', this.value);
            if (this.value) {
                document.getElementById('deadline').value = formatDateForDisplay(this.value);
                validateDateFormat(document.getElementById('deadline'));
            }
        });

        document.getElementById('planned_date_picker').addEventListener('change', function() {
            console.log('Planned date picker changed:', this.value);
            if (this.value) {
                document.getElementById('planned_date').value = formatDateForDisplay(this.value);
                validateDateFormat(document.getElementById('planned_date'));
            }
        });

        // Add click event prevention to avoid form submission conflicts
        document.getElementById('deadline_picker').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        document.getElementById('planned_date_picker').addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Handle cancel button
        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.querySelector('button[type="button"]');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    console.log('Cancel button clicked');
                    window.location.href = 'index.php';
                });
            }
        });

        // Handle form submission
        document.getElementById('taskForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('Form submission started');
            
            // Add visual feedback
            const submitBtn = this.querySelector('button[type="submit"]');
            const cancelBtn = this.querySelector('button[type="button"]');
            const originalSubmitText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            cancelBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Task...';
            
            const formData = new FormData(this);
            const data = {};
            
            // Convert form data to object
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '' || key === 'description') {
                    data[key] = value;
                }
            }
            
            console.log('Form data collected:', data);
            
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

            // Convert date fields from dd.mm.YYYY format to storage format
            if (data.deadline) {
                data.deadline = formatDateForStorage(data.deadline);
                console.log('Converted deadline:', data.deadline);
            }
            if (data.planned_date) {
                data.planned_date = formatDateForStorage(data.planned_date);
                console.log('Converted planned_date:', data.planned_date);
            }
            
            console.log('Final data for API:', data);
            
            try {
                const response = await fetch('/taskmanager/api.php/tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                console.log('API response:', result);
                
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
                    priorityValue.textContent = '50 (Medium)';
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
                console.error('Form submission error:', error);
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
            } finally {
                // Re-enable buttons
                submitBtn.disabled = false;
                cancelBtn.disabled = false;
                submitBtn.innerHTML = originalSubmitText;
            }
        });
    </script>
</body>
</html>