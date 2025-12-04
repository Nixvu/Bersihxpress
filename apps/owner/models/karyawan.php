<?php
// UPDATE users (nama, telepon, email)
function updateUser($userId, $namaLengkap = null, $noTelepon = null, $email = null) {
    global $conn;
    $fields = [];
    $params = [];
    if ($namaLengkap !== null) {
        $fields[] = 'nama_lengkap = ?';
        $params[] = $namaLengkap;
    }
    if ($noTelepon !== null) {
        $fields[] = 'no_telepon = ?';
        $params[] = $noTelepon;
    }
    // Hanya update email jika tidak kosong
    if ($email !== null && $email !== '') {
        $fields[] = 'email = ?';
        $params[] = $email;
    }
    if (!$fields) return false;
    $params[] = $userId;
    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

// CREATE karyawan
function createKaryawan($bisnisId, $userId, $gajiPokok, $status = 'aktif', $tanggalBergabung = null) {
    global $conn;
    $tanggalBergabung = $tanggalBergabung ?: date('Y-m-d');
    // Generate UUID untuk karyawan_id
    $karyawanId = generateUUID();
    $stmt = $conn->prepare('
        INSERT INTO karyawan (karyawan_id, bisnis_id, user_id, gaji_pokok, status, tanggal_bergabung)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    if ($stmt->execute([$karyawanId, $bisnisId, $userId, $gajiPokok, $status, $tanggalBergabung])) {
        return $karyawanId;
    }
    return false;
}

// READ karyawan (single, with bisnisId)
function readKaryawan($karyawanId, $bisnisId = null) {
    global $conn;
    $query = '
        SELECT 
            k.karyawan_id as id,
            k.gaji_pokok as gaji,
            k.status,
            k.tanggal_bergabung,
            u.nama_lengkap as nama,
            u.email,
            u.no_telepon as telepon,
            COUNT(t.transaksi_id) as total_transaksi,
            COUNT(CASE WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transaksi_bulan
        FROM karyawan k
        INNER JOIN users u ON k.user_id = u.user_id
        LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id
        WHERE k.karyawan_id = ?';
    $params = [$karyawanId];
    if ($bisnisId !== null) {
        $query .= ' AND k.bisnis_id = ?';
        $params[] = $bisnisId;
    }
    $query .= ' GROUP BY k.karyawan_id';
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) {
        $data['bergabung'] = date('d M Y', strtotime($data['tanggal_bergabung']));
        $data['gaji_display'] = 'Rp ' . number_format($data['gaji'], 0, ',', '.');
    }
    return $data;
}

// UPDATE karyawan
function updateKaryawan($karyawanId, $gajiPokok = null, $status = null, $tanggalBergabung = null) {
    global $conn;
    $fields = [];
    $params = [];
    if ($gajiPokok !== null) {
        $fields[] = 'gaji_pokok = ?';
        $params[] = $gajiPokok;
    }
    if ($status !== null) {
        $fields[] = 'status = ?';
        $params[] = $status;
    }
    if ($tanggalBergabung !== null) {
        $fields[] = 'tanggal_bergabung = ?';
        $params[] = $tanggalBergabung;
    }
    if (!$fields) return false;
    $params[] = $karyawanId;
    $sql = 'UPDATE karyawan SET ' . implode(', ', $fields) . ' WHERE karyawan_id = ?';
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}

// DELETE karyawan
function deleteKaryawan($karyawanId) {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM karyawan WHERE karyawan_id = ?');
    return $stmt->execute([$karyawanId]);
}

// LIST karyawan
function karyawan_list($bisnisId, $searchTerm = '') {
    global $conn;
    $query = '
        SELECT 
            k.karyawan_id,
            k.gaji_pokok,
            k.status,
            k.tanggal_bergabung,
            u.nama_lengkap,
            u.email,
            u.no_telepon,
            u.created_at,
            COUNT(t.transaksi_id) as total_transaksi,
            COUNT(CASE WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transaksi_bulan_ini
        FROM karyawan k
        INNER JOIN users u ON k.user_id = u.user_id
        LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id
        WHERE k.bisnis_id = ?
    ';
    $params = [$bisnisId];
    if ($searchTerm !== '') {
        $query .= ' AND (u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.no_telepon LIKE ?)';
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    $query .= ' GROUP BY k.karyawan_id ORDER BY u.nama_lengkap ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as &$employee) {
        $employee['gaji_pokok_display'] = 'Rp ' . number_format($employee['gaji_pokok'], 0, ',', '.');
        $employee['bergabung_display'] = date('d M Y', strtotime($employee['tanggal_bergabung']));
        $employee['status_badge'] = $employee['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        $employee['created_display'] = date('d M Y H:i', strtotime($employee['created_at']));
    }
    unset($employee);
    return $employees;
}

// RESET PASSWORD karyawan
function resetKaryawanPassword($karyawanId, $newPassword) {
    global $conn;
    // Ambil user_id dari karyawan_id
    $stmt = $conn->prepare('SELECT user_id FROM karyawan WHERE karyawan_id = ?');
    $stmt->execute([$karyawanId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['user_id'])) {
        return false;
    }
    $userId = $row['user_id'];
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt2 = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    return $stmt2->execute([$hashedPassword, $userId]);
}
