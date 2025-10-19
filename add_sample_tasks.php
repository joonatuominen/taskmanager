<?php
/**
 * Add sample tasks for demonstration
 */

require_once __DIR__ . '/database/config.php';

try {
    $taskDb = new TaskDatabase();
    
    $sampleTasks = [
        [
            'title' => 'Prepare client presentation',
            'description' => 'Create slides for quarterly review meeting with ABC Corp. Include Q3 performance metrics, budget analysis, and Q4 projections. Remember to add the new product roadmap section.',
            'estimated_duration' => 90,
            'priority' => 15,
            'deadline' => '2025-10-20 14:00:00',
            'planned_date' => '2025-10-20 10:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'title' => 'Update website content',
            'description' => 'Review and update the About Us page, add new team member bios, update service descriptions, and ensure all contact information is current. Also check for broken links.',
            'estimated_duration' => 60,
            'priority' => 35,
            'deadline' => null,
            'planned_date' => '2025-10-21 15:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'title' => 'Daily email review',
            'description' => 'Check and respond to all pending emails. Priority: client inquiries, team updates, and vendor communications. Archive or delegate non-urgent items.',
            'estimated_duration' => 30,
            'priority' => 45,
            'deadline' => null,
            'planned_date' => '2025-10-19 16:00:00',
            'recurrency_type_id' => 2 // daily
        ],
        [
            'title' => 'Plan weekend activities',
            'description' => 'Research local events, check weather forecast, coordinate with family members, and make any necessary reservations. Consider both indoor and outdoor options.',
            'estimated_duration' => 45,
            'priority' => 70,
            'deadline' => '2025-10-20 20:00:00',
            'planned_date' => '2025-10-20 18:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'title' => 'Schedule dentist appointment',
            'description' => 'Call Dr. Smith\'s office to schedule routine cleaning and checkup. Request appointment for early morning if possible. Confirm insurance coverage and bring previous X-rays.',
            'estimated_duration' => 15,
            'priority' => 55,
            'deadline' => '2025-10-25 17:00:00',
            'planned_date' => '2025-10-22 12:00:00',
            'recurrency_type_id' => 1
        ],
        [
            'title' => 'Weekly office cleanup',
            'description' => 'Organize desk, file documents, clean computer screen and keyboard, empty trash, water plants, and organize supplies. Check inventory of office supplies.',
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