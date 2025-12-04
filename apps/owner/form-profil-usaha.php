<?php
error_log('POST: ' . print_r($_POST, true));
require_once __DIR__ . '/middleware/auth_owner.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('kelola.php');
}

$bisnisId = $_SESSION['owner_data']['bisnis_id'] ?? null;

if (!$bisnisId) {
    $_SESSION['owner_flash_error'] = 'Data bisnis tidak ditemukan. Silakan masuk ulang.';
    redirect('kelola.php');
}

$namaBisnis = sanitize($_POST['nama_bisnis'] ?? '');
$alamat = sanitize($_POST['alamat'] ?? '');
$noTelepon = sanitize($_POST['no_telepon'] ?? '');
$jamOperasional = sanitize($_POST['jam_operasional'] ?? '');

$errors = [];

if ($namaBisnis === '') {
    $errors[] = 'Nama bisnis wajib diisi.';
}

if ($alamat === '') {
    $errors[] = 'Alamat bisnis wajib diisi.';
}

if ($noTelepon !== '' && !preg_match('/^[0-9+\s-]+$/', $noTelepon)) {
    $errors[] = 'Format nomor telepon tidak valid.';
}

if (!empty($errors)) {
    $_SESSION['owner_flash_error'] = implode(' ', $errors);
    redirect('kelola.php');
}

try {
    $stmt = $conn->prepare('
        UPDATE bisnis
        SET nama_bisnis = ?,
            alamat = ?,
            no_telepon = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE bisnis_id = ?
    ');
    $stmt->execute([$namaBisnis, $alamat, $noTelepon !== '' ? $noTelepon : null, $bisnisId]);

    // Simpan jam operasional di session agar tetap muncul pada form
    if ($jamOperasional !== '') {
        $_SESSION['owner_data']['jam_operasional'] = $jamOperasional;
    } else {
        unset($_SESSION['owner_data']['jam_operasional']);
    }

    // Perbarui data bisnis di session agar konsisten dengan tampilan terbaru
    $_SESSION['owner_data']['nama_bisnis'] = $namaBisnis;
    $_SESSION['owner_data']['alamat'] = $alamat;
    $_SESSION['owner_data']['no_telepon'] = $noTelepon;

    $_SESSION['owner_flash_success'] = 'Profil usaha berhasil diperbarui.';
} catch (PDOException $e) {
    logError('Update profil usaha gagal', [
        'error' => $e->getMessage(),
        'bisnis_id' => $bisnisId,
    ]);
    $_SESSION['owner_flash_error'] = 'Terjadi kesalahan saat memperbarui profil usaha.';
}

redirect('kelola.php');
