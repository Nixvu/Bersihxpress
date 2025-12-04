<?php
require_once __DIR__ . '/../../../config/functions.php';
require_once __DIR__ . '/../../../config/database.php';

// Pastikan Android WebView
// enforceAndroidWebView();

// Cek apakah user sudah login
if (!isLoggedIn()) {
    // Jika request AJAX, kirim response JSON
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        sendResponse(false, 'Unauthorized access', null, 401);
        exit;
    }
    redirect('../../auth/masuk.php');
}

// Cek apakah user adalah owner
if (getUserRole() !== 'owner') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        sendResponse(false, 'Forbidden access', null, 403);
        exit;
    }
    redirect('../karyawan/dashboard.php');
}

// Ambil data owner dan bisnis
try {
    global $conn;
    
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        throw new Exception('Invalid user session');
    }
    
    // Ambil data owner
    $stmtOwner = $conn->prepare("
        SELECT u.*, b.* 
        FROM users u 
        LEFT JOIN bisnis b ON b.owner_id = u.user_id 
        WHERE u.user_id = ? AND u.role = 'owner' AND u.status = 'aktif'
    ");
    $stmtOwner->execute([$userId]);
    $ownerData = $stmtOwner->fetch();
    
    if (!$ownerData) {
        throw new Exception('Invalid owner data');
    }
    
    // Jika belum ada data bisnis dan bukan di halaman setup
    $currentPage = basename($_SERVER['PHP_SELF']);
    if (!$ownerData['bisnis_id'] && $currentPage !== 'setup_bisnis.php') {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            sendResponse(false, 'Business setup required', ['redirect' => '../owner/setup_bisnis.php']);
            exit;
        }
        redirect('../owner/setup_bisnis.php');
    }
    
    // Simpan data ke session untuk penggunaan di halaman
    $_SESSION['owner_data'] = $ownerData;
    
} catch (Exception $e) {
    logError('Error in owner middleware', [
        'error' => $e->getMessage(),
        'user_id' => $userId ?? 'not set'
    ]);
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        sendResponse(false, 'System error occurred', null, 500);
        exit;
    }
    
    // session_destroy();
    // redirect('../../auth/masuk.php');
}
?>