<?php
require_once '../config/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    $redirect = getUserRole() === 'owner' ? '../apps/owner/dashboard.php' : '../apps/karyawan/dashboard.php';
    redirect($redirect);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - BersihXpress</title>
    
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
    </style>

    <script src="../assets/js/webview.js"></script>
    <script src="../assets/js/tailwind.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="../assets/js/sweetalert.js"></script>
    <link rel="stylesheet" href="../assets/css/sweetalert2.min.css">
</head>
<body class="bg-gray-50 flex flex-col h-screen overflow-hidden">
    <div id="loading-overlay" class="loading-container">
        <img src="../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

<header class="relative h-[45%] bg-blue-600 rounded-b-[40px] flex flex-col justify-center items-center pb-10 text-white overflow-hidden">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute top-5 -left-5 w-20 h-20 bg-white opacity-10 rounded-full"></div>

        <div class="z-10 text-center mb-8">
            <img src="../assets/images/illustrations/Logo.svg" alt="BersihXpress Logo" class="w-32 mx-auto brightness-0 invert">
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center px-6 -mt-32 w-full z-20">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Masuk</h1>
                <p class="text-gray-500 text-sm mt-1">Silahkan masuk untuk melanjutkan</p>
            </div>

            <form id="loginForm" class="space-y-5">
                <input type="hidden" name="action" value="login">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="email" id="email" name="email" required placeholder="Alamat Email"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                             <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required placeholder="Kata Sandi"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300 mt-4">
                    Masuk
                </button>

                <a href="daftar.php"
                    class="flex justify-center items-center w-full bg-white text-blue-600 font-bold py-3.5 px-4 rounded-xl border-2 border-blue-600 hover:bg-blue-50 focus:outline-none transition duration-300">
                    Daftar Akun
                </a>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-500">
                    Lupa kata sandi? 
                    <a href="email.php" class="text-blue-600 hover:text-blue-800 font-bold ml-1">Klik Disini</a>
                </p>
            </div>
        </div>
        
        <div class="mt-auto mb-6 text-gray-400 text-sm">
            Ver 1.0
        </div>
    </main>

    <script src="../assets/js/icons.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = `Masuk...`;

            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = `Masuk`;
                if (data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.innerHTML = `Masuk`;
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan',
                    text: 'Tidak dapat terhubung ke server.',
                });
            });
        });
    </script>
</body>
</html>