<?php
require_once __DIR__ . '/middleware/auth_karyawan.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/functions.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }

    $karyawan = $_SESSION['karyawan_data'] ?? null;
    $userId = $karyawan['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('Session tidak valid');
    }

    $passwordLama = trim($_POST['password_lama'] ?? '');
    $passwordBaru = trim($_POST['password_baru'] ?? '');
    $passwordKonf = trim($_POST['password_konfirmasi'] ?? '');

    if (empty($passwordLama) || empty($passwordBaru) || empty($passwordKonf)) {
        throw new Exception('Semua field harus diisi');
    }

    if ($passwordBaru !== $passwordKonf) {
        throw new Exception('Konfirmasi kata sandi tidak cocok');
    }

    if (strlen($passwordBaru) < 6) {
        throw new Exception('Kata sandi minimal 6 karakter');
    }

    // Ambil hash password saat ini
    $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('User tidak ditemukan');
    }

    $currentHash = $row['password'] ?? '';
    if (!password_verify($passwordLama, $currentHash)) {
        throw new Exception('Password lama tidak cocok');
    }

    // Update password
    $newHash = password_hash($passwordBaru, PASSWORD_DEFAULT);
    $update = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
    $update->execute([$newHash, $userId]);

    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_message'] = 'Password berhasil diperbarui';
} catch (Exception $e) {
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = $e->getMessage();
}

header('Location: profile.php');
exit;

?>
