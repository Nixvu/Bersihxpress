<?php
require_once __DIR__ . '/middleware/auth_owner.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;

if (!$bisnisId) {
    http_response_code(403);
    exit('Akses ditolak');
}

// Get export parameters
$dataTypes = explode(',', $_GET['data'] ?? '');
$format = $_GET['format'] ?? 'pdf';
$filterType = $_GET['filter'] ?? 'bulan_ini';
$tanggalMulai = $_GET['tanggal_mulai'] ?? null;
$tanggalSelesai = $_GET['tanggal_selesai'] ?? null;

// Validate format
if (!in_array($format, ['pdf', 'csv'])) {
    http_response_code(400);
    exit('Format tidak valid');
}

// Set filter conditions
$whereClause = '';
$params = [$bisnisId];

switch ($filterType) {
    case 'hari_ini':
        $whereClause = ' AND DATE(t.created_at) = CURDATE()';
        break;
    case '7_hari':
        $whereClause = ' AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
        break;
    case 'bulan_ini':
        $whereClause = ' AND YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())';
        break;
    case 'tahun_ini':
        $whereClause = ' AND YEAR(t.created_at) = YEAR(CURDATE())';
        break;
    case 'kustom':
        if ($tanggalMulai && $tanggalSelesai) {
            $whereClause = ' AND DATE(t.created_at) BETWEEN ? AND ?';
            $params[] = $tanggalMulai;
            $params[] = $tanggalSelesai;
        }
        break;
}

// Generate filename
$timestamp = date('Y-m-d_H-i-s');
$filename = "laporan_bisnis_{$timestamp}";

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    
    $output = fopen('php://output', 'w');
    
    // CSV Header
    fputcsv($output, ['=== LAPORAN BISNIS ===']);
    fputcsv($output, ['Bisnis: ' . ($ownerData['nama_bisnis'] ?? 'N/A')]);
    fputcsv($output, ['Periode: ' . getFilterText($filterType, $tanggalMulai, $tanggalSelesai)]);
    fputcsv($output, ['Diekspor pada: ' . date('d/m/Y H:i:s')]);
    fputcsv($output, []); // Empty row
    
    foreach ($dataTypes as $dataType) {
        switch ($dataType) {
            case 'pendapatan':
                exportPendapatanCSV($output, $conn, $params, $whereClause);
                break;
            case 'pengeluaran':
                exportPengeluaranCSV($output, $conn, $params, $whereClause);
                break;
            case 'pelanggan':
                exportPelangganCSV($output, $conn, $bisnisId);
                break;
            case 'kinerja':
                exportKinerjaCSV($output, $conn, $bisnisId, $whereClause, $params);
                break;
        }
        fputcsv($output, []); // Empty row between sections
    }
    
    fclose($output);
    
} else {
    // PDF Export (Simple HTML to PDF)
    header('Content-Type: application/pdf');
    header("Content-Disposition: attachment; filename=\"{$filename}.pdf\"");
    
    // Start HTML output
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Bisnis</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; page-break-inside: avoid; }
        .section h2 { color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f3f4f6; }
        .summary { background-color: #f9fafb; padding: 15px; border-radius: 5px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN BISNIS</h1>
        <p><strong>' . htmlspecialchars($ownerData['nama_bisnis'] ?? 'N/A') . '</strong></p>
        <p>Periode: ' . getFilterText($filterType, $tanggalMulai, $tanggalSelesai) . '</p>
        <p>Diekspor pada: ' . date('d/m/Y H:i:s') . '</p>
    </div>';
    
    foreach ($dataTypes as $dataType) {
        switch ($dataType) {
            case 'pendapatan':
                exportPendapatanPDF($conn, $params, $whereClause);
                break;
            case 'pengeluaran':
                exportPengeluaranPDF($conn, $params, $whereClause);
                break;
            case 'pelanggan':
                exportPelangganPDF($conn, $bisnisId);
                break;
            case 'kinerja':
                exportKinerjaPDF($conn, $bisnisId, $whereClause, $params);
                break;
        }
    }
    
    echo '</body></html>';
}

function getFilterText($filterType, $tanggalMulai = null, $tanggalSelesai = null) {
    switch ($filterType) {
        case 'hari_ini': return 'Hari Ini';
        case '7_hari': return '7 Hari Terakhir';
        case 'bulan_ini': return 'Bulan Ini';
        case 'tahun_ini': return 'Tahun Ini';
        case 'kustom': 
            return $tanggalMulai && $tanggalSelesai ? 
                date('d/m/Y', strtotime($tanggalMulai)) . ' - ' . date('d/m/Y', strtotime($tanggalSelesai)) : 
                'Rentang Kustom';
        default: return 'Bulan Ini';
    }
}

