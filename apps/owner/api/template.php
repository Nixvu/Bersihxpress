<?php
session_start();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';
require_once __DIR__ . '/../middleware/auth_owner.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;

if (!$bisnisId) {
    $_SESSION['template_flash_error'] = 'Akses tidak valid.';
    header('Location: ../template.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'update_nota') {
        $header = sanitize($_POST['header'] ?? '');
        $footer = sanitize($_POST['footer'] ?? '');
        $logoFileName = null;
        
        if (empty($header)) {
            throw new InvalidArgumentException('Header nota tidak boleh kosong.');
        }
        
        // Handle logo upload
        if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['logo_upload']['tmp_name'];
            $fileName = basename($_FILES['logo_upload']['name']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array($ext, $allowed)) {
                $newName = 'logo_' . $bisnisId . '_' . time() . '.' . $ext;
                $targetPath = '../../../assets/logo/' . $newName;
                
                // Create directory if not exists
                if (!is_dir('../../../assets/logo')) {
                    mkdir('../../../assets/logo', 0777, true);
                }
                
                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $logoFileName = $newName;
                    
                    // Update logo in bisnis table
                    $stmt = $conn->prepare("UPDATE bisnis SET logo = ? WHERE bisnis_id = ?");
                    $stmt->execute([$logoFileName, $bisnisId]);
                }
            }
        }
        
        // Check if template exists
        $stmt = $conn->prepare("SELECT template_id FROM template_nota WHERE bisnis_id = ?");
        $stmt->execute([$bisnisId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing template
            $stmt = $conn->prepare("
                UPDATE template_nota 
                SET header = ?, footer = ?, format_nota = 'default', updated_at = CURRENT_TIMESTAMP 
                WHERE bisnis_id = ?
            ");
            $stmt->execute([$header, $footer, $bisnisId]);
        } else {
            // Insert new template
            $templateId = generateUUID();
            $stmt = $conn->prepare("
                INSERT INTO template_nota (template_id, bisnis_id, header, footer, format_nota) 
                VALUES (?, ?, ?, ?, 'default')
            ");
            $stmt->execute([$templateId, $bisnisId, $header, $footer]);
        }
        
        $successMessage = 'Template nota berhasil disimpan.';
        if ($logoFileName) {
            $successMessage .= ' Logo berhasil diupload.';
        }
        
        $_SESSION['template_flash_success'] = $successMessage;
        
    } elseif ($action === 'update_pesan') {
        $jenis = $_POST['jenis'] ?? '';
        $isiPesan = sanitize($_POST['isi_pesan'] ?? '');
        
        $allowedJenis = ['masuk', 'proses', 'selesai', 'pembayaran'];
        if (!in_array($jenis, $allowedJenis)) {
            throw new InvalidArgumentException('Jenis pesan tidak valid.');
        }
        
        if (empty($isiPesan)) {
            throw new InvalidArgumentException('Isi pesan tidak boleh kosong.');
        }
        
        // Check if template exists
        $stmt = $conn->prepare("SELECT template_id FROM template_pesan WHERE bisnis_id = ? AND jenis = ?");
        $stmt->execute([$bisnisId, $jenis]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing template
            $stmt = $conn->prepare("
                UPDATE template_pesan 
                SET isi_pesan = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE bisnis_id = ? AND jenis = ?
            ");
            $stmt->execute([$isiPesan, $bisnisId, $jenis]);
        } else {
            // Insert new template
            $templateId = generateUUID();
            $stmt = $conn->prepare("
                INSERT INTO template_pesan (template_id, bisnis_id, jenis, isi_pesan, is_active) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$templateId, $bisnisId, $jenis, $isiPesan]);
        }
        
        $_SESSION['template_flash_success'] = 'Template pesan berhasil disimpan.';
        
    } elseif ($action === 'delete_template_nota') {
        // Delete template nota
        $stmt = $conn->prepare("DELETE FROM template_nota WHERE bisnis_id = ?");
        $stmt->execute([$bisnisId]);
        
        $_SESSION['template_flash_success'] = 'Template nota berhasil dihapus.';
        
    } elseif ($action === 'delete_template_pesan') {
        $jenis = $_POST['jenis'] ?? '';
        
        $allowedJenis = ['masuk', 'proses', 'selesai', 'pembayaran'];
        if (!in_array($jenis, $allowedJenis)) {
            throw new InvalidArgumentException('Jenis pesan tidak valid.');
        }
        
        // Delete template pesan
        $stmt = $conn->prepare("DELETE FROM template_pesan WHERE bisnis_id = ? AND jenis = ?");
        $stmt->execute([$bisnisId, $jenis]);
        
        $_SESSION['template_flash_success'] = 'Template pesan berhasil dihapus.';
        
    } else {
        throw new InvalidArgumentException('Aksi tidak valid.');
    }
    
} catch (InvalidArgumentException $e) {
    $_SESSION['template_flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    $_SESSION['template_flash_error'] = 'Error database: ' . $e->getMessage();
} catch (Exception $e) {
    $_SESSION['template_flash_error'] = 'Error: ' . $e->getMessage();
}

header('Location: ../template.php');
exit;