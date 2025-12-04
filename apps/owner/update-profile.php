<?php
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/../../config/database.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => 'profile.php'
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method tidak diizinkan');
    }


    $ownerId = $_SESSION['owner_data']['owner_id'] ?? null;
    $bisnisId = $_SESSION['owner_data']['bisnis_id'] ?? null;
    if (!$ownerId || !$bisnisId) {
        throw new Exception('Session tidak valid');
    }

    // Get form data
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noTelepon = trim($_POST['no_telepon'] ?? '');
    $logoBisnis = null;

    // Handle upload logo bisnis
    $uploadLogoStatus = null;
    if (isset($_FILES['profil_upload']) && $_FILES['profil_upload']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profil_upload']['tmp_name'];
        $fileName = basename($_FILES['profil_upload']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $newName = 'logo_' . $bisnisId . '_' . time() . '.' . $ext;
            $logoRelPath = 'assets/logo/' . $newName;
            $targetDir = realpath(__DIR__ . '/../../assets/logo');
            if ($targetDir === false) {
                // Folder belum ada, buat dulu
                $baseAssets = realpath(__DIR__ . '/../../assets');
                $targetDir = $baseAssets . '/logo';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
            }
            $targetPath = $targetDir . '/' . $newName;
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $logoBisnis = $logoRelPath;
                $uploadLogoStatus = 'success';
            } else {
                $uploadLogoStatus = 'error';
            }
        } else {
            $uploadLogoStatus = 'invalid';
        }
    }

    // Validasi input
    if (empty($namaLengkap)) {
        throw new Exception('Nama lengkap tidak boleh kosong');
    }

    if (strlen($namaLengkap) < 2) {
        throw new Exception('Nama lengkap minimal 2 karakter');
    }

    if (strlen($namaLengkap) > 100) {
        throw new Exception('Nama lengkap maksimal 100 karakter');
    }

    // Validasi nomor telepon jika diisi
    if (!empty($noTelepon)) {
        // Remove spaces and special characters for validation
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $noTelepon);
        
        if (!preg_match('/^[\+]?[0-9]{8,15}$/', $cleanPhone)) {
            throw new Exception('Format nomor telepon tidak valid (8-15 digit)');
        }
    }


    // Update users table (nama, email, no_telepon)
    $sqlUser = "UPDATE users SET nama_lengkap = ?, email = ?, no_telepon = ? WHERE user_id = ?";
    $paramsUser = [$namaLengkap, $email, $noTelepon, $ownerId];
    $stmtUser = $conn->prepare($sqlUser);
    $resultUser = $stmtUser->execute($paramsUser);

    // Update bisnis logo if uploaded
    $resultBisnis = true;
    if ($logoBisnis) {
        $sqlBisnis = "UPDATE bisnis SET logo = ? WHERE bisnis_id = ?";
        $stmtBisnis = $conn->prepare($sqlBisnis);
        $resultBisnis = $stmtBisnis->execute([$logoBisnis, $bisnisId]);
    }

    if (!$resultUser || !$resultBisnis) {
        throw new Exception('Gagal memperbarui data ke database');
    }

    if ($stmtUser->rowCount() === 0 && !$logoBisnis) {
        throw new Exception('Tidak ada perubahan data atau user tidak ditemukan');
    }

    // Set flash message sesuai status upload logo
    if ($uploadLogoStatus === 'success') {
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = 'Profil berhasil diperbarui. Logo bisnis berhasil diupload.';
        $response['success'] = true;
        $response['message'] = 'Profil berhasil diperbarui. Logo bisnis berhasil diupload.';
    } elseif ($uploadLogoStatus === 'error') {
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = 'Profil berhasil diperbarui, namun logo bisnis gagal diupload.';
        $response['success'] = false;
        $response['message'] = 'Profil berhasil diperbarui, namun logo bisnis gagal diupload.';
    } elseif ($uploadLogoStatus === 'invalid') {
        $_SESSION['flash_type'] = 'error';
        $_SESSION['flash_message'] = 'Profil berhasil diperbarui, namun format file logo tidak didukung.';
        $response['success'] = false;
        $response['message'] = 'Profil berhasil diperbarui, namun format file logo tidak didukung.';
    } else {
        $_SESSION['flash_type'] = 'success';
        $_SESSION['flash_message'] = 'Profil berhasil diperbarui.';
        $response['success'] = true;
        $response['message'] = 'Profil berhasil diperbarui.';
    }

} catch (Exception $e) {
    // Set error message
    $_SESSION['flash_type'] = 'error';
    $_SESSION['flash_message'] = $e->getMessage();
    
    $response['message'] = $e->getMessage();
}

// Always redirect back to profile page
header('Location: profile.php');
exit;
?>