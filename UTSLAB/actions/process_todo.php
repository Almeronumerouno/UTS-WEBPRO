<?php
session_start();
include '../config/db.php'; // Pastikan path ini benar

// Cek apakah pengguna telah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $task = trim($_POST['task']);
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $description = trim($_POST['description']);
    $user_id = $_SESSION['user_id']; // Ambil user ID dari session

    // Validasi input
    if (empty($task) || empty($due_date) || empty($priority)) {
        $_SESSION['error'] = "Nama tugas, tanggal jatuh tempo, dan prioritas harus diisi.";
        header("Location: ../views/create.php");
        exit();
    }

    // Hitung selisih antara tanggal saat ini dan due date
    $currentDate = new DateTime();
    $currentDate->setTime(0, 0); // Set tanggal saat ini ke tengah malam

    $dueDate = new DateTime($due_date);
    $dueDate->setTime(0, 0); // Set due date ke tengah malam

    // Debugging output
    echo "Current Date: " . $currentDate->format('Y-m-d') . "<br>";
    echo "Due Date: " . $dueDate->format('Y-m-d') . "<br>";

    $interval = $currentDate->diff($dueDate);
    $daysLeft = $interval->days;
    
    // Reminder hanya diset jika due date lebih besar atau sama dengan current date
    $reminder = ($dueDate >= $currentDate) ? 1 : 0;

    // Debugging output
    echo "Days Left: " . $daysLeft . "<br>";
    echo "Reminder: " . ($reminder ? 'Ya' : 'Tidak') . "<br>";

    // Masukkan task baru ke database
    try {
        $stmt = $pdo->prepare("INSERT INTO tasks (task, due_date, status, user_id, description, reminder) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$task, $due_date, $priority, $user_id, $description, $reminder]);

        // Redirect ke dashboard setelah sukses menambahkan
        header("Location: ../pages/dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menambahkan tugas: " . $e->getMessage();
        header("Location: ../views/create.php");
        exit();
    }
} else {
    // Redirect ke create.php jika diakses secara langsung
    header("Location: ../views/create.php");
    exit();
}
?>
