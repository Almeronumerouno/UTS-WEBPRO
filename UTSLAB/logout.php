<?php
session_start(); // Memulai session

// Mengakhiri session (logout)
session_destroy(); // Hapus semua session

// Arahkan kembali ke halaman index setelah logout
header("Location: ../index.php");
exit; // Mengakhiri eksekusi script setelah redirect
?>
