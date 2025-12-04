<?php

require_once 'middleware/auth_owner.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once 'api/query-dashboard.php';

$ownerData = null;
$pendapatan = 0;
$pengeluaran = 0;
$totalTransaksi = 0;
$kiloanSelesai = 0;
$satuanSelesai = 0;
$chartPendapatan = [];
$ringkasanTransaksi = [];
$logoSrc = 'https://placehold.co/48x48/FFFFFF/3B82F6?text=F';
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $stats = handleDashboardAction('get_stats', $conn, $userId);
    $ownerData = $stats['ownerData'];
    $pendapatan = $stats['pendapatan'];
    $pengeluaran = $stats['pengeluaran'];
    $totalTransaksi = $stats['totalTransaksi'];
    $kiloanSelesai = $stats['kiloanSelesai'];
    $satuanSelesai = $stats['satuanSelesai'];
    
    // Get logo from bisnis table, with personalized placeholder
    $namaLengkap = $ownerData['nama_lengkap'] ?? 'B';
    $inisial = strtoupper(substr($namaLengkap, 0, 1));
    // Default to a personalized placeholder
    $logoSrc = "https://placehold.co/48x48/FFFFFF/3B82F6?text=$inisial"; 

    if ($ownerData && !empty($ownerData['bisnis_id'])) {
        try {
            $stmt = $conn->prepare('SELECT logo FROM bisnis WHERE bisnis_id = ?');
            $stmt->execute([$ownerData['bisnis_id']]);
            $bisnisInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($bisnisInfo && !empty($bisnisInfo['logo'])) {
                // If a custom logo exists, override the placeholder
                $logoSrc = '../../' . ltrim($bisnisInfo['logo'], '/');
            }
        } catch (PDOException $e) {
            error_log('Error loading bisnis logo: ' . $e->getMessage());
        }
    }
    
    $chartPendapatan = [];
    if ($ownerData && !empty($ownerData['bisnis_id'])) {
        $chartPendapatan = handleDashboardAction('chart_pendapatan', $conn, $userId, ['bisnis_id' => $ownerData['bisnis_id']]);
    } else {
        $chartPendapatan = handleDashboardAction('dummy_chart', $conn, $userId);
    }
    if ($ownerData && !empty($ownerData['bisnis_id'])) {
        $ringkasanTransaksi = handleDashboardAction('ringkasan_transaksi', $conn, $userId, ['bisnis_id' => $ownerData['bisnis_id']]);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Owner - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">

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
                        <h1 class="text-2xl font-bold">Hi, <?php echo htmlspecialchars($ownerData['nama_lengkap'] ?? 'Owner'); ?></h1>
                        <p class="text-sm opacity-90 business-name"><?php echo htmlspecialchars($ownerData['nama_bisnis'] ?? 'BersihXpress'); ?></p>
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
                        <div class="w-1/2">
                            <p class="text-lg font-bold text-gray-900">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></p>
                            <span class="text-sm text-gray-500 flex items-center justify-center">Pendapatan <svg data-feather="arrow-up-right" class="w-4 h-4 text-green-500 ml-1"></svg></span>
                        </div>
                        <div class="w-1/2 border-l">
                            <p class="text-lg font-bold text-gray-900">Rp <?php echo number_format($pengeluaran, 0, ',', '.'); ?></p>
                            <span class="text-sm text-gray-500 flex items-center justify-center">Pengeluaran <svg data-feather="arrow-down-right" class="w-4 h-4 text-red-500 ml-1"></svg></span>
                        </div>
                    </div>

                    <div class="flex justify-around text-center pt-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $totalTransaksi; ?></p>
                            <span class="text-sm text-gray-500">Total Transaksi</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $kiloanSelesai; ?> KG</p>
                            <span class="text-sm text-gray-500">Kiloan Selesai</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $satuanSelesai; ?></p>
                            <span class="text-sm text-gray-500">Satuan Selesai</span>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Scrollable content area -->
        <div class="flex-grow overflow-y-auto no-scrollbar px-6 pb-24">

            <!-- Quick actions -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Aksi Cepat</h2>
                <div class="grid grid-cols-4 gap-4 text-center">
                    <button id="btn-open-transaksi"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="file-plus" class="w-6 h-6 mb-1 text-blue-600"></svg>
                        <span class="text-xs font-medium">Buat Transaksi</span>
                    </button>

                    <button id="btn-open-pengeluaran"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="trending-down" class="w-6 h-6 mb-1 text-red-600"></svg>
                        <span class="text-xs font-medium">Catat Pengeluaran</span>
                    </button>
                    
                    <button id="btn-open-pelanggan"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="user-plus" class="w-6 h-6 mb-1 text-green-600"></svg>
                        <span class="text-xs font-medium">Tambah Pelanggan</span>
                    </button>

                    <a href="laporan.php"
                        class="bg-white text-gray-700 rounded-lg p-3 shadow flex flex-col items-center justify-center hover:bg-gray-50">
                        <svg data-feather="bar-chart-2" class="w-6 h-6 mb-1 text-purple-600"></svg>
                        <span class="text-xs font-medium">Lihat Laporan</span>
                    </a>
                </div>
            </section>

            <!-- Revenue trend (static visual) -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Tren Pendapatan</h2>
                <div class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Pendapatan 7 Hari Terakhir</h3>
                    <canvas id="chartPendapatan" height="180"></canvas>
                </div>
            </section>

            <!-- Summary / quick list -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Ringkasan Transaksi</h2>
                <div class="bg-white rounded-lg shadow-md p-5">
                    <?php
                    // Group transactions by status
                    $pending = [];
                    $proses = [];
                    $selesai = [];
                    if (!empty($ringkasanTransaksi) && is_array($ringkasanTransaksi)) {
                        foreach ($ringkasanTransaksi as $t) {
                            $s = strtolower($t['status'] ?? '');
                            if ($s === 'pending') $pending[] = $t;
                            elseif ($s === 'proses' || $s === 'in_progress' || $s === 'proses') $proses[] = $t;
                            else $selesai[] = $t;
                        }
                    }
                    ?>

                    <div class="flex border-b border-gray-200 mb-3">
                        <button class="aksi-tab-button aksi-tab-button-active flex-1 text-sm text-gray-600 py-3 px-1 text-center" data-target="#tab-pending">Pending (<?php echo count($pending); ?>)</button>
                        <button class="aksi-tab-button flex-1 text-sm text-gray-600 py-3 px-1 text-center" data-target="#tab-proses">Proses (<?php echo count($proses); ?>)</button>
                        <button class="aksi-tab-button flex-1 text-sm text-gray-600 py-3 px-1 text-center" data-target="#tab-selesai">Selesai (<?php echo count($selesai); ?>)</button>
                    </div>

                    <div>
                        <div id="tab-pending" class="aksi-tab-panel">
                            <?php if (empty($pending)): ?>
                                <div class="py-6 text-center text-sm text-gray-500">Belum ada transaksi pada status "Pending".</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($pending as $r): ?>
                                    <a href="transaksi.php?id=<?php echo urlencode($r['transaksi_id'] ?? ($r['id'] ?? '')); ?>" class="block p-4 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($r['nama'] ?? $r['pelanggan_nama'] ?? '-'); ?></p>
                                                <p class="text-sm text-gray-500">ID <?php echo htmlspecialchars($r['transaksi_id'] ?? ($r['id'] ?? '-')); ?> • <?php echo htmlspecialchars($r['tanggal_masuk'] ?? $r['created_at'] ?? ''); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-gray-700">Rp <?php echo number_format($r['total_harga'] ?? $r['subtotal'] ?? 0,0,',','.'); ?></div>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(ucfirst($r['status'] ?? '-')); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="tab-proses" class="aksi-tab-panel hidden">
                            <?php if (empty($proses)): ?>
                                <div class="py-6 text-center text-sm text-gray-500">Belum ada transaksi pada status "Proses".</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($proses as $r): ?>
                                    <a href="transaksi.php?id=<?php echo urlencode($r['transaksi_id'] ?? ($r['id'] ?? '')); ?>" class="block p-4 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($r['nama'] ?? $r['pelanggan_nama'] ?? '-'); ?></p>
                                                <p class="text-sm text-gray-500">ID <?php echo htmlspecialchars($r['transaksi_id'] ?? ($r['id'] ?? '-')); ?> • <?php echo htmlspecialchars($r['tanggal_masuk'] ?? $r['created_at'] ?? ''); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-gray-700">Rp <?php echo number_format($r['total_harga'] ?? $r['subtotal'] ?? 0,0,',','.'); ?></div>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(ucfirst($r['status'] ?? '-')); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div id="tab-selesai" class="aksi-tab-panel hidden">
                            <?php if (empty($selesai)): ?>
                                <div class="py-6 text-center text-sm text-gray-500">Belum ada transaksi pada status "Selesai".</div>
                            <?php else: ?>
                                <div class="space-y-2">
                                    <?php foreach ($selesai as $r): ?>
                                    <a href="transaksi.php?id=<?php echo urlencode($r['transaksi_id'] ?? ($r['id'] ?? '')); ?>" class="block p-4 rounded-lg hover:bg-gray-50 border border-transparent hover:border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($r['nama'] ?? $r['pelanggan_nama'] ?? '-'); ?></p>
                                                <p class="text-sm text-gray-500">ID <?php echo htmlspecialchars($r['transaksi_id'] ?? ($r['id'] ?? '-')); ?> • <?php echo htmlspecialchars($r['tanggal_selesai'] ?? $r['updated_at'] ?? ''); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-medium text-gray-700">Rp <?php echo number_format($r['total_harga'] ?? $r['subtotal'] ?? 0,0,',','.'); ?></div>
                                                <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars(ucfirst($r['status'] ?? '-')); ?></div>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="border-t border-gray-200 mt-3 pt-3">
                            <a href="transaksi.php" class="block w-full text-center text-sm font-medium text-blue-600 py-3 hover:bg-gray-50 rounded-b-lg">Lihat Semua Transaksi</a>
                        </div>
                    </div>
                </div>
            </section>

        </div> <!-- end scrollable content -->

    </div> <!-- end main-content -->

    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="grid" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Kelola</span>
        </a>
        <a href="transaksi.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Transaksi</span>
        </a>
        <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Beranda</span>
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
        <!-- <?php include 'modals/modal-transaksi-template.php'; ?> -->
        <?php include 'modals/modal-pengeluaran.php'; ?>
        <?php include 'modals/modal-tambah-pelanggan.php'; ?>
        <?php include 'modals/modal-edit-usaha.php'; ?>
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-dashboard.js"></script>
    <script src="../../assets/js/modal-toggle.js"></script>
    <script>
    // Tab switching for Ringkasan Transaksi
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.aksi-tab-button');
        const panels = document.querySelectorAll('.aksi-tab-panel');
        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                tabButtons.forEach(b => b.classList.remove('aksi-tab-button-active'));
                panels.forEach(p => p.classList.add('hidden'));
                this.classList.add('aksi-tab-button-active');
                const target = this.getAttribute('data-target');
                const panel = document.querySelector(target);
                if (panel) panel.classList.remove('hidden');
            });
        });
    });
    // Chart Pendapatan 7 Hari Terakhir
    const ctx = document.getElementById('chartPendapatan').getContext('2d');
    const chartResult = <?php echo json_encode($chartPendapatan); ?>;
    const chartLabels = (chartResult.labels && chartResult.labels.length) ? chartResult.labels : ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    const chartData = (chartResult.data && chartResult.data.length) ? chartResult.data : (Array.isArray(chartResult) ? chartResult : []);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: chartData,
                backgroundColor: 'rgba(59, 130, 246, 0.7)',
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
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // <?php if (isset($_GET['success'])): ?>
    // window.onload = function() {
    //     alert('<?php echo htmlspecialchars($_GET['msg'] ?? 'Berhasil.'); ?>');
    // };
    // <?php elseif (isset($_GET['error'])): ?>
    // window.onload = function() {
    //     alert('<?php echo 'Error: ' . str_replace("'", "\\'", htmlspecialchars($_GET['msg'] ?? 'Terjadi kesalahan.')); ?>');
    // };
    // <?php endif; ?>
    </script>

</body>
</html>