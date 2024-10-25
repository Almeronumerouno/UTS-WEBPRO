<?php 
session_start(); 

// Generate CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect ke dashboard jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rachenllo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-image: url('../assets/images/backlogin.png'); /* Ganti dengan path gambar background yang sesuai */
            background-size: cover;
            background-position: center;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.15);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            max-width: 400px;
            width: 90%; /* Tambahkan lebar maksimum kontainer */
            backdrop-filter: blur(5px);
            animation: fadeIn 1s ease-in-out;
        }
        h1 {
            font-size: 2.5rem;
            margin-bottom: 30px;
            color: white;
        }
        label {
            font-size: 1.1rem;
            margin-bottom: 5px;
            display: block;
            text-align: left;
            color: white;
        }
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            border-radius: 5px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 1rem;
        }
        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .login-container a {
            color: #ccc;
            text-decoration: none;
            font-size: 0.9em;
        }
        .login-container button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #333;
            color: white;
            font-size: 1em;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .login-container button:hover {
            background: #555;
        }
        .remember-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            width: 100%; /* Pastikan kontainer ini juga lebar penuh */
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>

        <!-- Tampilkan pesan error jika login gagal -->
        <?php
        if (isset($_SESSION['login_error'])) {
            echo "<p style='color:red;'>" . htmlspecialchars($_SESSION['login_error']) . "</p>";
            unset($_SESSION['login_error']); // Hapus error setelah ditampilkan
        }

        // Tampilkan waktu tunggu jika terlalu banyak percobaan
        if (isset($_SESSION['lockout_time'])) {
            $time_left = $_SESSION['lockout_time'] - time();
            if ($time_left > 0) {
                echo "<p style='color:red;'>Silakan tunggu " . ceil($time_left/60) . " menit sebelum mencoba lagi.</p>";
                exit;
            } else {
                unset($_SESSION['lockout_time']);
                unset($_SESSION['login_attempts']);
            }
        }
        ?>

        <form action="../actions/login_proses.php" method="post">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required maxlength="50">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            
            <div class="remember-container">
                <div>
                    <input id="remember-me" type="checkbox">
                    <label for="remember-me">Remember me</label>
                </div>
                <a href="#">Forgot Password?</a>
            </div>
            
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
