<?php
require_once 'middleware/auth_karyawan.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/functions.php';
require_once 'api/query-dashboard.php';



$karyawanId = $_SESSION['karyawan_data']['karyawan_id'] ?? null;

$estimasiKomisi = 0;
$kilogram = 0;
$kinerjaData = [];
$kinerjaData['rincian'] = [];

// Ambil gaji pokok dari tabel karyawan
$gajiPokok = 0;
if (isset($karyawanId)) {
    $stmtGaji = $conn->prepare('SELECT gaji_pokok FROM karyawan WHERE karyawan_id = ? LIMIT 1');
    $stmtGaji->execute([$karyawanId]);
    $gajiRow = $stmtGaji->fetch(PDO::FETCH_ASSOC);
    $gajiPokok = $gajiRow && isset($gajiRow['gaji_pokok']) ? (int)$gajiRow['gaji_pokok'] : 0;
}

// Ambil filter dari GET
$filterType = $_GET['filter'] ?? 'bulan_ini';
$tanggalMulai = $_GET['tanggal_mulai'] ?? null;
$tanggalSelesai = $_GET['tanggal_selesai'] ?? null;

// Helper untuk where clause
function getFilterWhere($filterType, $tanggalMulai, $tanggalSelesai, $field = 't.tanggal_selesai') {
    switch ($filterType) {
        case 'hari_ini':
            return "DATE($field) = CURDATE()";
        case '7_hari':
            return "$field >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND $field <= CURDATE()";
        case 'bulan_ini':
            return "MONTH($field) = MONTH(CURDATE()) AND YEAR($field) = YEAR(CURDATE())";
        case 'tahun_ini':
            return "YEAR($field) = YEAR(CURDATE())";
        case 'kustom':
            if ($tanggalMulai && $tanggalSelesai) {
                return "$field >= '" . addslashes($tanggalMulai) . "' AND $field <= '" . addslashes($tanggalSelesai) . "'";
            } else {
                return "1=1";
            }
        default:
            return "MONTH($field) = MONTH(CURDATE()) AND YEAR($field) = YEAR(CURDATE())";
    }
}

