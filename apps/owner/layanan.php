<?php
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/functions.php'; 
require_once __DIR__ . '/models/kategori.php';
require_once __DIR__ . '/models/layanan.php';
// Ambil data owner dari session
$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';
// Ambil flash messages 
$flashSuccess = getFlash('layanan_flash_success');
$flashError = getFlash('layanan_flash_error');
// Cek apakah tabel layanan memiliki kolom bisnis_id
$hasLayananBisnisColumn = false;
try {
    $conn->query('SELECT bisnis_id FROM layanan LIMIT 1');
    $hasLayananBisnisColumn = true;
} catch (PDOException $ignored) {
    $hasLayananBisnisColumn = false;
}
// Pastikan kategori default ada
if (!$bisnisId) {
    $flashError = $flashError ?? 'Data bisnis tidak tersedia.';
    $kategoriList = [];
    $services = [];
} else {
    try {
        $kategoriList = ensureDefaultKategori($conn, $bisnisId);
    } catch (PDOException $e) {
        logError('Fetch kategori layanan gagal', [
            'error' => $e->getMessage(),
            'bisnis_id' => $bisnisId,
        ]);
        $kategoriList = [];
        $flashError = $flashError ?? 'Gagal memuat kategori layanan.';
    }
}
// Handle form submission untuk CRUD layanan
if ($bisnisId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handler form CRUD layanan dipindahkan ke models/layanan.php
    require_once __DIR__ . '/models/layanan.php';
    handleLayananForm($conn, $bisnisId, $hasLayananBisnisColumn);
}
// Ambil data layanan berdasarkan filter dan pencarian
$searchTerm = getSearchTerm();
$selectedKategori = $_GET['kategori'] ?? 'all';
$services = [];
// Hanya ambil layanan jika bisnisId tersedia
if ($bisnisId) {
    try {
        $services = readLayanan($conn, $bisnisId, $hasLayananBisnisColumn, $selectedKategori, $searchTerm);
    } catch (PDOException $e) {
        logError('Fetch layanan gagal', [
            'error' => $e->getMessage(),
            'bisnis_id' => $bisnisId,
        ]);
        $services = [];
        $flashError = $flashError ?? 'Gagal memuat data layanan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kelola Layanan - BersihXpress</title>
        <script src="https://cdn.tailwindcss.com"></script>

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
        <div id="main-content" class="flex flex-col flex-grow overflow-hidden">
            
            <!-- Header (Sticky) -->
            <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 shadow-lg flex-shrink-0">
                <h1 class="text-2xl font-bold text-white">Kelola Layanan</h1>
                <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>

                <!-- Search & Tombol Tambah -->
                <div class="flex items-center space-x-3 mt-4">
                    <div class="relative flex-grow" id="search-container">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                        </span>
                        <input type="text" 
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            placeholder="Cari nama layanan, kategori, atau deskripsi..."
                            id="search-input"
                            value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <?php if ($searchTerm !== ''): ?>
                        <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg data-feather="x" class="h-5 w-5 text-gray-400 hover:text-gray-600"></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <button id="btn-tambah-layanan" class="bg-white p-3 rounded-lg shadow flex-shrink-0 hover:bg-gray-50 transition-colors">
                        <svg data-feather="plus" class="h-6 w-6 text-blue-600"></svg>
                    </button>
                </div>
            </header>

            <!--  Filter Chips (Sticky) -->
            <nav class="sticky top-[160px] z-10 bg-gray-100 pt-4 pb-3 px-6 flex-shrink-0">
                <div class="flex space-x-3 overflow-x-auto no-scrollbar">
                    <?php
                    $allActive = $selectedKategori === 'all' || $selectedKategori === '';
                    $baseClasses = 'px-5 py-2 rounded-full text-sm whitespace-nowrap transition-colors';
                    $activeClasses = 'filter-chip-active font-semibold';
                    $inactiveClasses = 'bg-white text-gray-700 hover:bg-blue-50';
                    ?>
                    <a href="<?php echo htmlspecialchars(layananFilterUrl('all', $searchTerm)); ?>"
                        class="<?php echo $baseClasses . ' ' . ($allActive ? $activeClasses : $inactiveClasses); ?>">
                        Semua
                    </a>
                    <?php foreach ($kategoriList as $kategori):
                        $isActive = $selectedKategori === $kategori['kategori_id'];
                        $classes = $baseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses);
                    ?>
                    <a href="<?php echo htmlspecialchars(layananFilterUrl($kategori['kategori_id'], $searchTerm)); ?>"
                        class="<?php echo $classes; ?>">
                        <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </nav>

            <!-- Daftar Layanan (Scrollable) -->
            <main class="flex-grow overflow-y-auto p-6 space-y-3 no-scrollbar pb-24">
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

                <?php if (empty($services)): ?>
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
                    Belum ada layanan. Tambahkan layanan baru untuk bisnis Anda.
                </div>
                <?php else: ?>
                <?php foreach ($services as $service): ?>
                <button
                    class="btn-buka-opsi w-full bg-white rounded-lg shadow p-4 text-left flex items-center justify-between"
                    data-id="<?php echo htmlspecialchars($service['layanan_id']); ?>"
                    data-nama="<?php echo htmlspecialchars($service['nama_layanan']); ?>"
                    data-kategori="<?php echo htmlspecialchars($service['kategori_id']); ?>"
                    data-kategori-nama="<?php echo htmlspecialchars($service['nama_kategori'] ?? 'Tanpa Kategori'); ?>"
                    data-harga="<?php echo htmlspecialchars($service['harga']); ?>"
                    data-satuan="<?php echo htmlspecialchars($service['satuan']); ?>"
                    data-estimasi="<?php echo htmlspecialchars($service['estimasi_waktu'] ?? ''); ?>"
                    data-deskripsi="<?php echo htmlspecialchars($service['deskripsi'] ?? ''); ?>">
                    <div class="flex items-center pr-4">
                        <div class="p-3 bg-gray-100 rounded-full mr-4"><svg data-feather="tag"
                                class="w-5 h-5 text-gray-600"></svg></div>
                        <div class="text-left">
                            <p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($service['nama_layanan']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($service['nama_kategori'] ?? 'Tanpa Kategori'); ?>
                                &bull;
                                <?php echo htmlspecialchars($service['estimasi_display']); ?>
                            </p>
                            <p class="text-sm font-semibold text-blue-600">
                                <?php echo htmlspecialchars($service['harga_display']); ?>
                                <?php if (!empty($service['satuan'])): ?> / <?php echo htmlspecialchars($service['satuan']); ?><?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400 flex-shrink-0"></svg>
                </button>
                <?php endforeach; ?>
                <?php endif; ?>

            </main>
        </div>

        <!-- Navigation Button -->
        <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
            <a href="kelola.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
                <svg data-feather="grid" class="w-6 h-6"></svg>
                <span class="text-xs mt-1 font-semibold">Kelola</span>
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
            <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
                <svg data-feather="user" class="w-6 h-6"></svg>
                <span class="text-xs mt-1">Akun</span>
            </a>
        </nav>

        <!-- Modal Container -->
        <div id="modal-container" class="hidden z-30">
            <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>
            
            <div id="modal-opsi-layanan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
                
                <div class="flex-shrink-0">
                    <div class="w-full py-3">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center px-6 pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Opsi Layanan</h2>
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-opsi-layanan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                    <div class="px-6 pb-4">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Layanan terpilih</p>
                        <p id="opsi-layanan-nama" class="mt-1 text-base font-semibold text-gray-900">-</p>
                    </div>
                </div>
                
                <div class="px-6 space-y-3">
                    <button id="btn-edit-layanan"
                        class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="edit"
                                    class="w-5 h-5 text-gray-700"></svg></div>
                            <div>
                                <p class="font-semibold text-gray-800">Edit Layanan</p>
                                <p class="text-sm text-gray-500">Memperbaharui detail layanan</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </button>
                    <button id="btn-hapus-layanan"
                        class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="trash-2"
                                    class="w-5 h-5 text-red-600"></svg></div>
                            <div>
                                <p class="font-semibold text-red-600">Hapus Layanan</p>
                                <p class="text-sm text-gray-500">Menghapus layanan ini</p>
                            </div>
                        </div>
                        <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                    </button>
                </div>
                
                <div class="flex-shrink-0 p-6 bg-white">
                    <button
                        class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50"
                        data-modal-id="modal-opsi-layanan">
                        Batal
                    </button>
                </div>
            </div>
            
            <div id="modal-edit-layanan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
                <div class="flex-shrink-0">
                    <div class="w-full py-3">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center px-6 pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Edit Layanan</h2>
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-edit-layanan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                
                <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                    <form id="form-edit-layanan" class="space-y-4" method="POST" action="api/query-layanan.php">
                        <input type="hidden" name="action" value="update_service">
                        <input type="hidden" name="layanan_id" id="edit_layanan_id">

                        <div>
                            <label for="edit_nama_layanan" class="text-sm font-medium text-gray-600">Nama Layanan <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="tag" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="text" id="edit_nama_layanan" name="nama_layanan"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Nama layanan"
                                    required>
                            </div>
                        </div>

                        <div>
                            <label for="edit_kategori_id" class="text-sm font-medium text-gray-600">Kategori <span class="text-red-500">*</span></label>
                            <select id="edit_kategori_id" name="kategori_id" required
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($kategoriList as $kategori): ?>
                                <option value="<?php echo htmlspecialchars($kategori['kategori_id']); ?>">
                                    <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="edit_harga" class="text-sm font-medium text-gray-600">Harga (per unit) <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="number" step="0.01" min="0" id="edit_harga" name="harga"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="0"
                                    required>
                            </div>
                        </div>

                        <div>
                            <label for="edit_satuan" class="text-sm font-medium text-gray-600">Satuan <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="box" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="text" id="edit_satuan" name="satuan"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: kg, pcs, m²" required>
                            </div>
                        </div>

                        <div>
                            <label for="edit_estimasi" class="text-sm font-medium text-gray-600">Estimasi Waktu (jam) <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="clock" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="number" min="0" id="edit_estimasi" name="estimasi_waktu"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: 24" required>
                            </div>
                        </div>

                        <div>
                            <label for="edit_deskripsi" class="text-sm font-medium text-gray-600">Deskripsi</label>
                            <textarea id="edit_deskripsi" name="deskripsi" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-3"
                                placeholder="Catatan tambahan layanan (opsional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                    <button type="submit" form="form-edit-layanan"
                        class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                        data-modal-id="modal-edit-layanan">
                        Simpan Perubahan
                    </button>
                </div>
            </div>

            <div id="modal-tambah-layanan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
                <div class="flex-shrink-0">
                    <div class="w-full py-3">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center px-6 pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Tambah Layanan Baru</h2>
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-tambah-layanan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                
                <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                    <form id="form-tambah-layanan" class="space-y-4" method="POST" action="api/query-layanan.php">
                        <input type="hidden" name="action" value="create_service">

                        <div>
                            <label for="tambah_nama_layanan" class="text-sm font-medium text-gray-600">Nama Layanan <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="tag" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="text" id="tambah_nama_layanan" name="nama_layanan"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: Kiloan Express" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_kategori_id" class="text-sm font-medium text-gray-600">Kategori <span class="text-red-500">*</span></label>
                            <select id="tambah_kategori_id" name="kategori_id" required
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($kategoriList as $kategori): ?>
                                <option value="<?php echo htmlspecialchars($kategori['kategori_id']); ?>">
                                    <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="tambah_harga" class="text-sm font-medium text-gray-600">Harga (per unit) <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="number" step="0.01" min="0" id="tambah_harga" name="harga"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: 10000" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_unit" class="text-sm font-medium text-gray-600">Satuan <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="box" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="text" id="tambah_unit" name="satuan"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: kg, pcs, m²" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_estimasi" class="text-sm font-medium text-gray-600">Estimasi Waktu (jam) <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="clock" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="number" min="0" id="tambah_estimasi" name="estimasi_waktu"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: 24" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_deskripsi" class="text-sm font-medium text-gray-600">Deskripsi</label>
                            <textarea id="tambah_deskripsi" name="deskripsi" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-3"
                                placeholder="Catatan tambahan layanan (opsional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                    <button type="submit" form="form-tambah-layanan"
                        class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                        data-modal-id="modal-tambah-layanan">
                        Simpan Layanan
                    </button>
                </div>
            </div>

            <div id="modal-hapus-layanan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
                <!-- Header -->
                <div class="flex-shrink-0 pt-4 pb-2 px-6">
                    <div class="w-full flex justify-center mb-2">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-xl font-bold text-gray-900">Hapus Layanan</h2>
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800 rounded-full focus:outline-none focus:ring-2 focus:ring-gray-300"
                            data-modal-id="modal-hapus-layanan" aria-label="Tutup">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 pb-2">
                    <div class="bg-red-50 border border-red-100 rounded-lg p-3 mb-3 flex items-center">
                        <svg data-feather="alert-triangle" class="w-5 h-5 text-red-500 mr-2"></svg>
                        <span class="text-sm text-red-700 font-semibold">Aksi ini tidak dapat dibatalkan!</span>
                    </div>
                    <p class="text-sm text-gray-600 mb-2">Anda yakin ingin menghapus layanan berikut?</p>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Layanan terpilih</p>
                    <p id="hapus-layanan-nama" class="text-base font-bold text-gray-900 mb-4">-</p>
                </div>
                <div class="px-6 pb-6">
                    <form id="form-hapus-layanan" method="POST" action="api/query-layanan.php">
                        <input type="hidden" name="action" value="delete_service">
                        <input type="hidden" name="layanan_id" id="hapus_layanan_id">
                        <div class="grid grid-cols-1 gap-3">
                            <button type="submit"
                                class="btn-hapus-confirm w-full bg-red-600 text-white font-bold py-3 px-4 rounded-lg shadow hover:bg-red-700 transition-colors">
                                Hapus
                            </button>
                            <button type="button"
                                class="btn-close-centered w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg shadow hover:bg-gray-50 transition-colors"
                                data-modal-id="modal-hapus-layanan">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script src="../../assets/js/icons.js"></script>
        <script src="../../assets/js/main.js"></script>
        <script src="../../assets/js/owner-layanan.js"></script>
        <script>
            // Enhanced search functionality - handle langsung di halaman
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('search-input');
                const clearButton = document.getElementById('clear-search');
                const serviceCards = document.querySelectorAll('.btn-buka-opsi');
                
                // Real-time search filtering
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase().trim();
                        filterServices(searchTerm);
                        
                        // Update URL without page reload
                        const url = new URL(window.location);
                        if (searchTerm) {
                            url.searchParams.set('q', searchTerm);
                        } else {
                            url.searchParams.delete('q');
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
                        filterServices('');
                        
                        // Update URL
                        const url = new URL(window.location);
                        url.searchParams.delete('q');
                        window.history.replaceState({}, '', url);
                        
                        // Hide clear button
                        this.style.display = 'none';
                    });
                }
                
                function filterServices(searchTerm) {
                    let visibleCount = 0;
                    
                    serviceCards.forEach(card => {
                        const nama = card.dataset.nama?.toLowerCase() || '';
                        const kategori = card.dataset.kategoriNama?.toLowerCase() || '';
                        const satuan = card.dataset.satuan?.toLowerCase() || '';
                        const deskripsi = card.dataset.deskripsi?.toLowerCase() || '';
                        
                        const isMatch = !searchTerm || 
                            nama.includes(searchTerm) ||
                            kategori.includes(searchTerm) ||
                            satuan.includes(searchTerm) ||
                            deskripsi.includes(searchTerm);
                        
                        if (isMatch) {
                            card.style.display = 'flex';
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
                    if (!searchTerm) return;
                    
                    const textElements = card.querySelectorAll('p');
                    textElements.forEach(element => {
                        const originalText = element.dataset.originalText || element.textContent;
                        element.dataset.originalText = originalText;
                        
                        if (searchTerm) {
                            const regex = new RegExp(`(${searchTerm})`, 'gi');
                            element.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
                        } else {
                            element.innerHTML = originalText;
                        }
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
                    
                    if (visibleCount === 0 && searchTerm) {
                        if (!emptyState) {
                            emptyState = document.createElement('div');
                            emptyState.id = 'search-empty-state';
                            emptyState.className = 'rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 mt-4';
                            emptyState.innerHTML = `
                                <svg data-feather="search" class="w-8 h-8 text-gray-400 mx-auto mb-2"></svg>
                                <p class="font-medium">Tidak ada layanan ditemukan</p>
                                <p>Coba gunakan kata kunci yang berbeda</p>
                            `;
                            mainContainer.insertBefore(emptyState, mainContainer.firstElementChild.nextSibling);
                            feather.replace();
                        }
                        emptyState.style.display = 'block';
                    } else if (emptyState) {
                        emptyState.style.display = 'none';
                    }
                }
                
                // Initial filter based on URL params
                const urlParams = new URLSearchParams(window.location.search);
                const initialSearch = urlParams.get('q') || '';
                if (initialSearch && searchInput) {
                    searchInput.value = initialSearch;
                    filterServices(initialSearch.toLowerCase());
                    toggleClearButton(initialSearch);
                }
            });
        </script>
    </body>

</html>