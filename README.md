# BersihXpress

BersihXpress adalah aplikasi manajemen bisnis laundry/pembersihan berbasis web yang dirancang untuk membantu pemilik usaha dan karyawan mengelola operasional sehari-hari dengan efisien. Aplikasi ini mendukung dua jenis pengguna (Owner dan Karyawan) dengan antarmuka yang terpisah dan fungsionalitas yang disesuaikan. Selain itu, aplikasi ini dioptimalkan untuk akses melalui Android WebView, memberikan pengalaman mobile yang mulus.

## Fitur Utama

*   **Manajemen Multi-Peran**:
    *   **Owner**: Mengelola seluruh aspek bisnis, termasuk karyawan, layanan, pelanggan, transaksi, dan laporan keuangan.
    *   **Karyawan**: Mengelola transaksi pelanggan, absensi, dan melihat profil.
*   **Manajemen Transaksi**: Pencatatan, pembaruan, dan pelacakan transaksi laundry/pembersihan.
*   **Manajemen Pelanggan**: Mendata dan mengelola informasi pelanggan.
*   **Manajemen Layanan**: Mengelola daftar layanan yang ditawarkan beserta harga.
*   **Laporan Komprehensif**: Generasi laporan dalam format PDF (menggunakan mPDF) untuk analisis bisnis.
*   **Sistem Otentikasi**: Sistem login dan registrasi yang aman.
*   **Antarmuka Responsif**: Desain modern dan responsif menggunakan Tailwind CSS, memastikan tampilan yang baik di berbagai perangkat.
*   **Dukungan Android WebView**: Pengalaman pengguna yang dioptimalkan saat diakses melalui aplikasi Android WebView.

## Teknologi yang Digunakan

*   **Backend**: PHP
*   **Frontend**: HTML, CSS (dengan Tailwind CSS), JavaScript
*   **Database**: MySQL (diasumsikan)
*   **Dependensi PHP**:
    *   `mpdf/mpdf`: Untuk generasi laporan PDF.
*   **Dependensi Frontend (Dev)**:
    *   `tailwindcss`: Framework CSS Utility-first.
    *   `postcss`, `autoprefixer`: Untuk pemrosesan CSS.

## Persyaratan Sistem

*   Web server (Apache, Nginx, atau sejenisnya)
*   PHP 7.4+ (atau versi yang kompatibel)
*   MySQL/MariaDB
*   Composer
*   Node.js & NPM/Yarn (untuk pengembangan frontend jika Anda ingin memodifikasi styling Tailwind CSS)

## Panduan Instalasi

Ikuti langkah-langkah berikut untuk menginstal dan menjalankan proyek BersihXpress:

1.  **Clone Repositori:**
    ```bash
    git clone https://github.com/your-username/BersihXpress.git
    cd BersihXpress
    ```
    *(Ganti `https://github.com/your-username/BersihXpress.git` dengan URL repositori Anda)*

2.  **Konfigurasi Database:**
    *   Buat database baru di server MySQL/MariaDB Anda (misal: `bersihxpress`).
    *   Impor skema database dari file `config/bersihxpress.sql` ke database yang baru Anda buat.
    *   Perbarui kredensial database di `config/database.php` (atau file konfigurasi database yang relevan) sesuai dengan pengaturan Anda.

3.  **Instal Dependensi PHP:**
    ```bash
    composer install
    ```

4.  **Instal Dependensi Frontend (Opsional - untuk pengembangan styling):**
    ```bash
    npm install
    # atau jika Anda menggunakan yarn
    # yarn install
    ```

5.  **Konfigurasi Web Server:**
    *   Arahkan `Document Root` server web Anda ke direktori `BersihXpress/`.
    *   Pastikan PHP diaktifkan dan terkonfigurasi dengan benar di server Anda.
    *   Pastikan file dan folder memiliki izin yang benar agar web server dapat membacanya.

6.  **Akses Aplikasi:**
    *   Buka browser Anda dan navigasikan ke URL di mana aplikasi Anda di-host (misal: `http://localhost/BersihXpress`).
    *   Jika Anda mengakses dari browser, Anda akan diarahkan ke halaman login (`auth/masuk.php`).
    *   Jika Anda mensimulasikan akses dari Android WebView (dengan memodifikasi User Agent atau membuat aplikasi WebView), Anda akan melalui alur onboarding terlebih dahulu.  

## Penggunaan

*   **Untuk Owner**: Akses melalui browser web biasa dan masuk menggunakan kredensial owner.
*   > Login Owner : [**Email :** owner@bersihxpress.com | **Password  :** owner123 ]
*   **Untuk Karyawan**: Karyawan dapat masuk melalui browser web atau melalui Android WebView jika aplikasi mobile disediakan.
*   > Login Karyawan : [**Email :** karyawan@bersihxpress.com | **Password  :** karyawan123 ]

## Kontribusi

Jika Anda ingin berkontribusi pada proyek ini, silakan fork repositori, buat branch baru, lakukan perubahan Anda, dan ajukan pull request.
