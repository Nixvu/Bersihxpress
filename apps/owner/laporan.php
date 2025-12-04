<?php
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/query-laporan.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

// Pastikan ada bisnis_id
if (!$bisnisId) {
    $_SESSION['error_message'] = 'Data bisnis tidak ditemukan';
    header('Location: dashboard.php');
    exit;
}

// Filter tanggal
$filterType = $_GET['filter'] ?? 'bulan_ini';
$tanggalMulai = $_GET['tanggal_mulai'] ?? null;
$tanggalSelesai = $_GET['tanggal_selesai'] ?? null;

// Inisialisasi query class
$laporanQuery = new LaporanQuery($bisnisId);

// Ambil semua data laporan
$data = $laporanQuery->getAllData($filterType, $tanggalMulai, $tanggalSelesai);

// Extract data untuk kemudahan akses
$pendapatanData = $data['pendapatan'];
$layananTerlaris = $data['layanan_terlaris'];
$rincianTransaksi = $data['rincian_transaksi'];

// Ambil transaksi hari ini untuk rincian pendapatan
$transaksiHariIni = $laporanQuery->getTransaksiHariIni(4);
$transaksiTerakhir = $laporanQuery->getTransaksiTerakhir(5);
$pengeluaranData = $data['pengeluaran'];
$kategoriPengeluaran = $data['kategori_pengeluaran'];
$pengeluaranTerbaru = $data['pengeluaran_terbaru'];
$pelangganData = $data['pelanggan'];
$pelangganTeratas = $data['pelanggan_teratas'];
$karyawanData = $data['karyawan'];
$kinerjKaryawan = $data['kinerja_karyawan'];

// Helper functions
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    return date('d/m/Y - H:i', strtotime($tanggal));
}

function formatTanggalSingkat($tanggal) {
    return date('d M Y', strtotime($tanggal));
}

function getStatusBadge($status) {
    switch ($status) {
        case 'selesai':
        case 'diambil':
            return '<span class="text-xs font-semibold bg-green-100 text-green-700 rounded-full px-2 py-0.5">Selesai</span>';
        case 'proses':
            return '<span class="text-xs font-semibold bg-blue-100 text-blue-700 rounded-full px-2 py-0.5">Diproses</span>';
        case 'pending':
            return '<span class="text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full px-2 py-0.5">Pending</span>';
        default:
            return '<span class="text-xs font-semibold bg-gray-100 text-gray-700 rounded-full px-2 py-0.5">' . ucfirst($status) . '</span>';
    }
}

