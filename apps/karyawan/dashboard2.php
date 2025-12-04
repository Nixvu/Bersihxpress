<?php


require_once 'middleware/auth_karyawan.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/database.php';
// Pastikan session tidak double start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/functions.php';
require_once 'api/query-dashboard.php';

// Flash message handling
$flash_message = '';
$flash_type = '';

if (isset($_GET['success']) && $_GET['success'] == '1') {
    $flash_type = 'success';
    $flash_message = $_GET['msg'] ?? 'Operasi berhasil!';
} elseif (isset($_GET['error']) && $_GET['error'] == '1') {
    $flash_type = 'error';
    $flash_message = $_GET['msg'] ?? 'Terjadi kesalahan!';
}


$karyawanData = null;
$estimasiKomisi = 0;
$totalTransaksi = 0;
$transaksiSelesai = 0;
$transaksiProses = 0;
$kilogram = 0;
$satuan = 0;
$meteran = 0;
$chartKinerja = [];
$tugasSaya = [];
$logoSrc = 'https://placehold.co/48x48/FFFFFF/3B82F6?text=F';

if (isset($_SESSION['karyawan_data'])) {
    $karyawanId = $_SESSION['karyawan_data']['karyawan_id'];
    $stats = handleKaryawanDashboardAction('get_stats', $conn, $karyawanId);
    $karyawanData = isset($stats['karyawanData']) ? $stats['karyawanData'] : null;
    $estimasiKomisi = isset($stats['estimasiKomisi']) ? $stats['estimasiKomisi'] : 0;
    $totalTransaksi = isset($stats['totalTransaksi']) ? $stats['totalTransaksi'] : 0;
    $transaksiSelesai = isset($stats['transaksiSelesai']) ? $stats['transaksiSelesai'] : 0;
    $transaksiProses = isset($stats['transaksiProses']) ? $stats['transaksiProses'] : 0;
    $kilogram = isset($stats['kilogram']) ? $stats['kilogram'] : 0;
    $satuan = isset($stats['satuan']) ? $stats['satuan'] : 0;
    $meteran = isset($stats['meteran']) ? $stats['meteran'] : 0;

    // Get logo from bisnis table
    if ($karyawanData && !empty($karyawanData['bisnis_id'])) {
        try {
            $stmt = $conn->prepare('SELECT logo FROM bisnis WHERE bisnis_id = ?');
            $stmt->execute([$karyawanData['bisnis_id']]);
            $bisnisInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bisnisInfo && !empty($bisnisInfo['logo'])) {
                $logoSrc = '../../assets/logo/' . htmlspecialchars($bisnisInfo['logo']);
            }
        } catch (PDOException $e) {
            error_log('Error loading bisnis logo: ' . $e->getMessage());
        }
    }

    // Chart kinerja 7 hari terakhir
    $chartKinerja = handleKaryawanDashboardAction('chart_kinerja', $conn, $karyawanId);

    // Tugas saya
    $tugasSaya = handleKaryawanDashboardAction('tugas_saya', $conn, $karyawanId);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Karyawan - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

    <!-- Flash Message -->
    <?php if (!empty($flash_message)): ?>
    <div id="flash-message" class="fixed top-4 left-4 right-4 z-50 px-4 py-3 rounded-lg text-sm font-medium <?php echo $flash_type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
        <div class="flex justify-between items-center">
            <span><?php echo htmlspecialchars($flash_message); ?></span>
            <button onclick="document.getElementById('flash-message').style.display='none'" class="ml-4 text-lg font-bold">&times;</button>
        </div>
    </div>
    <script>
        // Auto hide flash message after 5 seconds
        setTimeout(function() {
            const flash = document.getElementById('flash-message');
            if (flash) flash.style.display = 'none';
        }, 5000);
    </script>
    <?php endif; ?>

    <!-- Loading overlay -->
    <div id="loading-overlay" class="loading-container" aria-hidden="true">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <!-- Header / Greeting -->
        <div class="flex-shrink-0">
            <header class="relative bg-blue-600 h-56 w-full rounded-b-[40px] p-6 text-white z-10">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">Hi, <?php echo htmlspecialchars($karyawanData['nama_lengkap'] ?? 'Karyawan'); ?></h1>
                        <p class="text-sm opacity-90 business-name"><?php echo htmlspecialchars($karyawanData['nama_bisnis'] ?? 'BersihXpress'); ?></p>
                    </div>
                    <img src="<?php echo $logoSrc; ?>" alt="Avatar Pengguna" class="w-12 h-12 rounded-full border-2 border-white/50">
                </div>
            </header>

            <!-- Stat cards -->
            <main class="relative z-20 -mt-24 px-6">
                <section class="bg-white rounded-lg shadow-md p-5">
                    <div class="flex justify-center -mt-2 -mr-2 mb-1">
                        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full">Bulan ini</span>
                    </div>

                    <div class="flex justify-between text-center border-b pb-4">
                        <div class="w-1/3">
                            <p class="text-lg font-bold text-gray-900">Rp <?php echo number_format($estimasiKomisi, 0, ',', '.'); ?></p>
                            <span class="text-sm text-gray-500">Estimasi Komisi</span>
                        </div>
                        <div class="w-1/3 border-l">
                            <p class="text-lg font-bold text-gray-900"><?php echo $transaksiProses; ?></p>
                            <span class="text-sm text-gray-500">Diproses</span>
                        </div>
                        <div class="w-1/3 border-l">
                            <p class="text-lg font-bold text-gray-900"><?php echo $transaksiSelesai; ?></p>
                            <span class="text-sm text-gray-500">Selesai</span>
                        </div>
                    </div>

                    <div class="flex justify-around text-center pt-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo number_format($kilogram, 0); ?> Kg</p>
                            <span class="text-sm text-gray-500">Kilogram</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $satuan; ?> Pcs</p>
                            <span class="text-sm text-gray-500">Satuan</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo number_format($meteran, 0); ?> M</p>
                            <span class="text-sm text-gray-500">Meteran</span>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Scrollable content area -->
        <div class="flex-grow overflow-y-auto no-scrollbar px-6 pb-24">

            <!-- Quick actions (3 kolom untuk karyawan) -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Aksi Cepat</h2>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <button id="btn-open-transaksi"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="file-plus" class="w-6 h-6 mb-1 text-blue-600"></svg>
                        <span class="text-xs font-medium">Buat Transaksi</span>
                    </button>

                    <button id="btn-open-absensi"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="user-check" class="w-6 h-6 mb-1 text-green-600"></svg>
                        <span class="text-xs font-medium">Absensi</span>
                    </button>
                    
                    <a href="pelanggan.php"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="users" class="w-6 h-6 mb-1 text-purple-600"></svg>
                        <span class="text-xs font-medium">Data Pelanggan</span>
                    </a>
                </div>
            </section>

            <!-- Kinerja Chart -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Kinerja 7 Hari Terakhir</h2>
                <div class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Transaksi Selesai</h3>
                    <canvas id="chartKinerja" height="180"></canvas>
                </div>
            </section>

            <!-- Tugas Saya -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Tugas Saya</h2>
                <div class="bg-white rounded-lg shadow-md p-5">
                    <?php
                    $dikerjakan = $tugasSaya['dikerjakan'] ?? [];
                    $selesaiHariIni = $tugasSaya['selesai_hari_ini'] ?? [];
                    ?>

                    <div class="flex space-x-2 bg-gray-200 p-1 rounded-lg mb-4">
                        <button class="tugas-tab-button aksi-tab-button-active w-1/2 py-2 rounded-md text-sm font-medium" data-target="#tab-dikerjakan">
                            Masih Dikerjakan (<?php echo count($dikerjakan); ?>)
                        </button>
                        <button class="tugas-tab-button w-1/2 py-2 rounded-md text-sm text-gray-600" data-target="#tab-selesai-hari-ini">
                            Selesai Hari Ini (<?php echo count($selesaiHariIni); ?>)
                        </button>
                    </div>

                    <div>
                        <div id="tab-dikerjakan" class="tugas-tab-panel">
                            <?php if (empty($dikerjakan)): ?>
                                <div class="py-6 text-center text-sm text-gray-500">Belum ada tugas yang sedang dikerjakan.</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($dikerjakan as $t): ?>
                                    <a href="transaksi.php?id=<?php echo urlencode($t['transaksi_id']); ?>" class="block p-4 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($t['nama_pelanggan']); ?> (#<?php echo htmlspecialchars($t['transaksi_id']); ?>)</p>
                                                <p class="text-sm text-gray-500">Masuk: <?php echo date('H:i', strtotime($t['created_at'])); ?></p>
                                            </div>
                                            <span class="text-xs font-semibold <?php 
                                                echo $t['status'] === 'diproses' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700';
                                            ?> rounded-full px-2 py-0.5">
                                                <?php echo ucfirst($t['status']); ?>
                                            </span>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="tab-selesai-hari-ini" class="tugas-tab-panel hidden">
                            <?php if (empty($selesaiHariIni)): ?>
                                <div class="py-6 text-center text-sm text-gray-500">Belum ada tugas yang selesai hari ini.</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($selesaiHariIni as $t): ?>
                                    <a href="transaksi.php?id=<?php echo urlencode($t['transaksi_id']); ?>" class="block p-4 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($t['nama_pelanggan']); ?> (#<?php echo htmlspecialchars($t['transaksi_id']); ?>)</p>
                                                <p class="text-sm text-gray-500">Selesai: <?php echo date('H:i', strtotime($t['updated_at'])); ?></p>
                                            </div>
                                            <span class="text-xs font-semibold bg-green-100 text-green-700 rounded-full px-2 py-0.5">Selesai</span>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

        </div> <!-- end scrollable content -->

    </div> <!-- end main-content -->

    <!-- Navigation dengan 4 menu untuk karyawan -->
    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-4 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="transaksi.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Transaksi</span>
        </a>
        <a href="dashboard.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Beranda</span>
        </a>
        <a href="laporan.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="bar-chart-2" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Laporan</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Akun</span>
        </a>
    </nav>

    <!-- Modal container (include modular modal files) -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>  
        <?php include 'modals/modal-buat-transaksi.php'; ?>
        <?php include 'modals/modal-rincian-transaksi.php'; ?>
        <?php include 'modals/modal-absensi.php'; ?>
        <?php include 'modals/modal-tambah-pelanggan.php'; ?>
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/karyawan-dashboard.js"></script>
    <script src="../../assets/js/modal-toggle.js"></script>
    <script>
    // Tab switching untuk Tugas Saya
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tugas-tab-button');
        const panels = document.querySelectorAll('.tugas-tab-panel');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                tabButtons.forEach(b => b.classList.remove('aksi-tab-button-active', 'bg-white', 'text-blue-600'));
                panels.forEach(p => p.classList.add('hidden'));
                this.classList.add('aksi-tab-button-active', 'bg-white', 'text-blue-600');
                this.classList.remove('text-gray-600');
                const target = this.getAttribute('data-target');
                const panel = document.querySelector(target);
                if (panel) panel.classList.remove('hidden');
            });
        });
    });
    
    // Chart Kinerja 7 Hari Terakhir
    const ctx = document.getElementById('chartKinerja').getContext('2d');
    const chartResult = <?php echo json_encode($chartKinerja); ?>;
    const chartLabels = (chartResult.labels && chartResult.labels.length) ? chartResult.labels : ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    const chartData = (chartResult.data && chartResult.data.length) ? chartResult.data : (Array.isArray(chartResult) ? chartResult : [0,0,0,0,0,0,0]);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Transaksi Selesai',
                data: chartData,
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderRadius: 6,
                maxBarThickness: 32
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    </script>

</body>
</html>