function exportPendapatanCSV($output, $conn, $params, $whereClause) {
    fputcsv($output, ['=== LAPORAN PENDAPATAN ===']);
    
    // Summary
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total_harga), 0) as total_pendapatan
        FROM transaksi t WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause
    );
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    fputcsv($output, ['Total Transaksi', $summary['total_transaksi']]);
    fputcsv($output, ['Total Pendapatan', 'Rp ' . number_format($summary['total_pendapatan'], 0, ',', '.')]);
    fputcsv($output, []);
    
    // Detail
    fputcsv($output, ['No Nota', 'Nama Pelanggan', 'Total Harga', 'Status', 'Tanggal']);
    
    $stmt = $conn->prepare("
        SELECT t.no_nota, COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan, 
               t.total_harga, t.status, t.created_at
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause . "
        ORDER BY t.created_at DESC
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['no_nota'],
            $row['nama_pelanggan'],
            'Rp ' . number_format($row['total_harga'], 0, ',', '.'),
            ucfirst($row['status']),
            date('d/m/Y H:i', strtotime($row['created_at']))
        ]);
    }
}

function exportPendapatanPDF($conn, $params, $whereClause) {
    echo '<div class="section">';
    echo '<h2>LAPORAN PENDAPATAN</h2>';
    
    // Summary
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_transaksi, COALESCE(SUM(total_harga), 0) as total_pendapatan
        FROM transaksi t WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause
    );
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    echo '<div class="summary">';
    echo '<p><strong>Total Transaksi:</strong> ' . number_format($summary['total_transaksi']) . '</p>';
    echo '<p><strong>Total Pendapatan:</strong> Rp ' . number_format($summary['total_pendapatan'], 0, ',', '.') . '</p>';
    echo '</div>';
    
    // Detail table
    echo '<table>';
    echo '<thead><tr><th>No Nota</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Tanggal</th></tr></thead>';
    echo '<tbody>';
    
    $stmt = $conn->prepare("
        SELECT t.no_nota, COALESCE(p.nama, 'Tanpa Nama') as nama_pelanggan, 
               t.total_harga, t.status, t.created_at
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.bisnis_id = ? AND t.status != 'batal'" . $whereClause . "
        ORDER BY t.created_at DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['no_nota']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nama_pelanggan']) . '</td>';
        echo '<td class="text-right">Rp ' . number_format($row['total_harga'], 0, ',', '.') . '</td>';
        echo '<td>' . ucfirst($row['status']) . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($row['created_at'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

function exportPengeluaranCSV($output, $conn, $params, $whereClause) {
    fputcsv($output, ['=== LAPORAN PENGELUARAN ===']);
    
    // Summary
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_item, COALESCE(SUM(jumlah), 0) as total_pengeluaran
        FROM pengeluaran WHERE bisnis_id = ?" . str_replace('t.created_at', 'created_at', $whereClause)
    );
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    fputcsv($output, ['Total Item', $summary['total_item']]);
    fputcsv($output, ['Total Pengeluaran', 'Rp ' . number_format($summary['total_pengeluaran'], 0, ',', '.')]);
    fputcsv($output, []);
    
    // Detail
    fputcsv($output, ['Keterangan', 'Kategori', 'Jumlah', 'Tanggal']);
    
    $stmt = $conn->prepare("
        SELECT keterangan, kategori, jumlah, tanggal
        FROM pengeluaran 
        WHERE bisnis_id = ?" . str_replace('t.created_at', 'created_at', $whereClause) . "
        ORDER BY tanggal DESC
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['keterangan'],
            ucfirst($row['kategori']),
            'Rp ' . number_format($row['jumlah'], 0, ',', '.'),
            date('d/m/Y', strtotime($row['tanggal']))
        ]);
    }
}

function exportPengeluaranPDF($conn, $params, $whereClause) {
    echo '<div class="section">';
    echo '<h2>LAPORAN PENGELUARAN</h2>';
    
    // Summary
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total_item, COALESCE(SUM(jumlah), 0) as total_pengeluaran
        FROM pengeluaran WHERE bisnis_id = ?" . str_replace('t.created_at', 'created_at', $whereClause)
    );
    $stmt->execute($params);
    $summary = $stmt->fetch();
    
    echo '<div class="summary">';
    echo '<p><strong>Total Item:</strong> ' . number_format($summary['total_item']) . '</p>';
    echo '<p><strong>Total Pengeluaran:</strong> Rp ' . number_format($summary['total_pengeluaran'], 0, ',', '.') . '</p>';
    echo '</div>';
    
    // Detail table
    echo '<table>';
    echo '<thead><tr><th>Keterangan</th><th>Kategori</th><th>Jumlah</th><th>Tanggal</th></tr></thead>';
    echo '<tbody>';
    
    $stmt = $conn->prepare("
        SELECT keterangan, kategori, jumlah, tanggal
        FROM pengeluaran 
        WHERE bisnis_id = ?" . str_replace('t.created_at', 'created_at', $whereClause) . "
        ORDER BY tanggal DESC
        LIMIT 50
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['keterangan']) . '</td>';
        echo '<td>' . ucfirst($row['kategori']) . '</td>';
        echo '<td class="text-right">Rp ' . number_format($row['jumlah'], 0, ',', '.') . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($row['tanggal'])) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

