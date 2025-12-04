<?php
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/components/layout.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

// Handle form submissions dan flash messages
$flashSuccess = $_SESSION['template_flash_success'] ?? null;
$flashError = $_SESSION['template_flash_error'] ?? null;
unset($_SESSION['template_flash_success'], $_SESSION['template_flash_error']);

$flashMessage = null;
$flashType = null;
if ($flashSuccess) {
    $flashMessage = $flashSuccess;
    $flashType = 'success';
} elseif ($flashError) {
    $flashMessage = $flashError;
    $flashType = 'error';
}

// Initialize template data
$templateNota = null;
$templatePesan = [];

if ($bisnisId) {
    try {
        // Get template nota dengan handling jika belum ada
        $stmt = $conn->prepare("SELECT * FROM template_nota WHERE bisnis_id = ? LIMIT 1");
        $stmt->execute([$bisnisId]);
        $templateNota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get bisnis info including logo
        $stmt = $conn->prepare("SELECT logo FROM bisnis WHERE bisnis_id = ? LIMIT 1");
        $stmt->execute([$bisnisId]);
        $bisnisInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Siapkan logo dengan placeholder yang dipersonalisasi
        $namaLengkap = $ownerData['nama_lengkap'] ?? 'B';
        $inisial = strtoupper(substr($namaLengkap, 0, 1));
        // Ukuran placeholder disesuaikan untuk nota
        $logoPath = "https://placehold.co/100x40/3B82F6/FFFFFF?text=Logo"; 
        
        if ($bisnisInfo && !empty($bisnisInfo['logo'])) {
            $logoPath = '../../' . ltrim($bisnisInfo['logo'], '/'); // Timpa dengan logo asli jika ada
        }
        
        // Get template pesan dengan handling untuk semua jenis
        $stmt = $conn->prepare("SELECT jenis, isi_pesan FROM template_pesan WHERE bisnis_id = ? AND is_active = 1 ORDER BY jenis");
        $stmt->execute([$bisnisId]);
        $pesanData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert to associative array by jenis
        $pesanByJenis = [];
        foreach ($pesanData as $pesan) {
            $pesanByJenis[$pesan['jenis']] = $pesan['isi_pesan'];
        }
        $templatePesan = $pesanByJenis;
        
    } catch (PDOException $e) {
        $flashError = "Error loading template: " . $e->getMessage();
        $flashType = 'error';
    }
}

// Set default values jika template belum ada
$isEmptyTemplate = false;
if (!$templateNota || !is_array($templateNota)) {
    $isEmptyTemplate = true;
    $templateNota = [
        'template_id' => null,
        'header' => $bisnisNama . "\nAlamat Bisnis Anda\nNo. HP: 0812-3456-7890",
        'footer' => "Terima kasih!\nBarang yang tidak diambil\nlebih dari 30 hari\nbukan tanggung jawab kami.",
        'format_nota' => 'default'
    ];
} else if (empty($templateNota['header']) && empty($templateNota['footer'])) {
    $isEmptyTemplate = true;
}

// Set default template pesan untuk semua jenis
$defaultPesan = [
    'masuk' => 'Hai [NAMA_PELANGGAN], pesanan Anda di [NAMA_OUTLET] dengan ID [ID_NOTA] telah kami terima. Total biaya: [TOTAL_HARGA]. Estimasi selesai: [ESTIMASI_SELESAI].',
    'proses' => 'Hai [NAMA_PELANGGAN], pesanan Anda [ID_NOTA] sedang kami proses cuci dan setrika.',
    'selesai' => 'Hai [NAMA_PELANGGAN], pesanan Anda [ID_NOTA] sudah selesai dan siap diambil. Total tagihan: [TOTAL_HARGA].',
    'pembayaran' => 'Hai [NAMA_PELANGGAN],   terima kasih telah melunasi pembayaran untuk pesanan [ID_NOTA] sebesar [TOTAL_HARGA].'
];

