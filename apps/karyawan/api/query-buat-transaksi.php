<?php
// query-buat-transaksi.php - Adaptasi untuk Karyawan
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

// Start session jika belum ada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check auth tanpa include middleware (karena sudah ada session_start di atas)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'karyawan') {
    error_log('ERROR: User tidak terautentikasi sebagai karyawan');
    header('Location: ../dashboard.php?error=1&msg=' . urlencode('Akses tidak sah'));
    exit;
}

if (!isset($_SESSION['karyawan_data'])) {
    error_log('ERROR: Data karyawan tidak ada di session');
    header('Location: ../dashboard.php?error=1&msg=' . urlencode('Session karyawan tidak valid'));
    exit;
}

function buatTransaksiKaryawan($data, $conn) {
    // Debug logging
    error_log('=== buatTransaksiKaryawan START ===');
    
    // Validasi session karyawan
    if (!isset($_SESSION['karyawan_data'])) {
        error_log('ERROR: Session karyawan_data tidak ada');
        return ['success' => false, 'message' => 'Session karyawan tidak valid.'];
    }

    $karyawan_data = $_SESSION['karyawan_data'];
    $bisnis_id = $karyawan_data['bisnis_id'] ?? null;
    $karyawan_id = $karyawan_data['karyawan_id'] ?? null;
    
    error_log('Bisnis ID: ' . $bisnis_id);
    error_log('Karyawan ID: ' . $karyawan_id);

    if (empty($bisnis_id) || empty($karyawan_id)) {
        error_log('ERROR: Data karyawan tidak lengkap');
        return ['success' => false, 'message' => 'Data karyawan tidak lengkap.'];
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
        $new_pelanggan_id = generateUUID();
        $stmtPelanggan = $conn->prepare("INSERT INTO pelanggan (pelanggan_id, bisnis_id, nama, alamat, no_telepon, tanggal_daftar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtPelanggan->execute([
            $new_pelanggan_id,
            $bisnis_id,
            $data['nama_pelanggan'],
            $data['alamat'] ?? '',
            $data['no_handphone'],
            $tanggal_masuk
        ]);
        $pelanggan_id = $new_pelanggan_id;
    }

    // Validasi field wajib transaksi - HANYA KOLOM YANG ADA DI DATABASE
    $wajib = [
        'total_harga' => 'Total Harga',
        'tanggal_selesai' => 'Tanggal Selesai', 
        'status' => 'Status',
        'dibayar' => 'Jumlah Dibayar'
    ];
    
    $kosong = [];
    foreach ($wajib as $key => $label) {
        if (empty($data[$key]) && $data[$key] !== '0') {
            $kosong[] = $label;
        }
    }
    
    if (count($kosong) > 0) {
        $msg = 'Field berikut wajib diisi: ' . implode(', ', $kosong);
        return ['success' => false, 'message' => $msg];
    }

    // Validasi layanan untuk template
    if (empty($data['layanan_id']) || !is_array($data['layanan_id'])) {
        error_log('ERROR: Validasi layanan gagal - layanan_id kosong atau bukan array');
        error_log('layanan_id: ' . print_r($data['layanan_id'] ?? 'NULL', true));
        return ['success' => false, 'message' => 'Minimal harus ada satu layanan yang dipilih.'];
    }
    
    error_log('Jumlah layanan: ' . count($data['layanan_id']));
    error_log('Layanan IDs: ' . print_r($data['layanan_id'], true));
    error_log('Quantities: ' . print_r($data['quantity'] ?? 'NULL', true));

    // Generate transaksi_id dan no_nota
    $transaksi_id = generateUUID();
    $no_nota = 'BX-' . date('ymd-His') . '-' . rand(100, 999);

    try {
        $conn->beginTransaction();

        // Insert transaksi utama - HANYA KOLOM YANG ADA DI DATABASE
        $stmt = $conn->prepare("INSERT INTO transaksi (transaksi_id, bisnis_id, pelanggan_id, karyawan_id, no_nota, tanggal_masuk, tanggal_selesai, status, total_harga, dibayar, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $transaksi_id,
            $bisnis_id,
            $pelanggan_id,
            $karyawan_id,
            $no_nota,
            $tanggal_masuk,
            $data['tanggal_selesai'],
            $data['status'],
            $data['total_harga'],
            $data['dibayar'],
            $data['catatan'] ?? ''
        ]);
        
        error_log("Transaksi inserted successfully: $transaksi_id");

        // Insert detail layanan dari form template
        $count = count($data['layanan_id']);
        error_log('Processing ' . $count . ' layanan items');
        
        for ($i = 0; $i < $count; $i++) {
            if (empty($data['layanan_id'][$i]) || empty($data['quantity'][$i])) {
                error_log("Skip layanan index $i - layanan_id atau quantity kosong");
                continue; // Skip layanan kosong
            }

            $layanan_id = $data['layanan_id'][$i];
            $jumlah = floatval($data['quantity'][$i]);
            
            error_log("Processing layanan $i: ID=$layanan_id, Qty=$jumlah");

            // Ambil harga dari database
            $stmtLayanan = $conn->prepare("SELECT harga, nama_layanan FROM layanan WHERE layanan_id = ?");
            $stmtLayanan->execute([$layanan_id]);
            $layananRow = $stmtLayanan->fetch(PDO::FETCH_ASSOC);
            
            if (!$layananRow) {
                error_log("ERROR: Layanan tidak ditemukan untuk ID: $layanan_id");
                throw new Exception("Layanan dengan ID {$layanan_id} tidak ditemukan.");
            }

            $harga_satuan = floatval($layananRow['harga']);
            $subtotal = $harga_satuan * $jumlah;
            
            error_log("Layanan: {$layananRow['nama_layanan']}, Harga: $harga_satuan, Subtotal: $subtotal");

            // Insert detail transaksi
            $detail_id = generateUUID();
            $stmtDetail = $conn->prepare("INSERT INTO detail_transaksi (detail_id, transaksi_id, layanan_id, jumlah, harga_satuan, subtotal, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtDetail->execute([
                $detail_id,
                $transaksi_id,
                $layanan_id,
                $jumlah,
                $harga_satuan,
                $subtotal,
                '' // catatan detail layanan
            ]);
            
            error_log("Detail transaksi inserted: $detail_id");
        }

        $conn->commit();
        error_log("Transaction committed successfully");
        return ['success' => true, 'transaksi_id' => $transaksi_id, 'no_nota' => $no_nota];

    } catch (Exception $e) {
        $conn->rollback();
        error_log('Error membuat transaksi: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan saat menyimpan transaksi: ' . $e->getMessage()];
    }
}

