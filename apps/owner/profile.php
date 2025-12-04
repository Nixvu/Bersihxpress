<?php
    require_once __DIR__ . '/middleware/auth_owner.php';
    require_once __DIR__ . '/components/layout.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../config/functions.php';


    $ownerData = $_SESSION['owner_data'] ?? [];
    $bisnisId = $ownerData['bisnis_id'] ?? null;
    $bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

    $ownerId = $ownerData['user_id'] ?? null;

    $bisnis = [];
    $owner = [];
    $statistics = [
        'total_transaksi' => 0,
        'total_pendapatan' => 0,
        'total_karyawan' => 0,
        'total_pelanggan' => 0,
        'transaksi_bulan_ini' => 0,
        'pendapatan_bulan_ini' => 0
    ];

    try {
        if ($bisnisId && $conn) {
            // Ambil data bisnis dan logo
            $stmt = $conn->prepare('SELECT * FROM bisnis WHERE bisnis_id = ?');
            $stmt->execute([$bisnisId]);
            $bisnis = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Siapkan logo dengan placeholder yang dipersonalisasi
            $namaLengkap = $ownerData['nama_lengkap'] ?? 'B';
            $inisial = strtoupper(substr($namaLengkap, 0, 1));
            $logoPath = "https://placehold.co/64x64/3B82F6/FFFFFF?text=$inisial"; // Default placeholder
            
            if (!empty($bisnis['logo'])) {
                $logoPath = '../../' . ltrim($bisnis['logo'], '/'); // Timpa dengan logo asli jika ada
            }

            // Ambil statistik bisnis dengan satu query untuk transaksi dan pendapatan
            $stmt = $conn->prepare('
                SELECT 
                    (SELECT COUNT(*) FROM transaksi WHERE bisnis_id = ?) as total_transaksi,
                    (SELECT COALESCE(SUM(dibayar), 0) FROM transaksi WHERE bisnis_id = ? AND status != "batal") as total_pendapatan,
                    (SELECT COUNT(*) FROM karyawan WHERE bisnis_id = ? AND status = "aktif") as total_karyawan,
                    (SELECT COUNT(*) FROM pelanggan WHERE bisnis_id = ?) as total_pelanggan,
                    (SELECT COUNT(*) FROM transaksi WHERE bisnis_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as transaksi_bulan_ini,
                    (SELECT COALESCE(SUM(dibayar), 0) FROM transaksi WHERE bisnis_id = ? AND status != "batal" AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())) as pendapatan_bulan_ini
            ');
            $stmt->execute([$bisnisId, $bisnisId, $bisnisId, $bisnisId, $bisnisId, $bisnisId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $statistics['total_transaksi'] = (int)$row['total_transaksi'];
                $statistics['total_pendapatan'] = (float)$row['total_pendapatan'];
                $statistics['total_karyawan'] = (int)$row['total_karyawan'];
                $statistics['total_pelanggan'] = (int)$row['total_pelanggan'];
                $statistics['transaksi_bulan_ini'] = (int)$row['transaksi_bulan_ini'];
                $statistics['pendapatan_bulan_ini'] = (float)$row['pendapatan_bulan_ini'];
            }
        }

        if ($ownerId && $conn) {
            $stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
            $stmt->execute([$ownerId]);
            $owner = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
    } catch (Exception $e) {
        error_log('Profile error: ' . $e->getMessage());
    }

    function formatCurrency($amount) {
        return 'Rp ' . number_format($amount ?: 0, 0, ',', '.');
    }

    function formatDate($date) {
        if (!$date) return '-';
        return date('d M Y', strtotime($date));
    }
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta   charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <!-- Main Content -->
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <!-- Wrapper Sticky untuk Header dan Kartu Profil -->
        <div class="flex-shrink-0">
            <header class="relative bg-blue-600 h-56 w-full rounded-b-[40px] p-6 text-white z-10">
                <h1 class="text-2xl font-bold">Pengaturan Akun</h1>
                <p class="text-sm opacity-90"><?php echo htmlspecialchars($bisnisNama); ?></p>
            </header>

            <main class="relative z-20 -mt-24 px-6">
                <section class="bg-white rounded-lg shadow-md p-5">
                    <div class="flex items-center space-x-4 border-b pb-4">
                        <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Avatar Pengguna"
                            class="w-16 h-16 rounded-full" id="profil-avatar-main">
                        <div>
                            <p class="text-lg font-bold text-gray-900 flex items-center" id="profil-nama-main">
                                <?php echo htmlspecialchars($owner['nama_lengkap'] ?? 'Owner'); ?>
                                <svg data-feather="check-circle" class="w-5 h-5 text-blue-500 fill-white ml-2"></svg>
                            </p>
                            <p class="text-sm text-gray-500">Owner <?php echo htmlspecialchars($bisnisNama); ?></p>
                        </div>
                    </div>
                    <div class="flex justify-around text-center pt-4">
                        <div class="w-2/3">
                            <p class="text-sm font-semibold text-gray-900 truncate" id="profil-email-main"><?php echo htmlspecialchars($owner['email'] ?? '-'); ?></p>
                            <span class="text-sm text-gray-500">Email</span>
                        </div>
                        <div class="w-1/3 border-l">
                            <p class="text-sm font-semibold text-gray-900"><?php echo formatDate($owner['created_at'] ?? null) ?: date('d/m/y'); ?></p>
                            <span class="text-sm text-gray-500">Bergabung</span>
                        </div>
                    </div>
                </section>
            </main>
        </div> 
        
        <!-- Area Menu (Scrollable) -->
        <div class="flex-grow overflow-y-auto no-scrollbar px-6 pb-24">
            <!-- Section Menu Pengaturan -->
            <section class="mt-6">
                <h2 class="text-base font-semibold text-gray-600 mb-2 px-1">Pengaturan Akun</h2>
                <div class="bg-white rounded-lg shadow space-y-1">
                    
                <!-- Menu Edit Profil -->
                    <button id="btn-edit-profil" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 rounded-t-lg text-left">
                        <div class="flex items-center">
                            <svg data-feather="user" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <div>
                                <p class="font-medium text-gray-800">Edit Profil</p>
                                <p class="text-sm text-gray-500">Perbaharui informasi akun</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </button>

                    <!-- Menu Edit Bisnis -->
                    <button id="btn-edit-bisnis" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 text-left">
                        <div class="flex items-center">
                            <svg data-feather="home" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <div>
                                <p class="font-medium text-gray-800">Edit Bisnis</p>
                                <p class="text-sm text-gray-500">Informasi bisnis laundry</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </button>

                    <!-- Menu Ubah Kata Sandi -->
                    <button id="btn-ubah-password" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 text-left">
                        <div class="flex items-center">
                            <svg data-feather="lock" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <div>
                                <p class="font-medium text-gray-800">Ubah Kata Sandi</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </button>
                </div>
            </section>

            <!-- Section Menu Laporan & Statistik -->
            <section class="mt-6">
                <h2 class="text-base font-semibold text-gray-600 mb-2 px-1">Bisnis & Analitik</h2>
                <div class="bg-white rounded-lg shadow divide-y">

                    <!-- Kartu Statistik Ringkas -->
                    <div class="p-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Ringkasan Bisnis</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-blue-50 rounded-lg p-3">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-blue-100 rounded-lg mr-2">
                                        <svg data-feather="file-text" class="w-4 h-4 text-blue-600"></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Transaksi</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $statistics['total_transaksi']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 rounded-lg p-3">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-green-100 rounded-lg mr-2">
                                        <svg data-feather="dollar-sign" class="w-4 h-4 text-green-600"></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Pendapatan</p>
                                        <p class="text-xs font-bold text-gray-900"><?php echo formatCurrency($statistics['total_pendapatan']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-yellow-50 rounded-lg p-3">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-yellow-100 rounded-lg mr-2">
                                        <svg data-feather="users" class="w-4 h-4 text-yellow-600"></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Karyawan</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $statistics['total_karyawan']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 rounded-lg p-3">
                                <div class="flex items-center">
                                    <div class="p-1.5 bg-purple-100 rounded-lg mr-2">
                                        <svg data-feather="heart" class="w-4 h-4 text-purple-600"></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Pelanggan</p>
                                        <p class="text-sm font-bold text-gray-900"><?php echo $statistics['total_pelanggan']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tautan ke halaman laporan lengkap -->
                    <a href="laporan.php" class="flex items-center justify-between p-4 hover:bg-gray-50">
                        <div class="flex items-center">
                            <svg data-feather="bar-chart-2" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <div>
                                <p class="font-medium text-gray-800">Laporan Lengkap</p>
                                <p class="text-sm text-gray-500">Analitik dan grafik detail</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </a>
                </div>
            </section>

            <!-- Section Menu Aplikasi -->
            <section class="mt-6">
                <h2 class="text-base font-semibold text-gray-600 mb-2 px-1">Pengaturan Aplikasi</h2>
                <div class="bg-white rounded-lg shadow divide-y">

                    <!-- Toggle Notifikasi -->
                    <div class="flex items-center justify-between p-4">
                        <div class="flex items-center">
                            <svg data-feather="bell" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <p class="font-medium text-gray-800">Notifikasi</p>
                        </div>
                        <label for="toggleNotif" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="toggleNotif" class="sr-only toggle-checkbox" checked>
                            <div class="w-11 h-6 bg-gray-200 rounded-full toggle-label-bg"></div>
                            <div
                                class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform toggle-label">
                            </div>
                        </label>
                    </div>

                    <!-- Tautan 'Tentang Aplikasi' -->
                    <a href="tentang.php" class="flex items-center justify-between p-4 hover:bg-gray-50">
                        <div class="flex items-center">
                            <svg data-feather="info" class="w-5 h-5 text-gray-500 mr-4"></svg>
                            <div>
                                <p class="font-medium text-gray-800">Tentang Aplikasi</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </a>
                </div>
            </section>

            <!-- Section Aksi Keluar Akun -->
            <section class="mt-6">
                <div class="bg-white rounded-lg shadow">
                    <a href="../../logout.php""
                        class="w-full flex items-center justify-between p-4 text-red-600 hover:bg-red-50 rounded-lg">
                        <div class="flex items-center">
                            <svg data-feather="log-out" class="w-5 h-5 mr-4"></svg>
                            <p class="font-medium">Keluar Akun</p>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5"></svg>
                    </a>
                </div>
            </section>

        </div>
    </div> 
    
    <!-- Navigasi Button -->
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

    <!-- Modal Container -->
    <div id="modal-container" class="hidden z-30">
        <!-- Backdrop Gelap (z-40) -->
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>

        <!-- MODAL 1: Edit Profil Owner -->
        <div id="modal-edit-profil"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3"><div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div></div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Profil</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-edit-profil">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-profil" class="space-y-4" method="POST" action="update-profile.php" enctype="multipart/form-data">
                    <div class="flex flex-col items-center">
                        <?php
                        // Definisikan placeholder khusus untuk form dengan ukuran lebih besar
                        $formLogoPath = !empty($bisnis['logo']) 
                            ? '../../' . ltrim($bisnis['logo'], '/') 
                            : "https://placehold.co/96x96/3B82F6/FFFFFF?text=$inisial";
                        ?>
                        <img src="<?php echo htmlspecialchars($formLogoPath); ?>" alt="Avatar" class="w-24 h-24 rounded-full mb-2" id="profil-avatar-form">
                        <input type="file" id="profil_upload" name="profil_upload" accept="image/*" style="display:none;">
                        <label for="profil_upload" class="cursor-pointer text-sm font-medium text-blue-600 hover:text-blue-700">
                            Ganti Foto
                        </label>
                    </div>
                    <div><label for="profil_nama" class="text-sm font-medium text-gray-600">Nama Lengkap</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="user" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="text" id="profil_nama" name="nama_lengkap" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" value="<?php echo htmlspecialchars($owner['nama_lengkap'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div><label for="profil_email" class="text-sm font-medium text-gray-600">Email</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="email" id="profil_email" name="email" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-50" value="<?php echo htmlspecialchars($owner['email'] ?? ''); ?>" readonly>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Email tidak dapat diubah dari sini</p>
                    </div>
                    <div><label for="profil_no_hp" class="text-sm font-medium text-gray-600">No. Handphone</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="tel" id="profil_no_hp" name="no_telepon" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Masukkan No. Handphone" value="<?php echo htmlspecialchars($owner['no_telepon'] ?? ''); ?>">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Format: 081234567890 atau +6281234567890</p>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" class="btn-close-modal w-full bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-200" data-modal-id="modal-edit-profil">
                        Batal
                    </button>
                    <button type="submit" form="form-edit-profil" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span class="btn-text">Simpan Perubahan</span>
                        <span class="btn-loading hidden">
                            <svg class="animate-spin inline-block w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle>
                                <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- MODAL 2: Edit Bisnis (Include dari file terpisah) -->
        <?php include __DIR__ . '/modals/modal-edit-usaha.php'; ?>

        <!-- MODAL 3: Ubah Password -->
        <div id="modal-ubah-password"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
            <div class="flex-shrink-0">
                <div class="w-full py-3"><div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div></div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Ubah Kata Sandi</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-ubah-password">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-ubah-password" class="space-y-4" method="POST">
                    <div><label for="pass_lama" class="text-sm font-medium text-gray-600">Password Lama</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="lock" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="password" id="pass_lama" name="password_lama" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Masukkan password lama">
                        </div>
                    </div>
                    <div><label for="pass_baru" class="text-sm font-medium text-gray-600">Password Baru</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="key" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="password" id="pass_baru" name="password_baru" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Masukkan password baru">
                        </div>
                    </div>
                    <div><label for="pass_konfirmasi" class="text-sm font-medium text-gray-600">Konfirmasi Password Baru</label>
                        <div class="relative mt-1"><span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="key" class="h-5 w-5 text-gray-400"></svg></span>
                            <input type="password" id="pass_konfirmasi" name="password_konfirmasi" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Ulangi password baru">
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-ubah-password"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-ubah-password">
                    Simpan Password
                </button>
            </div>
        </div>

        <!-- (Komentar) MODAL 4: Konfirmasi Keluar Akun (Centered, z-60)
        <div id="modal-keluar-akun"
            class="modal-centered fixed inset-0 z-50 flex items-center justify-center p-6">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
                <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
                    <svg data-feather="log-out" class="w-6 h-6 text-red-600"></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2 text-center">Keluar Akun</h2>
                <p class="text-sm text-gray-600 mb-6 text-center">Anda yakin ingin keluar dari aplikasi BersihXpress?</p>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        class="btn-close-centered w-full bg-white border border-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-50"
                        data-modal-id="modal-keluar-akun">
                        Batal
                    </button>
                    <button
                        class="btn-keluar-confirm w-full bg-red-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-red-700">
                        Keluar
                    </button>
                </div>
            </div>
        </div> -->

        <!-- MODAL 5: Notifikasi Toast (z-60) -->
        <div id="toast-notifikasi" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-sm font-medium py-2 px-4 rounded-full z-60 transition-all duration-300 opacity-0">
            Notifikasi diaktifkan!
        </div>

    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
    // Profile page functionality - simplified version following pattern from other pages
    document.addEventListener('DOMContentLoaded', function() {
        // Modal elements
        const modalContainer = document.getElementById('modal-container');
        const modalBackdrop = document.getElementById('modal-backdrop');
        const editProfilBtn = document.getElementById('btn-edit-profil');
        const editBisnisBtn = document.getElementById('btn-edit-bisnis');
        const ubahPasswordBtn = document.getElementById('btn-ubah-password');
        const keluarAkunBtn = document.getElementById('btn-keluar-akun');
        
        // Modal management functions - following pattern from owner-pelanggan.js
        function openSlideModal(modal) {
            if (!modal) return;
            modalContainer.classList.remove('hidden');
            modalBackdrop.classList.remove('opacity-0');
            modal.style.transform = 'translateY(100%)';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                modal.style.transform = 'translateY(0)';
            });
            
            // Focus first input
            const firstInput = modal.querySelector('input:not([type="hidden"]), textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 200);
            }
        }
        
        function closeSlideModal(modal) {
            if (!modal) return;
            modal.style.transform = 'translateY(100%)';
            setTimeout(() => {
                modal.classList.add('hidden');
                if (!isAnyModalOpen()) {
                    closeBackdrop();
                }
            }, 250);
        }
        
        function openCenteredModal(modal) {
            if (!modal) return;
            // Pastikan modal container dan backdrop tampil
            modalContainer.classList.remove('hidden');
            modalBackdrop.classList.remove('opacity-0');
            modalBackdrop.classList.remove('hidden');
            // Tampilkan modal
            modal.classList.remove('hidden');
            // Pastikan modal-keluar-akun juga tidak hidden
            if (modal.id === 'modal-keluar-akun') {
                modal.classList.remove('hidden');
            }
            document.body.style.overflow = 'hidden';
        }
        
        function closeCenteredModal(modal) {
            if (!modal) return;
            setTimeout(() => {
                modal.classList.add('hidden');
                if (!isAnyModalOpen()) {
                    closeBackdrop();
                }
            }, 200);
        }
        
        function closeBackdrop() {
            modalBackdrop.classList.add('opacity-0');
            setTimeout(() => {
                modalContainer.classList.add('hidden');
                document.body.style.overflow = '';
            }, 250);
        }
        
        function isAnyModalOpen() {
            const anySlideOpen = Array.from(document.querySelectorAll('.modal-popup')).some(el => !el.classList.contains('hidden'));
            const anyCenteredOpen = Array.from(document.querySelectorAll('.modal-centered')).some(el => !el.classList.contains('hidden'));
            return anySlideOpen || anyCenteredOpen;
        }
        
        // Button event listeners
        editProfilBtn?.addEventListener('click', () => {
            const modal = document.getElementById('modal-edit-profil');
            openSlideModal(modal);
        });
        
        editBisnisBtn?.addEventListener('click', () => {
            const modal = document.getElementById('modal-profil-usaha');
            openSlideModal(modal);
        });
        
        ubahPasswordBtn?.addEventListener('click', () => {
            const modal = document.getElementById('modal-ubah-password');
            openSlideModal(modal);
        });
        
        keluarAkunBtn?.addEventListener('click', () => {
            const modal = document.getElementById('modal-keluar-akun');
            openCenteredModal(modal);
        });
        
        // Close modal buttons
        document.querySelectorAll('.btn-close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal-id') || btn.closest('.modal-popup').id;
                const modal = document.getElementById(modalId);
                closeSlideModal(modal);
                closeBackdrop();
            });
        });
        
        document.querySelectorAll('.btn-close-centered').forEach(btn => {
            btn.addEventListener('click', () => {
                const modalId = btn.getAttribute('data-modal-id');
                const modal = document.getElementById(modalId);
                closeCenteredModal(modal);
            });
        });
        
        // Close on backdrop click
        modalBackdrop?.addEventListener('click', () => {
            document.querySelectorAll('.modal-popup').forEach(closeSlideModal);
            document.querySelectorAll('.modal-centered').forEach(closeCenteredModal);
        });
        
        // Form submission handling
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="animate-spin inline-block w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path></svg>Menyimpan...';
                }
            });
        });
        
        // Logout confirmation
        document.querySelector('.btn-keluar-confirm')?.addEventListener('click', function() {
            this.innerHTML = '<svg class="animate-spin inline-block w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path></svg>Keluar...';
            this.disabled = true;
            setTimeout(() => {
                window.location.href = '../../logout.php';
            }, 500);
        });
        
        // Flash message handling (simple version like other pages)
        <?php if (!empty($flashMessage)): ?>
        setTimeout(() => {
            const flashDiv = document.createElement('div');
            flashDiv.className = 'fixed top-4 left-4 right-4 z-50 rounded-lg border px-4 py-3 text-sm <?php echo $flashType === "success" ? "border-green-200 bg-green-50 text-green-800" : "border-red-200 bg-red-50 text-red-700"; ?>';
            flashDiv.textContent = '<?php echo addslashes($flashMessage); ?>';
            document.body.appendChild(flashDiv);
            
            setTimeout(() => {
                flashDiv.remove();
            }, 5000);
        }, 500);
        <?php endif; ?>

        // Preview foto profil
        const profilUpload = document.getElementById('profil_upload');
        const profilAvatarForm = document.getElementById('profil-avatar-form');
        if (profilUpload && profilAvatarForm) {
            profilUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        profilAvatarForm.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    });
    </script>
</body>

</html>