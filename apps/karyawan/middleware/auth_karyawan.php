<?php
// Middleware untuk autentikasi karyawan
session_start();

// Cek apakah user sudah login dan merupakan karyawan
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    // Redirect ke halaman login
    header('Location: ../../auth/masuk.php?redirect=karyawan');
    exit();
}

// Pastikan data karyawan tersedia
if (!isset($_SESSION['karyawan_data'])) {
    // Jika tidak ada data karyawan, redirect ke login
    session_destroy();
    header('Location: ../../auth/masuk.php?error=session_expired');
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>