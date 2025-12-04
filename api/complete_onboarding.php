<?php
// api/complete_onboarding.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session untuk menandakan onboarding telah selesai
$_SESSION['onboarding_completed'] = true;

// Beri respons JSON untuk konfirmasi
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Onboarding status has been set.']);
?>
