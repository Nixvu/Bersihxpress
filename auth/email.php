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
    <title>Lupa Kata Sandi - BersihXpress</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>

    <script src="../assets/js/webview.js"></script>
    <script src="../assets/js/tailwind.js"></script>
    <!-- SweetAlert2 CDN -->
    <script src="../assets/js/sweetalert.js"></script>
    <link rel="stylesheet" href="../assets/css/sweetalert2.min.css">
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
            <img src="../assets/images/illustrations/Email.svg" alt="Ilustrasi Lupa Sandi" class="h-48 w-auto drop-shadow-md">
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center px-6 -mt-2 w-full z-10 pt-10">
        <div class="w-full max-w-sm">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Lupa Kata Sandi</h1>
                <p class="text-gray-500 text-sm mt-1">Silahkan masukan email yang telah didaftar</p>
            </div>

            <form id="resetForm" class="space-y-6">
                <input type="hidden" name="action" value="reset_password">

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <input type="email" id="email" name="email" required placeholder="Alamat Email"
                        class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300">
                    Selanjutnya
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    sudah punta akun?
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
        // Helper function untuk menyalin OTP dan menampilkan notifikasi
        function copyOtp(otp) {
            navigator.clipboard.writeText(otp).then(() => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
                Toast.fire({ icon: 'success', title: 'Kode berhasil disalin!' });
            });
        }

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = `Mengirim...`;

            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                submitButton.innerHTML = `Reset Kata Sandi`;

                if (data.success) {
                    const otp = data.data.otp;
                    Swal.fire({
                        title: 'Kode Verifikasi Anda',
                        icon: 'success',
                        html:
                            `<p class="mb-3">Jangan berikan kode ini kepada siapapun!</p>` +
                            `<div class="my-4">
                                <strong style="font-size: 1.75em; letter-spacing: 3px; padding: 10px 15px; border: 1px dashed #ddd; background-color: #f9f9f9; border-radius: 8px;">${otp}</strong>
                            </div>
                            <button onclick="copyOtp('${otp}')" class="swal2-styled" style="background-color: #6B7280; margin-top: 10px;">Salin Kode</button>`,
                        confirmButtonText: 'Lanjutkan ke Verifikasi',
                        allowOutsideClick: false,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'otp_sandi.php';
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message,
                    });
                }
            })
            .catch(error => {
                submitButton.disabled = false;
                submitButton.innerHTML = `Reset Kata Sandi`;
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