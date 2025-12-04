<?php

session_start();
require_once '../../apps/owner/query-laporan.php';

// Suppress PHP notices and warnings
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? 1;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

// Set periode laporan selalu dari tanggal 1 sampai akhir bulan ini (sama dengan export_pdf.php)
$tanggalMulai = date('Y-m-01');
$tanggalSelesai = date('Y-m-t');
$jenis = $_GET['jenis'] ?? 'semua';

$laporanQuery = new LaporanQuery($bisnisId);
$data = $laporanQuery->getAllData('kustom', $tanggalMulai, $tanggalSelesai);

$periodeLabel = date('d F Y', strtotime($tanggalMulai)) . ' s/d ' . date('d F Y', strtotime($tanggalSelesai));

// Set headers untuk download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Laporan_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $bisnisNama) . '_' . date('Y_m') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

// Tulis BOM untuk UTF-8 agar Excel bisa membaca karakter Indonesia
fwrite($output, "\xEF\xBB\xBF");

// Header laporan
fputcsv($output, ['LAPORAN BISNIS - ' . strtoupper($bisnisNama)]);
fputcsv($output, ['Periode: ' . $periodeLabel]);
fputcsv($output, ['Tanggal Ekspor: ' . date('d F Y, H:i') . ' WIB']);
fputcsv($output, []); // Baris kosong

// Cek checklist untuk setiap bagian
if (isset($_GET['export_pendapatan'])) {
    $pendapatan = $data['pendapatan'];
    fputcsv($output, ['LAPORAN PENDAPATAN']);
    fputcsv($output, ['Metrik', 'Nilai']);
    fputcsv($output, ['Total Transaksi', number_format($pendapatan['total_transaksi'])]);
    fputcsv($output, ['Total Pendapatan', 'Rp ' . number_format($pendapatan['total_pendapatan'], 0, ',', '.')]);
    fputcsv($output, []); // Baris kosong
}

if (isset($_GET['export_pengeluaran'])) {
    $pengeluaran = $data['pengeluaran'];
    fputcsv($output, ['LAPORAN PENGELUARAN']);
    fputcsv($output, ['Metrik', 'Nilai']);
    fputcsv($output, ['Total Pengeluaran', 'Rp ' . number_format($pengeluaran['total_pengeluaran'], 0, ',', '.')]);
    fputcsv($output, []); // Baris kosong
}

if (isset($_GET['export_pelanggan'])) {
    $pelanggan = $data['pelanggan'];
    fputcsv($output, ['DATA PELANGGAN']);
    fputcsv($output, ['Metrik', 'Nilai']);
    fputcsv($output, ['Total Pelanggan', number_format($pelanggan['total_pelanggan'])]);
    fputcsv($output, ['Pelanggan Baru (30 Hari)', number_format($pelanggan['pelanggan_baru'])]);
    fputcsv($output, []); // Baris kosong
}

if (isset($_GET['export_kinerja'])) {
    $kinerja = $data['kinerja_karyawan'];
    fputcsv($output, ['KINERJA KARYAWAN']);
    
    if (!empty($kinerja)) {
        fputcsv($output, ['Nama Karyawan', 'Transaksi Selesai', 'Persentase']);
        
        $totalTransaksi = array_sum(array_column($kinerja, 'transaksi_selesai'));
        
        foreach ($kinerja as $row) {
            $persentase = $totalTransaksi > 0 ? round(($row['transaksi_selesai'] / $totalTransaksi) * 100, 1) : 0;
            fputcsv($output, [
                $row['nama_lengkap'],
                number_format($row['transaksi_selesai']),
                $persentase . '%'
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data kinerja karyawan tersedia untuk periode ini.']);
    }
    fputcsv($output, []); // Baris kosong
}

// Footer laporan
fputcsv($output, []);
fputcsv($output, ['---']);
fputcsv($output, ['Laporan ini dibuat secara otomatis oleh sistem BersihXpress']);
fputcsv($output, ['Copyright ' . date('Y') . ' ' . $bisnisNama . ' - Semua hak dilindungi']);

fclose($output);
exit;