function exportPelangganCSV($output, $conn, $bisnisId) {
    fputcsv($output, ['=== DATA PELANGGAN ===']);
    fputcsv($output, ['Nama', 'No Telepon', 'Email', 'Alamat', 'Total Transaksi', 'Total Belanja', 'Bergabung']);
    
    $stmt = $conn->prepare("
        SELECT p.nama, p.no_telepon, p.email, p.alamat, p.created_at,
               COUNT(t.transaksi_id) as total_transaksi,
               COALESCE(SUM(t.total_harga), 0) as total_belanja
        FROM pelanggan p
        LEFT JOIN transaksi t ON p.pelanggan_id = t.pelanggan_id AND t.status != 'batal'
        WHERE p.bisnis_id = ?
        GROUP BY p.pelanggan_id
        ORDER BY total_belanja DESC
    ");
    $stmt->execute([$bisnisId]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['nama'],
            $row['no_telepon'] ?: '-',
            $row['email'] ?: '-',
            $row['alamat'] ?: '-',
            $row['total_transaksi'],
            'Rp ' . number_format($row['total_belanja'], 0, ',', '.'),
            date('d/m/Y', strtotime($row['created_at']))
        ]);
    }
}

function exportPelangganPDF($conn, $bisnisId) {
    echo '<div class="section">';
    echo '<h2>DATA PELANGGAN</h2>';
    
    echo '<table>';
    echo '<thead><tr><th>Nama</th><th>Kontak</th><th>Total Transaksi</th><th>Total Belanja</th></tr></thead>';
    echo '<tbody>';
    
    $stmt = $conn->prepare("
        SELECT p.nama, p.no_telepon, p.email,
               COUNT(t.transaksi_id) as total_transaksi,
               COALESCE(SUM(t.total_harga), 0) as total_belanja
        FROM pelanggan p
        LEFT JOIN transaksi t ON p.pelanggan_id = t.pelanggan_id AND t.status != 'batal'
        WHERE p.bisnis_id = ?
        GROUP BY p.pelanggan_id
        ORDER BY total_belanja DESC
        LIMIT 50
    ");
    $stmt->execute([$bisnisId]);
    
    while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['nama']) . '</td>';
        echo '<td>' . htmlspecialchars($row['no_telepon'] ?: $row['email'] ?: '-') . '</td>';
        echo '<td class="text-right">' . $row['total_transaksi'] . '</td>';
        echo '<td class="text-right">Rp ' . number_format($row['total_belanja'], 0, ',', '.') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

function exportKinerjaCSV($output, $conn, $bisnisId, $whereClause, $params) {
    fputcsv($output, ['=== KINERJA KARYAWAN ===']);
    fputcsv($output, ['Nama Karyawan', 'Transaksi Selesai', 'Status']);
    
    $stmt = $conn->prepare("
        SELECT u.nama_lengkap, COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as transaksi_selesai, k.status
        FROM karyawan k
        JOIN users u ON k.user_id = u.user_id
        LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id" . str_replace('t.bisnis_id', 't.bisnis_id', $whereClause) . "
        WHERE k.bisnis_id = ?
        GROUP BY k.karyawan_id, u.nama_lengkap, k.status
        ORDER BY transaksi_selesai DESC
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['nama_lengkap'],
            $row['transaksi_selesai'],
            ucfirst($row['status'])
        ]);
    }
}

function exportKinerjaPDF($conn, $bisnisId, $whereClause, $params) {
    echo '<div class="section">';
    echo '<h2>KINERJA KARYAWAN</h2>';
    
    echo '<table>';
    echo '<thead><tr><th>Nama Karyawan</th><th>Transaksi Selesai</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    
    $stmt = $conn->prepare("
        SELECT u.nama_lengkap, COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as transaksi_selesai, k.status
        FROM karyawan k
        JOIN users u ON k.user_id = u.user_id
        LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id" . str_replace('t.bisnis_id', 't.bisnis_id', $whereClause) . "
        WHERE k.bisnis_id = ?
        GROUP BY k.karyawan_id, u.nama_lengkap, k.status
        ORDER BY transaksi_selesai DESC
    ");
    $stmt->execute($params);
    
    while ($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['nama_lengkap']) . '</td>';
        echo '<td class="text-right">' . $row['transaksi_selesai'] . '</td>';
        echo '<td>' . ucfirst($row['status']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}
?>