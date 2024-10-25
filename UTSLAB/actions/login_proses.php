<?php
session_start();
include '../config/db.php';

// Fungsi untuk logging
function logLoginAttempt($username, $success) {
    $log_message = date('Y-m-d H:i:s') . " | Login attempt: $username | Success: " . ($success ? 'Yes' : 'No') . "\n";
    file_put_contents('../logs/login.log', $log_message, FILE_APPEND);
}

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit;
}

// Cek CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['login_error'] = "Invalid request.";
    header("Location: ../pages/login.php");
    exit;
}

// Cek jika masih dalam masa lockout
if (isset($_SESSION['lockout_time']) && time() < $_SESSION['lockout_time']) {
    header("Location: ../pages/login.php");
    exit;
}

// Cek metode request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input dari form
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $username = htmlspecialchars(trim($_POST['username']));
        $password = trim($_POST['password']);

        // Validasi panjang username
        if (strlen($username) > 50) {
            $_SESSION['login_error'] = "Username terlalu panjang.";
            header("Location: ../pages/login.php");
            exit;
        }

        // Inisialisasi login attempts jika belum ada
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = 0;
        }

        // Cek jumlah percobaan login
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['lockout_time'] = time() + (15 * 60); // Lockout 15 menit
            $_SESSION['login_error'] = "Terlalu banyak percobaan. Silakan tunggu 15 menit.";
            logLoginAttempt($username, false);
            header("Location: ../pages/login.php");
            exit;
        }

        try {
            // Query untuk mendapatkan pengguna
            $stmt = $pdo->prepare("SELECT * FROM pengguna WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Memeriksa hasil query
            if ($user && password_verify($password, $user['password'])) {
                // Reset login attempts jika berhasil
                $_SESSION['login_attempts'] = 0;
                unset($_SESSION['lockout_time']);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];

                // Update last login time jika kolom tersedia
                $updateStmt = $pdo->prepare("UPDATE pengguna SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Log successful login
                logLoginAttempt($username, true);

                header("Location: ../pages/dashboard.php");
                exit;
            } else {
                // Increment login attempts
                $_SESSION['login_attempts']++;
                
                // Log failed attempt
                logLoginAttempt($username, false);

                $_SESSION['login_error'] = "Username atau password salah.";
                header("Location: ../pages/login.php");
                exit;
            }
        } catch (PDOException $e) {
            $_SESSION['login_error'] = "Terjadi kesalahan sistem.";
            logLoginAttempt($username, false);
            header("Location: ../pages/login.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Harap isi username dan password.";
        header("Location: ../pages/login.php");
        exit;
    }
} else {
    header("Location: ../pages/login.php");
    exit;
}
?>