if (isset($karyawanId)) {
    $whereFilter = getFilterWhere($filterType, $tanggalMulai, $tanggalSelesai);
    $stmtRinci = $conn->prepare('
        SELECT t.transaksi_id,
               t.no_nota AS nota,
               COALESCE(p.nama, "-") AS nama_customer,
               t.tanggal_selesai,
               COALESCE(SUM(CASE WHEN LOWER(l.satuan) IN ("kg","kilo","kilogram") THEN dt.jumlah ELSE 0 END), 0) AS berat_kg,
               COALESCE(SUM(CASE WHEN LOWER(l.satuan) IN ("pcs","pc","buah") THEN dt.jumlah ELSE 0 END), 0) AS jumlah_pcs,
               COALESCE(SUM(CASE WHEN LOWER(l.satuan) IN ("m","m2","meter","meteran") THEN dt.jumlah ELSE 0 END), 0) AS jumlah_meter
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        LEFT JOIN detail_transaksi dt ON t.transaksi_id = dt.transaksi_id
        LEFT JOIN layanan l ON dt.layanan_id = l.layanan_id
        WHERE t.karyawan_id = ? 
          AND t.status = "selesai"
          AND ' . $whereFilter . '
        GROUP BY t.transaksi_id
        ORDER BY t.tanggal_selesai DESC
    ');
    $stmtRinci->execute([$karyawanId]);
    while ($row = $stmtRinci->fetch(PDO::FETCH_ASSOC)) {
        $kg = (float) $row['berat_kg'];
        $pcs = (float) $row['jumlah_pcs'];
        $meter = (float) $row['jumlah_meter'];

        // DB hanya punya: Kiloan, Satuan, Meteran
        if ($kg > 0) {
            $row['berat_selesai'] = $kg;
            $row['berat_unit'] = 'Kiloan';
            $row['berat_display'] = '+ ' . rtrim(rtrim(number_format($kg, 2, ',', '.'), '0'), ',') . ' Kiloan';
        } elseif ($pcs > 0) {
            $row['berat_selesai'] = $pcs;
            $row['berat_unit'] = 'Satuan';
            $row['berat_display'] = '+ ' . (int)$pcs . ' Satuan';
        } elseif ($meter > 0) {
            $row['berat_selesai'] = $meter;
            $row['berat_unit'] = 'Meteran';
            $row['berat_display'] = '+ ' . rtrim(rtrim(number_format($meter, 2, ',', '.'), '0'), ',') . ' Meteran';
        } else {
            $row['berat_selesai'] = 0;
            $row['berat_unit'] = '';
            $row['berat_display'] = '';
        }

        $kinerjaData['rincian'][] = $row;
    }
    $transaksiSelesai = count($kinerjaData['rincian']);
}


if (isset($karyawanId)) {
    // Hitung kehadiran pada bulan ini (jam_masuk dan jam_keluar terisi)
    $stmtKeh = $conn->prepare('
        SELECT COUNT(*) AS total
        FROM absensi
        WHERE karyawan_id = ?
          AND jam_masuk IS NOT NULL
          AND jam_keluar IS NOT NULL
          AND MONTH(tanggal) = MONTH(CURDATE())
          AND YEAR(tanggal) = YEAR(CURDATE())
    ');
    $stmtKeh->execute([$karyawanId]);
    $kehadiran = (int) ($stmtKeh->fetchColumn() ?: 0);

    // Simpan total ke kinerjaData untuk tampilan
    $kinerjaData['total_kehadiran'] = $kehadiran;

    // Grupkan kehadiran per bulan untuk tahun berjalan (array bulan => jumlah)
    $stmtKehBulan = $conn->prepare('
        SELECT MONTH(tanggal) AS bulan, COUNT(*) AS total
        FROM absensi
        WHERE karyawan_id = ?
          AND jam_masuk IS NOT NULL
          AND jam_keluar IS NOT NULL
          AND YEAR(tanggal) = YEAR(CURDATE())
        GROUP BY MONTH(tanggal)
        ORDER BY MONTH(tanggal) ASC
    ');
    $stmtKehBulan->execute([$karyawanId]);
    $kehadiranPerBulan = [];
    while ($row = $stmtKehBulan->fetch(PDO::FETCH_ASSOC)) {
        $kehadiranPerBulan[(int)$row['bulan']] = (int)$row['total'];
    }
    $kinerjaData['kehadiran_per_bulan'] = $kehadiranPerBulan;
}


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
    // Estimasi Komisi: total komisi dari transaksi selesai sesuai filter
    $whereKomisi = getFilterWhere($filterType, $tanggalMulai, $tanggalSelesai, 'tanggal_selesai');
    $stmtKomisi = $conn->prepare('SELECT SUM(total_harga) as total FROM transaksi WHERE karyawan_id = ? AND status = "selesai" AND ' . $whereKomisi);
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

function formatTanggalWaktu($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00 00:00:00' || $tanggal == '-') return '-';
    return date('d M Y, H:i', strtotime($tanggal));
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kinerja - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <!-- <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div> -->

    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">

        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 pb-6 shadow-lg flex-shrink-0">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-white">Laporan Kinerja</h1>
                    <p id="active-filter-display" class="text-sm opacity-90 text-white"><?php echo getFilterText($filterType); ?></p>
                </div>
                <div class="flex space-x-2">
                    <button id="btn-filter-tanggal" type="button" class="p-2 bg-white/20 rounded-lg hover:bg-white/30"
                        aria-label="Filter tanggal">
                        <svg data-feather="calendar" class="w-6 h-6 text-white" aria-hidden="true"></svg>
                    </button>
                </div>
            </div>
        </header>

        <div class="flex-grow overflow-y-auto p-6 space-y-6 pb-24 no-scrollbar">

            <section class="bg-white rounded-lg shadow-md p-5 text-center">
                <h2 class="text-base font-semibold text-gray-800 mb-2">Gaji Pokok</h2>
                <p class="text-4xl font-bold text-blue-600 mb-4">
                    <?php echo number_format($gajiPokok, 0, ',', '.'); ?>
                </p>
                <div class="mt-4">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">Komisi</h3>
                    <p class="text-2xl font-bold text-green-600">
                        <?php echo number_format($estimasiKomisi, 0, ',', '.'); ?>
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-center border-t pt-4">
                    <div>
                        <p class="text-xl font-bold text-gray-900">
                            <?php echo $kilogram; ?> Kg
                        </p>
                        <span class="text-sm text-gray-500">Total Kiloan Selesai</span>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900">
                            <?php echo isset($kinerjaData['total_kehadiran']) ? $kinerjaData['total_kehadiran'] . ' Hari' : '0 Hari'; ?>
                        </p>
                        <span class="text-sm text-gray-500">Total Kehadiran</span>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Rincian Kinerja Selesai</h2>
                <div class="bg-white rounded-lg shadow p-4 divide-y divide-gray-100">
                    <?php if (!empty($kinerjaData['rincian'])) : ?>
                    <?php foreach ($kinerjaData['rincian'] as $rincian) : ?>
                    <div class="flex justify-between items-center py-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($rincian['nota']); ?> (<?php echo htmlspecialchars($rincian['nama_customer']); ?>)
                            </p>
                            <p class="text-xs text-gray-500">Selesai: <?php echo formatTanggalWaktu($rincian['tanggal_selesai']); ?></p>
                        </div>
                        <span class="text-sm font-medium text-green-600">
                            <?php echo isset($rincian['berat_selesai']) ? '+ ' . $rincian['berat_selesai'] . ' Kg' : ''; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    <?php else : ?>
                    <div class="py-3">
                        <p class="text-sm text-gray-500">Tidak ada rincian kinerja untuk bulan ini.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <?php
            // Ambil data absensi sesuai filter
            $absensiData = $absensiData ?? [];
            if (isset($karyawanId)) {
                $whereAbsensi = '';
                switch ($filterType) {
                    case 'hari_ini':
                        $whereAbsensi = "AND tanggal = CURDATE()";
                        break;
                    case '7_hari':
                        $whereAbsensi = "AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND tanggal <= CURDATE()";
                        break;
                    case 'bulan_ini':
                        $whereAbsensi = "AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
                        break;
                    case 'tahun_ini':
                        $whereAbsensi = "AND YEAR(tanggal) = YEAR(CURDATE())";
                        break;
                    case 'kustom':
                        if ($tanggalMulai && $tanggalSelesai) {
                            $whereAbsensi = "AND tanggal >= '" . addslashes($tanggalMulai) . "' AND tanggal <= '" . addslashes($tanggalSelesai) . "'";
                        }
                        break;
                    default:
                        $whereAbsensi = "AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())";
                }
                $stmtAbsensi = $conn->prepare('
                    SELECT tanggal, jam_masuk, jam_keluar
                    FROM absensi
                    WHERE karyawan_id = ? ' . $whereAbsensi . '
                    ORDER BY tanggal DESC, jam_masuk DESC
                ');
                $stmtAbsensi->execute([$karyawanId]);
                $absensiData = $stmtAbsensi->fetchAll(PDO::FETCH_ASSOC);
            }
            ?>

            <section>
            <h2 class="text-lg font-semibold text-gray-800 mb-3">Rincian Absensi</h2>
                <div class="bg-white rounded-lg shadow p-4 divide-y divide-gray-100">
                    <?php if (!empty($absensiData)) : ?>
                        <?php foreach ($absensiData as $absensi) : 
                            $tanggalDisplay = $absensi['tanggal'] && $absensi['tanggal'] != '0000-00-00' ? date('d M Y', strtotime($absensi['tanggal'])) : '-';
                            $jamMasukRaw = $absensi['jam_masuk'] ?: null;
                            $jamPulangRaw = $absensi['jam_keluar'] ?: null;
                            $jamMasuk = $jamMasukRaw ? date('H:i', strtotime($jamMasukRaw)) : '-';
                            $jamPulang = $jamPulangRaw ? date('H:i', strtotime($jamPulangRaw)) : '-';

                            // Tentukan terlambat: batas 08:00:00 (lebih dari 08:00 => terlambat)
                            $lateLabel = '<span class="text-sm text-gray-500">-</span>';
                            if ($jamMasukRaw) {
                                $cutoff = strtotime('08:00:00');
                                $masukTime = strtotime(date('H:i:s', strtotime($jamMasukRaw)));
                                if ($masukTime > $cutoff) {
                                    $lateMinutes = (int) floor(($masukTime - $cutoff) / 60);
                                    $lateLabel = '<span class="text-sm font-medium text-red-600">Terlambat +' . $lateMinutes . ' m</span>';
                                } else {
                                    $lateLabel = '<span class="text-sm font-medium text-green-600">Tepat Waktu</span>';
                                }
                            }
                        ?>
                        <div class="flex justify-between items-center py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($tanggalDisplay); ?></p>
                                <p class="text-xs text-gray-500"><?php echo 'Masuk: ' . htmlspecialchars($jamMasuk) . ' / Pulang: ' . htmlspecialchars($jamPulang); ?></p>
                            </div>
                            <div class="text-right">
                                <?php echo $lateLabel; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="py-3">
                            <p class="text-sm text-gray-500">Tidak ada rincian absensi untuk bulan ini.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </div>
    <nav
        class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-4 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">

        <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Beranda</span>
        </a>

        <a href="transaksi.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Transaksi</span>
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
    </body>
</html>