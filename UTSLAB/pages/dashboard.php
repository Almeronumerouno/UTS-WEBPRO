<?php
session_start();
include '../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle task deletion
if (isset($_GET['delete_task_id'])) {
    $task_id = $_GET['delete_task_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$task_id, $user_id]);
        header("Location: dashboard.php");
        exit();
    } catch (PDOException $e) {
        die("Failed to delete task: " . $e->getMessage());
    }
}

// Get search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build the query based on status filter
if ($status_filter === 'completed') {
    // Query for completed tasks from complete_todo table
    $query = "SELECT id, task_id, task, description, due_date, status, completed_at FROM complete_todo WHERE user_id = ?";
    $params = [$user_id];
    
    if (!empty($search)) {
        $query .= " AND (task LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $query .= " ORDER BY completed_at DESC";
} else {
    // Query for active tasks
    $query = "SELECT * FROM tasks WHERE user_id = ?";
    $params = [$user_id];

    if (!empty($search)) {
        $query .= " AND (task LIKE ? OR description LIKE ? OR status LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status_filter !== 'all') {
        $query .= " AND status = ?";
        $params[] = $status_filter;
    }
    $query .= " ORDER BY due_date ASC";
}

// Execute the main query
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}

// Get statistics for active tasks
try {
    $activeTasksQuery = "SELECT COUNT(*) as total, 
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'in progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status NOT IN ('completed', 'in progress') THEN 1 ELSE 0 END) as pending
        FROM tasks WHERE user_id = ?";
    $stmt = $pdo->prepare($activeTasksQuery);
    $stmt->execute([$user_id]);
    $taskStats = $stmt->fetch();
    
    $totalTasks = $taskStats['total'];
    $completedTasks = $taskStats['completed'];
    $inProgressTasks = $taskStats['in_progress'];
    $pendingTasks = $taskStats['pending'];
} catch (PDOException $e) {
    die("Failed to get task statistics: " . $e->getMessage());
}