function getFilterText($filterType) {
    switch ($filterType) {
        case 'hari_ini': return 'Filter: Hari Ini';
        case '7_hari': return 'Filter: 7 Hari Terakhir';
        case 'bulan_ini': return 'Filter: Bulan Ini';
        case 'tahun_ini': return 'Filter: Tahun Ini';
        case 'kustom': return 'Filter: Rentang Kustom';
        default: return 'Filter: Bulan Ini';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Bisnis - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 pb-6 shadow-lg flex-shrink-0">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Laporan Bisnis</h1>
                    <p id="active-filter-display" class="text-sm opacity-90 text-white"><?php echo getFilterText($filterType); ?></p>
                    <p class="text-xs opacity-75 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>
                </div>
                <div class="flex space-x-2">
                    <button id="btn-filter-tanggal" type="button" class="p-2 bg-white/20 rounded-lg hover:bg-white/30"
                        aria-label="Filter tanggal">
                        <svg data-feather="calendar" class="w-6 h-6 text-white" aria-hidden="true"></svg>
                    </button>
                    <button id="btn-export" type="button" class="p-2 bg-white/20 rounded-lg hover:bg-white/30"
                        aria-label="Ekspor laporan">
                        <svg data-feather="download" class="w-6 h-6 text-white" aria-hidden="true"></svg>
                    </button>
                </div>
            </div>
        </header>

        <nav class="sticky top-[108px] z-10 bg-gray-100 pt-4 pb-3 px-6 flex-shrink-0" role="navigation"
            aria-label="Tab navigasi laporan">
            <div class="flex space-x-3 overflow-x-auto no-scrollbar" role="tablist" aria-label="Laporan tabs">
                <button
                    class="tab-button tab-button-active flex-shrink-0 whitespace-nowrap text-center py-2 px-4 rounded-lg font-semibold text-blue-700 bg-blue-100"
                    type="button" data-target="#tab-pendapatan" role="tab" aria-controls="tab-pendapatan"
                    aria-selected="true">
                    Pendapatan
                </button>
                <button
                    class="tab-button flex-shrink-0 whitespace-nowrap text-center py-2 px-4 rounded-lg font-medium text-gray-600 hover:bg-gray-200"
                    type="button" data-target="#tab-pengeluaran" role="tab" aria-controls="tab-pengeluaran"
                    aria-selected="false">
                    Pengeluaran
                </button>
                <button
                    class="tab-button flex-shrink-0 whitespace-nowrap text-center py-2 px-4 rounded-lg font-medium text-gray-600 hover:bg-gray-200"
                    type="button" data-target="#tab-pelanggan" role="tab" aria-controls="tab-pelanggan"
                    aria-selected="false">
                    Pelanggan
                </button>
                <button
                    class="tab-button flex-shrink-0 whitespace-nowrap text-center py-2 px-4 rounded-lg font-medium text-gray-600 hover:bg-gray-200"
                    type="button" data-target="#tab-karyawan" role="tab" aria-controls="tab-karyawan"
                    aria-selected="false">
                    Karyawan
                </button>
            </div>
        </nav>

        <div class="flex-grow overflow-y-auto p-6 space-y-6 pb-24 no-scrollbar">

            <!-- Pendapatan -->
            <div id="tab-pendapatan" class="space-y-6">
                <section>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500">Total Transaksi</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($pendapatanData['total_transaksi']); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500">Total Pendapatan</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo formatRupiah($pendapatanData['total_pendapatan']); ?></p>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-lg font-semibold text-gray-800">Transaksi Hari Ini</h2>
                        <span class="text-sm text-gray-500 font-medium">
                            <?php echo count($transaksiHariIni); ?> transaksi
                        </span>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($transaksiHariIni)): ?>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-gray-500">Belum ada transaksi hari ini</p>
                            <a href="transaksi.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium mt-1 inline-block">
                                Buat Transaksi Baru
                            </a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($transaksiHariIni as $transaksi): ?>
                        <div class="w-full bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center">
                                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">ID Nota #<?php echo htmlspecialchars($transaksi['no_nota']); ?></p>
                                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($transaksi['nama_pelanggan']); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-gray-900 text-right"><?php echo formatRupiah($transaksi['total_harga']); ?></p>
                                    <span class="text-xs font-semibold rounded-full px-2 py-0.5
                                        <?php echo $transaksi['status'] == 'selesai' ? 'bg-green-100 text-green-700' : 
                                                   ($transaksi['status'] == 'diproses' || $transaksi['status'] == 'proses' ? 'bg-blue-100 text-blue-700' : 
                                                   ($transaksi['status'] == 'diambil' ? 'bg-gray-100 text-gray-700' : 
                                                   ($transaksi['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'))); ?>">
                                        <?php echo ucfirst(htmlspecialchars($transaksi['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t border-dashed mt-3 pt-3 text-sm text-gray-500">
                                <div class="flex justify-between">
                                    <span>Masuk: <?php echo date('H:i', strtotime($transaksi['created_at'])); ?></span>
                                    <span>Oleh: <?php echo isset($transaksi['dibuat_oleh']) ? htmlspecialchars($transaksi['dibuat_oleh']) : 'Sistem'; ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section>
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-lg font-semibold text-gray-800">Transaksi Terakhir</h2>
                        <a href="transaksi.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($transaksiTerakhir)): ?>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-gray-500">Belum ada transaksi</p>
                            <a href="transaksi.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium mt-1 inline-block">
                                Buat Transaksi Baru
                            </a>
                        </div>
                        <?php else: ?>
                        <?php foreach ($transaksiTerakhir as $transaksi): ?>
                        <a href="transaksi.php" class="block w-full bg-white rounded-lg shadow p-4 hover:shadow-md transition-all hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center">
                                    <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">ID Nota #<?php echo htmlspecialchars($transaksi['no_nota']); ?></p>
                                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($transaksi['nama_pelanggan']); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-lg font-bold text-gray-900 text-right"><?php echo formatRupiah($transaksi['total_harga']); ?></p>
                                    <span class="text-xs font-semibold rounded-full px-2 py-0.5
                                        <?php echo $transaksi['status'] == 'selesai' ? 'bg-green-100 text-green-700' : 
                                                   ($transaksi['status'] == 'diproses' || $transaksi['status'] == 'proses' ? 'bg-blue-100 text-blue-700' : 
                                                   ($transaksi['status'] == 'diambil' ? 'bg-gray-100 text-gray-700' : 
                                                   ($transaksi['status'] == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-700'))); ?>">
                                        <?php echo ucfirst(htmlspecialchars($transaksi['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="border-t border-dashed mt-3 pt-3 text-sm text-gray-500">
                                <div class="flex justify-between">
                                    <span><?php echo date('d M Y - H:i', strtotime($transaksi['created_at'])); ?></span>
                                    <span>Oleh: <?php echo isset($transaksi['dibuat_oleh']) ? htmlspecialchars($transaksi['dibuat_oleh']) : 'Sistem'; ?></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Layanan Terlaris</h3>
                    <div class="space-y-3">
                        <?php if (empty($layananTerlaris)): ?>
                        <p class="text-sm text-gray-500 text-center">Data layanan belum tersedia</p>
                        <?php else: ?>
                        <?php foreach ($layananTerlaris as $index => $layanan): ?>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full text-xs font-bold flex items-center justify-center mr-3">
                                    <?php echo $index + 1; ?>
                                </span>
                                <span class="font-medium"><?php echo htmlspecialchars($layanan['nama_layanan']); ?></span>
                            </div>
                            <span class="text-sm text-gray-600"><?php echo number_format($layanan['jumlah_transaksi']); ?>x</span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Pengeluaran -->
            <div id="tab-pengeluaran" class="space-y-6 hidden" role="tabpanel" aria-hidden="true" style="margin-top: 0px;">
                <section>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500">Total Item</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($pengeluaranData['total_item']); ?></p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4">
                            <p class="text-sm text-gray-500">Total Pengeluaran</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo formatRupiah($pengeluaranData['total_pengeluaran']); ?></p>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Kategori Pengeluaran</h3>
                    <?php if (empty($kategoriPengeluaran)): ?>
                    <p class="text-sm text-gray-500 text-center">Belum ada data pengeluaran</p>
                    <?php else: ?>
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-48 h-48 relative">
                            <canvas id="pengeluaranChart" width="192" height="192"></canvas>
                        </div>
                    </div>
                    
                    <!-- Legend -->
                    <div class="grid grid-cols-1 gap-2">
                        <?php 
                        $totalPengeluaran = array_sum(array_column($kategoriPengeluaran, 'total'));
                        $colors = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#f97316'];
                        ?>
                        <?php foreach ($kategoriPengeluaran as $index => $kategori): ?>
                        <?php $percentage = $totalPengeluaran > 0 ? round(($kategori['total'] / $totalPengeluaran) * 100) : 0; ?>
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded chart-legend-item">
                            <div class="flex items-center">
                                <span class="w-3 h-3 rounded-full mr-3" 
                                      style="background-color: <?php echo $colors[$index % count($colors)]; ?>"></span>
                                <span class="text-sm font-medium"><?php echo ucfirst($kategori['kategori']); ?></span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold text-red-600"><?php echo formatRupiah($kategori['total']); ?></span>
                                <span class="text-xs text-gray-500 ml-2">(<?php echo $percentage; ?>%)</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx2 = document.getElementById('pengeluaranChart').getContext('2d');
                        
                        const kategorLabels = <?php echo json_encode(array_map(function($k) { return ucfirst($k['kategori']); }, $kategoriPengeluaran)); ?>;
                        const kategorData = <?php echo json_encode(array_column($kategoriPengeluaran, 'total')); ?>;
                        const totalPengeluaranAmount = <?php echo $totalPengeluaran; ?>;
                        const pengeluaranColors = ['#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#f97316'];
                        
                        const pengeluaranChart = new Chart(ctx2, {
                            type: 'pie',
                            data: {
                                labels: kategorLabels,
                                datasets: [{
                                    data: kategorData,
                                    backgroundColor: pengeluaranColors,
                                    borderColor: pengeluaranColors.map(color => color + 'CC'),
                                    borderWidth: 2,
                                    hoverOffset: 8
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.parsed;
                                                const percentage = totalPengeluaranAmount > 0 ? ((value / totalPengeluaranAmount) * 100).toFixed(1) : 0;
                                                const formattedValue = new Intl.NumberFormat('id-ID', {
                                                    style: 'currency',
                                                    currency: 'IDR',
                                                    minimumFractionDigits: 0
                                                }).format(value);
                                                return label + ': ' + formattedValue + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                },
                            }
                        });
                    });
                    </script>
                    <?php endif; ?>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Pengeluaran Terbaru</h3>
                    <div class="space-y-3">
                        <?php if (empty($pengeluaranTerbaru)): ?>
                        <p class="text-sm text-gray-500 text-center">Belum ada data pengeluaran</p>
                        <?php else: ?>
                        <?php foreach ($pengeluaranTerbaru as $pengeluaran): ?>
                        <div class="flex justify-between items-center text-sm">
                            <div>
                                <p class="font-medium text-gray-700"><?php echo htmlspecialchars($pengeluaran['keterangan']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo formatTanggalSingkat($pengeluaran['tanggal']); ?></p>
                            </div>
                            <span class="font-medium text-red-600">- <?php echo formatRupiah($pengeluaran['jumlah']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Pelanggan -->
            <div id="tab-pelanggan" class="space-y-6 hidden" role="tabpanel" aria-hidden="true" style="margin-top: 0px;">
                <section>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-white rounded-lg shadow p-3 text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($pelangganData['total_pelanggan']); ?></p>
                            <p class="text-xs text-gray-500">Total Pelanggan</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-3 text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($pelangganData['pelanggan_baru']); ?></p>
                            <p class="text-xs text-gray-500">Pelanggan Baru</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-3 text-center">
                            <p class="text-xl font-bold text-gray-900"><?php echo formatRupiah($pelangganData['rata_rata_belanja']); ?></p>
                            <p class="text-xs text-gray-500">Rata2 Belanja</p>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pelanggan Baru vs. Lama</h3>
                    <div class="flex items-center justify-center">
                        <div class="w-64 h-64 relative">
                            <canvas id="pelangganChart" width="256" height="256"></canvas>
                        </div>
                    </div>
                    
                    <!-- Legend dan Summary -->
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center justify-center mb-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                <span class="text-sm font-medium text-gray-700">Pelanggan Baru</span>
                            </div>
                            <p class="text-xl font-bold text-green-600"><?php echo number_format($pelangganData['pelanggan_baru']); ?></p>
                            <p class="text-xs text-gray-500">30 hari terakhir</p>
                        </div>
                        <div class="text-center p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-center mb-2">
                                <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                <span class="text-sm font-medium text-gray-700">Pelanggan Lama</span>
                            </div>
                            <p class="text-xl font-bold text-blue-600"><?php echo number_format($pelangganData['total_pelanggan'] - $pelangganData['pelanggan_baru']); ?></p>
                            <p class="text-xs text-gray-500">Existing customers</p>
                        </div>
                    </div>
                    
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const ctx = document.getElementById('pelangganChart').getContext('2d');
                        const pelangganBaru = <?php echo $pelangganData['pelanggan_baru']; ?>;
                        const pelangganLama = <?php echo $pelangganData['total_pelanggan'] - $pelangganData['pelanggan_baru']; ?>;
                        const totalPelanggan = <?php echo $pelangganData['total_pelanggan']; ?>;
                        
                        const pelangganChart = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Pelanggan Baru', 'Pelanggan Lama'],
                                datasets: [{
                                    data: [pelangganBaru, pelangganLama],
                                    backgroundColor: [
                                        '#10b981', // green-500
                                        '#3b82f6'  // blue-500
                                    ],
                                    borderColor: [
                                        '#059669', // green-600
                                        '#2563eb'  // blue-600
                                    ],
                                    borderWidth: 2,
                                    hoverBackgroundColor: [
                                        '#047857', // green-700
                                        '#1d4ed8'  // blue-700
                                    ]
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: 'rgba(255, 255, 255, 0.1)',
                                        borderWidth: 1,
                                        callbacks: {
                                            label: function(context) {
                                                const label = context.label || '';
                                                const value = context.parsed;
                                                const percentage = totalPelanggan > 0 ? ((value / totalPelanggan) * 100).toFixed(1) : 0;
                                                return label + ': ' + value.toLocaleString() + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                },
                                cutout: '60%',
                                animation: {
                                    // animateScale: true,
                                    animateRotate: true
                                }
                            }
                        });
                        
                        // Tambahkan label di tengah chart
                        const centerText = {
                            id: 'centerText',
                            beforeDatasetsDraw(chart, args, options) {
                                const {ctx, data} = chart;
                                ctx.save();
                                
                                const centerX = chart.getDatasetMeta(0).data[0].x;
                                const centerY = chart.getDatasetMeta(0).data[0].y;
                                
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'middle';
                                ctx.font = 'bold 18px sans-serif';
                                ctx.fillStyle = '#374151';
                                ctx.fillText(totalPelanggan.toLocaleString(), centerX, centerY - 10);
                                
                                ctx.font = '12px sans-serif';
                                ctx.fillStyle = '#6b7280';
                                ctx.fillText('Total Pelanggan', centerX, centerY + 10);
                                
                                ctx.restore();
                            }
                        };
                        
                        Chart.register(centerText);
                    });
                    </script>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Pelanggan Teratas</h3>
                    <div class="space-y-4">
                        <?php if (empty($pelangganTeratas)): ?>
                        <p class="text-sm text-gray-500 text-center">Data pelanggan teratas belum tersedia</p>
                        <?php else: ?>
                        <?php foreach ($pelangganTeratas as $index => $pelanggan): ?>
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <span class="w-6 h-6 bg-green-100 text-green-600 rounded-full text-xs font-bold flex items-center justify-center mr-3">
                                    <?php echo $index + 1; ?>
                                </span>
                                <span class="font-medium"><?php echo htmlspecialchars($pelanggan['nama']); ?></span>
                            </div>
                            <span class="font-bold text-green-600"><?php echo formatRupiah($pelanggan['total_belanja']); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <!-- Karyawan -->
            <div id="tab-karyawan" class="space-y-6 hidden" role="tabpanel" aria-hidden="true" style="margin-top: 0px;">
                <section>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-white rounded-lg shadow p-3 text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($karyawanData['total_karyawan']); ?></p>
                            <p class="text-xs text-gray-500">Total Karyawan</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-3 text-center">
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($karyawanData['rata_rata_harian'], 1); ?></p>
                            <p class="text-xs text-gray-500">Rata-rata / Hari</p>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg shadow p-5">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Kinerja Karyawan (Transaksi Selesai)</h3>
                    <div class="space-y-4">
                        <?php if (empty($kinerjKaryawan)): ?>
                        <p class="text-sm text-gray-500 text-center">Data kinerja karyawan belum tersedia</p>
                        <?php else: ?>
                        <?php 
                        $maxTransaksi = max(array_column($kinerjKaryawan, 'transaksi_selesai'));
                        if ($maxTransaksi == 0) $maxTransaksi = 1; // Hindari pembagian dengan 0
                        ?>
                        <?php foreach ($kinerjKaryawan as $karyawan): ?>
                        <div>
                            <div class="flex justify-between text-sm font-medium mb-1">
                                <span class="text-gray-800"><?php echo htmlspecialchars($karyawan['nama_lengkap']); ?></span>
                                <span class="text-gray-500"><?php echo number_format($karyawan['transaksi_selesai']); ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-blue-600 h-2.5 rounded-full" 
                                     style="width:<?php echo round(($karyawan['transaksi_selesai'] / $maxTransaksi) * 100); ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

        </div>
    </div>

    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
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
        <a href="laporan.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="bar-chart-2" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Laporan</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Akun</span>
        </a>
    </nav>

    <div id="modal-container" class="hidden z-30" aria-hidden="true">

        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>

        <div id="modal-export"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto"
            role="dialog" aria-modal="true" aria-labelledby="export-title">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 id="export-title" class="text-xl font-bold text-gray-900">Ekspor Laporan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-export"
                        type="button" aria-label="Tutup modal ekspor">
                        <svg data-feather="x" class="w-6 h-6" aria-hidden="true"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <!-- MODAL EKSPOR ASLI DIKOMENTARI UNTUK TESTING SEDERHANA -->
                
                <form id="form-export" class="space-y-6" action="export_pdf.php" method="get" target="_blank">
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Pilih Data</h3>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="checkbox" name="export_pendapatan"
                                    class="custom-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    checked>
                                <span class="ml-3 text-sm font-medium text-gray-900">Laporan Pendapatan</span>
                            </label>
                            <label class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="checkbox" name="export_pengeluaran"
                                    class="custom-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                    checked>
                                <span class="ml-3 text-sm font-medium text-gray-900">Laporan Pengeluaran</span>
                            </label>
                            <label class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="checkbox" name="export_pelanggan"
                                    class="custom-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-3 text-sm font-medium text-gray-900">Data Pelanggan</span>
                            </label>
                            <label class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="checkbox" name="export_kinerja"
                                    class="custom-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                                <span class="ml-3 text-sm font-medium text-gray-900">Data Kinerja Karyawan</span>
                            </label>
                        </div>
                    </section>
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Pilih Format</h3>
                        <div class="flex space-x-3">
                            <label class="flex-1">
                                <input type="radio" name="format" value="pdf" class="sr-only" checked>
                                <div
                                    class="cursor-pointer p-4 border border-blue-600 bg-blue-50 rounded-lg text-center">
                                    <svg data-feather="file-text" class="w-6 h-6 mx-auto mb-1 text-blue-700"
                                        aria-hidden="true"></svg>
                                    <span class="text-sm font-semibold text-blue-700">PDF</span>
                                    <p class="text-xs text-blue-600 mt-1">Siap cetak & share</p>
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="format" value="csv" class="sr-only">
                                <div class="cursor-pointer p-4 border border-gray-300 bg-white rounded-lg text-center">
                                    <svg data-feather="grid" class="w-6 h-6 mx-auto mb-1 text-gray-600"
                                        aria-hidden="true"></svg>
                                    <span class="text-sm font-semibold text-gray-600">CSV</span>
                                    <p class="text-xs text-gray-500 mt-1">Untuk Excel & analisis</p>
                                </div>
                            </label>
                        </div>
                        <div class="mt-3 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <svg data-feather="info" class="w-4 h-4 inline mr-1" aria-hidden="true"></svg>
                                File akan otomatis terunduh sesuai filter waktu yang sedang aktif
                            </p>
                        </div>
                    </section>
                </form>
               
                <!-- MODAL EKSPOR SEDERHANA UNTUK TESTING -->
                <!-- <form id="form-export" action="export_pdf.php" method="get" target="_blank">
                    <div class="flex flex-col items-center justify-center py-8">
                        <p class="text-lg text-gray-700 mb-4">Klik tombol di bawah untuk mengunduh laporan.</p>
                        <button type="submit" class="btn-simpan bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700">
                            Unduh Laporan
                        </button>
                    </div>
                </form> -->
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                                <button type="submit" id="btn-export-submit" form="form-export" action="export_pdf.php" method="get" target="_blank"
                                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                                    data-modal-id="modal-export">
                                    Unduh Laporan
                                </button>
            </div>
        </div>

        <div id="modal-filter-tanggal"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto"
            role="dialog" aria-modal="true" aria-labelledby="filter-title">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 id="filter-title" class="text-xl font-bold text-gray-900">Filter Waktu</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-filter-tanggal" type="button" aria-label="Tutup modal filter">
                        <svg data-feather="x" class="w-6 h-6" aria-hidden="true"></svg>
                    </button>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-filter" class="space-y-6">
                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Filter Cepat</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button"
                                class="btn-quick-filter w-full py-3 px-4 bg-gray-100 rounded-lg font-medium text-gray-700"
                                data-filter-text="Filter: Hari Ini">Hari Ini</button>
                            <button type="button"
                                class="btn-quick-filter w-full py-3 px-4 bg-gray-100 rounded-lg font-medium text-gray-700"
                                data-filter-text="Filter: 7 Hari Terakhir">7 Hari Terakhir</button>
                            <button type="button"
                                class="btn-quick-filter w-full py-3 px-4 bg-blue-100 rounded-lg font-bold text-blue-700"
                                data-filter-text="Filter: Bulan Ini">Bulan Ini</button>
                            <button type="button"
                                class="btn-quick-filter w/full py-3 px-4 bg-gray-100 rounded-lg font-medium text-gray-700"
                                data-filter-text="Filter: Tahun Ini">Tahun Ini</button>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Pilih Rentang Kustom</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="filter_tanggal_mulai" class="text-sm font-medium text-gray-600">Tanggal
                                    Mulai</label>
                                <input type="date" id="filter_tanggal_mulai" name="filter_tanggal_mulai"
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label for="filter_tanggal_selesai" class="text-sm font-medium text-gray-600">Tanggal
                                    Selesai</label>
                                <input type="date" id="filter_tanggal_selesai" name="filter_tanggal_selesai"
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-filter"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-filter-tanggal">
                    Terapkan Filter
                </button>
            </div>
        </div>

    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-laporan.js"></script>
    
    <style>
        /* Chart container styling untuk memastikan responsivitas */
        canvas {
            max-width: 100% !important;
            height: auto !important;
        }
        
        /* Animasi untuk chart legend */
        .chart-legend-item {
            transition: all 0.2s ease;
        }
        
        .chart-legend-item:hover {
            background-color: #f3f4f6;
            transform: translateX(2px);
        }
        
        /* Loading indicator untuk chart */
        .chart-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            color: #6b7280;
        }
    </style>
</body>

</html>