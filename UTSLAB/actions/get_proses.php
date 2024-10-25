<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Get all tasks
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count tasks by status
    $completed = 0;
    $pending = 0;
    
    foreach ($tasks as $task) {
        if ($task['status'] === 'completed') {
            $completed++;
        } else {
            $pending++;
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'completed' => $completed,
            'pending' => $pending
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
?>