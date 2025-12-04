<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
// Pastikan request dari Android WebView
// enforceAndroidWebView();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'login':
            handleLogin();
            break;
        case 'register':
            handleRegister();
            break;
        case 'verify_otp':
            handleOTPVerification();
            break;
        case 'reset_password':
            handleResetPassword();
            break;
                        case 'update_password':
                            handleUpdatePassword();
                            break;
                        case 'resend_otp':
                            handleResendOTP();
                            break;
                        default:            sendResponse(false, 'Invalid action');
    }
}

function handleLogin() {
    global $conn;
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Email dan password harus diisi');
    }
    
    try {
        $stmt = $conn->prepare("SELECT user_id, password, role, nama_lengkap FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama_lengkap'];

            // Jika role karyawan, ambil data karyawan dan set session
            if ($user['role'] === 'karyawan') {
                $stmtK = $conn->prepare("SELECT * FROM karyawan WHERE user_id = ? LIMIT 1");
                $stmtK->execute([$user['user_id']]);
                $karyawanData = $stmtK->fetch(PDO::FETCH_ASSOC);
                if ($karyawanData) {
                    $_SESSION['karyawan_data'] = $karyawanData;
                }
            }

            // Redirect berdasarkan role
            $redirect = $user['role'] === 'owner' ? '../apps/owner/dashboard.php' : '../apps/karyawan/dashboard.php';
            sendResponse(true, 'Login berhasil', ['redirect' => $redirect]);
        } else {
            sendResponse(false, 'Email atau password salah');
        }
    } catch (PDOException $e) {
        logError('Login error', ['email' => $email, 'error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function handleRegister() {
    global $conn;
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $nama = sanitize($_POST['nama_lengkap'] ?? '');
    $no_telepon = sanitize($_POST['no_telepon'] ?? '');
    
    if (empty($email) || empty($password) || empty($nama)) {
        sendResponse(false, 'Semua field harus diisi');
    }
    
    if (!validateEmail($email)) {
        sendResponse(false, 'Format email tidak valid');
    }
    
    try {
        // Cek email sudah terdaftar
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(false, 'Email sudah terdaftar');
        }
        
        // Generate OTP dan simpan ke session
        $otp = generateOTP();
        $_SESSION['register_otp'] = [
            'code' => $otp,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nama' => $nama,
            'no_telepon' => $no_telepon,
            'expires' => time() + (15 * 60) // 15 menit
        ];

        sendResponse(true, 'Kode verifikasi telah dibuat.', ['otp' => $otp]);
        
    } catch (PDOException $e) {
        logError('Register error', ['email' => $email, 'error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function handleOTPVerification() {
    global $conn;
    
    $otp = sanitize($_POST['otp'] ?? '');
    $type = sanitize($_POST['type'] ?? 'register'); // register atau reset
    
    if (empty($otp)) {
        sendResponse(false, 'OTP harus diisi');
    }
    
    $session_key = $type . '_otp';
    if (!isset($_SESSION[$session_key]) || time() > $_SESSION[$session_key]['expires']) {
        sendResponse(false, 'OTP telah kadaluarsa');
    }
    
    if ($otp !== $_SESSION[$session_key]['code']) {
        sendResponse(false, 'OTP tidak valid');
    }
    
    if ($type === 'register') {
        try {
            $user_id = generateUUID();
            $stmt = $conn->prepare("INSERT INTO users (user_id, email, password, role, nama_lengkap, no_telepon) VALUES (?, ?, ?, 'owner', ?, ?)");
            $stmt->execute([
                $user_id,
                $_SESSION[$session_key]['email'],
                $_SESSION[$session_key]['password'],
                $_SESSION[$session_key]['nama'],
                $_SESSION[$session_key]['no_telepon']
            ]);

            // Generate nama bisnis unik: 'Nama Bisnis 1', 'Nama Bisnis 2', dst
            $baseNamaBisnis = 'Nama Bisnis';
            $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM bisnis WHERE nama_bisnis LIKE ?");
            $stmtCount->execute([$baseNamaBisnis . '%']);
            $count = (int)($stmtCount->fetchColumn());
            $nama_bisnis = $baseNamaBisnis . ' ' . ($count + 1);

            $bisnis_id = generateUUID();
            $alamat = '';
            $no_telepon = $_SESSION[$session_key]['no_telepon'];
            $stmtBisnis = $conn->prepare("INSERT INTO bisnis (bisnis_id, owner_id, nama_bisnis, alamat, no_telepon) VALUES (?, ?, ?, ?, ?)");
            $stmtBisnis->execute([
                $bisnis_id,
                $user_id,
                $nama_bisnis,
                $alamat,
                $no_telepon
            ]);

            // Insert kategori default: kiloan, meteran, satuan
            $kategoriDefaults = [
                'Kiloan',
                'Meteran',
                'Satuan'
            ];
            $stmtKategori = $conn->prepare("INSERT INTO kategori_layanan (kategori_id, bisnis_id, nama_kategori) VALUES (?, ?, ?)");
            foreach ($kategoriDefaults as $namaKategori) {
                $kategori_id = generateUUID();
                $stmtKategori->execute([
                    $kategori_id,
                    $bisnis_id,
                    $namaKategori
                ]);
            }

            // Insert template pesan default untuk bisnis baru
            $templateDefaults = [
                ['jenis' => 'masuk', 'isi_pesan' => 'Hai [NAMA_PELANGGAN], pesanan Anda di [NAMA_OUTLET] dengan ID [ID_NOTA] telah kami terima. Total biaya: [TOTAL_HARGA]. Estimasi selesai: [ESTIMASI_SELESAI].'],
                ['jenis' => 'proses', 'isi_pesan' => 'Hai [NAMA_PELANGGAN], pesanan Anda [ID_NOTA] sedang kami proses cuci dan setrika.'],
                ['jenis' => 'selesai', 'isi_pesan' => 'Hai [NAMA_PELANGGAN], pesanan Anda [ID_NOTA] sudah selesai dan siap diambil. Total tagihan: [TOTAL_HARGA].'],
                ['jenis' => 'pembayaran', 'isi_pesan' => 'Hai [NAMA_PELANGGAN],   terima kasih telah melunasi pembayaran untuk pesanan [ID_NOTA] sebesar [TOTAL_HARGA].'],
            ];
            $stmtTemplatePesan = $conn->prepare("INSERT INTO template_pesan (template_id, bisnis_id, jenis, isi_pesan, is_active) VALUES (?, ?, ?, ?, 1)");
            foreach ($templateDefaults as $tpl) {
                $template_id = generateUUID();
                $stmtTemplatePesan->execute([
                    $template_id,
                    $bisnis_id,
                    $tpl['jenis'],
                    $tpl['isi_pesan']
                ]);
            }

            $_SESSION['registration_success'] = true;
            unset($_SESSION[$session_key]);
            sendResponse(true, 'Registrasi berhasil', ['redirect' => 'selesai_daftar.php']);
            
        } catch (PDOException $e) {
            logError('OTP verification error', ['error' => $e->getMessage()]);
            sendResponse(false, 'Terjadi kesalahan sistem');
        }
    } else if ($type === 'reset') {
        // OTP valid, set session verified dan redirect ke halaman update sandi
        $_SESSION[$session_key]['verified'] = true;
        sendResponse(true, 'Verifikasi OTP berhasil', ['redirect' => 'update_sandi.php']);
    }
}

function handleResetPassword() {
    global $conn;
    $email = sanitize($_POST['email'] ?? '');
    if (empty($email)) {
        sendResponse(false, 'Email harus diisi');
    }
    try {
        $stmt = $conn->prepare("SELECT user_id, nama_lengkap FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($user = $stmt->fetch()) {
            $otp = generateOTP();
            $_SESSION['reset_otp'] = [
                'code' => $otp,
                'email' => $email,
                'user_id' => $user['user_id'],
                'expires' => time() + (15 * 60)
            ];
                                                    sendResponse(true, 'Kode verifikasi telah dibuat.', ['otp' => $otp]);        } else {
            sendResponse(false, 'Email tidak terdaftar');
        }
    } catch (PDOException $e) {
        logError('Reset password error', ['email' => $email, 'error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function handleUpdatePassword() {
    global $conn;
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['reset_otp']['verified']) || $_SESSION['reset_otp']['verified'] !== true) {
        sendResponse(false, 'Session tidak valid, silakan ulangi proses.');
    }
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($email) || empty($password) || empty($confirm)) {
        sendResponse(false, 'Semua field harus diisi');
    }
    if ($password !== $confirm) {
        sendResponse(false, 'Konfirmasi kata sandi tidak cocok');
    }
    if (strlen($password) < 6) {
        sendResponse(false, 'Kata sandi minimal 6 karakter');
    }
    try {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $email]);
        unset($_SESSION['reset_otp']);
        $_SESSION['password_reset_success'] = true;
        sendResponse(true, 'Kata sandi berhasil diubah', ['redirect' => 'selesai_sandi.php']);
    } catch (PDOException $e) {
        logError('Update password error', ['email' => $email, 'error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function handleResendOTP() {
    global $conn;
    $email = sanitize($_POST['email'] ?? '');
    $type = sanitize($_POST['type'] ?? ''); // 'register' atau 'reset'

    if (empty($email) || empty($type)) {
        sendResponse(false, 'Email dan jenis OTP harus disediakan.');
    }

    // Validasi email berdasarkan jenis OTP
    if ($type === 'reset') {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Email tidak terdaftar.');
        }
    } elseif ($type === 'register') {
        if (!isset($_SESSION['register_otp']) || $_SESSION['register_otp']['email'] !== $email) {
            sendResponse(false, 'Sesi pendaftaran tidak valid atau email tidak cocok.');
        }
    } else {
        sendResponse(false, 'Jenis OTP tidak valid.');
    }
    
    $otp = generateOTP();
    $session_key = $type . '_otp';
    
    // Update OTP di session
    if ($type === 'register') {
        $_SESSION[$session_key]['code'] = $otp;
        $_SESSION[$session_key]['expires'] = time() + (15 * 60); // 15 menit
    } elseif ($type === 'reset') {
        $_SESSION[$session_key]['code'] = $otp;
        $_SESSION[$session_key]['expires'] = time() + (15 * 60); // 15 menit
        // Pertahankan user_id jika sudah ada dari proses reset password sebelumnya
        $_SESSION[$session_key]['user_id'] = $_SESSION[$session_key]['user_id'] ?? null; 
    }

    sendResponse(true, 'Kode OTP baru telah dikirim.', ['otp' => $otp]);
}