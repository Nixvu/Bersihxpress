<?php

require_once __DIR__ . '/../middleware/auth_owner.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';
require_once __DIR__ . '/../models/pelanggan.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['pelanggan_flash_error'] = 'Metode request tidak valid.';
    redirect('../pelanggan.php');
}

$bisnisId = $_SESSION['owner_data']['bisnis_id'] ?? null;

if (!$bisnisId) {
    $_SESSION['pelanggan_flash_error'] = 'Data bisnis tidak ditemukan. Silakan masuk ulang.';
    redirect('../pelanggan.php');
}

$action = $_POST['action'] ?? '';
$redirectUrl = '../pelanggan.php';

try {
    switch ($action) {
        case 'create_customer':
            $result = pelanggan_create($bisnisId, $_POST);
            if ($result['success']) {
                $_SESSION['pelanggan_flash_success'] = $result['message'];
            } else {
                throw new InvalidArgumentException($result['message']);
            }
            break;
        case 'update_customer':
            $result = pelanggan_update($bisnisId, $_POST);
            if ($result['success']) {
                $_SESSION['pelanggan_flash_success'] = $result['message'];
            } else {
                throw new InvalidArgumentException($result['message']);
            }
            break;
        case 'delete_customer':
            $result = pelanggan_delete($bisnisId, $_POST['pelanggan_id'] ?? '');
            if ($result['success']) {
                $_SESSION['pelanggan_flash_success'] = $result['message'];
            } else {
                throw new InvalidArgumentException($result['message']);
            }
            break;
        default:
            throw new InvalidArgumentException('Aksi tidak dikenal.');
    }
} catch (InvalidArgumentException $e) {
    $_SESSION['pelanggan_flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    logError('Query pelanggan gagal', [
        'error' => $e->getMessage(),
        'action' => $action,
        'bisnis_id' => $bisnisId,
    ]);
    $_SESSION['pelanggan_flash_error'] = 'Terjadi kesalahan saat memproses data pelanggan.';
}

redirect($redirectUrl);