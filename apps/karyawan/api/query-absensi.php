<?php
// File: api/query-absensi.php
// Handler absensi karyawan (masuk/pulang) via form POST
$bisnis_id = $_SESSION['karyawan_data']['bisnis_id'] ?? '';
$karyawan_id = $_SESSION['karyawan_data']['karyawan_id'] ?? '';

require_once '../../../config/database.php';
require_once '../../../config/functions.php';

// --- Helper untuk alert dev ---
function dev_alert($msg) {
    echo "<script>alert('" . addslashes($msg) . "'); window.history.back();</script>";
    exit;
}

// --- Validasi session karyawan ---
if (!isset($_SESSION['karyawan_id'])) {
    dev_alert('Session karyawan tidak ditemukan!');
}

$karyawan_id = $_SESSION['karyawan_id'];
$aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';
$waktu = isset($_POST['waktu']) ? $_POST['waktu'] : '';

if (!$aksi || !$waktu) {
    dev_alert('Data absensi tidak lengkap!');
}

// --- Validasi aksi ---
if ($aksi !== 'masuk' && $aksi !== 'pulang') {
    dev_alert('Aksi absensi tidak valid!');
}

// --- Query insert absensi ---
$conn = getConnection();

// --- Cek absensi hari ini ---
$tgl = substr($waktu, 0, 10); // format YYYY-MM-DD
$sql_cek = "SELECT * FROM absensi WHERE karyawan_id=? AND tanggal=?";
$stmt_cek = $conn->prepare($sql_cek);
$stmt_cek->bind_param('is', $karyawan_id, $tgl);
$stmt_cek->execute();
$res_cek = $stmt_cek->get_result();
$row = $res_cek->fetch_assoc();

if ($aksi === 'masuk') {
    if ($row && $row['jam_masuk']) {
        dev_alert('Sudah absen masuk hari ini!');
    }
    // Insert atau update absensi masuk
    if ($row) {
        $sql = "UPDATE absensi SET jam_masuk=? WHERE karyawan_id=? AND tanggal=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sis', $waktu, $karyawan_id, $tgl);
    } else {
        $sql = "INSERT INTO absensi (karyawan_id, tanggal, jam_masuk) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $karyawan_id, $tgl, $waktu);
    }
} else if ($aksi === 'pulang') {
    if ($row && $row['jam_pulang']) {
        dev_alert('Sudah absen pulang hari ini!');
    }
    if (!$row || !$row['jam_masuk']) {
        dev_alert('Belum absen masuk hari ini!');
    }
    // Update absensi pulang
    $sql = "UPDATE absensi SET jam_pulang=? WHERE karyawan_id=? AND tanggal=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sis', $waktu, $karyawan_id, $tgl);
}

if ($stmt->execute()) {
    dev_alert('Absensi berhasil!');
} else {
    dev_alert('Absensi gagal: ' . $stmt->error);
}

$conn->close();
