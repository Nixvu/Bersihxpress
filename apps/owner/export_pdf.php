<?php

session_start();
require_once '../../vendor/autoload.php';
require_once '../../apps/owner/query-laporan.php';

use Mpdf\Mpdf;

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? 1;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';


// Set periode laporan selalu dari tanggal 1 sampai akhir bulan ini
$tanggalMulai = date('Y-m-01');
$tanggalSelesai = date('Y-m-t');
$jenis = $_GET['jenis'] ?? 'semua';

$periodeLabel = date('d F Y', strtotime($tanggalMulai)) . ' s/d ' . date('d F Y', strtotime($tanggalSelesai));

$laporanQuery = new LaporanQuery($bisnisId);

$data = $laporanQuery->getAllData('kustom', $tanggalMulai, $tanggalSelesai);

// CSS Styling untuk PDF
$css = "
<style>
body {
    font-family: 'DejaVu Sans', sans-serif;
    line-height: 1.6;
    color: #333;
    margin: 0;
    padding: 20px;
}
.header {
    text-align: center;
    border-bottom: 3px solid #2563eb;
    padding-bottom: 20px;
    margin-bottom: 30px;
}
.header h1 {
    color: #1e40af;
    font-size: 24px;
    font-weight: bold;
    margin: 0 0 10px 0;
}
.header .periode {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}
.section {
    margin-bottom: 30px;
    break-inside: avoid;
}
.section h2 {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    padding: 12px 20px;
    margin: 0 0 15px 0;
    font-size: 18px;
    border-radius: 8px;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}
.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: #1e40af;
    margin-bottom: 5px;
}
.stat-label {
    color: #6b7280;
    font-size: 12px;
}
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.table th {
    background: #f1f5f9;
    color: #374151;
    font-weight: bold;
    padding: 12px 15px;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
}
.table td {
    padding: 10px 15px;
    border-bottom: 1px solid #f3f4f6;
}
.table tr:nth-child(even) {
    background: #f9fafb;
}
.currency {
    color: #059669;
    font-weight: bold;
}
.footer {
    margin-top: 40px;
    padding-top: 20px;
    border-top: 2px solid #e5e7eb;
    text-align: center;
    color: #6b7280;
    font-size: 12px;
}
.no-data {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 20px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px dashed #d1d5db;
}
</style>
";

$html = $css;
// Header dengan label periode

$html .= "<div class='header'>";
$html .= "<h1>LAPORAN BISNIS</h1>";
$html .= "<h2 style='color: #1e40af; margin: 5px 0;'>{$bisnisNama}</h2>";
$html .= "<p class='periode'>Periode: {$periodeLabel}</p>";
$html .= "<p style='color: #6b7280; font-size: 12px; margin-top: 10px;'>Tanggal Ekspor: ".date('d F Y, H:i')." WIB</p>";
$html .= "</div>";

// Cek checklist untuk setiap bagian
if (isset($_GET['export_pendapatan'])) {
    $pendapatan = $data['pendapatan'];
    $html .= "<div class='section'>";
    $html .= "<h2>Laporan Pendapatan</h2>";
    $html .= "<div class='stats-grid'>";
    $html .= "<div class='stat-card'>";
    $html .= "<div class='stat-value'>".number_format($pendapatan['total_transaksi'])."</div>";
    $html .= "<div class='stat-label'>Total Transaksi</div>";
    $html .= "</div>";
    $html .= "<div class='stat-card'>";
    $html .= "<div class='stat-value currency'>Rp ".number_format($pendapatan['total_pendapatan'],0,',','.')."</div>";
    $html .= "<div class='stat-label'>Total Pendapatan</div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
}

if (isset($_GET['export_pengeluaran'])) {
    $pengeluaran = $data['pengeluaran'];
    $html .= "<div class='section'>";
    $html .= "<h2>Laporan Pengeluaran</h2>";
    $html .= "<div class='stats-grid'>";
    $html .= "<div class='stat-card'>";
    $html .= "<div class='stat-value currency'>Rp ".number_format($pengeluaran['total_pengeluaran'],0,',','.')."</div>";
    $html .= "<div class='stat-label'>Total Pengeluaran</div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
}

if (isset($_GET['export_pelanggan'])) {
    $pelanggan = $data['pelanggan'];
    $html .= "<div class='section'>";
    $html .= "<h2>Data Pelanggan</h2>";
    $html .= "<div class='stats-grid'>";
    $html .= "<div class='stat-card'>";
    $html .= "<div class='stat-value'>".number_format($pelanggan['total_pelanggan'])."</div>";
    $html .= "<div class='stat-label'>Total Pelanggan</div>";
    $html .= "</div>";
    $html .= "<div class='stat-card'>";
    $html .= "<div class='stat-value'>".number_format($pelanggan['pelanggan_baru'])."</div>";
    $html .= "<div class='stat-label'>Pelanggan Baru (30 Hari)</div>";
    $html .= "</div>";
    $html .= "</div>";
    $html .= "</div>";
}

if (isset($_GET['export_kinerja'])) {
    $kinerja = $data['kinerja_karyawan'];
    $html .= "<div class='section'>";
    $html .= "<h2>Kinerja Karyawan</h2>";
    if (!empty($kinerja)) {
        $html .= "<table class='table'>";
        $html .= "<thead><tr><th>Nama Karyawan</th><th style='text-align: center;'>Transaksi Selesai</th><th style='text-align: center;'>Persentase</th></tr></thead><tbody>";
        
        $totalTransaksi = array_sum(array_column($kinerja, 'transaksi_selesai'));
        $maxTransaksi = max(array_column($kinerja, 'transaksi_selesai'));
        
        foreach ($kinerja as $row) {
            $persentase = $totalTransaksi > 0 ? round(($row['transaksi_selesai'] / $totalTransaksi) * 100, 1) : 0;
            $html .= "<tr>";
            $html .= "<td>".htmlspecialchars($row['nama_lengkap'])."</td>";
            $html .= "<td style='text-align: center; font-weight: bold;'>".number_format($row['transaksi_selesai'])."</td>";
            $html .= "<td style='text-align: center; color: #059669;'>".$persentase."%</td>";
            $html .= "</tr>";
        }
        $html .= "</tbody></table>";
    } else {
        $html .= "<div class='no-data'>Tidak ada data kinerja karyawan tersedia untuk periode ini.</div>";
    }
    $html .= "</div>";
}

// Footer laporan
$html .= "<div class='footer'>";
$html .= "<p>Laporan ini dibuat secara otomatis oleh sistem BersihXpress</p>";
$html .= "<p>© ".date('Y')." ".$bisnisNama." - Semua hak dilindungi</p>";
$html .= "</div>";

$mpdf = new Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'orientation' => 'P',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'tempDir' => sys_get_temp_dir(),
]);
$mpdf->SetTitle('Laporan Bisnis PDF');
$mpdf->WriteHTML($html);
$filename = "Laporan_{$bisnisNama}_{$tanggalMulai}_{$tanggalSelesai}.pdf";
$filename = "Laporan_{$bisnisNama}_" . date('Y_m') . ".pdf";
$mpdf->Output($filename, 'D');
exit;
