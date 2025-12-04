<?php
// query-dashboard.php
function handleDashboardAction($action, $conn, $userId, $data = []) {
    switch ($action) {
        case 'get_stats':
            $stmtOwner = $conn->prepare("
                SELECT u.*, b.*
                FROM users u
                LEFT JOIN bisnis b ON b.owner_id = u.user_id
                WHERE u.user_id = ? AND u.role = 'owner'
                LIMIT 1
            ");
            $stmtOwner->execute([$userId]);
            $ownerData = $stmtOwner->fetch();
            $stats = [
                'pendapatan' => 0,
                'pengeluaran' => 0,
                'totalTransaksi' => 0,
                'kiloanSelesai' => 0,
                'satuanSelesai' => 0,
                'ownerData' => $ownerData
            ];
            if ($ownerData && !empty($ownerData['bisnis_id'])) {
                $bisnisId = $ownerData['bisnis_id'];
                $stmtPendapatan = $conn->prepare("SELECT COALESCE(SUM(total_harga),0) AS pendapatan FROM transaksi WHERE bisnis_id = ? AND MONTH(tanggal_masuk) = MONTH(CURDATE()) AND YEAR(tanggal_masuk) = YEAR(CURDATE())");
                $stmtPendapatan->execute([$bisnisId]);
                $stats['pendapatan'] = $stmtPendapatan->fetch()['pendapatan'] ?? 0;
                $stmtPengeluaran = $conn->prepare("SELECT COALESCE(SUM(jumlah),0) AS pengeluaran FROM pengeluaran WHERE bisnis_id = ? AND MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
                $stmtPengeluaran->execute([$bisnisId]);
                $stats['pengeluaran'] = $stmtPengeluaran->fetch()['pengeluaran'] ?? 0;
                $stmtTotalTransaksi = $conn->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE bisnis_id = ? AND MONTH(tanggal_masuk) = MONTH(CURDATE()) AND YEAR(tanggal_masuk) = YEAR(CURDATE())");
                $stmtTotalTransaksi->execute([$bisnisId]);
                $stats['totalTransaksi'] = $stmtTotalTransaksi->fetch()['total'] ?? 0;
                $stmtKiloan = $conn->prepare("SELECT COALESCE(SUM(dt.jumlah),0) AS kg FROM detail_transaksi dt JOIN transaksi t ON dt.transaksi_id = t.transaksi_id JOIN layanan l ON dt.layanan_id = l.layanan_id WHERE t.bisnis_id = ? AND l.satuan = 'kg' AND t.status = 'selesai' AND MONTH(t.tanggal_masuk) = MONTH(CURDATE()) AND YEAR(t.tanggal_masuk) = YEAR(CURDATE())");
                $stmtKiloan->execute([$bisnisId]);
                $stats['kiloanSelesai'] = $stmtKiloan->fetch()['kg'] ?? 0;
                $stmtSatuan = $conn->prepare("SELECT COALESCE(SUM(dt.jumlah),0) AS pcs FROM detail_transaksi dt JOIN transaksi t ON dt.transaksi_id = t.transaksi_id JOIN layanan l ON dt.layanan_id = l.layanan_id WHERE t.bisnis_id = ? AND l.satuan = 'pcs' AND t.status = 'selesai' AND MONTH(t.tanggal_masuk) = MONTH(CURDATE()) AND YEAR(t.tanggal_masuk) = YEAR(CURDATE())");
                $stmtSatuan->execute([$bisnisId]);
                $stats['satuanSelesai'] = $stmtSatuan->fetch()['pcs'] ?? 0;
            }
            return $stats;
        case 'input_transaksi':
            $stmt = $conn->prepare("INSERT INTO transaksi (bisnis_id, pelanggan_id, total_harga, tanggal_masuk) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['bisnis_id'],
                $data['pelanggan_id'],
                $data['total_harga'],
                $data['tanggal_masuk']
            ]);
            return $conn->lastInsertId();
        case 'catat_pengeluaran':
            $stmt = $conn->prepare("INSERT INTO pengeluaran (bisnis_id, jumlah, keterangan, tanggal) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['bisnis_id'],
                $data['jumlah'],
                $data['keterangan'],
                $data['tanggal']
            ]);
            return $conn->lastInsertId();
        case 'tambah_pelanggan':
            $stmt = $conn->prepare("INSERT INTO pelanggan (bisnis_id, nama, telp, alamat) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $data['bisnis_id'],
                $data['nama'],
                $data['telp'],
                $data['alamat']
            ]);
            return $conn->lastInsertId();
        case 'dummy_chart':
            // kept for backward compatibility, but prefer using 'chart_pendapatan'
            return [120000, 150000, 90000, 110000, 170000, 80000, 130000];
        case 'chart_pendapatan':
            // expects ['bisnis_id' => '...'] in $data
            $bisnisId = $data['bisnis_id'] ?? null;
            if (empty($bisnisId)) return ['labels' => [], 'data' => []];

            // prepare date range: last 7 days including today
            $days = [];
            $labels = [];
            $totals = [];
            $weekdayMap = [0 => 'Min', 1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab'];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-{$i} days"));
                $days[] = $d;
                $w = (int)date('w', strtotime($d));
                $labels[] = $weekdayMap[$w];
                $totals[$d] = 0;
            }

            $stmt = $conn->prepare("SELECT DATE(tanggal_masuk) AS day, COALESCE(SUM(total_harga),0) AS total FROM transaksi WHERE bisnis_id = ? AND DATE(tanggal_masuk) BETWEEN ? AND ? GROUP BY DATE(tanggal_masuk) ORDER BY DATE(tanggal_masuk) ASC");
            $stmt->execute([$bisnisId, $days[0], $days[count($days)-1]]);
            $rows = $stmt->fetchAll();
            foreach ($rows as $r) {
                if (isset($r['day'])) {
                    $totals[$r['day']] = floatval($r['total']);
                }
            }

            $dataOut = [];
            foreach ($days as $d) $dataOut[] = $totals[$d] ?? 0;
            return ['labels' => $labels, 'data' => $dataOut];
        case 'ringkasan_transaksi':
            $stmt = $conn->prepare("SELECT t.transaksi_id, p.nama, t.total_harga, t.tanggal_masuk, t.status FROM transaksi t JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id WHERE t.bisnis_id = ? ORDER BY t.tanggal_masuk DESC LIMIT 3");
            $stmt->execute([$data['bisnis_id']]);
            return $stmt->fetchAll();
        default:
            return null;
    }
}
