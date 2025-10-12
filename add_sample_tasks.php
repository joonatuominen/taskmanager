<?php
/**
 * Add sample tasks for demonstration
 */

require_once __DIR__ . '/database/config.php';

try {
    $taskDb = new TaskDatabase();
    
    $sampleTasks = [
        [
            'description' => 'Prepare presentation for client meeting',
            'estimated_duration' => 90,
            'priority' => 15,
            'deadline' => '2025-10-13 14:00:00',
            'planned_date' => '2025-10-13 10:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'description' => 'Update website content',
            'estimated_duration' => 60,
            'priority' => 35,
            'deadline' => null,
            'planned_date' => '2025-10-14 15:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'description' => 'Review and respond to emails',
            'estimated_duration' => 30,
            'priority' => 45,
            'deadline' => null,
            'planned_date' => '2025-10-12 16:00:00',
            'recurrency_type_id' => 2 // daily
        ],
        [
            'description' => 'Plan weekend activities',
            'estimated_duration' => 45,
            'priority' => 70,
            'deadline' => '2025-10-13 20:00:00',
            'planned_date' => '2025-10-13 18:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'description' => 'Book dentist appointment',
            'estimated_duration' => 15,
            'priority' => 55,
            'deadline' => '2025-10-20 17:00:00',
            'planned_date' => '2025-10-15 12:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'description' => 'Clean office workspace',
            'estimated_duration' => 40,
            'priority' => 60,
            'deadline' => null,
            'planned_date' => null,
            'recurrency_type_id' => 3 // weekly
        ]
    ];
    
    $created = 0;
    foreach ($sampleTasks as $task) {
        if ($taskDb->createTask($task)) {
            $created++;
        }
    }
    
    echo "✅ Successfully created {$created} sample tasks!\n";
    echo "📊 You can now view your task manager at: http://localhost/taskmanager/\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>