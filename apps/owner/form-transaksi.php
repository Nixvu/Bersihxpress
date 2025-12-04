<?php
require_once __DIR__ . '/../../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama = $_POST['nama_pelanggan_manual'] ?? '';
    $no_hp = $_POST['no_handphone_manual'] ?? '';
    $alamat = $_POST['alamat_manual'] ?? '';
    $tgl_selesai = $_POST['tgl_selesai_manual'] ?? '';
    $status_awal = $_POST['status_awal_manual'] ?? '';
    $layanan = $_POST['layanan'] ?? '';
    $qty = $_POST['qty'] ?? '';
    $harga_total = $_POST['harga_total'] ?? '';
    $catatan = $_POST['catatan_manual'] ?? '';
    $subtotal = $_POST['subtotal'] ?? '';
    $diskon = $_POST['diskon'] ?? '';
    $biaya_antar = $_POST['biaya_antar'] ?? '';
    $total_akhir = $_POST['total_akhir'] ?? '';
    $metode_bayar = $_POST['metode_bayar_manual'] ?? '';
    $status_bayar = $_POST['status_bayar_manual'] ?? '';

    // Validasi sederhana
    if ($nama === '' || $no_hp === '' || $layanan === '' || $qty === '' || $harga_total === '') {
        $message = '<div style="color:red">Data wajib diisi!</div>';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO transaksi (nama_pelanggan, no_hp, alamat, tgl_selesai, status_awal, layanan, qty, harga_total, catatan, subtotal, diskon, biaya_antar, total_akhir, metode_bayar, status_bayar) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $no_hp, $alamat, $tgl_selesai, $status_awal, $layanan, $qty, $harga_total, $catatan, $subtotal, $diskon, $biaya_antar, $total_akhir, $metode_bayar, $status_bayar]);
            $message = '<div style="color:green">Transaksi berhasil disimpan!</div>';
        } catch (Exception $e) {
            $message = '<div style="color:red">Gagal menyimpan transaksi: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Transaksi Manual</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <h1>Form Transaksi Manual</h1>
    <?php echo $message; ?>
    <a href="../modals/modal-buat-transaksi.php">Kembali ke Buat Transaksi</a>
</body>
</html>
