<?php
// File: api/absensi.php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['karyawan_data']['karyawan_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session karyawan tidak ditemukan']);
    exit;
}

$karyawan_id = $_SESSION['karyawan_data']['karyawan_id'];
$aksi = $_POST['aksi'] ?? json_decode(file_get_contents('php://input'), true)['aksi'] ?? '';
$date = date('Y-m-d');

if ($aksi === 'masuk') {
    // Cek sudah absen masuk hari ini
    $stmt = $conn->prepare('SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ?');
    $stmt->execute([$karyawan_id, $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['jam_masuk']) {
        echo json_encode(['success' => false, 'message' => 'Sudah absen masuk hari ini']);
        exit;
    }
    $jam_masuk = date('H:i:s');
    if ($row) {
        // Update
        $stmt = $conn->prepare('UPDATE absensi SET jam_masuk = ?, status = ? WHERE absensi_id = ?');
        $stmt->execute([$jam_masuk, 'hadir', $row['absensi_id']]);
    } else {
        // Insert
        $stmt = $conn->prepare('INSERT INTO absensi (absensi_id, karyawan_id, tanggal, jam_masuk, status) VALUES (UUID(), ?, ?, ?, ?)');
        $stmt->execute([$karyawan_id, $date, $jam_masuk, 'hadir']);
    }
    echo json_encode(['success' => true, 'message' => 'Absen masuk berhasil']);
    exit;
}
if ($aksi === 'pulang') {
    // Cek sudah absen masuk hari ini
    $stmt = $conn->prepare('SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ?');
    $stmt->execute([$karyawan_id, $date]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['jam_masuk']) {
        echo json_encode(['success' => false, 'message' => 'Belum absen masuk hari ini']);
        exit;
    }
    if ($row['jam_keluar']) {
        echo json_encode(['success' => false, 'message' => 'Sudah absen pulang hari ini']);
        exit;
    }
    $jam_keluar = date('H:i:s');
    $stmt = $conn->prepare('UPDATE absensi SET jam_keluar = ? WHERE absensi_id = ?');
    $stmt->execute([$jam_keluar, $row['absensi_id']]);
    echo json_encode(['success' => true, 'message' => 'Absen pulang berhasil']);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
exit;
