<?php
session_start(); // Start the session

// Cek apakah pengguna telah login
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, alihkan ke halaman dashboard atau halaman lain
    header("Location: pages/dashboard.php"); // Ganti dengan halaman yang sesuai
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rachenllo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-image: url('assets/images/bakcindex.png');
            background-size: cover;
            background-position: center;
            color: white;
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            height: 100vh;
            padding: 50px;
        }

        .container {
            text-align: left;
            margin-top: 50px;
        }

        h1 {
            font-size: 8em;
            margin: 0;
        }

        p {
            font-size: 1.2em;
            margin: 10px 0 30px;
        }

        .button {
            display: flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            padding: 20px 40px;
            margin: 15px 0;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            text-align: left;
            width: 300px;
            transition: background-color 0.3s ease;
        }

        .button i {
            font-size: 2em;
            margin-right: 20px;
        }

        .button span {
            display: block;
        }

        .button small {
            display: block;
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.7);
        }

        .button:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Rachenllo</h1>
        <p>Make your own to do list and do done your task!</p>
        <button class="button" onclick="location.href='pages/login.php'">
            <i class="fas fa-user"></i>
            <span>Login<small>to access your saved tasks</small></span>
        </button>
        <button class="button" onclick="location.href='pages/register.php'">
            <i class="fas fa-user-plus"></i>
            <span>Register<small>to create and save your tasks</small></span>
        </button>
    </div>
</body>
</html>
