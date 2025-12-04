<?php
require_once '../../../config/database.php';
require_once '../../../config/functions.php';

// Pastikan request dari Android WebView
enforceAndroidWebView();

// Cek autentikasi
if (!isLoggedIn() || getUserRole() !== 'owner') {
    sendResponse(false, 'Unauthorized access');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'setup_bisnis':
            setupBisnis();
            break;
        case 'update_bisnis':
            updateBisnis();
            break;
        case 'upload_logo':
            uploadLogo();
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
}

function setupBisnis() {
    global $conn;
    try {
        $conn->beginTransaction();
        
        $ownerId = $_SESSION['user_id'];
        $namaBisnis = sanitize($_POST['nama_bisnis']);
        $alamat = sanitize($_POST['alamat']);
        $noTelepon = sanitize($_POST['no_telepon']);
        
        // Validasi input
        if (empty($namaBisnis) || empty($alamat)) {
            sendResponse(false, 'Nama bisnis dan alamat harus diisi');
        }
        
        // Generate ID bisnis
        $bisnisId = generateUUID();
        
        // Upload logo jika ada
        $logo = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logo = handleLogoUpload($_FILES['logo'], $bisnisId);
        }
        
        // Insert data bisnis
        $stmt = $conn->prepare("
            INSERT INTO bisnis (
                bisnis_id, owner_id, nama_bisnis, 
                alamat, no_telepon, logo
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $bisnisId, $ownerId, $namaBisnis,
            $alamat, $noTelepon, $logo
        ]);
        
        // Buat template nota default
        $stmt = $conn->prepare("
            INSERT INTO template_nota (
                template_id, bisnis_id, header, footer, format_nota
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            generateUUID(),
            $bisnisId,
            $namaBisnis . "\n" . $alamat . "\n" . $noTelepon,
            "Terima kasih atas kepercayaan Anda",
            "NOTA_HEADER\nINFO_PELANGGAN\nDETAIL_LAYANAN\nTOTAL_HARGA\nNOTA_FOOTER"
        ]);
        
        // Buat template pesan default
        $templatePesan = [
            'masuk' => "Terima kasih telah menggunakan jasa laundry kami. Nomor nota Anda: {NO_NOTA}",
            'proses' => "Pesanan Anda sedang dalam proses pengerjaan. Estimasi selesai: {ESTIMASI_SELESAI}",
            'selesai' => "Pesanan Anda telah selesai dan siap diambil. Total tagihan: Rp{TOTAL_HARGA}",
            'pembayaran' => "Pembayaran sebesar Rp{JUMLAH_BAYAR} telah diterima. Sisa tagihan: Rp{SISA_TAGIHAN}"
        ];
        
        foreach ($templatePesan as $jenis => $isi) {
            $stmt = $conn->prepare("
                INSERT INTO template_pesan (
                    template_id, bisnis_id, jenis, isi_pesan
                ) VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                generateUUID(),
                $bisnisId,
                $jenis,
                $isi
            ]);
        }
        
        $conn->commit();
        sendResponse(true, 'Setup bisnis berhasil', ['redirect' => 'dashboard.php']);
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Error in setup bisnis', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function handleLogoUpload($file, $bisnisId) {
    $targetDir = "../../../assets/uploads/logo/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png'];
    
    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('Format file tidak didukung');
    }
    
    $newFileName = $bisnisId . '.' . $fileExtension;
    $targetPath = $targetDir . $newFileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Gagal mengupload file');
    }
    
    return 'assets/uploads/logo/' . $newFileName;
}

function updateBisnis() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $namaBisnis = sanitize($_POST['nama_bisnis']);
        $alamat = sanitize($_POST['alamat']);
        $noTelepon = sanitize($_POST['no_telepon']);
        
        if (empty($namaBisnis) || empty($alamat)) {
            sendResponse(false, 'Nama bisnis dan alamat harus diisi');
        }
        
        // Update data bisnis
        $stmt = $conn->prepare("
            UPDATE bisnis 
            SET nama_bisnis = ?,
                alamat = ?,
                no_telepon = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE bisnis_id = ?
        ");
        $stmt->execute([$namaBisnis, $alamat, $noTelepon, $bisnisId]);
        
        // Update header template nota
        $headerNota = $namaBisnis . "\n" . $alamat . "\n" . $noTelepon;
        $stmt = $conn->prepare("
            UPDATE template_nota 
            SET header = ?
            WHERE bisnis_id = ?
        ");
        $stmt->execute([$headerNota, $bisnisId]);
        
        sendResponse(true, 'Data bisnis berhasil diperbarui');
    } catch (PDOException $e) {
        logError('Error updating bisnis', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function uploadLogo() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(false, 'File logo tidak ditemukan');
        }
        
        $logoPath = handleLogoUpload($_FILES['logo'], $bisnisId);
        
        $stmt = $conn->prepare("
            UPDATE bisnis 
            SET logo = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE bisnis_id = ?
        ");
        $stmt->execute([$logoPath, $bisnisId]);
        
        sendResponse(true, 'Logo berhasil diperbarui', ['logo_path' => $logoPath]);
    } catch (Exception $e) {
        logError('Error uploading logo', ['error' => $e->getMessage()]);
        sendResponse(false, $e->getMessage());
    }
}
?>