    <?php
    // File: actions/complete_todo.php
    session_start();
    require_once '../config/db.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Authentication required']);
        exit();
    }

    if (isset($_POST['id'])) {
        try {
            $pdo->beginTransaction();
            
            $user_id = $_SESSION['user_id'];
            $task_id = $_POST['id'];
            
            // Get the task data before deleting
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
            $stmt->execute([$task_id, $user_id]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($task) {
                // Insert into complete_todo table
                $stmt = $pdo->prepare("INSERT INTO complete_todo (
                    task_id,
                    user_id,
                    task,
                    description,
                    due_date,
                    status,
                    completed_at
                ) VALUES (?, ?, ?, ?, ?, 'completed', NOW())");
                
                $stmt->execute([
                    $task['id'],
                    $task['user_id'],
                    $task['task'],
                    $task['description'],
                    $task['due_date']
                ]);
                
                // Delete from tasks table
                $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
                $stmt->execute([$task_id, $user_id]);
                
                $pdo->commit();
                echo json_encode([
                    'status' => 'success',
                    'task_id' => $task_id,
                    'message' => 'Task completed and moved to archive'
                ]);
            } else {
                throw new Exception('Task not found');
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error completing task: " . $e->getMessage());
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to complete task: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    }