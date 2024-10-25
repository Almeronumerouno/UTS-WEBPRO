<?php
session_start();
include '../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$task_id = isset($_GET['id']) ? $_GET['id'] : null;

// Fetch existing task data
if ($task_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        $task = $stmt->fetch();
        
        if (!$task) {
            $_SESSION['error'] = "Task not found or unauthorized access.";
            header("Location: dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        die("Failed to fetch task: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_title = trim($_POST['task']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $reminder = isset($_POST['reminder']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE tasks SET 
            task = ?,
            description = ?,
            due_date = ?,
            status = ?,
            reminder = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?");
            
        $stmt->execute([
            $task_title,
            $description,
            $due_date,
            $status,
            $reminder,
            $task_id,
            $user_id
        ]);

        $_SESSION['success'] = "Task updated successfully!";
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        die("Failed to update task: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f39c12;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--card-background);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--text-color);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        input[type="text"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .save-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .cancel-btn {
            background-color: #6c757d;
            color: white;
        }

        .save-btn:hover {
            background-color: #357abd;
        }

        .cancel-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Task</h2>
        
        <form action="" method="POST">
            <div class="form-group">
                <label for="task">Task Title</label>
                <input type="text" id="task" name="task" value="<?= htmlspecialchars($task['task']) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($task['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" value="<?= htmlspecialchars($task['due_date']) ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Priority</label>
                <select id="status" name="status" required>
                    <option value="High" <?= $task['status'] === 'High' ? 'selected' : '' ?>>High</option>
                    <option value="Medium" <?= $task['status'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Low" <?= $task['status'] === 'Low' ? 'selected' : '' ?>>Low</option>
                </select>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="reminder" name="reminder" value="1" <?= $task['reminder'] ? 'checked' : '' ?>>
                <label for="reminder">Set Reminder</label>
            </div>

            <div class="button-group">
                <button type="submit" class="save-btn">Save Changes</button>
                <a href="dashboard.php"><button type="button" class="cancel-btn">Cancel</button></a>
            </div>
        </form>
    </div>
</body>
</html>