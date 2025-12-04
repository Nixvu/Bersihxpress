<?php
require_once __DIR__ . '/middleware/auth_owner.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';

$flashSuccess = $_SESSION['karyawan_flash_success'] ?? null;
$flashError = $_SESSION['karyawan_flash_error'] ?? null;
unset($_SESSION['karyawan_flash_success'], $_SESSION['karyawan_flash_error']);

if ($bisnisId && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
    } catch (InvalidArgumentException $e) {
        $_SESSION['karyawan_flash_error'] = $e->getMessage();
    } catch (PDOException $e) {
        logError('Aksi karyawan gagal', [
            'error' => $e->getMessage(),
            'action' => $action,
            'bisnis_id' => $bisnisId,
        ]);
        $_SESSION['karyawan_flash_error'] = 'Terjadi kesalahan saat memproses data karyawan.';
    }

    header('Location: karyawan.php');
    exit;
}

$searchTerm = trim($_GET['q'] ?? '');
$employees = [];

if ($bisnisId) {
    try {
        $query = '
            SELECT 
                k.karyawan_id,
                k.gaji_pokok,
                k.status,
                k.tanggal_bergabung,
                u.nama_lengkap,
                u.email,
                u.no_telepon,
                u.created_at,
                COUNT(t.transaksi_id) as total_transaksi,
                COUNT(CASE WHEN t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as transaksi_bulan_ini
            FROM karyawan k
            INNER JOIN users u ON k.user_id = u.user_id
            LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id
            WHERE k.bisnis_id = ?
        ';
        $params = [$bisnisId];

        if ($searchTerm !== '') {
            $query .= ' AND (u.nama_lengkap LIKE ? OR u.email LIKE ? OR u.no_telepon LIKE ?)';
            $searchPattern = '%' . $searchTerm . '%';
            $params[] = $searchPattern;
            $params[] = $searchPattern;
            $params[] = $searchPattern;
        }

        $query .= ' GROUP BY k.karyawan_id ORDER BY u.nama_lengkap ASC';

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($employees as &$employee) {
            $employee['gaji_pokok_display'] = 'Rp ' . number_format($employee['gaji_pokok'], 0, ',', '.');
            $employee['bergabung_display'] = date('d M Y', strtotime($employee['tanggal_bergabung']));
            $employee['status_badge'] = $employee['status'] === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        }
        unset($employee);
    } catch (PDOException $e) {
        logError('Fetch karyawan gagal', [
            'error' => $e->getMessage(),
            'bisnis_id' => $bisnisId,
        ]);
        $employees = [];
        $flashError = $flashError ?? 'Gagal memuat data karyawan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karyawan - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    
    <div id="main-content" class="flex flex-col flex-grow overflow-hidden">
        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Kelola Karyawan</h1>
            <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>

            <div class="flex items-center space-x-3 mt-4">
                <div class="relative flex-grow" id="search-container">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text" 
                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                        placeholder="Cari nama, email, atau nomor telepon..."
                        id="search-input"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <?php if ($searchTerm !== ''): ?>
                    <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg data-feather="x" class="h-5 w-5 text-gray-400 hover:text-gray-600"></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <button id="btn-tambah-karyawan" class="bg-white p-3 rounded-lg shadow flex-shrink-0 hover:bg-gray-50 transition-colors">
                    <svg data-feather="user-plus" class="h-6 w-6 text-blue-600"></svg>
                </button>
            </div>
        </header>

        <main class="flex-grow overflow-y-auto p-6 space-y-3 no-scrollbar pb-24">
            <?php if ($flashSuccess): ?>
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($flashError); ?>
            </div>
            <?php endif; ?>

            <?php if (empty($employees)): ?>
            <div class="rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500">
                Belum ada karyawan. Tambahkan karyawan baru untuk bisnis Anda.
            </div>
            <?php else: ?>
            <?php foreach ($employees as $employee): ?>
            <button
                class="btn-buka-opsi w-full bg-white rounded-lg shadow p-4 text-left flex items-center justify-between"
                data-id="<?php echo htmlspecialchars($employee['karyawan_id']); ?>"
                data-nama="<?php echo htmlspecialchars($employee['nama_lengkap']); ?>"
                data-email="<?php echo htmlspecialchars($employee['email']); ?>"
                data-telepon="<?php echo htmlspecialchars($employee['no_telepon'] ?? ''); ?>"
                data-gaji="<?php echo htmlspecialchars($employee['gaji_pokok']); ?>"
                data-status="<?php echo htmlspecialchars($employee['status']); ?>"
                data-bergabung="<?php echo htmlspecialchars($employee['bergabung_display']); ?>"
                data-total-transaksi="<?php echo (int)$employee['total_transaksi']; ?>"
                data-transaksi-bulan="<?php echo (int)$employee['transaksi_bulan_ini']; ?>">
                <div class="flex items-center pr-4">
                    <div class="p-3 bg-gray-100 rounded-full mr-4">
                        <svg data-feather="<?php echo $employee['status'] === 'aktif' ? 'user-check' : 'user-x'; ?>"
                            class="w-5 h-5 <?php echo $employee['status'] === 'aktif' ? 'text-green-600' : 'text-red-600'; ?>"></svg>
                    </div>
                    <div class="text-left">
                        <p class="text-base font-bold text-gray-900"><?php echo htmlspecialchars($employee['nama_lengkap']); ?></p>
                        <p class="text-sm text-gray-500">
                            <?php echo !empty($employee['email']) ? htmlspecialchars($employee['email']) : 'Tidak ada email'; ?>
                            <?php if (!empty($employee['no_telepon'])): ?>
                            &bull;
                            <?php echo htmlspecialchars($employee['no_telepon']); ?>
                            <?php endif; ?>
                        </p>
                        <div class="flex items-center space-x-2 mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $employee['status_badge']; ?>">
                                <?php echo ucfirst($employee['status']); ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <?php echo $employee['total_transaksi']; ?> transaksi &bull; <?php echo $employee['gaji_pokok_display']; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400 flex-shrink-0"></svg>
            </button>
            <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>

    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="grid" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Kelola</span>
        </a>
        <a href="transaksi.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Transaksi</span>
        </a>
        <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Beranda</span>
        </a>
        <a href="laporan.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="bar-chart-2" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Laporan</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Akun</span>
        </a>
    </nav>

    <!-- Modal Container dengan semua modal yang diperlukan -->
    <!-- Modal ini mengikuti pola yang sama dari layanan.php dan pelanggan.php -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>

        <!-- Modal 1: Opsi Karyawan -->
        <div id="modal-opsi-karyawan" class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
            <div class="flex-shrink-0">
                <div class="w-full py-3"><div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div></div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Opsi Karyawan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-opsi-karyawan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
                <div class="px-6 pb-4">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan terpilih</p>
                    <p id="opsi-karyawan-nama" class="mt-1 text-base font-semibold text-gray-900">-</p>
                </div>
            </div>
            <div class="px-6 space-y-3">
                <button id="btn-info-kinerja" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="bar-chart-2" class="w-5 h-5 text-gray-700"></svg></div>
                        <div><p class="font-semibold text-gray-800">Informasi Kinerja</p><p class="text-sm text-gray-500">Lihat kehadiran & transaksi selesai</p></div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <!-- Tambahkan tombol opsi lainnya... -->
            </div>
            <div class="flex-shrink-0 p-6 bg-white">
                <button class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50" data-modal-id="modal-opsi-karyawan">Batal</button>
            </div>
        </div>

        <!-- Modal tambahan akan ditambahkan di sini sesuai pola yang sama -->
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-karyawan.js"></script>
</body>
</html>