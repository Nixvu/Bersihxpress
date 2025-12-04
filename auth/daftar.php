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
    <title>Daftar - BersihXpress</title>

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

    <main class="flex-grow flex flex-col items-center px-6 -mt-32 w-full z-20 h-full overflow-y-auto pb-10 no-scrollbar">
        <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl p-8 mb-4">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Daftar</h2>
                <p class="text-gray-500 text-sm mt-1">Silahkan daftar untuk melanjutkan</p>
            </div>

            <form id="registerForm" class="space-y-4">
                <input type="hidden" name="action" value="register">

                <div>
                    <label for="nama_lengkap" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Pengguna</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" required placeholder="Nama Pengguna"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                    </div>
                </div>

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
                    <label for="no_telepon" class="block text-sm font-medium text-gray-700 mb-1.5">No Handphone</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <input type="tel" id="no_telepon" name="no_telepon" required placeholder="No Handphone"
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
                        <input type="password" id="password" name="password" required minlength="6" placeholder="Kata Sandi"
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder-gray-400 outline-none transition-all">
                    </div>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300 mt-2">
                    Daftar Akun
                </button>
            </form>

            <div class="mt-6 text-center">
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
    
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = `Mendaftar...`;
    
                fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Daftar`;
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
                                window.location.href = 'otp_daftar.php';
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registrasi Gagal',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Daftar`;
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: 'Tidak dapat terhubung ke server.',
                    });
                });
            });
        </script></body>

</html>