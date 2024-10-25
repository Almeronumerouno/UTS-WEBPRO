<?php
// Database configuration
$host = 'localhost';  // Database host
$db = 'utslab';       // Database name
$user = 'root';       // MySQL username
$pass = '';           // MySQL password (leave empty if none)

// Create a PDO instance
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set the default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle connection errors
    die("Could not connect to the database: " . $e->getMessage());
}
?>