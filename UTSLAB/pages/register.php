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
    <title>Register - Rachenllo</title>
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
        .register-container {
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
        .register-container a {
            color: #ccc;
            text-decoration: none;
            font-size: 0.9em;
        }
        .register-container button {
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
        .register-container button:hover {
            background: #555;
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
    <div class="register-container">
        <h1>Register</h1>

        <!-- Tampilkan pesan error jika registrasi gagal -->
        <?php
        if (isset($_SESSION['register_error'])) {
            echo "<p style='color:red;'>" . htmlspecialchars($_SESSION['register_error']) . "</p>";
            unset($_SESSION['register_error']); // Hapus error setelah ditampilkan
        }
        ?>

        <form action="../actions/register_proses.php" method="post">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required maxlength="50">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <button type="submit">REGISTER</button>
        </form>
        
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
