<?php
// Fungsi generate UUID v4 sederhana
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
           mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

require_once 'middleware/auth_karyawan.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/functions.php';
require_once 'api/query-dashboard.php';

$logoSrc = null;
$namaBisnis = '';
$karyawanData = null;

if (isset($_SESSION['karyawan_data'])) {
    $karyawanId = $_SESSION['karyawan_data']['karyawan_id'];
    $stmt = $conn->prepare('
        SELECT u.nama_lengkap, b.nama_bisnis, b.logo
        FROM karyawan k
        JOIN users u ON k.user_id = u.user_id
        JOIN bisnis b ON k.bisnis_id = b.bisnis_id
        WHERE k.karyawan_id = ?
    ');
    $stmt->execute([$karyawanId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $karyawanData = ['nama_lengkap' => $result['nama_lengkap']];
        $namaBisnis = $result['nama_bisnis'];
        if (!empty($result['logo'])) {
            if (strpos($result['logo'], 'assets/logo/') === 0) {
                $logoSrc = '../../' . ltrim($result['logo'], '/');
            } else {
                $logoSrc = '../../assets/logo/' . ltrim($result['logo'], '/');
            }
        }
    }
}

$estimasiKomisi = 0;
$transaksiProses = 0;
$transaksiSelesai = 0;
$kilogram = 0;
$satuan = 0;
$meteran = 0;
$transaksiDikerjakan = [];
$transaksiSelesaiHariIni = [];
// Ambil jam_masuk dan jam_keluar dari tabel absensi untuk karyawan hari ini
$jam_masuk = null;
$jam_keluar = null;
if (isset($karyawanId)) {
    $stmtAbs = $conn->prepare('SELECT jam_masuk, jam_keluar FROM absensi WHERE karyawan_id = ? AND tanggal = CURDATE() LIMIT 1');
    $stmtAbs->execute([$karyawanId]);
    $absRow = $stmtAbs->fetch(PDO::FETCH_ASSOC);
    if ($absRow) {
        $jam_masuk = $absRow['jam_masuk'] ?: null;
        $jam_keluar = $absRow['jam_keluar'] ?: null;
    }
}


if (isset($karyawanId)) {
    // Estimasi Komisi: total komisi dari transaksi selesai (misal 10% dari total_harga, bisa disesuaikan)
    $stmtKomisi = $conn->prepare('SELECT SUM(total_harga) as total FROM transaksi WHERE karyawan_id = ? AND status = "selesai"');
    $stmtKomisi->execute([$karyawanId]);
    $rowKomisi = $stmtKomisi->fetch(PDO::FETCH_ASSOC);
    $estimasiKomisi = $rowKomisi && $rowKomisi['total'] ? $rowKomisi['total'] * 0.1 : 0;

    // Proses: jumlah transaksi status proses
    $stmtProses = $conn->prepare('SELECT COUNT(*) as total FROM transaksi WHERE karyawan_id = ? AND status = "proses"');
    $stmtProses->execute([$karyawanId]);
    $rowProses = $stmtProses->fetch(PDO::FETCH_ASSOC);
    $transaksiProses = $rowProses ? $rowProses['total'] : 0;

    // Selesai: jumlah transaksi status selesai
    $stmtSelesai = $conn->prepare('SELECT COUNT(*) as total FROM transaksi WHERE karyawan_id = ? AND status = "selesai"');
    $stmtSelesai->execute([$karyawanId]);
    $rowSelesai = $stmtSelesai->fetch(PDO::FETCH_ASSOC);
    $transaksiSelesai = $rowSelesai ? $rowSelesai['total'] : 0;

    // Kilogram, Satuan, Meteran: dari detail_transaksi, group by satuan
    $stmtDetail = $conn->prepare('
        SELECT l.satuan, SUM(dt.jumlah) as total
        FROM transaksi t
        JOIN detail_transaksi dt ON t.transaksi_id = dt.transaksi_id
        JOIN layanan l ON dt.layanan_id = l.layanan_id
        WHERE t.karyawan_id = ?
        GROUP BY l.satuan
    ');
    $stmtDetail->execute([$karyawanId]);
    while ($row = $stmtDetail->fetch(PDO::FETCH_ASSOC)) {
        if (strtolower($row['satuan']) == 'kg') $kilogram = $row['total'];
        elseif (strtolower($row['satuan']) == 'pcs') $satuan = $row['total'];
        elseif (strtolower($row['satuan']) == 'm' || strtolower($row['satuan']) == 'm2' || strtolower($row['satuan']) == 'meteran') $meteran = $row['total'];
    }

    // Transaksi masih dikerjakan (status proses/antrian)
    $stmtDikerjakan = $conn->prepare('
        SELECT t.no_nota, p.nama as nama_pelanggan, t.tanggal_masuk, t.status
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.karyawan_id = ? AND (t.status = "proses" OR t.status = "pending")
        ORDER BY t.tanggal_masuk DESC
        LIMIT 5
    ');
    $stmtDikerjakan->execute([$karyawanId]);
    $transaksiDikerjakan = $stmtDikerjakan->fetchAll(PDO::FETCH_ASSOC);

    // Transaksi selesai hari ini
    $stmtSelesai = $conn->prepare('
        SELECT t.no_nota, p.nama as nama_pelanggan, t.tanggal_selesai, t.status
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.karyawan_id = ? AND t.status = "selesai" AND DATE(t.tanggal_selesai) = CURDATE()
        ORDER BY t.tanggal_selesai DESC
        LIMIT 5
    ');
    $stmtSelesai->execute([$karyawanId]);
    $transaksiSelesaiHariIni = $stmtSelesai->fetchAll(PDO::FETCH_ASSOC);
}
$daftar_pelanggan = [];
$daftar_layanan = [];
// Fix: $bisnis_id should be set from karyawan context
if (isset($karyawanId)) {
    $stmtBisnis = $conn->prepare('SELECT bisnis_id FROM karyawan WHERE karyawan_id = ?');
    $stmtBisnis->execute([$karyawanId]);
    $rowBisnis = $stmtBisnis->fetch(PDO::FETCH_ASSOC);
    $bisnis_id = $rowBisnis ? $rowBisnis['bisnis_id'] : null;
    if ($bisnis_id) {
        $stmt = $conn->prepare("SELECT pelanggan_id, nama, no_telepon FROM pelanggan WHERE bisnis_id = ? ORDER BY nama ASC");
        $stmt->execute([$bisnis_id]);
        $daftar_pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Ambil layanan dari bisnis
        $stmtLayanan = $conn->prepare("SELECT layanan_id, nama_layanan, harga, satuan FROM layanan WHERE kategori_id IN (SELECT kategori_id FROM kategori_layanan WHERE bisnis_id = ?) ORDER BY nama_layanan ASC");
        $stmtLayanan->execute([$bisnis_id]);
        $daftar_layanan = $stmtLayanan->fetchAll(PDO::FETCH_ASSOC);
    }
}

$flashAbsensiSuccess = '';
$flashAbsensiError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && isset($_POST['waktu'])) {
    // Ambil karyawan_id dari session
    $karyawan_id = $_SESSION['karyawan_data']['karyawan_id'] ?? '';
    $aksi = $_POST['aksi'];
    $waktu = $_POST['waktu'];
    // Flash message feedback
    if (!$karyawan_id) {
        setFlash('absensi_flash_error', 'Session karyawan tidak ditemukan!');
        header('Location: dashboard.php');
        exit;
    }
    if (!$aksi || !$waktu) {
        setFlash('absensi_flash_error', 'Data absensi tidak lengkap!');
        header('Location: dashboard.php');
        exit;
    }
    if ($aksi !== 'masuk' && $aksi !== 'pulang') {
        setFlash('absensi_flash_error', 'Aksi absensi tidak valid!');
        header('Location: dashboard.php');
        exit;
    }
    $tgl = substr($waktu, 0, 10);
    $sql_cek = "SELECT * FROM absensi WHERE karyawan_id=? AND tanggal=?";
    $stmt_cek = $conn->prepare($sql_cek);
    $stmt_cek->execute([$karyawan_id, $tgl]);
    $row = $stmt_cek->fetch(PDO::FETCH_ASSOC);
    if ($aksi === 'masuk') {
        if ($row && $row['jam_masuk']) {
            setFlash('absensi_flash_error', 'Sudah absen masuk hari ini!');
            header('Location: dashboard.php');
            exit;
        }
        if ($row) {
            $sql = "UPDATE absensi SET jam_masuk=? WHERE karyawan_id=? AND tanggal=?";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$waktu, $karyawan_id, $tgl]);
        } else {
            // Validasi karyawan_id sebelum insert
            if (!$karyawan_id) {
                setFlash('absensi_flash_error', 'Karyawan ID tidak valid!');
                header('Location: dashboard.php');
                exit;
            }
            $absensi_id = generate_uuid();
            $sql = "INSERT INTO absensi (absensi_id, karyawan_id, tanggal, jam_masuk, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute([$absensi_id, $karyawan_id, $tgl, $waktu, 'hadir']);
        }
        if (isset($result) && $result) {
            setFlash('absensi_flash_success', 'Absensi masuk berhasil!');
        } else {
            setFlash('absensi_flash_error', 'Absensi masuk gagal: ' . ($stmt ? $stmt->errorInfo()[2] : ''));
        }
        header('Location: dashboard.php');
        exit;
    } else if ($aksi === 'pulang') {
        if ($row && $row['jam_keluar']) {
            setFlash('absensi_flash_error', 'Sudah absen pulang hari ini!');
            header('Location: dashboard.php');
            exit;
        }
        if (!$row || !$row['jam_masuk']) {
            setFlash('absensi_flash_error', 'Belum absen masuk hari ini!');
            header('Location: dashboard.php');
            exit;
        }
        $sql = "UPDATE absensi SET jam_keluar=? WHERE karyawan_id=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([$waktu, $karyawan_id, $tgl]);
        if (isset($result) && $result) {
            setFlash('absensi_flash_success', 'Absensi pulang berhasil!');
        } else {
            setFlash('absensi_flash_error', 'Absensi pulang gagal: ' . ($stmt ? $stmt->errorInfo()[2] : ''));
        }
        header('Location: dashboard.php');
        exit;
    }
// Ambil flash message absensi
$flashAbsensiSuccess = getFlash('absensi_flash_success');
$flashAbsensiError = getFlash('absensi_flash_error');
}


?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - BersihXpress</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
        <?php if ($flashAbsensiSuccess): ?>
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 mx-6 mt-6">
            <?php echo htmlspecialchars($flashAbsensiSuccess); ?>
        </div>
        <?php endif; ?>
        <?php if ($flashAbsensiError): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 mx-6 mt-6">
            <?php echo htmlspecialchars($flashAbsensiError); ?>
        </div>
        <?php endif; ?>
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">
        <div class="flex-shrink-0">
            <header class="relative bg-blue-600 h-56 w-full rounded-b-[40px] p-6 text-white z-10">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">Hi, <?php echo htmlspecialchars($karyawanData['nama_lengkap'] ?? ''); ?></h1>
                        <p class="text-sm opacity-90"><?php echo htmlspecialchars($namaBisnis ?? ''); ?></p>
                    </div>
                    <img src="<?php echo $logoSrc ?? 'https://placehold.co/64x64/EFEFEF/333333?text=F'; ?>" alt="Logo Bisnis" class="w-12 h-12 rounded-full border-2 border-white/50">
                </div>
            </header>
            <main class="relative z-20 -mt-24 px-6">
                <section class="bg-white rounded-lg shadow-md p-5">
                    <!-- (Komentar) Statistik Karyawan (Sama) -->
                    <?php
                    // Statistik bulan ini
                    $estimasiKomisiBulanIni = 0;
                    $transaksiProsesBulanIni = 0;
                    $transaksiSelesaiBulanIni = 0;
                    $kilogramBulanIni = 0;
                    $satuanBulanIni = 0;
                    $meteranBulanIni = 0;
                    if (isset($karyawanId)) {
                        // Komisi bulan ini
                        $stmtKomisiBulan = $conn->prepare('SELECT SUM(total_harga) as total FROM transaksi WHERE karyawan_id = ? AND status = "selesai" AND MONTH(tanggal_selesai) = MONTH(CURDATE()) AND YEAR(tanggal_selesai) = YEAR(CURDATE())');
                        $stmtKomisiBulan->execute([$karyawanId]);
                        $rowKomisiBulan = $stmtKomisiBulan->fetch(PDO::FETCH_ASSOC);
                        $estimasiKomisiBulanIni = $rowKomisiBulan && $rowKomisiBulan['total'] ? $rowKomisiBulan['total'] * 0.1 : 0;

                        // Diproses bulan ini
                        $stmtProsesBulan = $conn->prepare('SELECT COUNT(*) as total FROM transaksi WHERE karyawan_id = ? AND status = "proses" AND MONTH(tanggal_masuk) = MONTH(CURDATE()) AND YEAR(tanggal_masuk) = YEAR(CURDATE())');
                        $stmtProsesBulan->execute([$karyawanId]);
                        $transaksiProsesBulanIni = $stmtProsesBulan->fetchColumn();

                        // Selesai bulan ini
                        $stmtSelesaiBulan = $conn->prepare('SELECT COUNT(*) as total FROM transaksi WHERE karyawan_id = ? AND status = "selesai" AND MONTH(tanggal_selesai) = MONTH(CURDATE()) AND YEAR(tanggal_selesai) = YEAR(CURDATE())');
                        $stmtSelesaiBulan->execute([$karyawanId]);
                        $transaksiSelesaiBulanIni = $stmtSelesaiBulan->fetchColumn();

                        // Kilogram, Satuan, Meteran bulan ini
                        $stmtDetailBulan = $conn->prepare('
                            SELECT l.satuan, SUM(dt.jumlah) as total
                            FROM transaksi t
                            JOIN detail_transaksi dt ON t.transaksi_id = dt.transaksi_id
                            JOIN layanan l ON dt.layanan_id = l.layanan_id
                            WHERE t.karyawan_id = ?
                              AND MONTH(t.tanggal_selesai) = MONTH(CURDATE())
                              AND YEAR(t.tanggal_selesai) = YEAR(CURDATE())
                            GROUP BY l.satuan
                        ');
                        $stmtDetailBulan->execute([$karyawanId]);
                        while ($row = $stmtDetailBulan->fetch(PDO::FETCH_ASSOC)) {
                            if (strtolower($row['satuan']) == 'kg' || strtolower($row['satuan']) == 'kilo' || strtolower($row['satuan']) == 'kilogram') $kilogramBulanIni = $row['total'];
                            elseif (strtolower($row['satuan']) == 'pcs' || strtolower($row['satuan']) == 'pc' || strtolower($row['satuan']) == 'buah') $satuanBulanIni = $row['total'];
                            elseif (strtolower($row['satuan']) == 'm' || strtolower($row['satuan']) == 'm2' || strtolower($row['satuan']) == 'meter' || strtolower($row['satuan']) == 'meteran') $meteranBulanIni = $row['total'];
                        }
                    }
                    ?>
                    <div class="flex justify-between text-center border-b pb-4">
                        <div class="w-1/3">
                            <p class="text-lg font-bold text-gray-900">Rp <?php echo number_format($estimasiKomisiBulanIni, 0, ',', '.'); ?></p>
                            <span class="text-sm text-gray-500">Estimasi Komisi</span>
                        </div>
                        <div class="w-1/3 border-l">
                            <p class="text-lg font-bold text-gray-900"><?php echo $transaksiProsesBulanIni; ?></p>
                            <span class="text-sm text-gray-500">Diproses</span>
                        </div>
                        <div class="w-1/3 border-l">
                            <p class="text-lg font-bold text-gray-900"><?php echo $transaksiSelesaiBulanIni; ?></p>
                            <span class="text-sm text-gray-500">Selesai</span>
                        </div>
                    </div>
                    <div class="flex justify-around text-center pt-4">
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $kilogramBulanIni; ?> Kg</p>
                            <span class="text-sm text-gray-500">Kilogram</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $satuanBulanIni; ?> Pcs</p>
                            <span class="text-sm text-gray-500">Satuan</span>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-gray-900"><?php echo $meteranBulanIni; ?> M</p>
                            <span class="text-sm text-gray-500">Meteran</span>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- (Komentar) Area Konten Scrollable -->
        <div class="flex-grow overflow-y-auto no-scrollbar px-6 pb-24">

            <!-- (Komentar) Aksi Cepat (Grid 3-Kolom) (Sama) -->
            <section class="mt-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Aksi Cepat</h2>
                <div class="grid grid-cols-3 gap-3 text-center">
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

            <!-- (Komentar) PERUBAHAN BESAR: Mengganti "Daftar Transaksi" dengan "Tugas Saya" (TAB) -->
    <section class="mt-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Tugas Saya</h2>
            <div class="flex space-x-2 bg-gray-200 p-1 rounded-lg">
                <button class="tugas-tab-button aksi-tab-button-active w-1/2 py-2 rounded-md text-sm"
                    data-target="#tab-dikerjakan">
                        Masih Dikerjakan (<?php echo count($transaksiDikerjakan); ?>)
                </button>
                <button class="tugas-tab-button w-1/2 py-2 rounded-md text-sm text-gray-600"
                    data-target="#tab-selesai-hari-ini">
                    Selesai Hari Ini (<?php echo count($transaksiSelesaiHariIni); ?>)
                </button>
            </div>

            <div id="tab-dikerjakan" class="tugas-tab-panel mt-4" href="transaksi.php">
                <div class="bg-white rounded-lg shadow p-4 divide-y divide-gray-100">
                    <?php if (!empty($transaksiDikerjakan)): ?>
                        <?php foreach ($transaksiDikerjakan as $trx): ?>
                        <a href="transaksi.php" class="w-full flex justify-between items-center text-left py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($trx['nama_pelanggan'] ?? '-'); ?> (#<?php echo htmlspecialchars($trx['no_nota']); ?>)</p>
                                <p class="text-xs text-gray-500">Masuk: <?php echo date('H:i', strtotime($trx['tanggal_masuk'])); ?></p>
                            </div>
                            <span class="text-xs font-semibold rounded-full px-2 py-0.5
                                <?php echo ($trx['status'] == 'proses') ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                <?php echo ucfirst($trx['status']); ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                            <?php else: ?>
                            <div class="text-center text-gray-400 py-4">Tidak ada transaksi yang sedang dikerjakan.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="tab-selesai-hari-ini" class="tugas-tab-panel mt-4 hidden"> 
                <div class="bg-white rounded-lg shadow p-4 divide-y divide-gray-100">
                    <?php if (!empty($transaksiSelesaiHariIni)): ?>
                        <?php foreach ($transaksiSelesaiHariIni as $trx): ?>
                        <a href="transaksi.php" class="w-full flex justify-between items-center text-left py-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($trx['nama_pelanggan'] ?? '-'); ?> (#<?php echo htmlspecialchars($trx['no_nota']); ?>)</p>
                                    <p class="text-xs text-gray-500">Selesai: <?php echo date('H:i', strtotime($trx['tanggal_selesai'])); ?></p>
                                </div>
                                <span class="text-xs font-semibold bg-green-100 text-green-700 rounded-full px-2 py-0.5">Selesai</span>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                <div class="text-center text-gray-400 py-4">Tidak ada transaksi selesai hari ini.</div>
                    <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
    (function(){
        function qs(sel, root=document) { return root.querySelector(sel); }
        function qsa(sel, root=document) { return Array.from(root.querySelectorAll(sel)); }

        document.addEventListener('DOMContentLoaded', function(){
            const tabButtons = qsa('.tugas-tab-button');
            const tabPanels = qsa('.tugas-tab-panel');

            if (!tabButtons.length || !tabPanels.length) return;

            function showPanel(targetSelector, clickedButton){
                // hide all panels
                tabPanels.forEach(p => p.classList.add('hidden'));
                // remove active from all buttons
                tabButtons.forEach(b => {
                    b.classList.remove('aksi-tab-button-active');
                    b.classList.remove('text-gray-800');
                    b.classList.add('text-gray-600');
                    b.setAttribute('aria-selected', 'false');
                });

                // show target
                const panel = qs(targetSelector);
                if (panel) panel.classList.remove('hidden');

                // mark active button
                if (clickedButton) {
                    clickedButton.classList.add('aksi-tab-button-active');
                    clickedButton.classList.remove('text-gray-600');
                    clickedButton.classList.add('text-gray-800');
                    clickedButton.setAttribute('aria-selected', 'true');
                    clickedButton.focus();
                }
            }

            // init: find existing active or default to first
            let activeBtn = tabButtons.find(b => b.classList.contains('aksi-tab-button-active')) || tabButtons[0];
            showPanel(activeBtn.getAttribute('data-target'), activeBtn);

            // click handlers
            tabButtons.forEach(btn => {
                btn.setAttribute('role','tab');
                btn.setAttribute('tabindex','0');
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    showPanel(this.getAttribute('data-target'), this);
                });
                // keyboard navigation (Left/Right / Enter/Space)
                btn.addEventListener('keydown', function(e){
                    const idx = tabButtons.indexOf(this);
                    if (e.key === 'ArrowRight') {
                        const next = tabButtons[(idx + 1) % tabButtons.length];
                        next.click();
                        e.preventDefault();
                    } else if (e.key === 'ArrowLeft') {
                        const prev = tabButtons[(idx - 1 + tabButtons.length) % tabButtons.length];
                        prev.click();
                        e.preventDefault();
                    } else if (e.key === 'Enter' || e.key === ' ') {
                        this.click();
                        e.preventDefault();
                    }
                });
            });

            // Optional: switch tab if URL hash matches a panel id
            const hash = location.hash;
            if (hash) {
                const matchingBtn = tabButtons.find(b => b.getAttribute('data-target') === hash);
                if (matchingBtn) showPanel(hash, matchingBtn);
            }
        });
    })();
    </script>

            </div> <!-- (Komentar) Penutup Area Scroll -->

        </div> <!-- (Komentar) Penutup main-content -->

        <!-- ====================================================== -->
        <!-- (Komentar) 2. NAVIGASI BAWAH (3 TOMBOL)                -->
        <!-- ====================================================== -->
    <nav
        class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-4 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        
        <a href="dashboard.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Beranda</span>
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


    <!-- ====================================================== -->
    <!-- (Komentar) 3. KONTAINER MODAL (POPUP)                  -->
    <!-- ====================================================== -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>

        <!-- (Komentar) MODAL 1: Opsi Buat Transaksi (Sama seperti sebelumnya) -->
        <div id="modal-buat-transaksi"
            class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto" style="transform:translateY(100%);transition:transform 0.3s;">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Buat Transaksi</h2>
                    <button class="btn-close-global p-1 text-gray-500 hover:text-gray-800">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="px-6 pb-6 space-y-3 overflow-y-auto no-scrollbar">
                <button id="btn-transaksi-manual"
                    class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="box" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Transaksi Manual</p>
                            <p class="text-sm text-gray-500">Manual input</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <button id="btn-transaksi-template"
                    class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="grid" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Transaksi Template</p>
                            <p class="text-sm text-gray-500">Pilih dari paket layanan jadi</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-100">
                <button
                    class="btn-close-global w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
            </div>
        </div>
        

        <!-- (Komentar) MODAL 2: Form Transaksi Manual (Sudah ada) -->
        <div id="modal-rincian-transaksi"
            class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Buat Transaksi Manual</h2>
                    <button class="btn-close-global p-1 text-gray-500 hover:text-gray-800">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-buat-transaksi" action="api/query-buat-transaksi.php" method="POST" class="space-y-6">
                    <!-- Hidden fields for database -->
                    <input type="hidden" name="bisnis_id" id="bisnis_id" readonly class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100 mb-2" value="<?php echo htmlspecialchars($ownerData['bisnis_id'] ?? ''); ?>" placeholder="Bisnis ID (readonly)">
                    <input type="hidden" name="karyawan_id" id="karyawan_id">
                    <input type="hidden" name="pelanggan_id" id="pelanggan_id">

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">1. Informasi Pelanggan</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="cari_pelanggan_manual" class="text-sm font-medium text-gray-600">
                                    Cari Pelanggan (Nama / No HP)
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                        <select id="cari_pelanggan_template" name="pelanggan_id" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                                        <option value="">-- Pilih pelanggan yang pernah datang --</option>
                                        <?php foreach ($daftar_pelanggan as $plg): ?>
                                            <option value="<?php echo htmlspecialchars($plg['pelanggan_id']); ?>">
                                                <?php echo htmlspecialchars($plg['nama']); ?> (<?php echo htmlspecialchars($plg['no_telepon']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <p class="text-sm text-left text-gray-500 -my-2">atau masukkan data pelanggan baru ...</p>
                            <div>
                                <label for="nama_pelanggan_manual" class="text-sm font-medium text-gray-600">
                                    Nama Pelanggan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                        <input type="text" id="nama_pelanggan_template" name="nama_pelanggan" required
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="Nama Pelanggan">
                                </div>
                            </div>
                            <div>
                                <label for="no_handphone_manual" class="text-sm font-medium text-gray-600">
                                    No Handphone (WA) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                        <input type="tel" id="no_handphone_template" name="no_handphone" required
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="No Handphone Pelanggan">
                                </div>
                            </div>
                            <div>
                                <label for="alamat_manual" class="text-sm font-medium text-gray-600">Alamat (Opsional)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                        <svg data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                    <textarea id="alamat_manual" name="alamat" rows="2"
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="Alamat untuk data / pengantaran"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">2. Informasi Pesanan</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="tgl_selesai_manual" class="text-sm font-medium text-gray-600">
                                    Estimasi Selesai <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="tgl_selesai_manual" name="tanggal_selesai" required
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                            </div>

                            <div>
                                <label for="status_awal_manual" class="text-sm font-medium text-gray-600">
                                    Status Awal <span class="text-red-500">*</span>
                                </label>
                                <select id="status_awal_manual" name="status" required
                                    class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="pending" selected>Pending (Antrian)</option>
                                    <option value="proses">Langsung Diproses</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">3. Rincian Layanan</h3>
                        <div class="border-b pb-3 mb-3 layanan-item">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-sm font-medium text-gray-600">
                                    Layanan 1 <span class="text-red-500">*</span>
                                </label>
                                <button type="button" class="text-red-500 hover:text-red-700 btn-remove-layanan" style="display: none;">
                                    <svg data-feather="trash-2" class="w-4 h-4"></svg>
                                </button>
                            </div>
                            <select name="layanan_id[]" required
                                class="w-full py-3 px-3 border border-gray-300 rounded-lg bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500 layanan-select">
                                <option value="">-- Pilih Layanan (dari Kelola Layanan) --</option>
                                <?php foreach ($daftar_layanan as $layanan): ?>
                                    <option value="<?php echo htmlspecialchars($layanan['layanan_id']); ?>">
                                        <?php echo htmlspecialchars($layanan['nama_layanan']); ?> (Rp <?php echo number_format($layanan['harga'],0,',','.'); ?> / <?php echo htmlspecialchars($layanan['satuan']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="quantity[]" placeholder="Qty (Kg / Pcs)" required min="0.1" step="0.1"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg quantity-input">
                                <input type="text" placeholder="Harga Total"
                                    class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100 harga-total"
                                    readonly>
                                <input type="hidden" name="harga_satuan[]" class="harga-satuan-hidden">
                            </div>
                        </div>
                        <button type="button" id="btn-tambah-layanan"
                            class="w-full border-2 border-dashed border-blue-500 text-blue-500 font-semibold py-3 px-4 rounded-lg hover:bg-blue-50">
                            + Tambah Layanan Lain
                        </button>
                        <div>
                            <div class="border-t pt-4"></div>
                            <label for="catatan_manual" class="text-sm font-medium text-gray-600">Catatan (Opsional)</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                    <svg data-feather="clipboard" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <textarea id="catatan_manual" name="catatan" rows="2"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Misal: Alergi parfum, minta lipat rapi..."></textarea>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">4. Rincian Pembayaran</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-600">Subtotal</label>
                                <input type="text" id="subtotal-display"
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg bg-gray-100"
                                    value="Rp 0" readonly>
                                <input type="hidden" name="subtotal" id="subtotal-value">
                            </div>
                            <div>
                                <label for="diskon" class="text-sm font-medium text-gray-600">Diskon (Rp)</label>
                                <input type="number" id="diskon" name="diskon" min="0"
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Contoh: 1000" value="0">
                            </div>
                            <div>
                                <label for="biaya_antar" class="text-sm font-medium text-gray-600">Biaya Antar (Rp)</label>
                                <input type="number" id="biaya_antar" name="biaya_antar" min="0"
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg" value="0">
                            </div>
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-900">Total Akhir</span>
                                    <span class="text-2xl font-bold text-blue-600" id="total-akhir-display">Rp 0</span>
                                </div>
                                <input type="hidden" name="total_harga" id="total-harga-value" required>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="metode_bayar_manual" class="text-sm font-medium text-gray-600">
                                        Metode Bayar <span class="text-red-500">*</span>
                                    </label>
                                    <select id="metode_bayar_manual" name="metode_bayar" required
                                        class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="dibayar_manual" class="text-sm font-medium text-gray-600">
                                        Jumlah Dibayar <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="dibayar_manual" name="dibayar" required min="0" step="0.01"
                                        class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg"
                                        placeholder="Jumlah yang dibayar">
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            <script>
            // --- ENHANCED DYNAMIC TRANSAKSI FORM LOGIC ---
            document.addEventListener('DOMContentLoaded', function() {
                // --- Pelanggan Dropdown Logic General ---
                const pelangganData = {};
                <?php foreach ($daftar_pelanggan as $plg): ?>
                pelangganData['<?php echo $plg['pelanggan_id']; ?>'] = {
                    nama: '<?php echo addslashes($plg['nama']); ?>',
                    no_telepon: '<?php echo addslashes($plg['no_telepon']); ?>'
                };
                <?php endforeach; ?>

                // Manual form
                const selectManual = document.getElementById('cari_pelanggan_manual');
                const namaManual = document.getElementById('nama_pelanggan_manual');
                const nohpManual = document.getElementById('no_handphone_manual');
                if (selectManual && namaManual && nohpManual) {
                    selectManual.addEventListener('change', function() {
                        const selectedId = this.value;
                        if (selectedId && pelangganData[selectedId]) {
                            namaManual.value = pelangganData[selectedId].nama;
                            namaManual.readOnly = true;
                            nohpManual.value = pelangganData[selectedId].no_telepon;
                            nohpManual.readOnly = true;
                        } else {
                            namaManual.value = '';
                            namaManual.readOnly = false;
                            nohpManual.value = '';
                            nohpManual.readOnly = false;
                        }
                    });
                }

                // Template form
                const selectTemplate = document.getElementById('cari_pelanggan_template');
                const namaTemplate = document.getElementById('nama_pelanggan_template');
                const nohpTemplate = document.getElementById('no_handphone_template');
                if (selectTemplate && namaTemplate && nohpTemplate) {
                    selectTemplate.addEventListener('change', function() {
                        const selectedId = this.value;
                        if (selectedId && pelangganData[selectedId]) {
                            namaTemplate.value = pelangganData[selectedId].nama;
                            namaTemplate.readOnly = true;
                            nohpTemplate.value = pelangganData[selectedId].no_telepon;
                            nohpTemplate.readOnly = true;
                        } else {
                            namaTemplate.value = '';
                            namaTemplate.readOnly = false;
                            nohpTemplate.value = '';
                            nohpTemplate.readOnly = false;
                        }
                    });
                }
                function formatRupiah(num) {
                    return 'Rp ' + num.toLocaleString('id-ID');
                }


                // Mapping layanan_id ke harga dari PHP
                const layananHargaMap = {};
                <?php foreach ($daftar_layanan as $layanan): ?>
                layananHargaMap['<?php echo $layanan['layanan_id']; ?>'] = <?php echo floatval($layanan['harga']); ?>;
                <?php endforeach; ?>

                const form = document.getElementById('form-buat-transaksi');
                const layananContainer = form.querySelector('section:nth-of-type(3)');
                const btnTambahLayanan = document.getElementById('btn-tambah-layanan');
                const subtotalDisplay = document.getElementById('subtotal-display');
                const subtotalValue = document.getElementById('subtotal-value');
                const diskonInput = document.getElementById('diskon');
                const biayaAntarInput = document.getElementById('biaya_antar');
                const totalAkhirDisplay = document.getElementById('total-akhir-display');
                const totalHargaValue = document.getElementById('total-harga-value');

                function getLayananBlocks() {
                    return Array.from(layananContainer.querySelectorAll('.layanan-item'));
                }

                function calculateSubtotal() {
                    let subtotal = 0;
                    getLayananBlocks().forEach(block => {
                        const select = block.querySelector('.layanan-select');
                        const qtyInput = block.querySelector('.quantity-input');
                        const layananId = select.value;
                        const price = layananHargaMap[layananId] || 0;
                        const qty = parseFloat(qtyInput.value) || 0;
                        subtotal += price * qty;
                    });
                    return subtotal;
                }

                function updateLayananPrices() {
                    getLayananBlocks().forEach(block => {
                        const select = block.querySelector('.layanan-select');
                        const qtyInput = block.querySelector('.quantity-input');
                        const hargaTotalInput = block.querySelector('.harga-total');
                        const hargaSatuanInput = block.querySelector('.harga-satuan-hidden');
                        const layananId = select.value;
                        const price = layananHargaMap[layananId] || 0;
                        hargaSatuanInput.value = price;
                        const qty = parseFloat(qtyInput.value) || 0;
                        const total = price * qty;
                        // If qty is empty or zero, show unit price instead of Rp 0 for better UX
                        if (qty > 0) {
                            hargaTotalInput.value = formatRupiah(total);
                        } else {
                            // show unit price as default
                            hargaTotalInput.value = price > 0 ? formatRupiah(price) : 'Rp 0';
                        }
                    });
                }

                function updateTotals() {
                    updateLayananPrices();
                    const subtotal = calculateSubtotal();
                    subtotalDisplay.value = formatRupiah(subtotal);
                    subtotalValue.value = subtotal;
                    const diskon = parseFloat(diskonInput.value) || 0;
                    const biayaAntar = parseFloat(biayaAntarInput.value) || 0;
                    let total = subtotal - diskon + biayaAntar;
                    if (total < 0) total = 0;
                    totalAkhirDisplay.textContent = formatRupiah(total);
                    totalHargaValue.value = total;
                }

                function attachLayananEvents(block) {
                    const select = block.querySelector('.layanan-select');
                    const qtyInput = block.querySelector('.quantity-input');
                    const hargaSatuanInput = block.querySelector('.harga-satuan-hidden');
                    select.addEventListener('change', function() {
                        updateTotals();
                        hargaSatuanInput.value = layananHargaMap[select.value] || 0;
                    });
                    qtyInput.addEventListener('input', updateTotals);
                    const btnRemove = block.querySelector('.btn-remove-layanan');
                    if (btnRemove) {
                        btnRemove.addEventListener('click', function() {
                            const layananBlocks = getLayananBlocks();
                            if (layananBlocks.length > 1) {
                                block.remove();
                                updateTotals();
                                updateRemoveButtons();
                            }
                        });
                    }
                }

                // Update visibility of remove buttons
                function updateRemoveButtons() {
                    const blocks = getLayananBlocks();
                    blocks.forEach((block, index) => {
                        const btnRemove = block.querySelector('.btn-remove-layanan');
                        if (btnRemove) {
                            btnRemove.style.display = blocks.length > 1 ? 'block' : 'none';
                        }
                        
                        // Update label
                        const label = block.querySelector('label');
                        label.innerHTML = `Layanan ${index + 1} <span class="text-red-500">*</span>`;
                    });
                }

                // Initial attach for first layanan
                getLayananBlocks().forEach(attachLayananEvents);

                // Add layanan logic
                btnTambahLayanan.addEventListener('click', function(e) {
                    e.preventDefault();
                    const firstBlock = getLayananBlocks()[0];
                    const newBlock = firstBlock.cloneNode(true);
                    
                    // Reset values
                    newBlock.querySelector('.layanan-select').selectedIndex = 0;
                    newBlock.querySelector('.quantity-input').value = '';
                    newBlock.querySelector('.harga-total').value = 'Rp 0';
                    newBlock.querySelector('.harga-satuan-hidden').value = '';
                    attachLayananEvents(newBlock);
                    // Insert the new layanan block right before the add-button inside the same section
                    layananContainer.insertBefore(newBlock, btnTambahLayanan);
                    updateRemoveButtons();
                    updateTotals();
                });

                // Diskon and biaya antar logic
                diskonInput.addEventListener('input', updateTotals);
                biayaAntarInput.addEventListener('input', updateTotals);

                // Form validation
                form.addEventListener('submit', function(e) {
                    const layananBlocks = getLayananBlocks();
                    let hasValidLayanan = false;
                    
                    layananBlocks.forEach(block => {
                        const select = block.querySelector('.layanan-select');
                        const qty = block.querySelector('.quantity-input');
                        if (select.value && qty.value && parseFloat(qty.value) > 0) {
                            hasValidLayanan = true;
                        }
                    });
                    
                    if (!hasValidLayanan) {
                        e.preventDefault();
                        alert('Minimal harus ada satu layanan yang dipilih dengan quantity yang valid!');
                        return;
                    }
                    
                    // Validate payment
                    const totalHarga = parseFloat(totalHargaValue.value) || 0;
                    const dibayar = parseFloat(document.getElementById('dibayar_manual').value) || 0;
                    
                    if (dibayar > totalHarga) {
                        const confirm = window.confirm(`Jumlah bayar (${formatRupiah(dibayar)}) lebih besar dari total (${formatRupiah(totalHarga)}). Lanjutkan?`);
                        if (!confirm) {
                            e.preventDefault();
                            return;
                        }
                    }
                });

                // Initial calculation
                updateRemoveButtons();
                updateTotals();

                // Set default datetime (current time + 3 days)
                const now = new Date();
                now.setDate(now.getDate() + 3);
                const defaultDateTime = now.toISOString().slice(0, 16);
                document.getElementById('tgl_selesai_manual').value = defaultDateTime;
            });
            </script>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-buat-transaksi"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Buat Transaksi
                </button>
            </div>
        </div>

        <div id="modal-transaksi-template"
            class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Buat Transaksi Cepat (POS)</h2>
                    <button class="btn-close-global p-1 text-gray-500 hover:text-gray-800">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-buat-template" action="api/query-buat-transaksi.php" method="POST" class="space-y-6">
                    <!-- Hidden fields for database -->
                    <input type="hidden" name="bisnis_id" id="bisnis_id" readonly class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100 mb-2" value="<?php echo htmlspecialchars($ownerData['bisnis_id'] ?? ''); ?>" placeholder="Bisnis ID (readonly)">
                    <input type="hidden" name="karyawan_id" id="karyawan_id">
                    <input type="hidden" name="pelanggan_id" id="pelanggan_id">
                    <input type="hidden" name="form_type" value="template">

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">1. Informasi Pelanggan</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="cari_pelanggan_manual" class="text-sm font-medium text-gray-600">
                                    Cari Pelanggan (Nama / No HP)
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                    <select id="cari_pelanggan_manual" name="pelanggan_id" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                                        <option value="">-- Pilih pelanggan yang pernah datang --</option>
                                        <?php foreach ($daftar_pelanggan as $plg): ?>
                                            <option value="<?php echo htmlspecialchars($plg['pelanggan_id']); ?>">
                                                <?php echo htmlspecialchars($plg['nama']); ?> (<?php echo htmlspecialchars($plg['no_telepon']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 -mt-2 mb-3">atau masukkan data pelanggan baru ...</p>
                            <div>
                                <label for="nama_pelanggan_template" class="text-sm font-medium text-gray-600">
                                    Nama Pelanggan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                    <input type="text" id="nama_pelanggan_manual" name="nama_pelanggan" required
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="Nama Pelanggan">
                                </div>
                            </div>
                            <div>
                                <label for="no_handphone_template" class="text-sm font-medium text-gray-600">
                                    No Handphone (WA) <span class="text-red-500">*</span>
                                </label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                    <input type="tel" id="no_handphone_manual" name="no_handphone" required
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="No Handphone Pelanggan">
                                </div>
                            </div>
                            <div>
                                <label for="alamat_manual" class="text-sm font-medium text-gray-600">Alamat (Opsional)</label>
                                <div class="relative mt-1">
                                    <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                        <svg data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg>
                                    </span>
                                    <textarea id="alamat_manual" name="alamat" rows="2"
                                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                        placeholder="Alamat untuk data / pengantaran"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">2. Informasi Pesanan</h3>
                        <div class="space-y-4">
                            <div>
                                <label for="tgl_selesai_template" class="text-sm font-medium text-gray-600">
                                    Estimasi Selesai <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="tgl_selesai_template" name="tanggal_selesai" required
                                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                            </div>

                            <div>
                                <label for="status_awal_template" class="text-sm font-medium text-gray-600">
                                    Status Awal <span class="text-red-500">*</span>
                                </label>
                                <select id="status_awal_template" name="status" required
                                    class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">-- Pilih Status --</option>
                                    <option value="pending" selected>Pending (Antrian)</option>
                                    <option value="proses">Langsung Diproses</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">3. Pilih Layanan (Menu Cepat)</h3>
                        <div class="space-y-2 mb-4" id="keranjang-layanan">
                            <!-- Selected services will appear here -->
                        </div>

                        <div class="grid grid-cols-2 gap-3" id="daftar-layanan-dinamis">
                            <?php foreach ($daftar_layanan as $layanan): ?>
                            <button type="button"
                                class="text-left bg-white border border-gray-300 rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 service-btn"
                                data-id="<?php echo htmlspecialchars($layanan['layanan_id']); ?>"
                                data-nama="<?php echo htmlspecialchars($layanan['nama_layanan']); ?>"
                                data-harga="<?php echo htmlspecialchars($layanan['harga']); ?>"
                                data-unit="<?php echo htmlspecialchars($layanan['satuan']); ?>">
                                <p class="font-semibold"><?php echo htmlspecialchars($layanan['nama_layanan']); ?></p>
                                <p class="text-sm text-gray-600">Rp <?php echo number_format($layanan['harga'],0,',','.'); ?> / <?php echo htmlspecialchars($layanan['satuan']); ?></p>
                            </button>
                            <?php endforeach; ?>
                        </div>

                        <div>
                            <div class="border-t pt-4"></div>
                            <label for="catatan_template" class="text-sm font-medium text-gray-600">Catatan (Opsional)</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                    <svg data-feather="clipboard" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <textarea id="catatan_template" name="catatan" rows="2"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Misal: Alergi parfum, minta lipat rapi..."></textarea>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">4. Rincian Pembayaran</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-900">Total Akhir</span>
                                    <span class="text-2xl font-bold text-blue-600" id="template-total-akhir">Rp 0</span>
                                </div>
                                <input type="hidden" name="total_harga" id="template-total-value" required>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="metode_bayar_template" class="text-sm font-medium text-gray-600">
                                        Metode Bayar <span class="text-red-500">*</span>
                                    </label>
                                    <select id="metode_bayar_template" name="metode_bayar" required
                                        class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">-- Pilih Metode --</option>
                                        <option value="Tunai">Tunai</option>
                                        <option value="QRIS">QRIS</option>
                                        <option value="Transfer">Transfer</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="dibayar_template" class="text-sm font-medium text-gray-600">
                                        Jumlah Dibayar <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" id="dibayar_template" name="dibayar" required min="0" step="0.01"
                                        class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg"
                                        placeholder="Jumlah yang dibayar">
                                </div>
                            </div>
                        </div>
                    </section>
                </form>
            </div>

            <script>
            // --- TEMPLATE FORM LOGIC ---
            document.addEventListener('DOMContentLoaded', function() {
                const templateForm = document.getElementById('form-buat-template');
                const keranjangLayanan = document.getElementById('keranjang-layanan');
                const totalDisplay = document.getElementById('template-total-akhir');
                const totalValue = document.getElementById('template-total-value');
                const serviceButtons = document.querySelectorAll('.service-btn');
                
                let selectedServices = [];

                function formatRupiah(num) {
                    return 'Rp ' + num.toLocaleString('id-ID');
                }

                function updateTotal() {
                    const total = selectedServices.reduce((sum, service) => {
                        return sum + (service.harga * service.quantity);
                    }, 0);
                    
                    totalDisplay.textContent = formatRupiah(total);
                    totalValue.value = total;
                }

                function renderKeranjang() {
                    if (selectedServices.length === 0) {
                        keranjangLayanan.innerHTML = '<p class="text-gray-500 text-sm">Belum ada layanan dipilih</p>';
                    } else {
                        keranjangLayanan.innerHTML = selectedServices.map((service, index) => `
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-semibold">${service.nama}</p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <input type="number" min="0.1" step="0.1" value="${service.quantity}" 
                                                class="w-20 px-2 py-1 border rounded text-sm quantity-template-input" 
                                                data-index="${index}">
                                            <span class="text-sm">${service.unit} × ${formatRupiah(service.harga)}</span>
                                        </div>
                                        <p class="text-sm text-blue-600 font-semibold">Total: ${formatRupiah(service.harga * service.quantity)}</p>
                                        <input type="hidden" name="layanan_id[]" value="${service.id}">
                                        <input type="hidden" name="quantity[]" value="${service.quantity}">
                                    </div>
                                    <button type="button" class="text-red-500 hover:text-red-700 remove-service-btn" data-index="${index}">
                                        <svg data-feather="x" class="w-4 h-4"></svg>
                                    </button>
                                </div>
                            </div>
                        `).join('');
                        
                        // Re-initialize feather icons
                        if (typeof feather !== 'undefined') {
                            feather.replace();
                        }
                        
                        // Add event listeners
                        document.querySelectorAll('.quantity-template-input').forEach(input => {
                            input.addEventListener('input', function() {
                                const index = this.dataset.index;
                                const newQuantity = parseFloat(this.value) || 0;
                                selectedServices[index].quantity = newQuantity;
                                
                                // Update hidden input
                                const hiddenInput = this.parentElement.parentElement.querySelector('input[name="quantity[]"]');
                                hiddenInput.value = newQuantity;
                                
                                renderKeranjang();
                                updateTotal();
                            });
                        });
                        
                        document.querySelectorAll('.remove-service-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                const index = parseInt(this.dataset.index);
                                selectedServices.splice(index, 1);
                                renderKeranjang();
                                updateTotal();
                            });
                        });
                    }
                }

                // Service button click handlers
                serviceButtons.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const id = this.dataset.id;
                        const nama = this.dataset.nama;
                        const harga = parseInt(this.dataset.harga);
                        const unit = this.dataset.unit;
                        // Check if service already exists
                        const existingIndex = selectedServices.findIndex(s => s.id === id);
                        if (existingIndex !== -1) {
                            selectedServices[existingIndex].quantity += 1;
                        } else {
                            selectedServices.push({
                                id: id,
                                nama: nama,
                                harga: harga,
                                unit: unit,
                                quantity: 1
                            });
                        }
                        renderKeranjang();
                        updateTotal();
                    });
                });

                // Form validation
                templateForm.addEventListener('submit', function(e) {
                    if (selectedServices.length === 0) {
                        e.preventDefault();
                        alert('Minimal harus memilih satu layanan!');
                        return;
                    }
                    
                    // Validate payment
                    const totalHarga = parseFloat(totalValue.value) || 0;
                    const dibayar = parseFloat(document.getElementById('dibayar_template').value) || 0;
                    
                    if (dibayar > totalHarga) {
                        const confirm = window.confirm(`Jumlah bayar (${formatRupiah(dibayar)}) lebih besar dari total (${formatRupiah(totalHarga)}). Lanjutkan?`);
                        if (!confirm) {
                            e.preventDefault();
                            return;
                        }
                    }
                });

                // Set default datetime
                const now = new Date();
                now.setDate(now.getDate() + 3);
                const defaultDateTime = now.toISOString().slice(0, 16);
                document.getElementById('tgl_selesai_template').value = defaultDateTime;

                // Initialize
                renderKeranjang();
                updateTotal();
            });
            </script>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-buat-template"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Buat Transaksi
                </button>
            </div>
        </div>

        <!-- (Komentar) MODAL 4: Modal Absensi (IMPROVEMENT) -->
        <div id="modal-absensi"
            class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Absensi Karyawan</h2>
                    <button class="btn-close-global p-1 text-gray-500 hover:text-gray-800"><svg data-feather="x"
                            class="w-6 h-6"></svg></button>
                </div>
            </div>

            <form id="form-absensi" method="POST" action="dashboard.php" class="flex flex-col flex-grow justify-between">
                <div class="p-6 text-center">
                    <p class="text-gray-600 mb-2">Waktu saat ini:</p>
                    <p class="text-4xl font-bold text-gray-900" id="absensi-clock">00:00:00</p>
                    <p class="text-sm text-gray-500" id="absensi-date"></p>

                    
                
                </div>
                <div class="p-6 space-y-3 border-t border-gray-100">
                    <input type="hidden" name="waktu" id="absensi-waktu">

                    <?php if (!empty($jam_masuk)): ?>
                        <div class="text-center text-sm text-gray-600">Telah mengisi absen masuk pada <strong><?php echo date('H:i:s', strtotime($jam_masuk)); ?></strong></div>
                        <div class="w-full bg-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg text-lg flex items-center justify-center opacity-80 cursor-not-allowed">
                            <svg data-feather="log-in" class="w-5 h-5 mr-2"></svg>
                            Absen Masuk
                        </div>
                    <?php else: ?>
                        <button name="aksi" value="masuk" type="submit"
                            class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 text-lg flex items-center justify-center">
                            <svg data-feather="log-in" class="w-5 h-5 mr-2"></svg>
                            Absen Masuk
                        </button>
                    <?php endif; ?>

                    <?php if (!empty($jam_keluar)): ?>
                        <div class="text-center text-sm text-gray-600">Telah mengisi absen pulang pada <strong><?php echo date('H:i:s', strtotime($jam_keluar)); ?></strong></div>
                        <div class="w-full bg-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg text-lg flex items-center justify-center opacity-80 cursor-not-allowed">
                            <svg data-feather="log-out" class="w-5 h-5 mr-2"></svg>
                            Absen Pulang
                        </div>
                    <?php else: ?>
                        <?php if (empty($jam_masuk)): ?>
                            <!-- Disable pulang jika belum absen masuk -->
                            <div class="text-center text-sm text-red-600"></div>
                            <button type="button" disabled
                                class="w-full bg-red-400 text-white font-bold py-3 px-4 rounded-lg text-lg flex items-center justify-center opacity-60 cursor-not-allowed"
                                title="Harap absen masuk terlebih dahulu">
                                <svg data-feather="log-out" class="w-5 h-5 mr-2"></svg>
                                Absen Pulang
                            </button>
                        <?php else: ?>
                            <button name="aksi" value="pulang" type="submit"
                                class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-red-700 text-lg flex items-center justify-center">
                                <svg data-feather="log-out" class="w-5 h-5 mr-2"></svg>
                                Absen Pulang
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </form>
            <script>
            // Realtime clock for absensi modal
            function pad(n) { return n < 10 ? '0' + n : n; }
            function updateAbsensiClock() {
                const now = new Date();
                const jam = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
                const tanggal = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                document.getElementById('absensi-clock').textContent = jam;
                document.getElementById('absensi-date').textContent = tanggal;
            }
            setInterval(updateAbsensiClock, 1000);
            updateAbsensiClock();
            // Set waktu hidden saat submit
            document.getElementById('form-absensi').addEventListener('submit', function(e) {
                const now = new Date();
                const waktu = now.getFullYear() + '-' + pad(now.getMonth()+1) + '-' + pad(now.getDate()) + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
                document.getElementById('absensi-waktu').value = waktu;
            });
            </script>
        </div>
        </div> <!-- (Komentar) Penutup Modal Container -->


    <!-- ====================================================== -->
    <!-- (Komentar) 4. SCRIPT                                   -->
    <!-- ====================================================== -->
    <!-- (Komentar) PERUBAHAN: Path disesuaikan (naik 2 level) -->
    <script src="../../assets/js/icons.js" defer></script>
    <script src="../../assets/js/main.js" defer></script>
    <!-- (Komentar) Path ke skrip khusus halaman ini -->
    <script src="../../assets/js/karyawan-dashboard.js" defer></script>
</body>

</html>