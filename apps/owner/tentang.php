<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
    require_once __DIR__ . '/middleware/auth_owner.php';
    require_once __DIR__ . '/components/layout.php'; 
    
    // Get bisnis info including logo
    $ownerData = $_SESSION['owner_data'] ?? [];
    $bisnisId = $ownerData['bisnis_id'] ?? null;
    
    // Siapkan logo dengan placeholder yang dipersonalisasi
    $namaLengkap = $ownerData['nama_lengkap'] ?? 'B';
    $inisial = strtoupper(substr($namaLengkap, 0, 1));
    $logoSrc = "https://placehold.co/64x64/3B82F6/FFFFFF?text=$inisial"; // Default placeholder

    if ($bisnisId) {
        try {
            $stmt = $conn->prepare('SELECT logo FROM bisnis WHERE bisnis_id = ?');
            $stmt->execute([$bisnisId]);
            $bisnisInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bisnisInfo && !empty($bisnisInfo['logo'])) {
                $logoSrc = '../../' . ltrim($bisnisInfo['logo'], '/'); // Timpa dengan logo asli jika ada
            }
        } catch (PDOException $e) {
            error_log('Error loading bisnis logo: ' . $e->getMessage());
        }
    }
    ?>
    <meta charset="UTF-8">
    <!-- (Komentar) Meta tag viewport, penting untuk WebView -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Aplikasi - BersihXpress</title>
    
    <link rel="stylesheet" href="../../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    <!-- ====================================================== -->
    <!-- (Komentar) 1. KONTEN UTAMA HALAMAN                     -->
    <!-- ====================================================== -->
    <!-- (Komentar) PERUBAHAN: 'overflow-y-auto pb-24' dihapus, diganti 'flex flex-col overflow-hidden' -->
    <!-- Ini membuat konten utama menjadi container flex, bukan area scroll -->
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <!-- (Komentar) Header Biru (Sticky) -->
        <!-- (Komentar) Dibuat lebih pendek dari dashboard, karena tidak ada search bar -->
        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 pb-28 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Tentang Aplikasi</h1>
            <p class="text-sm opacity-90 text-white">BersihXpress</p>
        </header>
        
        <!-- (Komentar) Konten Utama (dimulai di bawah header) -->
        <!-- (Komentar) PERUBAHAN: 'space-y-6' dihapus, 'flex-shrink-0' ditambahkan -->
        <!-- Ini "membekukan" kartu info aplikasi agar tidak ikut scroll -->
        <main class="relative z-20 -mt-20 p-6 flex-shrink-0">
            
            <!-- (Komentar) Kartu Informasi Aplikasi (INI SEKARANG STICKY) -->
            <section class="bg-white rounded-lg shadow-md p-5 flex items-center space-x-4">
                <img src="<?php echo $logoSrc; ?>" alt="Logo BersihXpress" class="w-16 h-16 rounded-lg">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">BersihXpress</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Adalah aplikasi manajemen usaha laundry yang memiliki banyak fitur lengkap di dalamnya untuk membantu UMKM.
                    </p>
                </div>
            </section>
        </main> <!-- (Komentar) PERUBAHAN: Tag main ditutup di sini -->

        <!-- (Komentar) PERUBAHAN: Area scroll baru dibuat di sini -->
        <!-- 'flex-grow' membuatnya mengisi sisa ruang, 'overflow-y-auto' membuatnya bisa scroll -->
        <!-- 'pb-24' dipindah ke sini untuk memberi ruang bagi bottom nav -->
        <!-- (Komentar) PERUBAHAN: Menambahkan kelas 'no-scrollbar' -->
        <div class="flex-grow overflow-y-auto p-6 space-y-6 pb-24 no-scrollbar">

            <!-- (Komentar) Bagian Tim Pengembang (INI SEKARANG SCROLLABLE) -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Tim Pengembang</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden divide-y divide-gray-200">
                    
                    <div class="p-4 flex items-center space-x-4">
                        <img src="../../assets/images/credit/Dpl.png" alt="Avatar Heru" class="w-12 h-12 rounded-full">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-1.5">
                                <p class="font-bold text-gray-900">Heru Budianto, M.Kom.</p>
                                <svg data-feather="check-circle" class="w-4 h-4 text-blue-500 fill-white"></svg>
                            </div>
                            <p class="text-sm text-gray-500">DPL KP Kelompok 13</p>
                        </div>
                    </div>
                    
                    <div class="p-4 flex items-center space-x-4">
                        <img src="../../assets/images/credit/Febri.png" alt="Avatar Febriana" class="w-12 h-12 rounded-full">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-1.5">
                                <p class="font-bold text-gray-900">Febriana</p>
                                <svg data-feather="check-circle" class="w-4 h-4 text-blue-500 fill-white"></svg>
                            </div>
                            <p class="text-sm text-gray-500">UI/UX & Frontend Dev</p>
                        </div>
                    </div>
                    
                    <div class="p-4 flex items-center space-x-4">
                        <img src="../../assets/images/credit/Fariz.png" alt="Avatar Ahmad" class="w-12 h-12 rounded-full">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-1.5">
                                <p class="font-bold text-gray-900">Ahmad Alfarizi</p>
                                <svg data-feather="check-circle" class="w-4 h-4 text-blue-500 fill-white"></svg>
                            </div>
                            <p class="text-sm text-gray-500">Backend Dev</p>
                        </div>
                    </div>
                    
                    <div class="p-4 flex items-center space-x-4">
                        <img src="../../assets/images/credit/Andra.png" alt="Avatar Andra" class="w-12 h-12 rounded-full">
                        <div class="flex-grow">
                            <div class="flex items-center space-x-1.5">
                                <p class="font-bold text-gray-900">Andra Sukma Santika</p>
                                <svg data-feather="check-circle" class="w-4 h-4 text-blue-500 fill-white"></svg>
                            </div>
                            <p class="text-sm text-gray-500">Backend Dev</p>
                        </div>
                    </div>

                </div>
            </section>

            <!-- ====================================================== -->
            <!-- (Komentar) SARAN DESAIN: Bagian "Sumber Luar" (INI SEKARANG SCROLLABLE) -->
            <!-- ====================================================== -->
            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Sumber Luar</h2>
                <div class="bg-white rounded-lg shadow p-5">
                    
                    <!-- (Komentar) Bagian Ilustrasi -->
                    <div class="flex items-start">
                        <svg data-feather="image" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></svg>
                        <div class="ml-3">
                            <h3 class="font-semibold text-gray-800">Ilustrasi</h3>
                            <p class="text-sm text-gray-600">
                                Semua ilustrasi di dalam aplikasi dibuat oleh 
                                <a href="https://storyset.com/pana" target="_blank" class="text-blue-600 hover:underline">Storyset by Freepik</a>.
                            </p>
                        </div>
                    </div>

                    <!-- (Komentar) Bagian Teknologi & Library -->
                    <div class="flex items-start mt-4 pt-4 border-t">
                        <svg data-feather="code" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></svg>
                        <div class="ml-3">
                            <h3 class="font-semibold text-gray-800">Teknologi & Library</h3>
                            <p class="text-sm text-gray-600 mb-2">
                                Aplikasi ini dibangun menggunakan teknologi hebat berikut:
                            </p>
                            <!-- (Komentar) Ini adalah desain "tags/pills" yang saya sarankan -->
                            <div class="flex flex-wrap gap-2">
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">PHP</span>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">HTML5</span>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">CSS3</span>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">JavaScript</span>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">Tailwind CSS</span>
                                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full">escpos-printer</span>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
            
        </div> <!-- (Komentar) PERUBAHAN: Penutup div area scroll -->

    </div>

    <!-- ====================================================== -->
    <!-- (Komentar) 2. NAVIGASI BAWAH (BOTTOM NAV) (Sticky)     -->
    <!-- ====================================================== -->
    <!-- (Komentar) 'sticky bottom-0' membuatnya menempel di bawah -->
    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col text-gray-500 items-center px-4 py-2">
            <svg data-feather="grid" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Kelola</span>
        </a>
        <a href="transaksi.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Transaksi</span>
        </a>
        <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Beranda</span>
        </a>
        <a href="laporan.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="bar-chart-2" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Laporan</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg  px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Akun</span>
        </a>
    </nav>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>

