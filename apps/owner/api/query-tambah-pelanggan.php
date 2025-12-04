<?php
require_once __DIR__ . '/../../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php');
        exit;
    }

    $bisnis_id = $_POST['bisnis_id'] ?? null;
    $nama = trim($_POST['nama'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $catatan = trim($_POST['catatan'] ?? '');

    $missing = [];
    if (empty($bisnis_id)) $missing[] = 'Bisnis';
    if (empty($nama)) $missing[] = 'Nama Pelanggan';

    if (!empty($missing)) {
        $msg = 'Field berikut wajib diisi: ' . implode(', ', $missing);
        header('Location: ../dashboard.php?error=1&msg=' . urlencode($msg));
        exit;
    }

    // Generate UUID v4
    function generate_uuid_v4() {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    $pelangganId = generate_uuid_v4();

    $stmt = $conn->prepare("INSERT INTO pelanggan (pelanggan_id, bisnis_id, nama, alamat, no_telepon, email, catatan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$pelangganId, $bisnis_id, $nama, $alamat ?: null, $no_telepon ?: null, $email ?: null, $catatan ?: null]);

    $successMsg = 'Pelanggan berhasil ditambahkan.';
    header('Location: ../dashboard.php?success=1&msg=' . urlencode($successMsg));
    exit;

} catch (Exception $e) {
    error_log('Error query-tambah-pelanggan: ' . $e->getMessage());
    $detail = substr($e->getMessage(), 0, 300);
    $msg = 'Gagal menambah pelanggan. Detail: ' . $detail;
    header('Location: ../dashboard.php?error=1&msg=' . urlencode($msg));
    exit;
}
