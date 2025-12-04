<?php
/**
 * Gerbang Utama Aplikasi BersihXpress
 * 
 * File ini bertindak sebagai router awal untuk menentukan halaman mana yang harus
 * ditampilkan kepada pengguna berdasarkan perangkat yang digunakan (Browser vs WebView)
 * dan status sesi mereka (kunjungan pertama, sudah login, dll).
 */

// Selalu mulai session di awal untuk mengakses variabel $_SESSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Memuat semua fungsi helper yang kita butuhkan
require_once 'config/functions.php';

/**
 * Cek apakah request berasal dari Android WebView.
 * Fungsi isAndroidWebView() sudah ada di config/functions.php, 
 * yang memeriksa keberadaan 'wv' atau 'WebView' di User Agent.
 * Ini sudah cukup untuk sebagian besar kasus.
 */
$is_webview = isAndroidWebView();

// ===================================================================
// LOGIKA UTAMA (ROUTER)
// ===================================================================

if ($is_webview) {
    // --- PENGGUNA MENGAKSES DARI ANDROID WEBVIEW ---

    // Cek apakah pengguna sudah pernah menyelesaikan onboarding
    if (!isset($_SESSION['onboarding_completed'])) {
        /**
         * Ini adalah kunjungan pertama kali di WebView.
         * Arahkan ke halaman onboarding.
         * 
         * PENTING: Di akhir halaman onboarding.html, Anda harus memiliki
         * kode JavaScript yang memanggil sebuah skrip PHP untuk mengatur
         * session 'onboarding_completed'. Contoh: membuat file
         * `api/complete_onboarding.php` yang isinya:
         * 
         * <?php
         * session_start();
         * $_SESSION['onboarding_completed'] = true;
         * echo json_encode(['success' => true]);
         * ?>
         */
        redirect('onboarding.php');
    } else {
        /**
         * Pengguna sudah pernah onboarding.
         * Arahkan ke splash screen. Halaman splash screen ini yang kemudian
         * akan menangani logika pengecekan login dan redirect ke
         * halaman login atau dashboard.
         */
        redirect('splash-login.php');
    }

} else {
    // --- PENGGUNA MENGAKSES DARI BROWSER BIASA (CHROME, DLL) ---
    
    /**
     * Langsung arahkan ke halaman login.
     * Jika pengguna sudah login, fungsi isLoggedIn() di dalam file
     * auth/masuk.php akan menangani redirect ke dashboard.
     */
    redirect('auth/masuk.php');
}

?>
