<?php
/**
 * PDF Export for Tasks
 * Creates a PDF-ready HTML document that can be printed to PDF
 */

require_once __DIR__ . '/database/config.php';

// Check if we should generate the export
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    
    try {
        // Get all tasks from database
        $connection = DatabaseConfig::getConnection();
        
        $query = "
            SELECT 
                t.*,
                rt.name as recurrency_type,
                CASE 
                    WHEN t.priority <= 10 THEN 'Critical'
                    WHEN t.priority <= 25 THEN 'High'
                    WHEN t.priority <= 50 THEN 'Medium'
                    WHEN t.priority <= 75 THEN 'Low'
                    ELSE 'Very Low'
                END as priority_label
            FROM tasks t
            LEFT JOIN recurrency_types rt ON t.recurrency_type_id = rt.id
            ORDER BY t.created_at DESC, t.priority ASC
        ";
        
        $stmt = $connection->prepare($query);
        $stmt->execute();
        $tasks = $stmt->fetchAll();
        
        if ($format === 'pdf') {
            // Generate PDF-ready HTML
            $filename = date('Y-m-d') . '_Task_Manager_Export';
            
            // Summary
            $totalTasks = count($tasks);
            $pendingTasks = count(array_filter($tasks, function($t) { return $t['status'] === 'pending'; }));
            $completedTasks = count(array_filter($tasks, function($t) { return $t['status'] === 'completed'; }));
            $inProgressTasks = count(array_filter($tasks, function($t) { return $t['status'] === 'in_progress'; }));
            
            ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= date('Y-m-d') ?>_Task_Manager_Export</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .page-break { page-break-before: always; }
        }
        
        /* Import exact styles from main app */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            margin: 20px;
        }
        
        .app-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 28px;
            color: #333;
            font-weight: 600;
        }
        
        .header .subtitle {
            margin: 8px 0 0 0;
            color: #666;
            font-size: 16px;
        }
        
        /* Summary stats matching app style */
        .summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 2px solid #e9ecef;
        }
        
        .summary h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }
        
        .summary-grid {
            display: flex;
            gap: 30px;
            justify-content: space-around;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-number {
            font-size: 32px;
            font-weight: 600;
            color: #667eea;
            display: block;
        }
        
        .summary-label {
            font-size: 14px;
            color: #666;
            margin-top: 4px;
        }
        
        /* Task container matching app exactly */
        .task-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .task-item {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f3f4;
            break-inside: avoid;
        }
        
        .task-item:last-child {
            border-bottom: none;
        }
        
        .task-item.completed {
            opacity: 0.8;
            background-color: #f8f9fa;
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
        }
        
        .task-item.completed .task-title {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .task-description {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.4;
            margin-bottom: 12px;
            border-left: 3px solid #e9ecef;
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 8px 12px;
        }
        
        .task-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        
        .task-item.completed .task-meta {
            color: #adb5bd;
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
        
        /* Priority badges - exact from app */
        .priority-critical { background: #ffe6e6; color: #dc3545; }
        .priority-high { background: #fff3e0; color: #ff6f00; }
        .priority-medium { background: #fff8e1; color: #f57c00; }
        .priority-low { background: #e8f5e8; color: #2e7d32; }
        .priority-very-low { background: #f5f5f5; color: #757575; }
        
        /* Status badges - exact from app */
        .status-pending { background: #e3f2fd; color: #1565c0; }
        .status-in-progress { background: #fff3e0; color: #e65100; }
        .status-completed { background: #e8f5e8; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }
        .status-on-hold { background: #f3e5f5; color: #7b1fa2; }
        
        /* Urgency badges - exact from app */
        .urgency-overdue { background: #ffebee; color: #c62828; }
        .urgency-due-today { background: #fff3e0; color: #ef6c00; }
        .urgency-due-soon { background: #fff8e1; color: #f57c00; }
        
        .task-id {
            font-size: 0.8rem;
            color: #999;
            font-weight: 500;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border: 2px solid #667eea;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="printWithFilename()" class="btn">🖨️ Print/Save as PDF</button>
        <a href="export_tasks.php?export=txt" class="btn btn-success">📄 Text Export</a>
        <a href="index.php" class="btn">← Back to Tasks</a>
    </div>

    <div class="app-container">
        <div class="header">
            <h1>📋 Task Manager Export</h1>
            <p class="subtitle">Generated on <?= date('l, F j, Y \a\t g:i A') ?></p>
        </div>

        <div class="summary">
            <h2>📊 Summary Statistics</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <span class="summary-number"><?= $totalTasks ?></span>
                    <div class="summary-label">Total Tasks</div>
                </div>
                <div class="summary-item">
                    <span class="summary-number"><?= $pendingTasks ?></span>
                    <div class="summary-label">Pending</div>
                </div>
                <div class="summary-item">
                    <span class="summary-number"><?= $inProgressTasks ?></span>
                    <div class="summary-label">In Progress</div>
                </div>
                <div class="summary-item">
                    <span class="summary-number"><?= $completedTasks ?></span>
                    <div class="summary-label">Completed</div>
                </div>
            </div>
        </div>

        <div class="task-container">
            <?php foreach ($tasks as $i => $task): 
                // Calculate urgency status like in the app
                $urgencyClass = '';
                if ($task['deadline']) {
                    $deadlineTime = strtotime($task['deadline']);
                    $now = time();
                    $today = strtotime('today');
                    
                    if ($deadlineTime < $now) {
                        $urgencyClass = 'urgency-overdue';
                    } elseif ($deadlineTime < strtotime('tomorrow')) {
                        $urgencyClass = 'urgency-due-today';
                    } elseif ($deadlineTime < strtotime('+3 days')) {
                        $urgencyClass = 'urgency-due-soon';
                    }
                }
            ?>
                <div class="task-item <?= $task['status'] === 'completed' ? 'completed' : '' ?>">
                    <div class="task-content">
                        <div class="task-main">
                            <div class="task-title">
                                <?= htmlspecialchars($task['title'] ?: $task['description']) ?>
                            </div>
                            
                            <?php if ($task['title'] && $task['description']): ?>
                                <div class="task-description">
                                    <?= htmlspecialchars($task['description']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="task-meta">
                                <span class="task-id">ID: <?= $task['id'] ?></span>
                                <?php if ($task['deadline']): ?>
                                    <span>📅 <?= date('M j, Y', strtotime($task['deadline'])) ?></span>
                                <?php endif; ?>
                                <?php if ($task['planned_date']): ?>
                                    <span>📋 Planned: <?= date('M j, Y', strtotime($task['planned_date'])) ?></span>
                                <?php endif; ?>
                                <span>📅 Created: <?= date('M j, Y', strtotime($task['created_at'])) ?></span>
                            </div>
                            
                            <div class="task-badges">
                                <span class="badge priority-<?= strtolower(str_replace(' ', '-', $task['priority_label'])) ?>">
                                    <?= $task['priority_label'] ?>
                                </span>
                                <span class="badge status-<?= str_replace('_', '-', $task['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                </span>
                                <?php if ($urgencyClass): ?>
                                    <span class="badge <?= $urgencyClass ?>">
                                        <?php 
                                        if ($urgencyClass === 'urgency-overdue') echo 'Overdue';
                                        elseif ($urgencyClass === 'urgency-due-today') echo 'Due Today';
                                        elseif ($urgencyClass === 'urgency-due-soon') echo 'Due Soon';
                                        ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($task['recurrency_type'] && $task['recurrency_type'] !== 'none'): ?>
                                    <span class="badge" style="background: #e3f2fd; color: #1565c0;">
                                        🔄 <?= ucfirst($task['recurrency_type']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (($i + 1) % 8 === 0 && $i + 1 < count($tasks)): ?>
                    <div class="page-break"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; padding: 20px; border-top: 2px solid #ddd; color: #666;">
        <p><strong>End of Report</strong></p>
        <p>Generated by Task Manager System on <?= date('Y-m-d H:i:s') ?></p>
        <p>Total Pages: <span id="page-count"></span></p>
    </div>

    <script>
        // Auto-trigger print dialog after page loads
        window.onload = function() {
            // Small delay to ensure page is fully rendered
            setTimeout(function() {
                // Focus the window and trigger print
                window.focus();
                // Set document title for PDF filename suggestion
                document.title = '<?= $filename ?>';
                // Uncomment the line below if you want auto-print
                // window.print();
            }, 500);
        };
        
        // Update print button to use proper filename
        function printWithFilename() {
            document.title = '<?= $filename ?>';
            window.print();
        }
    </script>
</body>
</html>
            <?php
            exit;
            
        } elseif ($format === 'txt') {
            // Your existing text export code here...
            // (keeping the text export as backup option)
        }
        
    } catch (Exception $e) {
        die('Error generating export: ' . htmlspecialchars($e->getMessage()));
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Tasks to PDF</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .export-btn {
            background-color: #dc3545;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 10px 10px 0;
        }
        .export-btn:hover {
            background-color: #c82333;
        }
        .export-btn.pdf {
            background-color: #dc3545;
        }
        .export-btn.txt {
            background-color: #007bff;
        }
        .export-btn.txt:hover {
            background-color: #0056b3;
        }
        .info {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .back-link {
            color: #007bff;
            text-decoration: none;
            margin-right: 20px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .pdf-info {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Export Tasks to PDF</h1>
        
        <div class="info">
            <h3>📋 What will be included:</h3>
            <ul>
                <li><strong>All tasks</strong> - Complete database export</li>
                <li><strong>Task details</strong> - ID, title, description, priority, status</li>
                <li><strong>Dates</strong> - Creation date, deadline, planned date</li>
                <li><strong>Summary statistics</strong> - Total, pending, completed tasks</li>
                <li><strong>Professional formatting</strong> - Clean, printable layout</li>
            </ul>
        </div>

        <div class="pdf-info">
            <h4>💡 How to get PDF:</h4>
            <p>Click "PDF Export" below → It opens a print-ready page → Use your browser's Print function → Choose "Save as PDF" as destination</p>
        </div>
        
        <h3>📥 Choose Export Format:</h3>
        
        <a href="?export=pdf" class="export-btn pdf" target="_blank">📄 PDF Export</a>
        <a href="export_tasks.php?export=txt" class="export-btn txt">📝 Text File (.txt)</a>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="back-link">← Back to Task Manager</a>
            <a href="test_config.php" class="back-link">🔧 Configuration Test</a>
        </div>
    </div>
</body>
</html>