<?php
require_once __DIR__ . '/../middleware/auth_owner.php';
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json');

try {
    $ownerId = $_SESSION['owner_data']['owner_id'] ?? null;
    $bisnisId = $_SESSION['owner_data']['bisnis_id'] ?? null;
    
    if (!$ownerId || !$bisnisId) {
        throw new Exception('Data session tidak valid');
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_profile_data':
            echo json_encode(getProfileData($conn, $ownerId, $bisnisId));
            break;
            
        case 'update_business':
            echo json_encode(updateBusiness($conn, $bisnisId, $_POST));
            break;
            
        case 'update_owner':
            echo json_encode(updateOwner($conn, $ownerId, $_POST));
            break;
            
        case 'get_statistics':
            echo json_encode(getBusinessStatistics($conn, $bisnisId));
            break;
            
        case 'get_chart_data':
            echo json_encode(getChartData($conn, $bisnisId));
            break;
            
        default:
            throw new Exception('Action tidak valid');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function getProfileData($conn, $ownerId, $bisnisId) {
    // Get business data
    $stmt = $conn->prepare('SELECT * FROM bisnis WHERE bisnis_id = ?');
    $stmt->execute([$bisnisId]);
    $bisnis = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get owner data
    $stmt = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$ownerId]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bisnis || !$owner) {
        throw new Exception('Data profil tidak ditemukan');
    }
    
    return [
        'success' => true,
        'data' => [
            'bisnis' => $bisnis,
            'owner' => $owner
        ]
    ];
}

function updateBusiness($conn, $bisnisId, $data) {
    $nama_bisnis = $data['nama_bisnis'] ?? '';
    $alamat = $data['alamat'] ?? '';
    $no_telepon = $data['no_telepon'] ?? '';
    
    if (empty($nama_bisnis) || empty($alamat)) {
        throw new Exception('Nama bisnis dan alamat wajib diisi');
    }
    
    $stmt = $conn->prepare('UPDATE bisnis SET nama_bisnis = ?, alamat = ?, no_telepon = ?, updated_at = NOW() WHERE bisnis_id = ?');
    $stmt->execute([$nama_bisnis, $alamat, $no_telepon, $bisnisId]);
    
    if ($stmt->rowCount() > 0) {
        return [
            'success' => true,
            'message' => 'Data bisnis berhasil diperbarui'
        ];
    } else {
        throw new Exception('Tidak ada perubahan data atau bisnis tidak ditemukan');
    }
}

function updateOwner($conn, $ownerId, $data) {
    $nama_lengkap = $data['nama_lengkap'] ?? '';
    $no_telepon = $data['no_telepon'] ?? '';
    
    if (empty($nama_lengkap)) {
        throw new Exception('Nama lengkap wajib diisi');
    }
    
    $stmt = $conn->prepare('UPDATE users SET nama_lengkap = ?, no_telepon = ?, updated_at = NOW() WHERE user_id = ?');
    $stmt->execute([$nama_lengkap, $no_telepon, $ownerId]);
    
    if ($stmt->rowCount() > 0) {
        return [
            'success' => true,
            'message' => 'Data profil berhasil diperbarui'
        ];
    } else {
        throw new Exception('Tidak ada perubahan data atau profil tidak ditemukan');
    }
}

function getBusinessStatistics($conn, $bisnisId) {
    // Total transaksi
    $stmt = $conn->prepare('SELECT COUNT(*) as total_transaksi FROM transaksi WHERE bisnis_id = ?');
    $stmt->execute([$bisnisId]);
    $totalTransaksi = $stmt->fetchColumn();
    
    // Total pendapatan
    $stmt = $conn->prepare('SELECT COALESCE(SUM(dibayar), 0) as total_pendapatan FROM transaksi WHERE bisnis_id = ? AND status != "batal"');
    $stmt->execute([$bisnisId]);
    $totalPendapatan = $stmt->fetchColumn();
    
    // Total karyawan
    $stmt = $conn->prepare('SELECT COUNT(*) as total_karyawan FROM karyawan WHERE bisnis_id = ? AND status = "aktif"');
    $stmt->execute([$bisnisId]);
    $totalKaryawan = $stmt->fetchColumn();
    
    // Total pelanggan
    $stmt = $conn->prepare('SELECT COUNT(*) as total_pelanggan FROM pelanggan WHERE bisnis_id = ?');
    $stmt->execute([$bisnisId]);
    $totalPelanggan = $stmt->fetchColumn();
    
    // Transaksi bulan ini
    $stmt = $conn->prepare('SELECT COUNT(*) as transaksi_bulan_ini FROM transaksi WHERE bisnis_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())');
    $stmt->execute([$bisnisId]);
    $transaksiBulanIni = $stmt->fetchColumn();
    
    // Pendapatan bulan ini
    $stmt = $conn->prepare('SELECT COALESCE(SUM(dibayar), 0) as pendapatan_bulan_ini FROM transaksi WHERE bisnis_id = ? AND status != "batal" AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())');
    $stmt->execute([$bisnisId]);
    $pendapatanBulanIni = $stmt->fetchColumn();
    
    return [
        'success' => true,
        'data' => [
            'total_transaksi' => (int)$totalTransaksi,
            'total_pendapatan' => (float)$totalPendapatan,
            'total_karyawan' => (int)$totalKaryawan,
            'total_pelanggan' => (int)$totalPelanggan,
            'transaksi_bulan_ini' => (int)$transaksiBulanIni,
            'pendapatan_bulan_ini' => (float)$pendapatanBulanIni
        ]
    ];
}

function getChartData($conn, $bisnisId) {
    // Pendapatan 7 hari terakhir
    $stmt = $conn->prepare('
        SELECT 
            DATE(created_at) as tanggal,
            COALESCE(SUM(dibayar), 0) as pendapatan
        FROM transaksi 
        WHERE bisnis_id = ? 
            AND status != "batal" 
            AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY tanggal ASC
    ');
    $stmt->execute([$bisnisId]);
    $pendapatanHarian = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transaksi 6 bulan terakhir
    $stmt = $conn->prepare('
        SELECT 
            DATE_FORMAT(created_at, "%Y-%m") as bulan,
            COUNT(*) as jumlah_transaksi
        FROM transaksi 
        WHERE bisnis_id = ? 
            AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, "%Y-%m")
        ORDER BY bulan ASC
    ');
    $stmt->execute([$bisnisId]);
    $transaksiBulanan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Layanan terpopuler
    $stmt = $conn->prepare('
        SELECT 
            l.nama_layanan,
            COUNT(dt.detail_id) as jumlah_transaksi,
            SUM(dt.subtotal) as total_pendapatan
        FROM detail_transaksi dt
        JOIN layanan l ON dt.layanan_id = l.layanan_id
        JOIN transaksi t ON dt.transaksi_id = t.transaksi_id
        WHERE t.bisnis_id = ? AND t.status != "batal"
        GROUP BY l.layanan_id, l.nama_layanan
        ORDER BY jumlah_transaksi DESC
        LIMIT 5
    ');
    $stmt->execute([$bisnisId]);
    $layananPopuler = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'success' => true,
        'data' => [
            'pendapatan_harian' => $pendapatanHarian,
            'transaksi_bulanan' => $transaksiBulanan,
            'layanan_populer' => $layananPopuler
        ]
    ];
}
?>