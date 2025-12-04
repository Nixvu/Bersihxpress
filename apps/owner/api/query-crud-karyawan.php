<!-- <?php
require_once __DIR__ . '/../middleware/auth_owner.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../karyawan.php');
    exit;
}

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;

if (!$bisnisId) {
    $_SESSION['karyawan_flash_error'] = 'Data bisnis tidak tersedia';
    header('Location: ../karyawan.php');
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_employee':
            $namaLengkap = sanitize($_POST['nama_lengkap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $noTelepon = trim($_POST['no_telepon'] ?? '');
            $gajiPokok = filter_var($_POST['gaji_pokok'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
            $tanggalBergabung = $_POST['tanggal_bergabung'] ?? date('Y-m-d');

            if ($namaLengkap === '' || $email === '' || $password === '') {
                throw new InvalidArgumentException('Nama, email, dan password wajib diisi.');
            }

            if (!validateEmail($email)) {
                throw new InvalidArgumentException('Format email tidak valid.');
            }

            if (strlen($password) < 6) {
                throw new InvalidArgumentException('Password minimal 6 karakter.');
            }

            // Check email exists
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new InvalidArgumentException('Email sudah terdaftar.');
            }

            // Create user
            $userId = generateUUID();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare('
                INSERT INTO users (user_id, email, password, role, nama_lengkap, no_telepon)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId,
                $email,
                $hashedPassword,
                'karyawan',
                $namaLengkap,
                $noTelepon !== '' ? $noTelepon : null
            ]);

            // Create karyawan
            $karyawanId = generateUUID();
            $stmt = $conn->prepare('
                INSERT INTO karyawan (karyawan_id, user_id, bisnis_id, gaji_pokok, tanggal_bergabung)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $karyawanId,
                $userId,
                $bisnisId,
                $gajiPokok,
                $tanggalBergabung
            ]);

            $_SESSION['karyawan_flash_success'] = 'Karyawan baru berhasil ditambahkan.';
            break;

        case 'update_employee':
            $karyawanId = $_POST['karyawan_id'] ?? '';
            $namaLengkap = sanitize($_POST['nama_lengkap'] ?? '');
            $noTelepon = trim($_POST['no_telepon'] ?? '');
            $gajiPokok = filter_var($_POST['gaji_pokok'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;

            if ($karyawanId === '' || $namaLengkap === '') {
                throw new InvalidArgumentException('ID karyawan dan nama wajib diisi.');
            }

            // Get user_id from karyawan
            $stmt = $conn->prepare('SELECT user_id FROM karyawan WHERE karyawan_id = ? AND bisnis_id = ?');
            $stmt->execute([$karyawanId, $bisnisId]);
            $result = $stmt->fetch();
            if (!$result) {
                throw new InvalidArgumentException('Karyawan tidak ditemukan.');
            }

            $userId = $result['user_id'];

            // Update user data
            $stmt = $conn->prepare('UPDATE users SET nama_lengkap = ?, no_telepon = ? WHERE user_id = ?');
            $stmt->execute([
                $namaLengkap,
                $noTelepon !== '' ? $noTelepon : null,
                $userId
            ]);

            // Update karyawan data
            $stmt = $conn->prepare('UPDATE karyawan SET gaji_pokok = ? WHERE karyawan_id = ?');
            $stmt->execute([$gajiPokok, $karyawanId]);

            $_SESSION['karyawan_flash_success'] = 'Data karyawan berhasil diperbarui.';
            break;

        case 'reset_password':
            $karyawanId = $_POST['karyawan_id'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';

            if ($karyawanId === '' || $newPassword === '') {
                throw new InvalidArgumentException('ID karyawan dan password baru wajib diisi.');
            }

            if (strlen($newPassword) < 6) {
                throw new InvalidArgumentException('Password minimal 6 karakter.');
            }

            // Get user_id from karyawan
            $stmt = $conn->prepare('SELECT user_id FROM karyawan WHERE karyawan_id = ? AND bisnis_id = ?');
            $stmt->execute([$karyawanId, $bisnisId]);
            $result = $stmt->fetch();
            if (!$result) {
                throw new InvalidArgumentException('Karyawan tidak ditemukan.');
            }

            $userId = $result['user_id'];
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->execute([$hashedPassword, $userId]);

            $_SESSION['karyawan_flash_success'] = 'Password karyawan berhasil direset.';
            break;

        case 'delete_employee':
            $karyawanId = $_POST['karyawan_id'] ?? '';

            if ($karyawanId === '') {
                throw new InvalidArgumentException('ID karyawan tidak valid.');
            }

            // Get user_id from karyawan
            $stmt = $conn->prepare('SELECT user_id FROM karyawan WHERE karyawan_id = ? AND bisnis_id = ?');
            $stmt->execute([$karyawanId, $bisnisId]);
            $result = $stmt->fetch();
            if (!$result) {
                throw new InvalidArgumentException('Karyawan tidak ditemukan.');
            }

            $userId = $result['user_id'];

            // Delete user (cascade will delete karyawan)
            $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);

            $_SESSION['karyawan_flash_success'] = 'Karyawan berhasil dihapus.';
            break;

        case 'process_salary':
            $karyawanId = $_POST['karyawan_id'] ?? '';
            $totalGaji = filter_var($_POST['total_gaji'] ?? 0, FILTER_VALIDATE_FLOAT) ?: 0;
            $keterangan = trim($_POST['keterangan'] ?? '');
            $periode = $_POST['periode'] ?? date('Y-m');

            if ($karyawanId === '' || $totalGaji <= 0) {
                throw new InvalidArgumentException('ID karyawan dan total gaji wajib diisi.');
            }

            // Record as expense
            $pengeluaranId = generateUUID();
            $stmt = $conn->prepare('
                INSERT INTO pengeluaran (pengeluaran_id, bisnis_id, kategori, jumlah, keterangan, tanggal, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $pengeluaranId,
                $bisnisId,
                'gaji',
                $totalGaji,
                $keterangan !== '' ? $keterangan : "Gaji periode $periode",
                date('Y-m-d'),
                $_SESSION['user_id']
            ]);

            $_SESSION['karyawan_flash_success'] = 'Pembayaran gaji berhasil dicatat sebagai pengeluaran.';
            break;

        default:
            throw new InvalidArgumentException('Aksi tidak dikenal.');
    }
    
    // Clean up selected employee session after successful operation
    unset($_SESSION['selected_karyawan']);
    
    // Redirect back to karyawan page
    header('Location: ../karyawan.php');
    exit;
    
} catch (InvalidArgumentException $e) {
    $_SESSION['karyawan_flash_error'] = $e->getMessage();
    header('Location: ../karyawan.php');
    exit;
} catch (PDOException $e) {
    logError('Aksi karyawan gagal', [
        'error' => $e->getMessage(),
        'action' => $action,
        'bisnis_id' => $bisnisId,
    ]);
    $_SESSION['karyawan_flash_error'] = 'Terjadi kesalahan saat memproses data karyawan.';
    header('Location: ../karyawan.php');
    exit;
}
?> -->