/**
 * ======================================================
 * SCRIPT GLOBAL (main.js)
 * Dimuat di semua halaman.
 * Meng-handle:
 * 1. Animasi loading screen (fade-out)
 * 2. Inisialisasi Feather Icons (feather.replace())
 * ======================================================
 */

window.addEventListener('load', () => {

    // 1. Sembunyikan Loading Screen
    const body = document.body;
    body.classList.add('is-loaded');

    setTimeout(() => {
        const loader = document.getElementById('loading-overlay');
        if (loader) {
            loader.remove();
        }
    }, 500); // Samakan dengan durasi transisi CSS (0.5s)

    // 2. Inisialisasi Feather Icons
    // Kita jalankan setelah 'load' untuk memastikan semua ikon sudah ada di DOM
    if (typeof feather !== 'undefined') {
        feather.replace();
    } else {
        console.warn('Feather Icons (icons.js) belum dimuat.');
    }
});

// REMOVED: Auto-fill form script that was causing auto-submit issues
// The automatic form modification was interfering with normal page behavior