<?php
// query-buat-transaksi.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

function buatTransaksiManual($data, $conn) {
    // Bisnis ID dari POST
    $bisnis_id = $data['bisnis_id'] ?? null;
    if (empty($bisnis_id)) {
        return ['success' => false, 'message' => 'Bisnis ID wajib diisi.'];
    }
    // Tanggal masuk otomatis
    $tanggal_masuk = date('Y-m-d H:i:s');

    // Validasi pelanggan
    $pelanggan_id = $data['pelanggan_id'] ?? '';
    if (empty($pelanggan_id)) {
        // Jika tidak pilih pelanggan, cek input manual
        if (empty($data['nama_pelanggan']) || empty($data['no_handphone'])) {
            return ['success' => false, 'message' => 'Nama dan No HP pelanggan wajib diisi jika tidak memilih pelanggan yang sudah ada.'];
        }
        // Buat pelanggan baru
        $new_pelanggan_id = uniqid('plg_');
        $stmtPelanggan = $conn->prepare("INSERT INTO pelanggan (pelanggan_id, bisnis_id, nama, alamat, no_telepon) VALUES (?, ?, ?, ?, ?)");
        $stmtPelanggan->execute([
            $new_pelanggan_id,
            $bisnis_id,
            $data['nama_pelanggan'],
            $data['alamat'] ?? '',
            $data['no_handphone']
        ]);
        $pelanggan_id = $new_pelanggan_id;
    }

    // Validasi minimal transaksi
    $wajib = [
        'total_harga' => 'Total Harga',
        'tanggal_selesai' => 'Tanggal Selesai',
        'status' => 'Status',
    ];
    $kosong = [];
    foreach ($wajib as $key => $label) {
        if (empty($data[$key])) {
            $kosong[] = $label;
        }
    }
    if (count($kosong) > 0) {
        $msg = 'Field berikut wajib diisi: ' . implode(', ', $kosong);
        return ['success' => false, 'message' => $msg];
    }

    // Generate transaksi_id dan no_nota
    $transaksi_id = uniqid('trx_');
    $no_nota = 'BX-' . date('ymd-His') . '-' . rand(100,999);
    // Insert transaksi
    $karyawan_id = !empty($data['karyawan_id']) ? $data['karyawan_id'] : null;
    $stmt = $conn->prepare("INSERT INTO transaksi (transaksi_id, bisnis_id, pelanggan_id, karyawan_id, no_nota, tanggal_masuk, tanggal_selesai, status, total_harga, dibayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $transaksi_id,
        $bisnis_id,
        $pelanggan_id,
        $karyawan_id,
        $no_nota,
        $tanggal_masuk,
        $data['tanggal_selesai'] ?? null,
        $data['status'],
        $data['total_harga'],
        $data['dibayar'] ?? 0,
        $data['catatan'] ?? ''
    ]);
    // Insert detail layanan
    if (!empty($data['layanan']) && is_array($data['layanan'])) {
        foreach ($data['layanan'] as $item) {
            $detail_id = uniqid('dtl_');
            // Ambil harga dan satuan dari database
            $stmtLayanan = $conn->prepare("SELECT harga FROM layanan WHERE layanan_id = ?");
            $stmtLayanan->execute([$item['layanan_id']]);
            $layananRow = $stmtLayanan->fetch(PDO::FETCH_ASSOC);
            $harga_satuan = $layananRow ? floatval($layananRow['harga']) : 0;
            $jumlah = floatval($item['jumlah']);
            $subtotal = $harga_satuan * $jumlah;
            $stmtDetail = $conn->prepare("INSERT INTO detail_transaksi (detail_id, transaksi_id, layanan_id, jumlah, harga_satuan, subtotal, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtDetail->execute([
                $detail_id,
                $transaksi_id,
                $item['layanan_id'],
                $jumlah,
                $harga_satuan,
                $subtotal,
                $item['catatan'] ?? ''
            ]);
        }
    }
    return ['success' => true, 'transaksi_id' => $transaksi_id, 'no_nota' => $no_nota];
}

// Handler POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    // Parsing layanan dari form manual & template
    if (!empty($_POST['layanan_id']) && is_array($_POST['layanan_id'])) {
        $layanan = [];
        $count = count($_POST['layanan_id']);
        for ($i = 0; $i < $count; $i++) {
            $jumlah = floatval($_POST['quantity'][$i]);
            $harga_satuan = isset($_POST['harga_satuan'][$i]) ? floatval($_POST['harga_satuan'][$i]) : 0;
            $layanan[] = [
                'layanan_id' => $_POST['layanan_id'][$i],
                'jumlah' => $jumlah,
                'harga_satuan' => $harga_satuan,
                'subtotal' => $harga_satuan * $jumlah,
                'catatan' => isset($_POST['catatan_layanan']) ? ($_POST['catatan_layanan'][$i] ?? '') : ''
            ];
        }
        $data['layanan'] = $layanan;
    } else if (!empty($data['layanan']) && is_string($data['layanan'])) {
        $data['layanan'] = json_decode($data['layanan'], true);
    }
    $result = buatTransaksiManual($data, $conn);
    if ($result['success']) {
        header('Location: ../dashboard.php?success=1');
        exit;
    } else {
        header('Location: ../dashboard.php?error=1&msg=' . urlencode($result['message']));
        exit;
    }
}

session_start();
$ownerData = $_SESSION['ownerData'] ?? [];
$bisnis_id = $ownerData['bisnis_id'] ?? null;
$tanggal_masuk = date('Y-m-d H:i:s');

$pelanggan_id = $_POST['pelanggan_id'] ?? '';
if (empty($pelanggan_id)) {
    // Validasi manual pelanggan
    if (empty($_POST['nama_pelanggan']) || empty($_POST['no_handphone'])) {
        $error = 'Nama dan No HP pelanggan wajib diisi jika tidak memilih pelanggan yang sudah ada.';
        // tampilkan error
    } else {
        // Buat pelanggan baru
        // ...insert pelanggan...
        $pelanggan_id = $new_pelanggan_id;
    }
}

$result = buatTransaksiManual($data, $conn, $ownerData);
if ($result['success']) {
$bisnis_id = $ownerData['bisnis_id'] ?? ($_POST['bisnis_id'] ?? null);
    // Redirect atau response JSON
    header('Location: ../dashboard.php?success=1');
    exit;
} else {
    header('Location: ../dashboard.php?error=1&msg=' . urlencode($result['message']));
    exit;
}
