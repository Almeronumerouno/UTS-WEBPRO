<?php
session_start();
include('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        echo "Error: Semua field harus diisi<br>";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            echo "Error: Username atau email sudah terdaftar<br>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO pengguna (username, email, password, created_at) VALUES (?, ?, ?, NOW())");

            if ($stmt->execute([$username, $email, $hashedPassword])) {
                header("Location: ../index.php");
                exit();
            } else {
                echo "Error saat registrasi: " . print_r($stmt->errorInfo(), true) . "<br>";
            }
        }
    }
}
?>
