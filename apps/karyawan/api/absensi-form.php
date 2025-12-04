<?php
// File: api/absensi-form.php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['karyawan_data']['karyawan_id'])) {
    header('Location: ../dashboard.php?absensi=error');
    exit;
}

$karyawan_id = $_SESSION['karyawan_data']['karyawan_id'];
$aksi = $_POST['aksi'] ?? '';
$waktu = $_POST['waktu'] ?? date('Y-m-d H:i:s');
$tanggal = date('Y-m-d', strtotime($waktu));

if ($aksi === 'masuk') {
    // Cek sudah absen masuk hari ini
    $stmt = $conn->prepare('SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ?');
    $stmt->execute([$karyawan_id, $tanggal]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['jam_masuk']) {
        header('Location: ../dashboard.php?absensi=duplikat_masuk');
        exit;
    }
    $jam_masuk = date('H:i:s', strtotime($waktu));
    if ($row) {
        $stmt = $conn->prepare('UPDATE absensi SET jam_masuk = ?, status = ? WHERE absensi_id = ?');
        $stmt->execute([$jam_masuk, 'hadir', $row['absensi_id']]);
    } else {
        $stmt = $conn->prepare('INSERT INTO absensi (absensi_id, karyawan_id, tanggal, jam_masuk, status) VALUES (UUID(), ?, ?, ?, ?)');
        $stmt->execute([$karyawan_id, $tanggal, $jam_masuk, 'hadir']);
    }
    header('Location: ../dashboard.php?absensi=masuk_ok');
    exit;
}
if ($aksi === 'pulang') {
    $stmt = $conn->prepare('SELECT * FROM absensi WHERE karyawan_id = ? AND tanggal = ?');
    $stmt->execute([$karyawan_id, $tanggal]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !$row['jam_masuk']) {
        header('Location: ../dashboard.php?absensi=belum_masuk');
        exit;
    }
    if ($row['jam_keluar']) {
        header('Location: ../dashboard.php?absensi=duplikat_pulang');
        exit;
    }
    $jam_keluar = date('H:i:s', strtotime($waktu));
    $stmt = $conn->prepare('UPDATE absensi SET jam_keluar = ? WHERE absensi_id = ?');
    $stmt->execute([$jam_keluar, $row['absensi_id']]);
    header('Location: ../dashboard.php?absensi=pulang_ok');
    exit;
}
header('Location: ../dashboard.php?absensi=error');
exit;
