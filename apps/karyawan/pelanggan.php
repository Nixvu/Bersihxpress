
<?php
    // require_once __DIR__ . '/middleware/auth_karyawan.php';
    require_once __DIR__ . '/components/layout.php';
    require_once __DIR__ . '/../../config/functions.php';
    require_once __DIR__ . '/models/pelanggan.php';

    $karyawanData = $_SESSION['karyawan_data'] ?? [];
    $bisnisId = $karyawanData['bisnis_id'] ?? null;
    $bisnisNama = $karyawanData['nama_bisnis'] ?? 'Bisnis Anda';
    // Ambil flash messages 
    $flashSuccess = getFlash('pelanggan_flash_success');
    $flashError = getFlash('pelanggan_flash_error');

    function pelangganFilterUrl(string $filter, string $searchTerm): string {
        $params = [];
        if ($filter !== 'semua') {
            $params['filter'] = $filter;
        }
        if ($searchTerm !== '') {
            $params['q'] = $searchTerm;
        }
        $query = http_build_query($params);
        return $query ? 'pelanggan.php?' . $query : 'pelanggan.php';
    }

    // Handler tambah pelanggan langsung di file ini
    if ($bisnisId && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'create_customer') {
                $result = pelanggan_create($bisnisId, $_POST);
                if ($result['success']) {
                    $_SESSION['pelanggan_flash_success'] = $result['message'];
                } else {
                    throw new InvalidArgumentException($result['message']);
                }
            } else {
                throw new InvalidArgumentException('Aksi tidak dikenal.');
            }
        } catch (InvalidArgumentException $e) {
            $_SESSION['pelanggan_flash_error'] = $e->getMessage();
        } catch (PDOException $e) {
            logError('Aksi pelanggan gagal', [
                'error' => $e->getMessage(),
                'action' => $action,
                'bisnis_id' => $bisnisId,
            ]);
            $_SESSION['pelanggan_flash_error'] = 'Terjadi kesalahan saat memproses data pelanggan.';
        }
        header('Location: pelanggan.php');
        exit;
    }

    $searchTerm = trim($_GET['q'] ?? '');
    $selectedFilter = $_GET['filter'] ?? 'semua';
    $customers = [];
    if ($bisnisId) {
        $customers = pelanggan_list($bisnisId, $searchTerm, $selectedFilter);
    }
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kelola Pelanggan - BersihXpress</title>
        
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
                <h1 class="text-2xl font-bold text-white">Kelola Pelanggan</h1>
                <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>

                <!--  Search & Tombol Tambah -->
                <div class="flex items-center space-x-3 mt-4">
                    <div class="relative flex-grow" id="search-container">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                        </span>
                        <input type="text" 
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                            placeholder="Cari nama, telepon, email, atau alamat..."
                            id="search-input"
                            value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <?php if ($searchTerm !== ''): ?>
                        <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <svg data-feather="x" class="h-5 w-5 text-gray-400 hover:text-gray-600"></svg>
                        </button>
                        <?php endif; ?>
                    </div>
                    <button id="btn-tambah-pelanggan" class="bg-white p-3 rounded-lg shadow flex-shrink-0 hover:bg-gray-50 transition-colors">
                        <svg data-feather="user-plus" class="h-6 w-6 text-blue-600"></svg>
                    </button>
                </div>
            </header>

            <!-- Filter Chips (Sticky) -->
            <nav class="sticky top-[160px] z-10 bg-gray-100 pt-4 pb-3 px-6 flex-shrink-0">
                <div class="flex space-x-3 overflow-x-auto no-scrollbar">
                    <?php
                    $selectedFilter = $_GET['filter'] ?? 'semua';
                    $baseClasses = 'px-5 py-2 rounded-full text-sm whitespace-nowrap transition-colors';
                    $activeClasses = 'filter-chip-active font-semibold';
                    $inactiveClasses = 'bg-white text-gray-700 hover:bg-blue-50';
                    ?>
                    <a href="<?php echo htmlspecialchars(pelangganFilterUrl('semua', $searchTerm)); ?>"
                        class="<?php echo $baseClasses . ' ' . ($selectedFilter === 'semua' ? $activeClasses : $inactiveClasses); ?>">
                        Semua
                    </a>
                    <a href="<?php echo htmlspecialchars(pelangganFilterUrl('terbaru', $searchTerm)); ?>"
                        class="<?php echo $baseClasses . ' ' . ($selectedFilter === 'terbaru' ? $activeClasses : $inactiveClasses); ?>">
                        Terbaru
                    </a>
                    <a href="<?php echo htmlspecialchars(pelangganFilterUrl('sering', $searchTerm)); ?>"
                        class="<?php echo $baseClasses . ' ' . ($selectedFilter === 'sering' ? $activeClasses : $inactiveClasses); ?>">
                        Sering
                    </a>
                    <a href="<?php echo htmlspecialchars(pelangganFilterUrl('jarang', $searchTerm)); ?>"
                        class="<?php echo $baseClasses . ' ' . ($selectedFilter === 'jarang' ? $activeClasses : $inactiveClasses); ?>">
                        Jarang
                    </a>
                </div>
            </nav>

            <!-- Daftar Pelanggan (Scrollable) -->
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

                <?php if (empty($customers)): ?>
                <div
                    class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
                    Belum ada pelanggan. Tambahkan pelanggan baru untuk bisnis Anda.
                </div>
                <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <button
                    class="btn-detail-pelanggan w-full bg-white rounded-lg shadow p-4 text-left flex items-center justify-between"
                    data-id="<?php echo htmlspecialchars($customer['pelanggan_id']); ?>"
                    data-nama="<?php echo htmlspecialchars($customer['nama']); ?>"
                    data-telepon="<?php echo htmlspecialchars($customer['no_telepon'] ?? ''); ?>"
                    data-email="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                    data-alamat="<?php echo htmlspecialchars($customer['alamat'] ?? ''); ?>"
                    data-catatan="<?php echo htmlspecialchars($customer['catatan'] ?? ''); ?>"
                    data-total-transaksi="<?php echo (int)$customer['total_transaksi']; ?>"
                    data-total-nilai="<?php echo htmlspecialchars((string) (float) $customer['total_nilai']); ?>"
                    data-created="<?php echo htmlspecialchars($customer['created_display']); ?>"
                    data-kategori-filter="<?php echo htmlspecialchars($customer['kategori_filter']); ?>"
                    data-is-recent="<?php echo $customer['is_recent'] ? 'true' : 'false'; ?>">
                    <div class="flex items-center pr-4">
                        <div class="p-3 bg-gray-100 rounded-full mr-4"><svg data-feather="user"
                                class="w-5 h-5 text-gray-600"></svg></div>
                        <div class="text-left">
                            <p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($customer['nama']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo !empty($customer['no_telepon']) ? htmlspecialchars($customer['no_telepon']) : 'Tidak ada telepon'; ?>
                                <?php if (!empty($customer['email'])): ?>
                                &bull;
                                <?php echo htmlspecialchars($customer['email']); ?>
                                <?php endif; ?>
                            </p>
                            <p class="text-sm font-semibold text-blue-600">
                                <?php echo $customer['total_transaksi']; ?> transaksi &bull; <?php echo $customer['total_nilai_display']; ?>
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
        <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-4 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
            <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
                <svg data-feather="home" class="w-6 h-6"></svg>
                <span class="text-xs mt-1">Beranda</span>
            </a>
            <a href="transaksi.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
                <svg data-feather="file-text" class="w-6 h-6"></svg>
                <span class="text-xs mt-1">Transaksi</span>
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
            
            <div id="modal-detail-pelanggan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[80vh]">
                <div class="flex-shrink-0">
                    <div class="w-full py-3">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center px-6 pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Detail Pelanggan</h2>
                        <button type="button" class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-detail-pelanggan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-grow overflow-y-auto px-6 pb-6 no-scrollbar">
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Nama Pelanggan</p>
                        <p id="detail-nama" class="text-lg font-semibold text-gray-900">-</p>
                    </div>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Nomor Telepon</dt>
                            <dd id="detail-telepon" class="text-sm text-gray-900 mt-1">-</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Email</dt>
                            <dd id="detail-email" class="text-sm text-gray-900 mt-1">-</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Alamat</dt>
                            <dd id="detail-alamat" class="text-sm text-gray-900 mt-1">-</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500">Catatan</dt>
                            <dd id="detail-catatan" class="text-sm text-gray-900 mt-1">-</dd>
                        </div>
                    </dl>
                    <div class="mt-6 border-t border-gray-200 pt-4 grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-xs uppercase tracking-wide text-blue-600">Total Transaksi</p>
                            <p id="detail-total-transaksi" class="text-lg font-semibold text-blue-700 mt-1">0</p>
                        </div>
                        <div class="bg-emerald-50 rounded-lg p-3">
                            <p class="text-xs uppercase tracking-wide text-emerald-600">Total Nilai</p>
                            <p id="detail-total-nilai" class="text-lg font-semibold text-emerald-700 mt-1">Rp 0</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3 col-span-2">
                            <p class="text-xs uppercase tracking-wide text-gray-500">Bergabung Sejak</p>
                            <p id="detail-created" class="text-sm font-medium text-gray-800 mt-1">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="modal-tambah-pelanggan"
                class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
                <div class="flex-shrink-0">
                    <div class="w-full py-3">
                        <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                    </div>
                    <div class="flex justify-between items-center px-6 pb-4">
                        <h2 class="text-xl font-bold text-gray-900">Tambah Pelanggan</h2>
                        <button type="button" class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-tambah-pelanggan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                    <form id="form-tambah-pelanggan" class="space-y-4" method="POST" action="pelanggan.php">
                        <input type="hidden" name="action" value="create_customer">

                        <div>
                            <label for="tambah_nama" class="text-sm font-medium text-gray-600">Nama <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="text" id="tambah_nama" name="nama"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Nama pelanggan" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_telepon" class="text-sm font-medium text-gray-600">Nomor Telepon <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="tel" id="tambah_telepon" name="no_telepon"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="08xxxx" pattern="[0-9+\s-]{6,20}" required>
                            </div>
                        </div>

                        <div>
                            <label for="tambah_email" class="text-sm font-medium text-gray-600">Email <span class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                    <input type="email" id="tambah_email2" name="email" required
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="nama@email.com">
                            </div>
                        </div>

                        <div>
                            <label for="tambah_alamat" class="text-sm font-medium text-gray-600">Alamat</label>
                            <textarea id="tambah_alamat" name="alamat" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                                placeholder="Detail alamat pelanggan"></textarea>
                        </div>

                        <div>
                            <label for="tambah_catatan" class="text-sm font-medium text-gray-600">Catatan</label>
                            <textarea id="tambah_catatan" name="catatan" rows="3"
                                class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                                placeholder="Catatan khusus (opsional)"></textarea>
                        </div>
                    </form>
                </div>
                <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                    <button type="submit" form="form-tambah-pelanggan"
                        class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                        data-modal-id="modal-tambah-pelanggan">
                        Simpan Pelanggan
                    </button>
                </div>
            </div>
        </div>

        <script src="../../assets/js/icons.js"></script>
        <script src="../../assets/js/main.js"></script>
        <script src="../../assets/js/karyawan-pelanggan.js"></script>
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