<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Selamat Datang - BersihXpress</title>

    <!-- (Komentar) Path ini mengasumsikan 'onboarding.html' ada di folder root -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/webview.css">
    <script src="assets/js/webview.js"></script>
    <script src="assets/js/tailwind.js"></script>
    <!-- (Komentar) Konfigurasi Font Poppins (sudah benar) -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>

<body class="flex flex-col h-screen overflow-hidden">
    <!-- (Komentar) Path ini mengasumsikan 'onboarding.html' ada di folder root -->
    <div id="loading-overlay" class="loading-container">
        <img src="assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <main id="slider-container" class="flex-grow relative">

        <!-- (Komentar) Slide 1 -->
        <div class="slide absolute inset-0 flex flex-col transition-opacity duration-500 ease-in-out">
            <div class="relative h-3/5 bg-blue-600 rounded-b-[40px] flex flex-col justify-center items-center p-6">
                <button class="btn-skip absolute top-6 right-6 text-white font-medium">Lewati</button>
                <!-- (Komentar) Pastikan path ilustrasi ini benar dari root -->
                <img src="assets/images/illustrations/Fitur.svg" alt="Ilustrasi Laundry" class="w-full max-w-xs">
            </div>
            <div class="h-2/5 flex flex-col justify-center items-center text-center p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Kelola Usaha Laundry Tanpa Ribet</h2>
                <p class="text-gray-600">Tinggalkan cara manual. Kini Anda bisa mencatat transaksi, memantau status
                    cucian, dan mengelola data pelanggan dalam satu aplikasi.</p>
            </div>
        </div>

        <!-- (Komentar) Slide 2 -->
        <div
            class="slide absolute inset-0 flex flex-col transition-opacity duration-500 ease-in-out opacity-0 pointer-events-none">
            <div class="relative h-3/5 bg-blue-600 rounded-b-[40px] flex flex-col justify-center items-center p-6">
                <button class="btn-back absolute top-6 left-6 text-white p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="btn-skip absolute top-6 right-6 text-white font-medium">Lewati</button>
                <img src="assets/images/illustrations/Fiture2.svg" alt="Ilustrasi Fitur" class="w-full max-w-xs">
            </div>
            <div class="h-2/5 flex flex-col justify-center items-center text-center p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Kelola Usaha Laundry Tanpa Ribet</h2>
                <p class="text-gray-600">Mulai dari pencatatan transaksi, cetak nota, laporan keuangan, hingga
                    perhitungan gaji karyawan, semua fitur yang Anda butuhkan ada di sini.</p>
            </div>
        </div>

        <!-- (Komentar) Slide 3 -->
        <div
            class="slide absolute inset-0 flex flex-col transition-opacity duration-500 ease-in-out opacity-0 pointer-events-none">
            <div class="relative h-3/5 bg-blue-600 rounded-b-[40px] flex flex-col justify-center items-center p-6">
                <button class="btn-back absolute top-6 left-6 text-white p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                <button class="btn-skip absolute top-6 right-6 text-white font-medium">Lewati</button>
                <img src="assets/images/illustrations/Analysis.svg" alt="Ilustrasi Keuangan" class="w-full max-w-xs">
            </div>
            <div class="h-2/5 flex flex-col justify-center items-center text-center p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Pantau Keuangan Dengan Mudah</h2>
                <p class="text-gray-600">Tidak perlu pusing rekap pemasukan dan pengeluaran. Dapatkan laporan keuangan
                    harian, mingguan, atau bulanan secara instan dan akurat.</p>
            </div>
        </div>

        <!-- (Komentar) Slide 4 -->
        <div
            class="slide absolute inset-0 flex flex-col transition-opacity duration-500 ease-in-out opacity-0 pointer-events-none">
            <div class="relative h-3/5 bg-blue-600 rounded-b-[40px] flex flex-col justify-center items-center p-6">
                <img src="assets/images/illustrations/Berhasil.svg" alt="Ilustrasi Sukses" class="w-full max-w-xs">
            </div>
            <div class="h-2/5 flex flex-col justify-center items-center text-center p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Semua Hanya Dalam Satu Genggaman</h2>
                <p class="text-gray-600">Tunggu apa lagi? Permudah manajemen usaha Anda sekarang. Daftar atau masuk
                    untuk membawa bisnis laundry Anda ke level selanjutnya.</p>
            </div>
        </div>

    </main>

    <!-- (Komentar) PERBAIKAN: Posisi Pagination Dots dan Tombol ditukar -->
    <footer class="flex-shrink-0 bg-gray-50 p-6">

        <!-- (Komentar) 1. Kontainer Tombol sekarang di atas -->
        <div id="button-container" class="space-y-3">
            <button id="btn-next"
                class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                Selanjutnya
            </button>
            <button id="btn-register"
                class="hidden w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                Daftar
            </button>
            <button id="btn-login"
                class="hidden w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                Masuk
            </button>
        </div>

        <!-- (Komentar) 2. Indikator Halaman (Dots) sekarang di bawah -->
        <!-- (Komentar) PERBAIKAN: Mengganti 'mb-4' (margin-bottom) menjadi 'mt-4' (margin-top) -->
        <div id="pagination-dots" class="flex justify-center space-x-2 mt-4">
            <div class="pagination-dot pagination-dot-active"></div>
            <div class="pagination-dot"></div>
            <div class="pagination-dot"></div>
            <div class="pagination-dot"></div>
        </div>

    </footer>

    <!-- (Komentar) Path ini mengasumsikan 'onboarding.html' ada di folder root -->
    <script src="assets/js/icons.js" defer></script>
    <script src="assets/js/main.js" defer></script>
    <script src="assets/js/onboarding1.js" defer></script>
</body>

</html>