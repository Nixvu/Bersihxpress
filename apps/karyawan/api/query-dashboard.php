<?php
// Query handler untuk dashboard karyawan

function handleKaryawanDashboardAction($action, $conn, $karyawanId, $params = []) {
    try {
        switch ($action) {
            case 'get_stats':
                return getKaryawanStats($conn, $karyawanId);
                
            case 'chart_kinerja':
                return getKaryawanKinerjaChart($conn, $karyawanId, $params);
                
            case 'tugas_saya':
                return getTugasSaya($conn, $karyawanId);
                
            case 'absensi_today':
                return getAbsensiToday($conn, $karyawanId);
                
            case 'submit_absensi':
                return submitAbsensi($conn, $karyawanId, $params);
                
            case 'input_transaksi_manual':
            case 'input_transaksi_template':
                $stmt = $conn->prepare("INSERT INTO transaksi (bisnis_id, pelanggan_id, karyawan_id, total_harga, tanggal_masuk, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $params['bisnis_id'],
                    $params['pelanggan_id'],
                    $karyawanId,
                    $params['total_harga'],
                    $params['tanggal_masuk'],
                    $params['status'] ?? 'pending'
                ]);
                return $conn->lastInsertId();
                
            case 'get_pelanggan_list':
                $stmt = $conn->prepare("SELECT pelanggan_id, nama, no_telepon FROM pelanggan WHERE bisnis_id = ? ORDER BY nama ASC");
                $stmt->execute([$params['bisnis_id']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            default:
                throw new Exception('Action tidak dikenal: ' . $action);
        }
    } catch (Exception $e) {
        error_log('Dashboard Karyawan Error: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
}

function getKaryawanStats($conn, $karyawanId) {
    // Get karyawan data
    $stmt = $conn->prepare('
        SELECT k.*, u.nama_lengkap, b.nama_bisnis, b.logo, b.bisnis_id
        FROM karyawan k 
        JOIN users u ON k.user_id = u.user_id 
        JOIN bisnis b ON k.bisnis_id = b.bisnis_id 
        WHERE k.karyawan_id = ?
    ');
    $stmt->execute([$karyawanId]);
    $karyawanData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$karyawanData) {
        throw new Exception('Data karyawan tidak ditemukan');
    }
    
    $bisnisId = $karyawanData['bisnis_id'];
    
    // Get statistics bulan ini
    $currentMonth = date('Y-m');
    
    // Total transaksi yang dikerjakan karyawan bulan ini
    $stmt = $conn->prepare('
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE karyawan_id = ? AND DATE_FORMAT(created_at, "%Y-%m") = ?
    ');
    $stmt->execute([$karyawanId, $currentMonth]);
    $totalTransaksi = (int)$stmt->fetchColumn();
    
    // Transaksi selesai bulan ini
    $stmt = $conn->prepare('
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE karyawan_id = ? AND status = "selesai" AND DATE_FORMAT(updated_at, "%Y-%m") = ?
    ');
    $stmt->execute([$karyawanId, $currentMonth]);
    $transaksiSelesai = (int)$stmt->fetchColumn();
    
    // Transaksi dalam proses
    $stmt = $conn->prepare('
        SELECT COUNT(*) as total 
        FROM transaksi 
        WHERE karyawan_id = ? AND status IN ("diproses", "antrian")
    ');
    $stmt->execute([$karyawanId]);
    $transaksiProses = (int)$stmt->fetchColumn();
    
    // Estimasi komisi (5% dari total pendapatan transaksi selesai)
    $stmt = $conn->prepare('
        SELECT COALESCE(SUM(dibayar), 0) as total 
        FROM transaksi 
        WHERE karyawan_id = ? AND status = "selesai" AND DATE_FORMAT(updated_at, "%Y-%m") = ?
    ');
    $stmt->execute([$karyawanId, $currentMonth]);
    $totalPendapatan = (float)$stmt->fetchColumn();
    $estimasiKomisi = $totalPendapatan * 0.05; // 5% komisi
    
    // Statistik detail (kilogram, satuan, meteran)
    $stmt = $conn->prepare('
        SELECT 
            COALESCE(SUM(CASE WHEN td.unit = "kg" THEN td.quantity ELSE 0 END), 0) as kilogram,
            COALESCE(SUM(CASE WHEN td.unit = "pcs" THEN td.quantity ELSE 0 END), 0) as satuan,
            COALESCE(SUM(CASE WHEN td.unit = "m" OR td.unit = "m2" THEN td.quantity ELSE 0 END), 0) as meteran
        FROM transaksi t
        JOIN transaksi_detail td ON t.transaksi_id = td.transaksi_id
        WHERE t.karyawan_id = ? AND t.status = "selesai" AND DATE_FORMAT(t.updated_at, "%Y-%m") = ?
    ');
    $stmt->execute([$karyawanId, $currentMonth]);
    $detailStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'karyawanData' => $karyawanData,
        'totalTransaksi' => $totalTransaksi,
        'transaksiSelesai' => $transaksiSelesai,
        'transaksiProses' => $transaksiProses,
        'estimasiKomisi' => $estimasiKomisi,
        'kilogram' => (float)($detailStats['kilogram'] ?? 0),
        'satuan' => (int)($detailStats['satuan'] ?? 0),
        'meteran' => (float)($detailStats['meteran'] ?? 0)
    ];
}

function getKaryawanKinerjaChart($conn, $karyawanId, $params) {
    // Chart kinerja 7 hari terakhir
    $labels = [];
    $data = [];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dayName = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][date('w', strtotime($date))];
        $labels[] = $dayName;
        
        // Count completed transactions per day
        $stmt = $conn->prepare('
            SELECT COUNT(*) as count 
            FROM transaksi 
            WHERE karyawan_id = ? AND status = "selesai" AND DATE(updated_at) = ?
        ');
        $stmt->execute([$karyawanId, $date]);
        $count = (int)$stmt->fetchColumn();
        $data[] = $count;
    }
    
    return [
        'labels' => $labels,
        'data' => $data
    ];
}

function getTugasSaya($conn, $karyawanId) {
    // Transaksi yang masih dikerjakan
    $stmt = $conn->prepare('
        SELECT t.*, p.nama as nama_pelanggan, p.no_telepon
        FROM transaksi t
        JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.karyawan_id = ? AND t.status IN ("antrian", "diproses")
        ORDER BY 
            CASE 
                WHEN t.status = "diproses" THEN 1
                WHEN t.status = "antrian" THEN 2
                ELSE 3
            END,
            t.estimasi_selesai ASC
    ');
    $stmt->execute([$karyawanId]);
    $tugasDikerjakan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transaksi selesai hari ini
    $stmt = $conn->prepare('
        SELECT t.*, p.nama as nama_pelanggan, p.no_telepon
        FROM transaksi t
        JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        WHERE t.karyawan_id = ? AND t.status = "selesai" AND DATE(t.updated_at) = CURDATE()
        ORDER BY t.updated_at DESC
    ');
    $stmt->execute([$karyawanId]);
    $selesaiHariIni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'dikerjakan' => $tugasDikerjakan,
        'selesai_hari_ini' => $selesaiHariIni
    ];
}

function getAbsensiToday($conn, $karyawanId) {
    $today = date('Y-m-d');
    
    $stmt = $conn->prepare('
        SELECT * FROM absensi 
        WHERE karyawan_id = ? AND DATE(tanggal) = ?
        ORDER BY jam_masuk DESC
        LIMIT 1
    ');
    $stmt->execute([$karyawanId, $today]);
    $absensi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $absensi ?: null;
}

function submitAbsensi($conn, $karyawanId, $params) {
    $type = $params['type'] ?? 'masuk'; // masuk atau pulang
    $today = date('Y-m-d');
    $currentTime = date('H:i:s');
    $currentDateTime = date('Y-m-d H:i:s');
    
    if ($type === 'masuk') {
        // Cek apakah sudah absen masuk hari ini
        $stmt = $conn->prepare('
            SELECT absensi_id FROM absensi 
            WHERE karyawan_id = ? AND DATE(tanggal) = ? AND jam_masuk IS NOT NULL
        ');
        $stmt->execute([$karyawanId, $today]);
        
        if ($stmt->fetch()) {
            throw new Exception('Anda sudah absen masuk hari ini');
        }
        
        // Insert absensi masuk
        $stmt = $conn->prepare('
            INSERT INTO absensi (karyawan_id, tanggal, jam_masuk, status, created_at) 
            VALUES (?, ?, ?, "masuk", ?)
        ');
        $stmt->execute([$karyawanId, $today, $currentTime, $currentDateTime]);
        
        return ['success' => true, 'message' => 'Absen masuk berhasil dicatat'];
        
    } else {
        // Cek absensi masuk hari ini
        $stmt = $conn->prepare('
            SELECT absensi_id FROM absensi 
            WHERE karyawan_id = ? AND DATE(tanggal) = ? AND jam_masuk IS NOT NULL
        ');
        $stmt->execute([$karyawanId, $today]);
        $absensiMasuk = $stmt->fetch();
        
        if (!$absensiMasuk) {
            throw new Exception('Anda belum absen masuk hari ini');
        }
        
        // Update dengan jam pulang
        $stmt = $conn->prepare('
            UPDATE absensi 
            SET jam_pulang = ?, status = "lengkap", updated_at = ?
            WHERE absensi_id = ?
        ');
        $stmt->execute([$currentTime, $currentDateTime, $absensiMasuk['absensi_id']]);
        
        return ['success' => true, 'message' => 'Absen pulang berhasil dicatat'];
    }
}

// Dummy function untuk development
function getDummyKaryawanChart() {
    return [
        'labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
        'data' => [5, 8, 6, 12, 9, 7, 4]
    ];
}
?>