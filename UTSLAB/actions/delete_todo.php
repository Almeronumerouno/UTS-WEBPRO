<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $todo_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    // Pastikan todo yang akan dihapus adalah milik user yang sedang login
    $query = "DELETE FROM todos WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $todo_id, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Task successfully deleted!";
    } else {
        $_SESSION['error'] = "Failed to delete task.";
    }
    
    header('Location: ../dashboard.php');
    exit();
} else {
    header('Location: ../dashboard.php');
    exit();
}
?>