// Handler POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = $_POST;
        
        // Debug logging
        error_log('=== TRANSAKSI DEBUG START ===');
        error_log('POST Data: ' . print_r($data, true));
        error_log('Session Data: ' . print_r($_SESSION, true));
        
        // Validasi data yang diterima
        if (empty($data['layanan_id']) || !is_array($data['layanan_id'])) {
            error_log('ERROR: layanan_id kosong atau bukan array');
            header('Location: ../dashboard.php?error=1&msg=' . urlencode('Data layanan tidak valid'));
            exit;
        }
        
        if (empty($data['total_harga']) || floatval($data['total_harga']) <= 0) {
            error_log('ERROR: total_harga kosong atau <= 0');
            header('Location: ../dashboard.php?error=1&msg=' . urlencode('Total harga harus lebih dari 0'));
            exit;
        }
        
        $result = buatTransaksiKaryawan($data, $conn);
        
        error_log('Result: ' . print_r($result, true));
        error_log('=== TRANSAKSI DEBUG END ===');
        
        if ($result['success']) {
            // Redirect ke dashboard dengan success message
            $successMsg = 'Transaksi berhasil dibuat dengan No. Nota: ' . $result['no_nota'];
            header('Location: ../dashboard.php?success=1&msg=' . urlencode($successMsg));
            exit;
        } else {
            // Redirect ke dashboard dengan error message
            header('Location: ../dashboard.php?error=1&msg=' . urlencode($result['message']));
            exit;
        }
        
    } catch (Exception $e) {
        error_log('Error handler POST: ' . $e->getMessage());
        header('Location: ../dashboard.php?error=1&msg=' . urlencode('Terjadi kesalahan sistem.'));
        exit;
    }
} else {
    // Jika bukan POST request, redirect ke dashboard
    header('Location: ../dashboard.php');
    exit;
}
?>