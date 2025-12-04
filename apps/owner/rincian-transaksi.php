<?php
require_once __DIR__ . '/middleware/auth_owner.php';
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

// Filter dan pencarian
$filterType = $_GET['filter'] ?? 'semua';
$searchQuery = $_GET['search'] ?? '';
$tanggalMulai = $_GET['tanggal_mulai'] ?? null;
$tanggalSelesai = $_GET['tanggal_selesai'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;

// Inisialisasi query class
$laporanQuery = new LaporanQuery($bisnisId);

// Ambil data transaksi dengan paginasi
$offset = ($page - 1) * $limit;
$transaksiData = $laporanQuery->getAllTransaksi($filterType, $searchQuery, $tanggalMulai, $tanggalSelesai, $limit, $offset);
$totalTransaksi = $laporanQuery->getTotalTransaksi($filterType, $searchQuery, $tanggalMulai, $tanggalSelesai);
$totalPages = ceil($totalTransaksi / $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Transaksi - <?php echo htmlspecialchars($bisnisNama); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="bg-gray-50">
    <div class="max-w-md mx-auto bg-white min-h-screen">
        <!-- Header -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-20">
            <div class="flex items-center px-4 py-3">
                <a href="laporan.php" class="mr-3 p-2 hover:bg-gray-100 rounded-full">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div class="flex-1">
                    <h1 class="text-xl font-bold text-gray-900">Rincian Transaksi</h1>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($bisnisNama); ?></p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="p-4 bg-white border-b border-gray-200">
            <form method="GET" class="space-y-3">
                <!-- Search Bar -->
                <div class="relative">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           placeholder="Cari nota, pelanggan..." 
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Buttons -->
                <div class="flex space-x-2 overflow-x-auto pb-2">
                    <button type="submit" name="filter" value="semua" 
                            class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap <?php echo $filterType == 'semua' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Semua
                    </button>
                    <button type="submit" name="filter" value="hari_ini" 
                            class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap <?php echo $filterType == 'hari_ini' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Hari Ini
                    </button>
                    <button type="submit" name="filter" value="7_hari" 
                            class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap <?php echo $filterType == '7_hari' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        7 Hari
                    </button>
                    <button type="submit" name="filter" value="bulan_ini" 
                            class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap <?php echo $filterType == 'bulan_ini' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        Bulan Ini
                    </button>
                </div>

                <!-- Custom Date Range (Hidden by default) -->
                <div id="custom-date-range" class="hidden space-y-2">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="tanggal_mulai" value="<?php echo $tanggalMulai; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <input type="date" name="tanggal_selesai" value="<?php echo $tanggalSelesai; ?>" 
                               class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <button type="submit" name="filter" value="kustom" 
                            class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                        Terapkan Filter
                    </button>
                </div>
            </form>
            
            <button onclick="toggleCustomDate()" class="text-sm text-blue-600 hover:text-blue-800 font-medium mt-2">
                Filter Tanggal Kustom
            </button>
        </div>

        <!-- Summary Stats -->
        <div class="p-4 bg-blue-50 border-b border-gray-200">
            <div class="grid grid-cols-3 gap-3 text-center">
                <div>
                    <p class="text-lg font-bold text-blue-600"><?php echo number_format($totalTransaksi); ?></p>
                    <p class="text-xs text-gray-600">Total Transaksi</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-green-600">
                        <?php 
                        $totalPendapatan = array_sum(array_column($transaksiData, 'total_harga'));
                        echo formatRupiah($totalPendapatan); 
                        ?>
                    </p>
                    <p class="text-xs text-gray-600">Total Pendapatan</p>
                </div>
                <div>
                    <p class="text-lg font-bold text-purple-600">
                        <?php 
                        $rataRata = $totalTransaksi > 0 ? $totalPendapatan / $totalTransaksi : 0;
                        echo formatRupiah($rataRata); 
                        ?>
                    </p>
                    <p class="text-xs text-gray-600">Rata-rata</p>
                </div>
            </div>
        </div>

        <!-- Transaction List -->
        <div class="p-4 space-y-3">
            <?php if (empty($transaksiData)): ?>
            <div class="text-center py-8">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-500">Tidak ada transaksi ditemukan</p>
                <?php if (!empty($searchQuery)): ?>
                <a href="rincian-transaksi.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Hapus filter pencarian
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            
            <?php foreach ($transaksiData as $transaksi): ?>
            <div class="bg-white rounded-lg shadow p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">ID Nota #<?php echo htmlspecialchars($transaksi['no_nota']); ?></p>
                        <p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($transaksi['nama_pelanggan']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-base font-bold text-gray-900"><?php echo formatRupiah($transaksi['total_harga']); ?></p>
                        <span class="text-xs font-semibold rounded-full px-2 py-0.5
                            <?php echo $transaksi['status'] == 'selesai' ? 'bg-green-100 text-green-700' : 
                                       ($transaksi['status'] == 'diproses' ? 'bg-blue-100 text-blue-700' : 
                                       ($transaksi['status'] == 'diambil' ? 'bg-purple-100 text-purple-700' : 
                                       ($transaksi['status'] == 'menunggu' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'))); ?>">
                            <?php echo ucfirst(htmlspecialchars($transaksi['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="text-sm text-gray-500">
                    <p>Masuk: <?php echo date('d/m/Y - H:i', strtotime($transaksi['created_at'])); ?></p>
                    <?php if ($transaksi['karyawan_nama']): ?>
                    <p>Karyawan: <?php echo htmlspecialchars($transaksi['karyawan_nama']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex space-x-2 mt-3">
                    <button onclick="viewDetail('<?php echo $transaksi['no_nota']; ?>')" 
                            class="flex-1 px-3 py-2 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-100">
                        Detail
                    </button>
                    <?php if ($transaksi['status'] != 'selesai' && $transaksi['status'] != 'batal'): ?>
                    <button onclick="updateStatus('<?php echo $transaksi['no_nota']; ?>')" 
                            class="flex-1 px-3 py-2 bg-green-50 text-green-600 text-sm font-medium rounded-lg hover:bg-green-100">
                        Update Status
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex justify-between items-center pt-4">
                <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filterType; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                    ← Sebelumnya
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
                
                <span class="text-sm text-gray-500">
                    Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
                </span>
                
                <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filterType; ?>&search=<?php echo urlencode($searchQuery); ?>" 
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium">
                    Selanjutnya →
                </a>
                <?php else: ?>
                <div></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleCustomDate() {
            const customDateRange = document.getElementById('custom-date-range');
            customDateRange.classList.toggle('hidden');
        }
        
        function viewDetail(noNota) {
            // Redirect to transaction detail page
            window.location.href = `transaksi.php?detail=${noNota}`;
        }
        
        function updateStatus(noNota) {
            // Redirect to status update page
            window.location.href = `transaksi.php?update=${noNota}`;
        }
    </script>
</body>
</html>