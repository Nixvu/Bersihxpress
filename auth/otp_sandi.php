<?php
require_once '../config/functions.php';

// Redirect jika tidak ada session reset_otp
if (!isset($_SESSION['reset_otp'])) {
    redirect('email.php');
}

// Cek expired OTP
if (time() > $_SESSION['reset_otp']['expires']) {
    unset($_SESSION['reset_otp']);
    redirect('email.php');
}

$email = $_SESSION['reset_otp']['email'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP - BersihXpress</title>

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
        <a href="email.php" class="absolute top-6 left-6 p-2 bg-white/20 rounded-full hover:bg-white/30 transition">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>

        <div class="absolute top-10 right-10 w-20 h-20 bg-white opacity-10 rounded-full"></div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-white opacity-10 rounded-full"></div>

        <div class="z-10 mt-4">
            <img src="../assets/images/illustrations/OTP.svg" alt="Ilustrasi OTP" class="h-48 w-auto">
        </div>
    </header>

    <main class="flex-grow flex flex-col items-center px-6 pt-8 w-full">
        <div class="w-full max-w-sm text-center">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Konfirmasi OTP</h1>
            <p class="text-gray-500 text-sm">
                Kami telah mengirim kode OTP ke email anda<br>
                <span class="font-semibold text-gray-700"><?php echo htmlspecialchars($email); ?></span>
            </p>

            <form id="otpForm" class="mt-8 space-y-8">
                <input type="hidden" name="action" value="verify_otp">
                <input type="hidden" name="type" value="reset">

                <div class="flex justify-center gap-2">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <input type="text" maxlength="1"
                            class="w-11 h-11 text-center text-xl font-bold text-gray-800 rounded-lg border-2 border-gray-300 focus:border-blue-600 focus:ring-0 outline-none transition-all"
                            data-otp-input required>
                    <?php endfor; ?>
                </div>

                <input type="hidden" name="otp" id="otpFinal">

                <button type="submit"
                    class="w-full bg-blue-600 text-white font-bold py-3.5 px-4 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md transition duration-300">
                    Konfirmasi OTP
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Belum menerima kode?
                    <button type="button" id="resendOTP" class="text-blue-600 hover:text-blue-800 font-bold ml-1">
                        Klik Disini
                    </button>
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
        // OTP input handling
        const otpInputs = document.querySelectorAll('[data-otp-input]');
        const otpFinal = document.getElementById('otpFinal');

        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Allow only numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');

                if (e.target.value) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Auto focus first input
            if (index === 0) input.focus();
        });

        // Form submission
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = `<svg class="animate-spin inline-block w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path></svg>Memverifikasi...`;

            // Combine OTP digits
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpFinal.value = otp;

            const formData = new FormData(this);

            fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Konfirmasi OTP`;
                    if (data.success) {
                        window.location.href = data.data.redirect;
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Verifikasi Gagal',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Konfirmasi OTP`;
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Terjadi Kesalahan',
                        text: 'Tidak dapat terhubung ke server.',
                    });
                });
        });

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

        // Resend OTP
        document.getElementById('resendOTP').addEventListener('click', function() {
            const submitButton = this;
            submitButton.disabled = true;
            submitButton.innerHTML = `Mengirim ulang...`;

            const formData = new FormData();
            formData.append('action', 'resend_otp'); // Panggil aksi resend_otp
            formData.append('type', 'reset');     // Tipe untuk reset password
            formData.append('email', '<?php echo $email; ?>');

            fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Klik Disini`; // Kembalikan teks tombol

                    if (data.success) {
                        const otp = data.data.otp;
                        Swal.fire({
                            title: 'Kode OTP Baru Anda',
                            icon: 'success',
                            html:
                                `<p class="mb-3">Jangan berikan kode ini kepada siapapun!</p>` +
                                `<div class="my-4">
                                    <strong style="font-size: 1.75em; letter-spacing: 3px; padding: 10px 15px; border: 1px dashed #ddd; background-color: #f9f9f9; border-radius: 8px;">${otp}</strong>
                                </div>
                                <button onclick="copyOtp('${otp}')" class="swal2-styled" style="background-color: #6B7280; margin-top: 10px;">Salin Kode</button>`,
                            confirmButtonText: 'Oke', // Hanya konfirmasi, tidak ada redirect dari sini
                            allowOutsideClick: false,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengirim Ulang',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = `Klik Disini`;
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