// Get total completed tasks count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complete_todo WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $completedTasksTotal = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Failed to get completed tasks count: " . $e->getMessage());
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Task Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f39c12;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            padding: 20px;
            color: white;
            transition: width 0.3s ease;
        }

        .sidebar h3 {
            margin-bottom: 30px;
            font-size: 24px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar li {
            position: relative;
            padding: 10px 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border-radius: 6px;
            margin-bottom: 5px;
        }

        .sidebar li:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .search-results {
            background-color: var(--card-background);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-stats {
            margin: 10px 0;
            color: #666;
        }

        .search-filters {
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .status-filter {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .search-button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .task-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-high {
            background-color: #ff4d4d;
            color: white;
        }

        .status-medium {
            background-color: #ffa64d;
            color: white;
        }

        .status-low {
            background-color: #4da6ff;
            color: white;
        }

        .task-details {
            margin-bottom: 15px;
        }

        .reminder-badge {
            background-color: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .task-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: white;
        }

        .edit-btn {
            background-color: #4da6ff;
        }

        .delete-btn {
            background-color: #ff4d4d;
        }

        .complete-btn {
            background-color: #4CAF50;
        }

        .no-results {
            text-align: center;
            color: #666;
            grid-column: 1 / -1;
            padding: 20px;
        }

        /* Additional styles from dashboard */
        .welcome-section {
            padding: 15px 0;
            margin: 10px 0 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome-text {
            color: #ffffff;
            margin: 0 0 5px 0;
            font-size: 0.9em;
            font-weight: 500;
        }

        .date-time, .clock {
            color: #ffffff;
            opacity: 0.8;
            margin: 0;
            font-size: 0.8em;
        }
        .logout-btn {
            display: inline-block;
            background-color: #ff4d4d;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: bold;
        }

        .logout-btn:hover {
            background-color: #e60000;
        }

        .create-task-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f39c12;
            --background-color: #f0f2f5;
            --card-background: #ffffff;
            --text-color: #333333;
            --accent-color: #e74c3c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
        }

        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: var(--primary-color);
            padding: 20px;
            color: white;
            transition: width 0.3s ease;
        }

        .sidebar h3 {
            margin-bottom: 30px;
            font-size: 24px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar li {
            position: relative;
            padding: 10px 0;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border-radius: 6px;
            margin-bottom: 5px;
        }

        .sidebar li:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }

        .search-results {
            background-color: var(--card-background);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .search-stats {
            margin: 10px 0;
            color: #666;
        }

        .search-filters {
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .status-filter {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }

        .search-button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .task-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .task-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-high {
            background-color: #ff4d4d;
            color: white;
        }

        .status-medium {
            background-color: #ffa64d;
            color: white;
        }

        .status-low {
            background-color: #4da6ff;
            color: white;
        }

        .task-details {
            margin-bottom: 15px;
        }

        .reminder-badge {
            background-color: #4CAF50;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .task-actions {
            display: flex;
            gap: 10px;
        }

        .task-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            color: white;
        }

        .edit-btn {
            background-color: #4da6ff;
        }

        .delete-btn {
            background-color: #ff4d4d;
        }

        .complete-btn {
            background-color: #4CAF50;
        }

        .no-results {
            text-align: center;
            color: #666;
            grid-column: 1 / -1;
            padding: 20px;
        }

        /* Additional styles from dashboard */
        .welcome-section {
            padding: 15px 0;
            margin: 10px 0 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .welcome-text {
            color: #ffffff;
            margin: 0 0 5px 0;
            font-size: 0.9em;
            font-weight: 500;
        }

        .date-time, .clock {
            color: #ffffff;
            opacity: 0.8;
            margin: 0;
            font-size: 0.8em;
        }

        .chart-container {
            background-color: var(--card-background);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            position: relative;
            width: 100%;
            height: 400px;
        }

        #progressChart {
            max-height: 250px;
            width: 100% !important;
            height: 100% !important;
        }

        .logout-btn {
            display: inline-block;
            background-color: #ff4d4d;
            color: white;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            font-weight: bold;
        }

        .logout-btn:hover {
            background-color: #e60000;
        }

        .create-task-btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h3>Dashboard</h3>
            <div class="welcome-section">
                <p class="welcome-text">Welcome, <?= htmlspecialchars($username); ?>!</p>
                <p class="date-time">
                    <?php 
                    date_default_timezone_set('Asia/Jakarta');
                    echo date('l, d F Y'); 
                    ?>
                </p>
                <p class="clock" id="real-time-clock"></p>
            </div>
            <ul>
                <li>Home</li>
                <li>To-do</li>
                <ul>
                    <li><a href="profile.php" style="color: white;">View Profile</a></li>
                </ul>
            </ul>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>

        <div class="main-content">
            <div class="search-results">
                <h2>Task Management</h2>
                <div class="search-stats">
                    <p>Active tasks: <?= $totalTasks ?></p>
                    <p>Completed tasks: <?= $completedTasksTotal ?></p>
                    <?php if (!empty($search)): ?>
                        <p>Searching for: "<?= htmlspecialchars($search) ?>"</p>
                    <?php endif; ?>
                </div>

                <div class="search-filters">
                    <form action="" method="GET" class="filter-form">
                        <input 
                            type="text" 
                            name="search" 
                            class="search-input" 
                            placeholder="Search tasks, descriptions, or status..." 
                            value="<?= htmlspecialchars($search) ?>"
                        >
                        <select name="status" class="status-filter">
                            <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Active Tasks</option>
                            <option value="High" <?= $status_filter === 'High' ? 'selected' : '' ?>>High</option>
                            <option value="Medium" <?= $status_filter === 'Medium' ? 'selected' : '' ?>>Medium</option>
                            <option value="Low" <?= $status_filter === 'Low' ? 'selected' : '' ?>>Low</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed Tasks</option>
                        </select>
                        <button type="submit" class="search-button">Search</button>
                    </form>
                </div>

                <a href="create.php"><button class="create-task-btn">Create New Task</button></a>

                <div class="tasks-grid">
                    <?php if (!empty($tasks)): ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php if ($status_filter === 'completed'): ?>
                                <div class="task-card completed-task-card">
                                    <div class="task-header">
                                        <h3><?= htmlspecialchars($task['task']) ?></h3>
                                        <span class="status-badge status-completed">Completed</span>
                                    </div>
                                    <div class="task-details">
                                        <p><strong>Description:</strong> <?= htmlspecialchars($task['description']) ?></p>
                                        <p><strong>Due Date:</strong> <?= htmlspecialchars($task['due_date']) ?></p>
                                        <p class="completed-timestamp">Completed on: <?= htmlspecialchars($task['completed_at']) ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="task-card">
                                    <div class="task-header">
                                        <h3><?= htmlspecialchars($task['task']) ?></h3>
                                        <span class="status-badge status-<?= strtolower($task['status']) ?>">
                                            <?= htmlspecialchars($task['status']) ?>
                                        </span>
                                    </div>
                                    <div class="task-details">
                                        <p><strong>Description:</strong> <?= htmlspecialchars($task['description']) ?></p>
                                        <p><strong>Due Date:</strong> <?= htmlspecialchars($task['due_date']) ?></p>
                                        <?php if ($task['reminder']): ?>
                                            <span class="reminder-badge">Reminder Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="task-actions">
                                        <button class="edit-btn" onclick="editTask(<?= $task['id'] ?>)">Edit</button>
                                        <button class="delete-btn" onclick="deleteTask(<?= $task['id'] ?>)">Delete</button>
                                        <button class="complete-btn" onclick="completeTask(<?= $task['id'] ?>)">Complete</button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-results">No tasks found.</p>
                    <?php endif; ?>
                </div>
            </div>
    <script>
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            let minutes = now.getMinutes();
            let seconds = now.getSeconds();
            hours = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            document.getElementById('real-time-clock').textContent = `${hours}:${minutes}:${seconds} WIB`;
        }
        updateClock();
        setInterval(updateClock, 1000);

        function deleteTask(id) {
            if (confirm('Are you sure you want to delete this task?')) {
                window.location.href = `dashboard.php?delete_task_id=${id}`;
            }
        }

        function completeTask(id) {
            if (confirm('Are you sure you want to mark this task as complete?')) {
                $.ajax({
                    url: '../actions/complete_todo.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            const taskCard = $(`button[onclick="completeTask(${id})"]`).closest('.task-card');
                            
                            taskCard.fadeOut(300, function() {
                                $(this).remove();
                                
                                const totalTasksElement = $('.search-stats p:first');
                                let currentTotal = parseInt(totalTasksElement.text().match(/\d+/)[0]);
                                totalTasksElement.text(`Active tasks: ${currentTotal - 1}`);
                                
                                // Update completed tasks count
                                const completedTasksElement = $('.search-stats p:eq(1)');
                                let completedTotal = parseInt(completedTasksElement.text().match(/\d+/)[0]);
                                completedTasksElement.text(`Completed tasks: ${completedTotal + 1}`);
                                
                                updateProgressChart();
                            });

                            showAlert('Task completed successfully!', 'success');
                        } else {
                            showAlert('Error: ' + (response.message || 'Failed to complete task'), 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax error:', error);
                        showAlert('Error: Failed to communicate with server', 'error');
                    }
                });
            }
        }

        function showAlert(message, type) {
            const alertDiv = $('<div>')
                .addClass('alert')
                .addClass(`alert-${type}`)
                .text(message)
                .css({
                    'position': 'fixed',
                    'top': '20px',
                    'right': '20px',
                    'padding': '15px',
                    'border-radius': '5px',
                    'z-index': '1000',
                    'background-color': type === 'success' ? '#d4edda' : '#f8d7da',
                    'color': type === 'success' ? '#155724' : '#721c24',
                    'border': `1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'}`
                })
                .hide();
            
            $('body').append(alertDiv);
            alertDiv.fadeIn(300);
            
            setTimeout(() => {
                alertDiv.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        function editTask(id) {
            window.location.href = `edit.php?id=${id}`;
        }   
         // Add event listener for search input with debouncing
         const searchInput = document.querySelector('.search-input');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.querySelector('.filter-form').submit();
        }, 300);
    });


        // Initialize tooltips and other UI enhancements
        $(document).ready(function() {
            // Update clock every second
            setInterval(updateClock, 1000);
            
            // Add hover effects to task cards
            $('.task-card').hover(
                function() { $(this).css('transform', 'translateY(-2px)'); },
                function() { $(this).css('transform', 'translateY(0)'); }
            );

            // Add smooth transitions for status changes
            $('.status-badge').on('change', function() {
                $(this).addClass('status-change-animation');
                setTimeout(() => {
                    $(this).removeClass('status-change-animation');
                }, 300);
            });
        });

        // Add handling for status filter change
        document.querySelector('.status-filter').addEventListener('change', function() {
            document.querySelector('.filter-form').submit();
        });
    </script>
</body>
</html>