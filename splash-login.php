<?php
// splash-login.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/functions.php';

// Tentukan URL tujuan default
$redirect_url = 'auth/masuk.php';

// Jika pengguna sudah login, ubah URL tujuan ke dashboard yang sesuai
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'owner') {
        $redirect_url = 'apps/owner/dashboard.php';
    } elseif ($role === 'karyawan') {
        $redirect_url = 'apps/karyawan/dashboard.php';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BersihXpress</title>

    <!-- Meta refresh dihapus, kita akan gunakan JavaScript yang lebih fleksibel -->

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/webview.js"></script>
    <script src="assets/js/tailwind.js"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Poppins', 'sans-serif'], }, } }
        }
    </script>
</head>

<body class="bg-blue-600 flex justify-center items-center h-screen">
    <div class="text-center">
        <img src="assets/images/illustrations/Logo.svg" alt="BersihXpress Logo" class="w-40 mx-auto animate-pulse">
    </div>
    <script>
        // Tunggu sekitar 2.1 detik sebelum mengarahkan pengguna
        setTimeout(() => {
            // Mengarahkan ke URL yang sudah ditentukan oleh logika PHP di atas
            window.location.href = '<?php echo $redirect_url; ?>';
        }, 2100);
    </script>
</body>

</html>
