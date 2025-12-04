<?php
require_once '../config/functions.php';

// Redirect jika tidak ada session reset_otp yang terverifikasi
if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp']['verified']) || $_SESSION['reset_otp']['verified'] !== true) {
    redirect('email.php');
}

$email = $_SESSION['reset_otp']['email'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Kata Sandi - BersihXpress</title>
    
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

    <header class="relative h-[45%] bg-blue-600 rounded-b-[40px] flex flex-col items-center justify-center p-6 text-white overflow-hidden">
        <a href="masuk.php" class="absolute top-6 left-6 p-2 bg-white/20 rounded-full hover:bg-white/30 transition">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>

        <div class="absolute top-10 -left-5 w-24 h-24 bg-white opacity-10 rounded-full"></div>
        <div class="absolute -top-10 right-10 w-40 h-40 bg-white opacity-5 rounded-full"></div>

        <div class="z-10 mt-6">
            <img src="../assets/images/illustrations/updatesandi.svg" alt="Ilustrasi Ubah Sandi" class="h-48 w-auto drop-shadow-md">
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center px-6 -mt-2 w-full z-10 pt-10">
        <div class="w-full max-w-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Ubah Kata Sandi</h1>
                <p class="text-gray-500 text-sm mt-1">Silahkan masuk kata sandi baru untuk masuk</p>
            </div>

            <form id="updatePasswordForm" class="space-y-5">
                <input type="hidden" name="action" value="update_password">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" id="password" name="password" required minlength="6" placeholder="Kata Sandi Baru"
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                </div>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Konfirmasi Kata Sandi Baru"
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300 mt-4">
                    Selanjutnya
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    sudah punya akun? 
                    <a href="masuk.php" class="text-blue-600 hover:text-blue-800 font-bold ml-1">Klik Disini</a>
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
        document.getElementById('updatePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                alert('Konfirmasi kata sandi tidak cocok');
                return;
            }
            
            const formData = new FormData(this);
            
            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'selesai_sandi.php';
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem');
            });
        });
    </script>
</body>
</html>