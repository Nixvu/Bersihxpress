<?php
require_once '../config/functions.php';

// Redirect jika belum reset password
if (!isset($_SESSION['password_reset_success'])) {
    redirect('email.php');
}

// Hapus session setelah ditampilkan
unset($_SESSION['password_reset_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password Berhasil - BersihXpress</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>

    <script src="../assets/js/webview.js"></script>
    <script src="../assets/js/tailwind.js"></script>
</head>
<body class="bg-white flex flex-col h-screen overflow-hidden">
    <div id="loading-overlay" class="loading-container">
        <img src="../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <header class="relative h-[55%] bg-blue-600 rounded-b-[50px] flex flex-col items-center justify-end pb-0 text-white overflow-hidden">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-5 -left-5 w-20 h-20 bg-white opacity-10 rounded-full"></div>

        <div class="z-10 mb-8 w-full flex justify-center px-6">
            <img src="../assets/images/illustrations/Berhasil.svg" alt="Reset Berhasil" class="w-auto h-56 max-w-full drop-shadow-lg">
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center justify-between px-8 pt-8 pb-10 w-full text-center">
        <div class="w-full">
            <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-4">
                Kata Sandi Berhasil Diperbarui!
            </h1>
            
            <p class="text-gray-500 text-sm leading-relaxed px-4">
                Kata sandi Anda telah berhasil diperbarui. Silakan masuk kembali menggunakan kata sandi baru Anda untuk melanjutkan.
            </p>
        </div>

        <button onclick="window.location.href='masuk.php'"
            class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300 mt-6">
            Masuk Sekarang
        </button>
    </main>

    <script src="../assets/js/icons.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>