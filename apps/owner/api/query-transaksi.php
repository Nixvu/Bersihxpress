<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

/**
 * Get transactions with filtering by status
 */

function getTransactions($conn, $bisnisId, $status = 'all', $search = '') {
    try {
        $sql = "
            SELECT 
                t.transaksi_id,
                t.no_nota,
                t.tanggal_masuk,
                t.tanggal_selesai,
                t.status,
                t.total_harga,
                t.dibayar,
                t.catatan,
                p.nama as pelanggan_nama,
                p.no_telepon as pelanggan_telepon,
                CASE 
                    WHEN t.dibayar >= t.total_harga THEN 'lunas'
                    WHEN t.dibayar > 0 THEN 'sebagian'
                    ELSE 'belum_lunas'
                END as status_bayar
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            WHERE t.bisnis_id = ?
        ";
        $params = [$bisnisId];
        if ($status !== 'all' && !empty($status)) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        if (!empty($search)) {
            $sql .= " AND (t.no_nota LIKE ? OR p.nama LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        $sql .= " ORDER BY t.tanggal_masuk DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Format transactions
        foreach ($transactions as &$transaction) {
            $transaction['total_harga_display'] = 'Rp ' . number_format($transaction['total_harga'], 0, ',', '.');
            $transaction['status_display'] = getStatusText($transaction['status']);
            $transaction['status_badge'] = getStatusBadgeClass($transaction['status']);
            $transaction['status_bayar_display'] = getPaymentStatusText($transaction['status_bayar']);
        }
        unset($transaction);
        return $transactions;
    } catch (PDOException $e) {
        logError('Error getting transactions', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Get transaction count by status
 */

function getTransactionCounts($conn, $bisnisId) {
    try {
        $sql = "
            SELECT 
                status,
                COUNT(*) as count
            FROM transaksi 
            WHERE bisnis_id = ?
            GROUP BY status
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$bisnisId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [
            'all' => 0,
            'pending' => 0,
            'proses' => 0,
            'selesai' => 0,
            'diambil' => 0,
            'batal' => 0
        ];
        foreach ($results as $row) {
            $status = strtolower($row['status']);
            if (isset($counts[$status])) {
                $counts[$status] = (int)$row['count'];
            }
            $counts['all'] += (int)$row['count'];
        }
        return $counts;
    } catch (PDOException $e) {
        logError('Error getting transaction counts', ['error' => $e->getMessage()]);
        return ['all' => 0, 'pending' => 0, 'proses' => 0, 'selesai' => 0, 'diambil' => 0, 'batal' => 0];
    }
}
// Payment status text
function getPaymentStatusText($statusBayar) {
    switch (strtolower($statusBayar)) {
        case 'lunas':
            return 'Lunas';
        case 'sebagian':
            return 'Sebagian';
        case 'belum_lunas':
            return 'Belum Lunas';
        default:
            return 'Belum Lunas';
    }
}

// Update transaction status
function updateTransactionStatus($conn, $bisnisId, $data) {
    $transaksiId = $data['transaksi_id'] ?? '';
    $newStatus = $data['new_status'] ?? '';
    $statusBayar = $data['status_bayar'] ?? '';
    if (!$transaksiId) {
        throw new InvalidArgumentException('ID transaksi tidak valid.');
    }
    $stmt = $conn->prepare('SELECT transaksi_id, status FROM transaksi WHERE transaksi_id = ? AND bisnis_id = ?');
    $stmt->execute([$transaksiId, $bisnisId]);
    $existingTransaction = $stmt->fetch();
    if (!$existingTransaction) {
        throw new InvalidArgumentException('Transaksi tidak ditemukan.');
    }
    $updateFields = [];
    $updateParams = [];
    if ($newStatus) {
        $updateFields[] = 'status = ?';
        $updateParams[] = $newStatus;
        if ($newStatus === 'selesai' && $existingTransaction['status'] !== 'selesai') {
            $updateFields[] = 'tanggal_selesai = NOW()';
        }
    }
    if ($statusBayar) {
        if ($statusBayar === 'lunas') {
            $updateFields[] = 'dibayar = total_harga';
        } elseif ($statusBayar === 'belum_lunas') {
            $updateFields[] = 'dibayar = 0';
        }
    }
    if ($updateFields) {
        $updateParams[] = $transaksiId;
        $updateParams[] = $bisnisId;
        $stmt = $conn->prepare('UPDATE transaksi SET ' . implode(', ', $updateFields) . ' WHERE transaksi_id = ? AND bisnis_id = ?');
        $stmt->execute($updateParams);
        $statusText = $newStatus ? " Status: {$newStatus}." : '';
        $paymentText = $statusBayar ? " Pembayaran: {$statusBayar}." : '';
        $_SESSION['transaksi_flash_success'] = 'Status transaksi berhasil diperbarui.' . $statusText . $paymentText;
    } else {
        throw new InvalidArgumentException('Tidak ada perubahan yang dilakukan.');
    }
}

// Create transaction
function createTransaction($conn, $bisnisId, $data) {
    $pelangganNama = sanitize($data['pelanggan_nama'] ?? '');
    $pelangganTelepon = trim($data['pelanggan_telepon'] ?? '');
    $pelangganAlamat = sanitize($data['pelanggan_alamat'] ?? '');
    $tanggalSelesai = $data['tanggal_selesai'] ?? '';
    $statusAwal = $data['status_awal'] ?? 'pending';
    $catatan = sanitize($data['catatan'] ?? '');
    $totalHarga = filter_var($data['total_harga'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
    $statusBayar = $data['status_bayar'] ?? 'belum_lunas';
    $metodeBayar = $data['metode_bayar'] ?? 'tunai';
    if (!$pelangganNama || $totalHarga <= 0) {
        throw new InvalidArgumentException('Nama pelanggan dan total harga wajib diisi.');
    }
    $conn->beginTransaction();
    $pelangganId = null;
    if (!empty($pelangganTelepon)) {
        $stmt = $conn->prepare('SELECT pelanggan_id FROM pelanggan WHERE no_telepon = ? AND bisnis_id = ?');
        $stmt->execute([$pelangganTelepon, $bisnisId]);
        $existingCustomer = $stmt->fetch();
        if ($existingCustomer) {
            $pelangganId = $existingCustomer['pelanggan_id'];
        }
    }
    if (!$pelangganId) {
        $pelangganId = generateUUID();
        $stmt = $conn->prepare('
            INSERT INTO pelanggan (pelanggan_id, bisnis_id, nama, no_telepon, alamat)
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $pelangganId,
            $bisnisId,
            $pelangganNama,
            $pelangganTelepon ?: null,
            $pelangganAlamat ?: null
        ]);
    }
    $noNota = generateNoteNumber($conn, $bisnisId);
    $transaksiId = generateUUID();
    $dibayar = $statusBayar === 'lunas' ? $totalHarga : 0;
    $stmt = $conn->prepare('
        INSERT INTO transaksi (transaksi_id, bisnis_id, pelanggan_id, karyawan_id, no_nota, tanggal_masuk, tanggal_selesai, status, total_harga, dibayar, catatan)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $transaksiId,
        $bisnisId,
        $pelangganId,
        null,
        $noNota,
        $tanggalSelesai ?: null,
        $statusAwal,
        $totalHarga,
        $dibayar,
        $catatan ?: null
    ]);
    $conn->commit();
    $_SESSION['transaksi_flash_success'] = 'Transaksi baru berhasil dibuat dengan nomor nota: ' . $noNota;
}

// Create expense
function createExpense($conn, $bisnisId, $data) {
    $namaPengeluaran = sanitize($data['nama_pengeluaran'] ?? '');
    $nominalPengeluaran = filter_var($data['nominal_pengeluaran'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
    $kategoriPengeluaran = sanitize($data['kategori_pengeluaran'] ?? '');
    $tanggalPengeluaran = $data['tanggal_pengeluaran'] ?? date('Y-m-d');
    $metodeBayarPengeluaran = $data['metode_bayar_pengeluaran'] ?? 'tunai';
    $keteranganPengeluaran = sanitize($data['keterangan_pengeluaran'] ?? '');
    if (!$namaPengeluaran || $nominalPengeluaran <= 0) {
        throw new InvalidArgumentException('Nama pengeluaran dan nominal wajib diisi dengan benar.');
    }
    if (!validateDate($tanggalPengeluaran)) {
        throw new InvalidArgumentException('Format tanggal tidak valid.');
    }
    $pengeluaranId = generateUUID();
    $stmt = $conn->prepare('
        INSERT INTO pengeluaran (pengeluaran_id, bisnis_id, kategori, jumlah, keterangan, tanggal, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([
        $pengeluaranId,
        $bisnisId,
        $kategoriPengeluaran ?: 'lain-lain',
        $nominalPengeluaran,
        $keteranganPengeluaran ?: $namaPengeluaran,
        $tanggalPengeluaran,
        $_SESSION['user_id']
    ]);
    $_SESSION['transaksi_flash_success'] = 'Pengeluaran Rp ' . number_format($nominalPengeluaran, 0, ',', '.') . ' berhasil dicatat.';
}

/**
 * Generate filter URL for transactions
 */
function transaksiFilterUrl($status, $search = '') {
    $params = [];
    
    if ($status !== 'all') {
        $params['status'] = $status;
    }
    
    if (!empty($search)) {
        $params['search'] = $search;
    }
    
    $baseUrl = $_SERVER['PHP_SELF'];
    return $baseUrl . (!empty($params) ? '?' . http_build_query($params) : '');
}

/**
 * Get status badge class for styling
 */
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-700';
        case 'proses':
            return 'bg-blue-100 text-blue-700';
        case 'selesai':
            return 'bg-green-100 text-green-700';
        case 'diambil':
            return 'bg-gray-100 text-gray-700';
        case 'batal':
            return 'bg-red-100 text-red-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
}

/**
 * Format status text for display
 */
function getStatusText($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'Pending';
        case 'proses':
            return 'Proses';
        case 'selesai':
            return 'Selesai';
        case 'diambil':
            return 'Diambil';
        case 'batal':
            return 'Batal';
        default:
            return ucfirst($status);
    }
}

/**
 * Calculate time remaining
 */
function calculateTimeRemaining($tanggalSelesai) {
    if (!$tanggalSelesai) return 'Belum ditentukan';
    
    $now = new DateTime();
    $selesai = new DateTime($tanggalSelesai);
    $diff = $now->diff($selesai);
    
    if ($selesai < $now) {
        return 'Sudah lewat';
    }
    
    if ($diff->days > 0) {
        return $diff->days . ' hari lagi';
    } elseif ($diff->h > 0) {
        return $diff->h . ' jam lagi';
    } else {
        return 'Kurang dari 1 jam';
    }
}
?>