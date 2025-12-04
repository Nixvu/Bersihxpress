<?php
require_once __DIR__ . '/middleware/auth_karyawan.php';
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

    // Use karyawan session, not owner
    $karyawanData = $_SESSION['karyawan_data'] ?? null;
    $userId = $karyawanData['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('Session tidak valid');
    }

    // Get form data
    $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noTelepon = trim($_POST['no_telepon'] ?? '');
    $fotoProfil = null;

    // Handle upload foto
    if (isset($_FILES['profil_upload']) && $_FILES['profil_upload']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profil_upload']['tmp_name'];
        $fileName = basename($_FILES['profil_upload']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($ext, $allowed)) {
            $newName = 'profil_' . $userId . '_' . time() . '.' . $ext;
            $targetPath = '../../assets/images/profil/' . $newName;
            if (!is_dir('../../assets/images/profil')) {
                mkdir('../../assets/images/profil', 0777, true);
            }
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $fotoProfil = 'assets/images/profil/' . $newName;
            }
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

    // Update database
    $sql = "UPDATE users SET nama_lengkap = ?, email = ?, no_telepon = ?";
    $params = [$namaLengkap, $email, $noTelepon];
    if ($fotoProfil) {
        $sql .= ", foto_profil = ?";
        $params[] = $fotoProfil;
    }
    $sql .= " WHERE user_id = ?";
    $params[] = $userId;
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($params);

    if (!$result) {
        throw new Exception('Gagal memperbarui data ke database');
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception('Tidak ada perubahan data atau user tidak ditemukan');
    }

    // Set success message
    $_SESSION['flash_type'] = 'success';
    $_SESSION['flash_message'] = 'Profil berhasil diperbarui';
    
    $response['success'] = true;
    $response['message'] = 'Profil berhasil diperbarui';

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