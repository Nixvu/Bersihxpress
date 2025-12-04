<?php
require_once __DIR__ . '/../middleware/auth_owner.php';
require_once __DIR__ . '/../query-laporan.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log the request
error_log('Export API called: ' . date('Y-m-d H:i:s'));
error_log('Request method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Request data: ' . file_get_contents('php://input'));

try {
    $ownerData = $_SESSION['owner_data'] ?? [];
    $bisnisId = $ownerData['bisnis_id'] ?? null;
    $bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

    if (!$bisnisId) {
        throw new Exception('Data bisnis tidak ditemukan');
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method !== 'POST') {
        throw new Exception('Method tidak valid');
    }

    // Ambil data dari POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    $format = $input['format'] ?? 'pdf';
    $exportData = $input['exportData'] ?? [];
    $filterType = $input['filterType'] ?? 'bulan_ini';
    $tanggalMulai = $input['tanggalMulai'] ?? null;
    $tanggalSelesai = $input['tanggalSelesai'] ?? null;

    if (empty($exportData)) {
        throw new Exception('Pilih minimal satu data untuk diekspor');
    }

    // Inisialisasi query class
    $laporanQuery = new LaporanQuery($bisnisId);
    $data = $laporanQuery->getAllData($filterType, $tanggalMulai, $tanggalSelesai);
    
    // Tambahkan bisnis_id ke data untuk kompatibilitas
    $data['bisnis_id'] = $bisnisId;

    // Generate file berdasarkan format
    if ($format === 'csv') {
        $filename = generateCSV($data, $exportData, $bisnisNama, $filterType);
    } else {
        $filename = generatePDF($data, $exportData, $bisnisNama, $filterType);
    }

    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'message' => 'File berhasil dibuat'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function generateCSV($data, $exportData, $bisnisNama, $filterType) {
    $filename = 'laporan_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = __DIR__ . '/../../../exports/' . $filename;
    
    // Buat direktori exports jika belum ada
    if (!is_dir(__DIR__ . '/../../../exports/')) {
        mkdir(__DIR__ . '/../../../exports/', 0755, true);
    }
    
    $file = fopen($filepath, 'w');
    
    // Header file
    fwrite($file, "\xEF\xBB\xBF"); // UTF-8 BOM untuk Excel
    
    // Info bisnis
    fputcsv($file, ["Laporan Bisnis - $bisnisNama"], ';');
    fputcsv($file, ["Tanggal Export: " . date('d/m/Y H:i:s')], ';');
    fputcsv($file, ["Filter: " . getFilterText($filterType)], ';');
    fputcsv($file, [""], ';'); // Baris kosong
    
    // Export data sesuai pilihan
    if (in_array('export_pendapatan', $exportData)) {
        exportPendapatanCSV($file, $data);
        fputcsv($file, [""], ';'); // Baris kosong
    }
    
    if (in_array('export_pengeluaran', $exportData)) {
        exportPengeluaranCSV($file, $data);
        fputcsv($file, [""], ';'); // Baris kosong
    }
    
    if (in_array('export_pelanggan', $exportData)) {
        exportPelangganCSV($file, $data);
        fputcsv($file, [""], ';'); // Baris kosong
    }
    
    if (in_array('export_kinerja', $exportData)) {
        exportKinerjaCSV($file, $data);
    }
    
    fclose($file);
    return $filename;
}

function generatePDF($data, $exportData, $bisnisNama, $filterType) {
    // Untuk PDF, kita akan menggunakan HTML to PDF sederhana
    $filename = 'laporan_' . date('Y-m-d_H-i-s') . '.html';
    $filepath = __DIR__ . '/../../../exports/' . $filename;
    
    // Buat direktori exports jika belum ada
    if (!is_dir(__DIR__ . '/../../../exports/')) {
        mkdir(__DIR__ . '/../../../exports/', 0755, true);
    }
    
    $html = generatePDFHTML($data, $exportData, $bisnisNama, $filterType);
    file_put_contents($filepath, $html);
    
    return $filename;
}

function exportPendapatanCSV($file, $data) {
    fputcsv($file, ["=== LAPORAN PENDAPATAN ==="], ';');
    fputcsv($file, [""], ';');
    
    // Summary pendapatan
    fputcsv($file, ["Ringkasan Pendapatan"], ';');
    fputcsv($file, ["Total Transaksi", $data['pendapatan']['total_transaksi']], ';');
    fputcsv($file, ["Total Pendapatan", "Rp " . number_format($data['pendapatan']['total_pendapatan'], 0, ',', '.')], ';');
    fputcsv($file, [""], ';');
    
    // Layanan terlaris
    if (!empty($data['layanan_terlaris'])) {
        fputcsv($file, ["Layanan Terlaris"], ';');
        fputcsv($file, ["Ranking", "Nama Layanan", "Jumlah Transaksi"], ';');
        foreach ($data['layanan_terlaris'] as $index => $layanan) {
            fputcsv($file, [
                $index + 1,
                $layanan['nama_layanan'],
                $layanan['jumlah_transaksi']
            ], ';');
        }
        fputcsv($file, [""], ';');
    }
    
    // Transaksi hari ini
    $laporanQuery = new LaporanQuery($data['bisnis_id'] ?? null);
    $transaksiHariIni = $laporanQuery->getTransaksiHariIni(50); // Ambil semua untuk export
    
    if (!empty($transaksiHariIni)) {
        fputcsv($file, ["Transaksi Hari Ini"], ';');
        fputcsv($file, ["No Nota", "Nama Pelanggan", "Total Harga", "Status", "Waktu Masuk"], ';');
        foreach ($transaksiHariIni as $transaksi) {
            fputcsv($file, [
                $transaksi['no_nota'],
                $transaksi['nama_pelanggan'],
                "Rp " . number_format($transaksi['total_harga'], 0, ',', '.'),
                ucfirst($transaksi['status']),
                date('d/m/Y H:i', strtotime($transaksi['created_at']))
            ], ';');
        }
    }
}

function exportPengeluaranCSV($file, $data) {
    fputcsv($file, ["=== LAPORAN PENGELUARAN ==="], ';');
    fputcsv($file, [""], ';');
    
    // Summary pengeluaran
    fputcsv($file, ["Ringkasan Pengeluaran"], ';');
    fputcsv($file, ["Total Item", $data['pengeluaran']['total_item']], ';');
    fputcsv($file, ["Total Pengeluaran", "Rp " . number_format($data['pengeluaran']['total_pengeluaran'], 0, ',', '.')], ';');
    fputcsv($file, [""], ';');
    
    // Kategori pengeluaran
    if (!empty($data['kategori_pengeluaran'])) {
        fputcsv($file, ["Kategori Pengeluaran"], ';');
        fputcsv($file, ["Kategori", "Total Pengeluaran", "Persentase"], ';');
        $totalPengeluaran = array_sum(array_column($data['kategori_pengeluaran'], 'total'));
        foreach ($data['kategori_pengeluaran'] as $kategori) {
            $percentage = $totalPengeluaran > 0 ? round(($kategori['total'] / $totalPengeluaran) * 100) : 0;
            fputcsv($file, [
                ucfirst($kategori['kategori']),
                "Rp " . number_format($kategori['total'], 0, ',', '.'),
                $percentage . "%"
            ], ';');
        }
        fputcsv($file, [""], ';');
    }
    
    // Pengeluaran terbaru
    if (!empty($data['pengeluaran_terbaru'])) {
        fputcsv($file, ["Pengeluaran Terbaru"], ';');
        fputcsv($file, ["Tanggal", "Keterangan", "Jumlah"], ';');
        foreach ($data['pengeluaran_terbaru'] as $pengeluaran) {
            fputcsv($file, [
                date('d/m/Y', strtotime($pengeluaran['tanggal'])),
                $pengeluaran['keterangan'],
                "Rp " . number_format($pengeluaran['jumlah'], 0, ',', '.')
            ], ';');
        }
    }
}

function exportPelangganCSV($file, $data) {
    fputcsv($file, ["=== DATA PELANGGAN ==="], ';');
    fputcsv($file, [""], ';');
    
    // Summary pelanggan
    fputcsv($file, ["Ringkasan Pelanggan"], ';');
    fputcsv($file, ["Total Pelanggan", $data['pelanggan']['total_pelanggan']], ';');
    fputcsv($file, ["Pelanggan Baru", $data['pelanggan']['pelanggan_baru']], ';');
    fputcsv($file, ["Pelanggan Lama", $data['pelanggan']['total_pelanggan'] - $data['pelanggan']['pelanggan_baru']], ';');
    fputcsv($file, ["Rata-rata Belanja", "Rp " . number_format($data['pelanggan']['rata_rata_belanja'], 0, ',', '.')], ';');
    fputcsv($file, [""], ';');
    
    // Pelanggan teratas
    if (!empty($data['pelanggan_teratas'])) {
        fputcsv($file, ["Pelanggan Teratas"], ';');
        fputcsv($file, ["Ranking", "Nama Pelanggan", "Total Belanja"], ';');
        foreach ($data['pelanggan_teratas'] as $index => $pelanggan) {
            fputcsv($file, [
                $index + 1,
                $pelanggan['nama'],
                "Rp " . number_format($pelanggan['total_belanja'], 0, ',', '.')
            ], ';');
        }
    }
}

function exportKinerjaCSV($file, $data) {
    fputcsv($file, ["=== DATA KINERJA KARYAWAN ==="], ';');
    fputcsv($file, [""], ';');
    
    // Summary karyawan
    fputcsv($file, ["Ringkasan Karyawan"], ';');
    fputcsv($file, ["Total Karyawan", $data['karyawan']['total_karyawan']], ';');
    fputcsv($file, ["Rata-rata Transaksi per Hari", number_format($data['karyawan']['rata_rata_harian'], 1)], ';');
    fputcsv($file, [""], ';');
    
    // Kinerja karyawan
    if (!empty($data['kinerja_karyawan'])) {
        fputcsv($file, ["Kinerja Karyawan"], ';');
        fputcsv($file, ["Nama Karyawan", "Transaksi Selesai"], ';');
        foreach ($data['kinerja_karyawan'] as $karyawan) {
            fputcsv($file, [
                $karyawan['nama_lengkap'],
                $karyawan['transaksi_selesai']
            ], ';');
        }
    }
}

function generatePDFHTML($data, $exportData, $bisnisNama, $filterType) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Laporan Bisnis - <?php echo htmlspecialchars($bisnisNama); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
            .section { margin-bottom: 30px; }
            .section h2 { color: #333; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .summary { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
            .text-right { text-align: right; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Laporan Bisnis</h1>
            <h2><?php echo htmlspecialchars($bisnisNama); ?></h2>
            <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
            <p><?php echo getFilterText($filterType); ?></p>
        </div>
        
        <?php if (in_array('export_pendapatan', $exportData)): ?>
        <div class="section">
            <h2>Laporan Pendapatan</h2>
            <div class="summary">
                <p><strong>Total Transaksi:</strong> <?php echo number_format($data['pendapatan']['total_transaksi']); ?></p>
                <p><strong>Total Pendapatan:</strong> Rp <?php echo number_format($data['pendapatan']['total_pendapatan'], 0, ',', '.'); ?></p>
            </div>
            
            <?php if (!empty($data['layanan_terlaris'])): ?>
            <h3>Layanan Terlaris</h3>
            <table>
                <thead>
                    <tr><th>Ranking</th><th>Nama Layanan</th><th>Jumlah Transaksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($data['layanan_terlaris'] as $index => $layanan): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($layanan['nama_layanan']); ?></td>
                        <td><?php echo number_format($layanan['jumlah_transaksi']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('export_pengeluaran', $exportData)): ?>
        <div class="section">
            <h2>Laporan Pengeluaran</h2>
            <div class="summary">
                <p><strong>Total Item:</strong> <?php echo number_format($data['pengeluaran']['total_item']); ?></p>
                <p><strong>Total Pengeluaran:</strong> Rp <?php echo number_format($data['pengeluaran']['total_pengeluaran'], 0, ',', '.'); ?></p>
            </div>
            
            <?php if (!empty($data['kategori_pengeluaran'])): ?>
            <h3>Kategori Pengeluaran</h3>
            <table>
                <thead>
                    <tr><th>Kategori</th><th>Total Pengeluaran</th><th>Persentase</th></tr>
                </thead>
                <tbody>
                    <?php 
                    $totalPengeluaran = array_sum(array_column($data['kategori_pengeluaran'], 'total'));
                    foreach ($data['kategori_pengeluaran'] as $kategori): 
                        $percentage = $totalPengeluaran > 0 ? round(($kategori['total'] / $totalPengeluaran) * 100) : 0;
                    ?>
                    <tr>
                        <td><?php echo ucfirst($kategori['kategori']); ?></td>
                        <td class="text-right">Rp <?php echo number_format($kategori['total'], 0, ',', '.'); ?></td>
                        <td class="text-right"><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('export_pelanggan', $exportData)): ?>
        <div class="section">
            <h2>Data Pelanggan</h2>
            <div class="summary">
                <p><strong>Total Pelanggan:</strong> <?php echo number_format($data['pelanggan']['total_pelanggan']); ?></p>
                <p><strong>Pelanggan Baru:</strong> <?php echo number_format($data['pelanggan']['pelanggan_baru']); ?></p>
                <p><strong>Rata-rata Belanja:</strong> Rp <?php echo number_format($data['pelanggan']['rata_rata_belanja'], 0, ',', '.'); ?></p>
            </div>
            
            <?php if (!empty($data['pelanggan_teratas'])): ?>
            <h3>Pelanggan Teratas</h3>
            <table>
                <thead>
                    <tr><th>Ranking</th><th>Nama Pelanggan</th><th>Total Belanja</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($data['pelanggan_teratas'] as $index => $pelanggan): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($pelanggan['nama']); ?></td>
                        <td class="text-right">Rp <?php echo number_format($pelanggan['total_belanja'], 0, ',', '.'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (in_array('export_kinerja', $exportData)): ?>
        <div class="section">
            <h2>Data Kinerja Karyawan</h2>
            <div class="summary">
                <p><strong>Total Karyawan:</strong> <?php echo number_format($data['karyawan']['total_karyawan']); ?></p>
                <p><strong>Rata-rata Transaksi per Hari:</strong> <?php echo number_format($data['karyawan']['rata_rata_harian'], 1); ?></p>
            </div>
            
            <?php if (!empty($data['kinerja_karyawan'])): ?>
            <h3>Kinerja Karyawan</h3>
            <table>
                <thead>
                    <tr><th>Nama Karyawan</th><th>Transaksi Selesai</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($data['kinerja_karyawan'] as $karyawan): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($karyawan['nama_lengkap']); ?></td>
                        <td class="text-right"><?php echo number_format($karyawan['transaksi_selesai']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="section no-print" style="text-align: center; margin-top: 50px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Cetak / Simpan sebagai PDF
            </button>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
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