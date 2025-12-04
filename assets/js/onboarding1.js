/**
 * ======================================================
 * SCRIPT KHUSUS: onboarding.html
 * Meng-handle:
 * 1. Logika slider fade-in/out
 * 2. Update indikator garis
 * 3. Koneksi ke Android (onboardingFinished)
 * 4. PERUBAHAN: Logika deteksi swipe
 * ======================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    // (Komentar) Pilih Elemen
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.pagination-dot');
    const btnNext = document.getElementById('btn-next');
    const btnRegister = document.getElementById('btn-register');
    const btnLogin = document.getElementById('btn-login');
    const btnsBack = document.querySelectorAll('.btn-back');
    const btnsSkip = document.querySelectorAll('.btn-skip');

    const totalSlides = slides.length;
    let currentSlide = 0;

    // (Komentar) FUNGSI UTAMA: goToSlide (Tidak berubah)
    function goToSlide(slideIndex) {
        if (slideIndex < 0) slideIndex = 0;
        if (slideIndex >= totalSlides) slideIndex = totalSlides - 1;

        // (Komentar) Cegah swipe jika halaman sudah sama
        if (slideIndex === currentSlide) return;

        slides[currentSlide].classList.add('opacity-0', 'pointer-events-none');
        slides[slideIndex].classList.remove('opacity-0', 'pointer-events-none');

        currentSlide = slideIndex;

        dots.forEach((dot, index) => {
            dot.classList.toggle('pagination-dot-active', index === slideIndex);
            dot.classList.toggle('pagination-dot', index !== slideIndex);
        });

        if (slideIndex === totalSlides - 1) { // (Komentar) Slide terakhir
            btnNext.classList.add('hidden');
            btnRegister.classList.remove('hidden');
            btnLogin.classList.remove('hidden');
        } else { // (Komentar) Slide 1, 2, atau 3
            btnNext.classList.remove('hidden');
            btnRegister.classList.add('hidden');
            btnLogin.classList.add('hidden');
        }
    }

    // (Komentar) Listener Tombol (Tidak berubah)
    btnNext.addEventListener('click', () => {
        goToSlide(currentSlide + 1);
    });

    btnsBack.forEach(btn => {
        btn.addEventListener('click', () => {
            goToSlide(currentSlide - 1);
        });
    });

    // (Komentar) --- KONEKSI KE ANDROID WEBVIEW (Tidak berubah) ---

    function completeOnboardingAndNavigate(pageUrl) {
        const basePath = 'auth/';
        const finalUrl = basePath + pageUrl;

        // Memanggil API untuk menandai onboarding selesai
        fetch('api/complete_onboarding.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Jika sukses, baru arahkan ke halaman berikutnya
                    console.log('Onboarding status set.');
                    window.location.href = finalUrl;
                } else {
                    // Jika gagal, tetap arahkan pengguna agar tidak terjebak
                    console.error('Failed to set onboarding status, but navigating anyway.');
                    window.location.href = finalUrl;
                }
            })
            .catch(error => {
                console.error('Error setting onboarding status:', error);
                // Tetap arahkan pengguna agar tidak terjebak di halaman onboarding
                window.location.href = finalUrl;
            });
    }

    btnsSkip.forEach(btn => {
        btn.addEventListener('click', () => {
            goToSlide(totalSlides - 1);
        });
    });

    btnRegister.addEventListener('click', () => {
        completeOnboardingAndNavigate('daftar.php');
    });

    btnLogin.addEventListener('click', () => {
        completeOnboardingAndNavigate('masuk.php');
    });

    // (Komentar) ==================================================
    // (Komentar) PERBAIKAN: BLOK LOGIKA SWIPE BARU
    // (Komentar) ==================================================
    const swipeArea = document.getElementById('slider-container');
    let touchStartX = 0;
    let touchMoveX = 0;
    const swipeThreshold = 50; // (Komentar) Jarak minimal (pixel) agar dianggap swipe

    swipeArea.addEventListener('touchstart', (e) => {
        // (Komentar) Catat posisi X awal saat jari menyentuh layar
        touchStartX = e.touches[0].clientX;
        touchMoveX = 0; // (Komentar) Reset posisi gerak
    }, { passive: true });

    swipeArea.addEventListener('touchmove', (e) => {
        // (Komentar) Catat posisi X terakhir saat jari bergerak
        if (touchStartX !== 0) { // (Komentar) Hanya catat jika sentuhan sudah dimulai
            touchMoveX = e.touches[0].clientX;
        }
    }, { passive: true });

    swipeArea.addEventListener('touchend', () => {
        if (touchStartX === 0 || touchMoveX === 0) {
            // (Komentar) Ini hanya klik, bukan swipe, jadi abaikan.
            return;
        }

        // (Komentar) Hitung perbedaan geseran
        const deltaX = touchStartX - touchMoveX;

        if (deltaX > swipeThreshold) {
            // (Komentar) Jari menggeser ke KIRI (Posisi Awal > Posisi Akhir)
            // (Komentar) Pindah ke slide berikutnya
            goToSlide(currentSlide + 1);

        } else if (deltaX < -swipeThreshold) {
            // (Komentar) Jari menggeser ke KANAN (Posisi Awal < Posisi Akhir)
            // (Komentar) Pindah ke slide sebelumnya
            goToSlide(currentSlide - 1);
        }

        // (Komentar) Reset posisi untuk swipe berikutnya
        touchStartX = 0;
        touchMoveX = 0;
    });
    // (Komentar) ==================================================
    // (Komentar) AKHIR BLOK LOGIKA SWIPE
    // (Komentar) ==================================================

    // (Komentar) Inisialisasi tampilan (Tidak berubah)
    goToSlide(0);
});