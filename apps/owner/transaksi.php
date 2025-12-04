<?php

require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/api/query-transaksi.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

// Flash messages
$flashSuccess = $_SESSION['transaksi_flash_success'] ?? null;
$flashError = $_SESSION['transaksi_flash_error'] ?? null;
unset($_SESSION['transaksi_flash_success'], $_SESSION['transaksi_flash_error']);

// Handle POST requests for transaction operations

if ($bisnisId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'update_status':
                updateTransactionStatus($conn, $bisnisId, $_POST);
                break;
            case 'create_transaction':
                createTransaction($conn, $bisnisId, $_POST);
                break;
            case 'create_expense':
                createExpense($conn, $bisnisId, $_POST);
                break;
            default:
                throw new InvalidArgumentException('Aksi tidak dikenal.');
        }
    } catch (Exception $e) {
        $_SESSION['transaksi_flash_error'] = $e->getMessage();
    }
    header('Location: transaksi.php');
    exit;
}


// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = trim($_GET['search'] ?? '');

// Get transactions and counts using query-transaksi.php
$transactions = getTransactions($conn, $bisnisId, $statusFilter, $searchTerm);
$transactionCounts = getTransactionCounts($conn, $bisnisId);

// Load WhatsApp message templates (per jenis) for this bisnis
$templatePesan = [];
if ($bisnisId) {
    try {
        $stmt = $conn->prepare("SELECT jenis, isi_pesan FROM template_pesan WHERE bisnis_id = ? AND is_active = 1 ORDER BY jenis");
        $stmt->execute([$bisnisId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $templatePesan[$r['jenis']] = $r['isi_pesan'];
        }
    } catch (PDOException $e) {
        // ignore - fallback handled later
    }
}

// // Default templates if not present
// $defaultPesan = [
//     'masuk' => 'Hai [NAMA_PELANGGAN], pesanan Anda [ID_NOTA] telah kami terima. Total: [TOTAL_HARGA].',
//     'proses' => 'Hai [NAMA_PELANGGAN], pesanan [ID_NOTA] sedang diproses.',
//     'selesai' => 'Hai [NAMA_PELANGGAN], pesanan [ID_NOTA] sudah selesai. Silakan ambil.',
//     'pembayaran' => 'Hai [NAMA_PELANGGAN], terima kasih, pembayaran untuk [ID_NOTA] sebesar [TOTAL_HARGA] telah diterima.'
// ];
// foreach ($defaultPesan as $k => $v) {
//     if (!isset($templatePesan[$k]) || $templatePesan[$k] === '') $templatePesan[$k] = $v;
// }
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
    <script src="../../assets/js/error-handler.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    <div id="main-content" class="flex flex-col flex-grow">
        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 pb-6 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Riwayat Transaksi</h1>
            <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>
            <!-- Search & Tombol Tambah -->
            <div class="flex items-center space-x-3 mt-4">
                <div class="relative flex-grow" id="search-container">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text" 
                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                        placeholder="Cari nama pelanggan atau ID nota..."
                        id="search-input"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <?php if ($searchTerm !== ''): ?>
                    <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg data-feather="x" class="h-5 w-5 text-gray-400 hover:text-gray-600"></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <a href="dashboard.php" class="bg-white p-3 rounded-lg shadow flex-shrink-0 hover:bg-gray-50 transition-colors">
                    <svg data-feather="plus" class="h-6 w-6 text-blue-600"></svg>
                </a>
            </div>

        </header>

        <nav class="sticky top-[174px] z-10 bg-gray-100 pt-4 pb-3 px-6 flex-shrink-0">
            <div class="flex space-x-3 overflow-x-auto no-scrollbar">
                <a href="?status=all&search=<?php echo urlencode($searchTerm); ?>" 
                   class="<?php echo ($statusFilter === 'all' || $statusFilter === '') ? 'filter-chip-active' : 'bg-white text-gray-700'; ?> px-5 py-2 rounded-full text-sm whitespace-nowrap">
                    Semua (<?php echo $transactionCounts['all']; ?>)
                </a>
                <a href="?status=pending&search=<?php echo urlencode($searchTerm); ?>" 
                   class="<?php echo $statusFilter === 'pending' ? 'filter-chip-active' : 'bg-white text-gray-700'; ?> px-5 py-2 rounded-full text-sm whitespace-nowrap">
                    Antrian (<?php echo $transactionCounts['pending']; ?>)
                </a>
                <a href="?status=proses&search=<?php echo urlencode($searchTerm); ?>" 
                   class="<?php echo $statusFilter === 'proses' ? 'filter-chip-active' : 'bg-white text-gray-700'; ?> px-5 py-2 rounded-full text-sm whitespace-nowrap">
                    Proses (<?php echo $transactionCounts['proses']; ?>)
                </a>
                <a href="?status=selesai&search=<?php echo urlencode($searchTerm); ?>" 
                   class="<?php echo $statusFilter === 'selesai' ? 'filter-chip-active' : 'bg-white text-gray-700'; ?> px-5 py-2 rounded-full text-sm whitespace-nowrap">
                    Selesai (<?php echo $transactionCounts['selesai']; ?>)
                </a>
            </div>
        </nav>

        <main class="flex-grow overflow-y-auto p-6 space-y-4">
            <?php if ($flashSuccess): ?>
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($flashError); ?>
            </div>
            <?php endif; ?>

            <?php if (empty($transactions)): ?>
            <div class="text-center py-8">
                <!-- <svg data-feather="inbox" class="mx-auto h-12 w-12 text-gray-400"></svg> -->
                <h3 class="mt-2 text-lg font-medium text-gray-900">
                    <?php echo empty($searchTerm) && $statusFilter === 'all' ? '' : ''; ?>
                </h3>
                <p class="mt-1 text-gray-500">
                    <?php echo empty($searchTerm) && $statusFilter === 'all' ? '' : 'Coba ubah filter atau kata kunci pencarian'; ?>
                </p>
            </div>
            <?php else: ?>
            <?php foreach ($transactions as $transaction): ?>
            <button class="transaksi-card w-full bg-white rounded-lg shadow p-4 text-left hover:bg-gray-50 transition-colors"
                data-transaksi-id="<?php echo htmlspecialchars($transaction['transaksi_id']); ?>"
                data-no-nota="<?php echo htmlspecialchars($transaction['no_nota']); ?>"
                data-pelanggan="<?php echo htmlspecialchars($transaction['pelanggan_nama'] ?? 'Guest'); ?>"
                data-total="<?php echo htmlspecialchars($transaction['total_harga_display']); ?>"
                data-status="<?php echo htmlspecialchars($transaction['status']); ?>"
                data-status-bayar="<?php echo htmlspecialchars($transaction['status_bayar']); ?>"
                data-tanggal-masuk="<?php echo htmlspecialchars($transaction['tanggal_masuk']); ?>"
                data-tanggal-selesai="<?php echo htmlspecialchars($transaction['tanggal_selesai']); ?>"
                data-catatan="<?php echo htmlspecialchars($transaction['catatan'] ?? ''); ?>"
                data-telepon="<?php echo htmlspecialchars($transaction['pelanggan_telepon'] ?? ''); ?>"
                data-pelanggan-id="<?php echo htmlspecialchars($transaction['pelanggan_id'] ?? ''); ?>">
                <div class="flex justify-between items-start">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="file-text" class="w-5 h-5 text-gray-600"></svg>
                        </div>
                        <div>
                            <p class="transaksi-nota text-sm font-semibold text-gray-800">ID Nota #<?php echo htmlspecialchars($transaction['no_nota']); ?></p>
                            <p class="transaksi-pelanggan text-lg font-bold text-gray-900"><?php echo htmlspecialchars($transaction['pelanggan_nama'] ?? 'Guest'); ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-lg font-bold text-gray-900 text-right"><?php echo $transaction['total_harga_display']; ?></p>
                        <span class="text-xs font-semibold <?php echo $transaction['status_badge']; ?> rounded-full px-2 py-0.5">
                            <?php echo $transaction['status_display']; ?>
                        </span>
                    </div>
                </div>
                <div class="border-t border-dashed mt-3 pt-3 text-sm text-gray-500">
                    <p>Masuk : <?php echo date('d/m/Y - H:i', strtotime($transaction['tanggal_masuk'])); ?></p>
                    <?php if ($transaction['tanggal_selesai']): ?>
                    <p>Selesai : <?php echo calculateTimeRemaining($transaction['tanggal_selesai']); ?></p>
                    <?php else: ?>
                    <p>Status Bayar: <?php echo $transaction['status_bayar_display']; ?></p>
                    <?php endif; ?>
                </div>
            </button>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

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

    <!-- Modal Container -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>
        
        <div id="modal-rincian-transaksi" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[95vh]">
            <div class="flex-shrink-0 bg-white rounded-t-[24px]">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Rincian Transaksi</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-rincian-transaksi">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>

                <div class="px-6 pb-4 border-b border-gray-200">
                    <p class="text-lg font-bold text-gray-900 transaksi-pelanggan-nama"><?php echo htmlspecialchars($selectedTransaction['pelanggan'] ?? 'Guest'); ?></p>
                    <p class="text-sm text-gray-500 -mt-1 transaksi-no-nota">ID Nota #<?php echo htmlspecialchars($selectedTransaction['no_nota'] ?? '0000'); ?></p>

                    <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Total Tagihan</span>
                            <p class="text-xl font-bold text-blue-600 transaksi-total-display"><?php echo htmlspecialchars($selectedTransaction['total_harga'] ?? 'Rp 0'); ?></p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Status Bayar</span>
                            <p class="text-xl font-bold text-gray-900 transaksi-status-bayar-display"><?php echo htmlspecialchars($selectedTransaction['status_bayar'] ?? 'Belum Lunas'); ?></p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Status Pesanan</span>
                            <p class="text-xl font-bold text-gray-900 transaksi-status-display"><?php echo htmlspecialchars($selectedTransaction['status'] ?? 'Pending'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto divide-y divide-gray-100 no-scrollbar">
                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Rincian Waktu</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tanggal Masuk</span>
                            <span class="font-medium text-gray-800 transaksi-tanggal-masuk">22 Okt 2025, 19:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Tanggal Selesai</span>
                            <span class="font-medium text-gray-800 transaksi-tanggal-selesai">23 Okt 2025, 19:00</span>
                        </div>
                    </div>
                </section>

                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Aksi Lainnya</h3>
                    <div class="space-y-3">
                        <button class="btn-cetak-nota w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                    <svg data-feather="printer" class="w-5 h-5 text-gray-700"></svg>
                                </div>
                                <p class="font-semibold text-gray-800">Cetak Nota</p>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        <button id="btn-kirim-wa" class="btn-kirim-wa w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3">
                                    <svg data-feather="message-circle" class="w-5 h-5 text-green-700"></svg>
                                </div>
                                <p class="font-semibold text-gray-800">Kirim Pesan (WhatsApp)</p>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        <button id="btn-opsi-lanjutan" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                                    <svg data-feather="settings" class="w-5 h-5 text-gray-700"></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">Ubah Status</p>
                                    <p class="text-sm text-gray-500">Ubah status bayar atau pesanan</p>
                                </div>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                    </div>
                </section>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button id="btn-aksi-utama" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 shadow-sm">
                    Tandai Selesai
                </button>
            </div>
        </div>

        <div id="modal-opsi-lanjutan" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Opsi Lanjutan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-opsi-lanjutan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="px-6 space-y-4 overflow-y-auto no-scrollbar flex-grow">
                <form id="form-opsi-lanjutan" method="POST" action="transaksi.php" class="space-y-4">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="transaksi_id" id="update_transaksi_id" class="transaksi-id-input">

                    <div>
                        <label for="opsi_pembayaran" class="text-sm font-medium text-gray-600">Ubah Status Pembayaran</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <select id="opsi_pembayaran" name="status_bayar"
                                class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">-- Tidak Diubah --</option>
                                <option value="belum_lunas">Belum Lunas</option>
                                <option value="lunas">Lunas</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label for="opsi_status" class="text-sm font-medium text-gray-600">Ubah Status Pesanan</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="refresh-cw" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <select id="opsi_status" name="new_status"
                                class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                <option value="">-- Tidak Diubah --</option>
                                <option value="pending">Pending</option>
                                <option value="proses">Diproses</option>
                                <option value="selesai">Selesai</option>
                                <option value="diambil">Diambil</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200 flex space-x-3">
                <button class="btn-close-modal w-1/2 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50" data-modal-id="modal-opsi-lanjutan">
                    Batal
                </button>
                <button type="submit" form="form-opsi-lanjutan" class="w-1/2 bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>

        <div id="modal-kirim-wa" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Kirim Pesan WhatsApp</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-kirim-wa">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="px-6 pb-4">
                <label for="wa-phone-input" class="text-sm font-medium text-gray-600 mb-2 block">Nomor WhatsApp</label>
                <input type="tel" id="wa-phone-input" class="w-full px-3 py-3 border border-gray-300 rounded-lg mb-4" placeholder="08xxxxxxxxxx"><?php echo htmlspecialchars($selectedTransaction['telepon'] ?? ''); ?>

                <label for="wa-template-select" class="text-sm font-medium text-gray-600 mb-2 block">Pilih Template Pesan</label>
                <select id="wa-template-select" class="w-full px-3 py-3 border border-gray-300 rounded-lg mb-4">
                    <?php
                    $labels = [
                        'masuk' => 'Pesanan Masuk',
                        'proses' => 'Pesanan Diproses',
                        'selesai' => 'Pesanan Selesai',
                        'pembayaran' => 'Pembayaran'
                    ];
                    foreach ($labels as $jenis => $label) {
                        $disabled = '';
                        echo "<option value=\"" . htmlspecialchars($jenis) . "\">" . htmlspecialchars($label) . "</option>\n";
                    }
                    ?>
                </select>

                <label for="wa-message-preview" class="text-sm font-medium text-gray-600 mb-2 block">Preview Pesan</label>
                <textarea id="wa-message-preview" class="w-full px-3 py-3 border border-gray-300 rounded-lg mb-4" rows="4" readonly></textarea>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button id="btn-kirim-wa-dinamis" class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700">
                    Kirim Pesan
                </button>
            </div>
        </div>

    
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        window.templatePesan = <?php echo json_encode($templatePesan, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || {};
        window.bisnisNama = <?php echo json_encode($bisnisNama); ?>;
    </script>
    <script src="../../assets/js/owner-transaksi.js"></script>
    <script>
    // Enhanced search functionality - berdasarkan referensi pelanggan.php
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const clearButton = document.getElementById('clear-search');
        const transaksiCards = document.querySelectorAll('.transaksi-card');
        const currentSearch = '<?php echo addslashes($searchTerm); ?>';
        const statusFilter = '<?php echo addslashes($statusFilter); ?>';
        
        // Real-time search filtering
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                filterTransaksi(searchTerm);
                
                // Update URL without page reload
                const url = new URL(window.location);
                if (searchTerm) {
                    url.searchParams.set('search', searchTerm);
                } else {
                    url.searchParams.delete('search');
                }
                if (statusFilter && statusFilter !== 'all') {
                    url.searchParams.set('status', statusFilter);
                }
                window.history.replaceState({}, '', url);
                
                // Toggle clear button
                toggleClearButton(searchTerm);
            });
        }
        
        // Clear search functionality
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                filterTransaksi('');
                
                // Update URL
                const url = new URL(window.location);
                url.searchParams.delete('search');
                if (statusFilter && statusFilter !== 'all') {
                    url.searchParams.set('status', statusFilter);
                } else {
                    url.searchParams.delete('status');
                }
                window.history.replaceState({}, '', url);
                
                // Hide clear button
                this.style.display = 'none';
                searchInput.focus();
            });
        }
        
        function filterTransaksi(searchTerm) {
            let visibleCount = 0;
            
            transaksiCards.forEach(card => {
                const noNota = card.dataset.noNota?.toLowerCase() || '';
                const pelanggan = card.dataset.pelanggan?.toLowerCase() || '';
                
                // Check search match
                const searchMatch = !searchTerm || 
                    noNota.includes(searchTerm) ||
                    pelanggan.includes(searchTerm);
                
                if (searchMatch) {
                    card.style.display = 'block';
                    visibleCount++;
                    highlightText(card, searchTerm);
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide empty state
            updateEmptyState(visibleCount, searchTerm);
        }
        
        function highlightText(card, searchTerm) {
            if (!searchTerm) {
                // Reset highlights
                const textElements = card.querySelectorAll('.transaksi-nota, .transaksi-pelanggan');
                textElements.forEach(element => {
                    if (element.dataset.originalText) {
                        element.innerHTML = element.dataset.originalText;
                    }
                });
                return;
            }
            
            const textElements = card.querySelectorAll('.transaksi-nota, .transaksi-pelanggan');
            textElements.forEach(element => {
                const originalText = element.dataset.originalText || element.textContent;
                element.dataset.originalText = originalText;
                
                const regex = new RegExp(`(${searchTerm})`, 'gi');
                element.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
            });
        }
        
        function toggleClearButton(searchTerm) {
            if (clearButton) {
                clearButton.style.display = searchTerm ? 'flex' : 'none';
            }
        }
        
        function updateEmptyState(visibleCount, searchTerm) {
            const mainContainer = document.querySelector('main');
            let emptyState = document.getElementById('search-empty-state');
        
            if (visibleCount === 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.id = 'search-empty-state';
                    emptyState.className = 'text-center py-8';
                    
                    let message = '';
                    let subMessage = '';
                    
                    if (searchTerm && statusFilter !== 'all') {
                        message = `Tidak ada transaksi ${getFilterName(statusFilter)} ditemukan`;
                        subMessage = 'Coba ubah filter atau kata kunci pencarian';
                    } else if (searchTerm) {
                        message = 'Tidak ada transaksi ditemukan';
                        subMessage = 'Coba gunakan kata kunci yang berbeda';
                    } else if (statusFilter !== 'all') {
                        message = `Belum ada transaksi ${getFilterName(statusFilter)}`;
                        subMessage = 'Transaksi akan muncul berdasarkan status yang dipilih';
                    } else {
                        message = 'Belum ada transaksi';
                        subMessage = 'Mulai buat transaksi pertama Anda';
                    }
                    
                    emptyState.innerHTML = `
                        <svg data-feather="search" class="mx-auto h-12 w-12 text-gray-400"></svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">${message}</h3>
                        <p class="mt-1 text-gray-500">${subMessage}</p>
                    `;
                    mainContainer.insertBefore(emptyState, mainContainer.firstElementChild.nextSibling);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
        
        function getFilterName(filter) {
            const names = {
                'pending': 'antrian',
                'proses': 'dalam proses', 
                'selesai': 'selesai',
                'diambil': 'diambil',
                'batal': 'dibatalkan'
            };
            return names[filter] || filter;
        }
        
        // Initial setup
        if (currentSearch && searchInput) {
            searchInput.value = currentSearch;
            toggleClearButton(currentSearch);
        }
        
        // Apply initial filter
        filterTransaksi(currentSearch);
    });
    </script>
</body>

</html>