// Merge dengan template yang ada
foreach ($defaultPesan as $jenis => $isi) {
    if (!isset($templatePesan[$jenis]) || empty($templatePesan[$jenis])) {
        $templatePesan[$jenis] = $isi;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Nota & Pesan - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    
    <!-- Flash Messages -->
    <?php if ($flashMessage): ?>
    <div id="flash-message" class="fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 <?php echo $flashType === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
        <?php echo htmlspecialchars($flashMessage); ?>
    </div>
    <?php endif; ?>
    
    <!-- ====================================================== -->
    <!-- (Komentar) 1. KONTEN UTAMA HALAMAN                     -->
    <!-- ====================================================== -->
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <!-- (Komentar) Header Biru (Sticky) -->
        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Template Nota & Pesan</h1>
            <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>
        </header>

        <!-- (Komentar) Navigasi Tab (Sticky) - Pola dari laporan.html -->
        <nav class="sticky top-[108px] z-10 bg-gray-100 pt-4 px-6 flex-shrink-0 border-b border-gray-200">
            <div class="flex -mb-px">
                <button class="tab-button tab-button-active flex-1 text-center py-3 border-b-2 font-semibold text-blue-700 bg-blue-100 border-blue-600" data-target="#tab-nota">
                    Nota Cetak
                </button>
                <button class="tab-button flex-1 text-center py-3 border-b-2" data-target="#tab-pesan">
                    Pesan Otomatis
                </button>
            </div>
        </nav>

        <!-- (Komentar) Konten Tab (Scrollable Area) -->

        <div class="flex-grow overflow-y-auto pb-24 no-scrollbar">

            <!-- ====================================================== -->
            <!-- (Komentar) PANEL KONTEN TAB 1: NOTA CETAK              -->
            <!-- ====================================================== -->
            <div id="tab-nota" class="p-6 space-y-6">
                
                <!-- (Komentar) Kartu Pengaturan Nota -->
                <section class="bg-white rounded-lg shadow p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-800">Pengaturan Nota Cetak</h2>
                        <button id="btn-edit-nota" class="text-sm text-blue-600 font-medium flex items-center">
                            <svg data-feather="edit-2" class="w-4 h-4 mr-1"></svg>
                            Edit
                        </button>
                    </div>

                    <!-- (Komentar) Preview Nota -->
                    <div class="nota-preview p-4 rounded-lg text-sm text-gray-800" style="background-color: #f9fafb; border: 1px solid #e5e7eb;">
                         <div class="flex justify-center mb-3">
                            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo Usaha" class="h-10" id="logo-preview">
                        </div>
                        <pre id="header-preview" class="text-center text-xs mb-3"><?php echo htmlspecialchars($templateNota['header'] ?? ''); ?></pre>
                        <div class="text-xs ">
                            <pre>
        --------------------------------
        Nota: #0913123
        Pelanggan: Kosimudin
        Tgl Masuk: 01/11/25 10:00
        Tgl Selesai: 02/11/25 10:00
                            </pre>
                        </div>
                        <pre class="text-xs mb-3"> 
        --------------------------------
        Kiloan Reguler (3kg)    21.000
        Kemeja Satuan (1pcs)    10.000
        --------------------------------
        Subtotal                31.000
        Total                   31.000
        --------------------------------</pre>
                        <pre id="footer-preview" class="text-center text-xs"><?php echo htmlspecialchars($templateNota['footer'] ?? ''); ?>
</pre>
                    </div>
                </section>
            </div>

            <!-- ====================================================== -->
            <!-- (Komentar) PANEL KONTEN TAB 2: PESAN OTOMATIS          -->
            <!-- (Komentar) Awalnya 'hidden'                            -->
            <!-- ====================================================== -->
            <div id="tab-pesan" class="p-6 space-y-6 hidden">
                
                <section>
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">Template Pesan (WhatsApp/SMS)</h2>
                    <p class="text-sm text-gray-600 mb-4 -mt-2">
                        Atur pesan otomatis untuk dikirim ke pelanggan. Gunakan variabel dinamis untuk menyisipkan info transaksi.
                    </p>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden divide-y divide-gray-200">
                        
                        <button class="btn-edit-pesan w-full flex items-center justify-between p-4 text-left hover:bg-gray-50" data-jenis="masuk" data-title="Pesanan Diterima">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="check-circle" class="w-5 h-5 text-gray-700"></svg></div>
                                <div>
                                    <p class="font-semibold text-gray-800">Pesanan Diterima</p>
                                    <p class="text-sm text-gray-500">Saat transaksi baru dibuat.</p>
                                </div>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        
                        <button class="btn-edit-pesan w-full flex items-center justify-between p-4 text-left hover:bg-gray-50" data-jenis="proses" data-title="Pesanan Diproses">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="refresh-cw" class="w-5 h-5 text-gray-700"></svg></div>
                                <div>
                                    <p class="font-semibold text-gray-800">Pesanan Diproses</p>
                                    <p class="text-sm text-gray-500">Saat status diubah ke 'Diproses'.</p>
                                </div>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>

                        <button class="btn-edit-pesan w-full flex items-center justify-between p-4 text-left hover:bg-gray-50" data-jenis="selesai" data-title="Pesanan Siap Diambil">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="package" class="w-5 h-5 text-gray-700"></svg></div>
                                <div>
                                    <p class="font-semibold text-gray-800">Pesanan Siap Diambil</p>
                                    <p class="text-sm text-gray-500">Saat status diubah ke 'Siap Diambil'.</p>
                                </div>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        
                        <button class="btn-edit-pesan w-full flex items-center justify-between p-4 text-left hover:bg-gray-50" data-jenis="pembayaran" data-title="Pembayaran Selesai">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="check-square" class="w-5 h-5 text-gray-700"></svg></div>
                                <div>
                                    <p class="font-semibold text-gray-800">Pembayaran Selesai</p>
                                    <p class="text-sm text-gray-500">Saat pembayaran dilunasi.</p>
                                </div>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>

                    </div>
                </section>
            </div>

        </div> <!-- (Komentar) Penutup Area Scroll -->

    </div>

    <!-- ====================================================== -->
    <!-- (Komentar) 2. NAVIGASI BAWAH (BOTTOM NAV) (Sticky)     -->
    <!-- ====================================================== -->
    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
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
        <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Akun</span>
        </a>
    </nav>

    <!-- ====================================================== -->
    <!-- (Komentar) 3. KONTAINER MODAL (POPUP)                  -->
    <!-- ====================================================== -->
    <div id="modal-container" class="hidden z-30">

        <!-- (Komentar) Backdrop Gelap (z-40) -->
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>

        <!-- (Komentar) MODAL 1: Edit Template Nota (Slide-up, z-50) -->
        <div id="modal-edit-nota"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Template Nota</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-edit-nota">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <!-- (Komentar) Form -->
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-nota" action="api/template.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="action" value="update_nota">
                    <div>
                        <label for="logo_upload" class="text-sm font-medium text-gray-600">Logo Usaha</label>
                        <div class="mt-1 flex items-center space-x-4">
                            <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="Logo" class="h-10 bg-gray-100 rounded" id="logo-form-preview">
                            <input type="file" id="logo_upload" name="logo_upload" accept="image/*" class="hidden">
                            <label for="logo_upload" class="cursor-pointer bg-white border border-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-lg hover:bg-gray-50 text-sm">
                                Ganti Logo
                            </label>
                        </div>
                    </div>
                    <div>
                        <label for="nota_header" class="text-sm font-medium text-gray-600">Header Nota</label>
                        <textarea name="header" id="nota_header" rows="4"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Nama Usaha&#10;Alamat Usaha&#10;No. HP"><?php echo htmlspecialchars($templateNota['header'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label for="nota_footer" class="text-sm font-medium text-gray-600">Footer Nota</label>
                        <textarea name="footer" id="nota_footer" rows="5"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Ucapan terima kasih, syarat & ketentuan, dll."><?php echo htmlspecialchars($templateNota['footer'] ?? ''); ?></textarea>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-edit-nota"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-edit-nota">
                    Simpan Perubahan
                </button>
            </div>
        </div>

        <!-- (Komentar) MODAL 2: Edit Template Pesan (REUSABLE) (Slide-up, z-50) -->
        <div id="modal-edit-pesan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900" id="modal-pesan-title">Edit Template Pesan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-edit-pesan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <!-- (Komentar) Form -->
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-pesan" action="api/template.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_pesan">
                    <input type="hidden" name="jenis" id="pesan_jenis" value="">
                    <div>
                        <label for="pesan_template" class="text-sm font-medium text-gray-600">Isi Pesan</label>
                        <textarea name="isi_pesan" id="pesan_template" rows="8"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Tulis template pesan Anda di sini..."></textarea>
                    </div>
                    
                    <!-- (Komentar) Fitur Kunci: Variabel Dinamis -->
                    <div>
                        <label class="text-sm font-medium text-gray-600">Variabel Dinamis</label>
                        <p class="text-xs text-gray-500 mb-2">Klik untuk menyalin dan tempel ke pesan Anda.</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[NAMA_PELANGGAN]">[NAMA_PELANGGAN]</button>
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[ID_NOTA]">[ID_NOTA]</button>
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[TOTAL_HARGA]">[TOTAL_HARGA]</button>
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[ESTIMASI_SELESAI]">[ESTIMASI_SELESAI]</button>
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[NAMA_OUTLET]">[NAMA_OUTLET]</button>
                            <button type="button" class="btn-variable bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-1 rounded-full hover:bg-gray-200" data-variable="[RINCIAN_ITEMS]">[RINCIAN_ITEMS]</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-edit-pesan"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-edit-pesan">
                    Simpan Perubahan
                </button>
            </div>
        </div>
        
        <!-- (Komentar) MODAL 3: Notifikasi Teks Disalin -->
        <div id="toast-copied" class="hidden fixed bottom-24 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-sm font-medium py-2 px-4 rounded-full z-50 transition-all duration-300 opacity-0">
            Variabel disalin!
        </div>

    </div>

    <!-- Hidden data for JavaScript -->
    <script>
        window.templateData = {
            pesan: <?php echo json_encode($templatePesan); ?>
        };
    </script>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-template.js"></script>
    
    <script>
        // Flash message animations
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessage = document.getElementById('flash-message');
            
            if (flashMessage) {
                setTimeout(() => flashMessage.classList.remove('translate-x-full'), 100);
                const hideDelay = flashMessage.classList.contains('bg-green-500') ? 3000 : 5000;
                setTimeout(() => flashMessage.classList.add('translate-x-full'), hideDelay);
            }
        });
    </script>
</body>

</html>