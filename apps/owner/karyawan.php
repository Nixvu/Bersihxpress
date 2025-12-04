<?php
    require_once __DIR__ . '/middleware/auth_owner.php';
    require_once __DIR__ . '/components/layout.php';
    require_once __DIR__ . '/../../config/functions.php';
    require_once __DIR__ . '/models/karyawan.php';

    $ownerData = $_SESSION['owner_data'] ?? [];
    $bisnisId = $ownerData['bisnis_id'] ?? null;
    $bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';
    
    $flashSuccess = getFlash('karyawan_flash_success');
    $flashError = getFlash('karyawan_flash_error');

    $searchTerm = trim($_GET['q'] ?? '');
    $employees = [];
    $karyawanId = $_GET['karyawan_id'] ?? $_POST['karyawan_id'] ?? null;

    if ($karyawanId && $bisnisId) {
        $selectedEmployee = karyawan_list($karyawanId, $bisnisId);
        if ($selectedEmployee) {
            $_SESSION['selected_karyawan'] = $selectedEmployee;
        }
    }

    if ($bisnisId) {
        $employees = karyawan_list($bisnisId, $searchTerm);
        if (!is_array($employees)) {
            $flashError = 'Gagal memuat data karyawan.';
        }
    } else {
        $flashError = 'Data bisnis tidak tersedia. Silakan setup bisnis terlebih dahulu.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'create_employee') {
            // Ambil data dari form
            $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $noTelepon = trim($_POST['no_telepon'] ?? '');
            $gajiPokok = (int)($_POST['gaji_pokok'] ?? 0);
            $tanggalBergabung = $_POST['tanggal_bergabung'] ?? date('Y-m-d');
            $password = $_POST['password'] ?? '';

            // Validasi sederhana
            if (!$namaLengkap || !$email || !$password) {
                setFlash('karyawan_flash_error', 'Nama, email, dan password wajib diisi.');
                header('Location: karyawan.php');
                exit;
            }

            // Generate UUID untuk user_id
            $userId = generateUUID();

            // Insert ke tabel users
            global $conn;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'karyawan';
            $stmt = $conn->prepare('INSERT INTO users (user_id, email, password, nama_lengkap, no_telepon, role) VALUES (?, ?, ?, ?, ?, ?)');
            $insertUser = $stmt->execute([$userId, $email, $hashedPassword, $namaLengkap, $noTelepon, $role]);

            if (!$insertUser) {
                setFlash('karyawan_flash_error', 'Gagal membuat akun karyawan. Email mungkin sudah digunakan.');
                header('Location: karyawan.php');
                exit;
            }

                // Tambah data karyawan
                $result = createKaryawan($bisnisId, $userId, $gajiPokok, 'aktif', $tanggalBergabung);
                    if ($result) {
                        // Ambil data karyawan baru dari database menggunakan karyawan_id hasil insert
                        $stmtK = $conn->prepare('SELECT * FROM karyawan WHERE karyawan_id = ?');
                        $stmtK->execute([$result]);
                        $dataKaryawan = $stmtK->fetch(PDO::FETCH_ASSOC);
                        // Ambil data user
                        $stmtU = $conn->prepare('SELECT * FROM users WHERE user_id = ?');
                        $stmtU->execute([$userId]);
                        $dataUser = $stmtU->fetch(PDO::FETCH_ASSOC);
                        // Gabungkan data user dan karyawan
                        $selectedEmployee = [
                            'karyawan_id' => $dataKaryawan['karyawan_id'],
                            'user_id' => $dataUser['user_id'],
                            'nama_lengkap' => $dataUser['nama_lengkap'],
                            'email' => $dataUser['email'],
                            'no_telepon' => $dataUser['no_telepon'],
                            'gaji_pokok' => $dataKaryawan['gaji_pokok'],
                            'status' => $dataKaryawan['status'],
                            'tanggal_bergabung' => $dataKaryawan['tanggal_bergabung'],
                        ];
                        $_SESSION['selected_karyawan'] = $selectedEmployee;
                        setFlash('karyawan_flash_success', 'Karyawan berhasil ditambahkan.');
                        header('Location: karyawan.php');
                        exit;
                    } else {
                        // Jika gagal menambah karyawan, hapus user yang sudah terlanjur dibuat agar tidak ada data nyangkut
                        $stmtDel = $conn->prepare('DELETE FROM users WHERE user_id = ?');
                        $stmtDel->execute([$userId]);
                        setFlash('karyawan_flash_error', 'Gagal menambah karyawan.');
                        header('Location: karyawan.php');
                        exit;
                    }
        }

        // Handle update_employee
        if ($action === 'update_employee') {
            $karyawanId = $_POST['karyawan_id'] ?? '';
            $namaLengkap = trim($_POST['nama_lengkap'] ?? '');
            $noTelepon = trim($_POST['no_telepon'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $gajiPokok = (int)($_POST['gaji_pokok'] ?? 0);
            $status = $_POST['status'] ?? 'aktif';
            $tanggalBergabung = $_POST['tanggal_bergabung'] ?? null;

            if (!$karyawanId || !$namaLengkap) {
                setFlash('karyawan_flash_error', 'ID dan nama karyawan wajib diisi.');
                header('Location: karyawan.php');
                exit;
            }

            // Ambil user_id dari karyawan_id
            global $conn;
            $stmt = $conn->prepare('SELECT user_id FROM karyawan WHERE karyawan_id = ?');
            $stmt->execute([$karyawanId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $row ? $row['user_id'] : null;

            if (!$userId) {
                setFlash('karyawan_flash_error', 'User ID tidak ditemukan untuk karyawan ini.');
                header('Location: karyawan.php');
                exit;
            }

            $resultUser = updateUser($userId, $namaLengkap, $noTelepon, $email);
            $resultKaryawan = updateKaryawan($karyawanId, $gajiPokok, $status, $tanggalBergabung);

            if ($resultUser && $resultKaryawan) {
                setFlash('karyawan_flash_success', 'Data karyawan berhasil diubah.');
            } else {
                setFlash('karyawan_flash_error', 'Gagal mengubah data karyawan.');
            }
            header('Location: karyawan.php');
            exit;
        }

        // Handle delete_employee
        if ($action === 'delete_employee') {
            $karyawanId = $_POST['karyawan_id'] ?? '';
            if (!$karyawanId) {
                setFlash('karyawan_flash_error', 'ID karyawan tidak ditemukan.');
                header('Location: karyawan.php');
                exit;
            }
            $result = deleteKaryawan($karyawanId);
            if ($result) {
                setFlash('karyawan_flash_success', 'Karyawan berhasil dihapus.');
            } else {
                setFlash('karyawan_flash_error', 'Gagal menghapus karyawan.');
            }
            header('Location: karyawan.php');
            exit;
        }

        // Handle reset_password
        if ($action === 'reset_password') {
            $karyawanId = $_POST['karyawan_id'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            if (!$karyawanId || !$newPassword) {
                setFlash('karyawan_flash_error', 'ID dan password baru wajib diisi.');
                header('Location: karyawan.php');
                exit;
            }
            $result = resetKaryawanPassword($karyawanId, $newPassword);
            if ($result) {
                setFlash('karyawan_flash_success', 'Password karyawan berhasil direset.');
            } else {
                setFlash('karyawan_flash_error', 'Gagal reset password karyawan.');
            }
            header('Location: karyawan.php');
            exit;
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

    <!-- Main Content -->
    <div id="main-content" class="flex flex-col flex-grow overflow-hidden">
        <!-- Header (Sticky) -->
        <header class="sticky top-0 z-10 bg-blue-600 rounded-b-[32px] p-6 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Kelola Karyawan</h1>
            <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>

            <!-- Search & Tombol Tambah -->
            <div class="flex items-center space-x-3 mt-4">
                <div class="relative flex-grow" id="search-container">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text"
                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-colors"
                        placeholder="Cari nama, email, atau nomor telepon..." id="search-input"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <?php if ($searchTerm !== ''): ?>
                    <button type="button" id="clear-search" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg data-feather="x" class="h-5 w-5 text-gray-400 hover:text-gray-600"></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <button id="btn-tambah-karyawan"
                    class="bg-white p-3 rounded-lg shadow flex-shrink-0 hover:bg-gray-50 transition-colors">
                    <svg data-feather="user-plus" class="h-6 w-6 text-blue-600"></svg>
                </button>
            </div>
        </header>

        <!-- Daftar Karyawan (Scrollable) -->
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
            <div class="">
                <!-- Belum ada karyawan. Tambahkan karyawan baru untuk bisnis Anda. -->
            </div>
            <?php else: ?>
            <?php foreach ($employees as $employee): ?>
            <button
                class="btn-buka-opsi-karyawan w-full bg-white rounded-lg shadow p-4 text-left flex items-center justify-between"
                data-id="<?php echo htmlspecialchars($employee['karyawan_id']); ?>"
                data-nama="<?php echo htmlspecialchars($employee['nama_lengkap']); ?>"
                data-email="<?php echo htmlspecialchars($employee['email']); ?>"
                data-telepon="<?php echo htmlspecialchars($employee['no_telepon'] ?? ''); ?>"
                data-gaji="<?php echo htmlspecialchars($employee['gaji_pokok']); ?>"
                data-gaji-display="<?php echo htmlspecialchars($employee['gaji_pokok_display']); ?>"
                data-status="<?php echo htmlspecialchars($employee['status']); ?>"
                data-bergabung="<?php echo htmlspecialchars($employee['bergabung_display']); ?>"
                data-total-transaksi="<?php echo (int)$employee['total_transaksi']; ?>"
                data-transaksi-bulan="<?php echo (int)$employee['transaksi_bulan_ini']; ?>"
                data-created="<?php echo htmlspecialchars($employee['created_display']); ?>">
                <div class="flex items-center pr-4">
                    <div class="p-3 bg-gray-100 rounded-full mr-4">
                        <svg data-feather="<?php echo $employee['status'] === 'aktif' ? 'user-check' : 'user-x'; ?>"
                            class="w-5 h-5 <?php echo $employee['status'] === 'aktif' ? 'text-green-600' : 'text-red-600'; ?>"></svg>
                    </div>
                    <div class="text-left">
                        <p class="karyawan-nama text-base font-bold text-gray-900">
                            <?php echo htmlspecialchars($employee['nama_lengkap']); ?></p>
                        <p class="text-sm text-gray-500">
                            <span
                                class="karyawan-email"><?php echo !empty($employee['email']) ? htmlspecialchars($employee['email']) : 'Tidak ada email'; ?></span>
                            <?php if (!empty($employee['no_telepon'])): ?> &bull;
                            <span class="karyawan-phone"><?php echo htmlspecialchars($employee['no_telepon']); ?></span>
                            <?php endif; ?>
                        </p>
                        <div class="flex items-center space-x-2 mt-1">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $employee['status_badge']; ?>">
                                <?php echo ucfirst($employee['status']); ?>
                            </span>
                            <span class="text-xs text-gray-500">
                                <?php echo $employee['total_transaksi']; ?> transaksi &bull;
                                <?php echo $employee['gaji_pokok_display']; ?>
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

    <!-- Navigation Button -->
    <nav
        class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
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

    <!-- Modal Container -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>
        
        <!-- MODAL 1: Opsi Karyawan -->
        <div id="modal-opsi-karyawan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
            >
            <!-- Header -->
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Opsi Karyawan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-opsi-karyawan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
                <div class="px-6 pb-4">
                    <?php $selectedEmployee = $_SESSION['selected_karyawan'] ?? []; ?>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan terpilih</p>
                    <div class="mt-1">
                        <p id="opsi-karyawan-nama" class="text-base font-semibold text-gray-900">
                            <?php echo htmlspecialchars($employees['nama'] ?? '-'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Options -->
            <div class="px-6 space-y-3">
                <button id="btn-detail-karyawan" data-target-modal="modal-info-kinerja"
                    class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="bar-chart-2" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Informasi Kinerja</p>
                            <p class="text-sm text-gray-500">Lihat kehadiran & transaksi selesai</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>

                <button id="btn-edit-karyawan" data-target-modal="modal-edit-karyawan"
                    class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="edit" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Edit Data Karyawan</p>
                            <p class="text-sm text-gray-500">Ubah nama, telepon, atau gaji</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>

                <button id="btn-reset-password" data-target-modal="modal-reset-password"
                    class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="key" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Reset Password</p>
                            <p class="text-sm text-gray-500">Buat password baru untuk karyawan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>

                <button id="btn-hapus-karyawan" data-target-modal="modal-hapus-karyawan"
                    class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="trash-2" class="w-5 h-5 text-red-600"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-red-600">Hapus Karyawan</p>
                            <p class="text-sm text-gray-500">Hapus akun dan data karyawan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
            </div>

            <!-- Tombol Batal -->
            <div class="flex-shrink-0 p-6 bg-white">
                <button
                    class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50"
                    data-modal-id="modal-opsi-karyawan">
                    Batal
                </button>
            </div>
        </div>

        <!-- MODAL 2: Form Edit Karyawan -->
        <div id="modal-edit-karyawan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Edit Karyawan</h2>
                    <div class="flex items-center space-x-2">
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-edit-karyawan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-karyawan" class="space-y-4" method="POST">
                    <input type="hidden" name="action" value="update_employee">
                    <input type="hidden" id="edit_karyawan_id" name="karyawan_id" class="employee-id-input employee-id-edit" value="">

                    <!-- ID Karyawan (Readonly) -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">ID Karyawan</label>
                        <input type="text" 
                            class="w-full mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono employee-id employee-id-edit-display"
                            value="" readonly>
                    </div>

                    <div>
                        <label for="edit_nama_lengkap" class="text-sm font-medium text-gray-600">Nama Lengkap <span
                                class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="edit_nama_lengkap" name="nama_lengkap"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-name-input"
                                value="<?php echo htmlspecialchars($selectedEmployee['nama'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div>
                        <label for="edit_no_telepon" class="text-sm font-medium text-gray-600">No Telepon</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="edit_no_telepon" name="no_telepon"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-phone-input"
                                value="<?php echo htmlspecialchars($selectedEmployee['telepon'] ?? ''); ?>"
                                placeholder="08xxxx">
                        </div>
                    </div>

                    <div>
                        <label for="edit_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="number" step="250000" min="0" max="10000000" id="edit_gaji_pokok" name="gaji_pokok"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-salary-input"
                                value="<?php echo htmlspecialchars($selectedEmployee['gaji'] ?? '0'); ?>" placeholder="">
                        </div>
                    </div>

                    <div>
                        <label for="edit_status" class="text-sm font-medium text-gray-600">Status</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="toggle-left" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <select id="edit_status" name="status"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                                <option value="aktif"
                                    <?php echo ($selectedEmployee['status'] ?? 'aktif') === 'aktif' ? 'selected' : ''; ?>>Aktif
                                </option>
                                <option value="tidak_aktif"
                                    <?php echo ($selectedEmployee['status'] ?? 'aktif') === 'tidak_aktif' ? 'selected' : ''; ?>>
                                    Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-edit-karyawan"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>

        <!-- MODAL 3: Tambah Karyawan -->
        <div id="modal-tambah-karyawan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Tambah Karyawan Baru</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-tambah-karyawan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-tambah-karyawan" class="space-y-4" method="POST">
                    <input type="hidden" name="action" value="create_employee">

                    <div>
                        <label for="tambah_nama_lengkap" class="text-sm font-medium text-gray-600">Nama Lengkap <span
                                class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="tambah_nama_lengkap" name="nama_lengkap"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Nama lengkap karyawan" required>
                        </div>
                    </div>

                    <div>
                        <label for="tambah_email" class="text-sm font-medium text-gray-600">Email <span
                                class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="email" id="tambah_email" name="email"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="email@example.com"
                                required>
                        </div>
                    </div>

                    <div>
                        <label for="tambah_no_telepon" class="text-sm font-medium text-gray-600">No Telepon</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="tambah_no_telepon" name="no_telepon"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="08xxxx">
                        </div>
                    </div>

                    <div>
                        <label for="tambah_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="number" step="500000" min="0" id="tambah_gaji_pokok" name="gaji_pokok"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="">
                        </div>
                    </div>

                    <div>
                        <label for="tambah_tanggal_bergabung" class="text-sm font-medium text-gray-600">Tanggal
                            Bergabung</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="calendar" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="date" id="tambah_tanggal_bergabung" name="tanggal_bergabung"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Login</h3>

                        <div>
                            <label for="tambah_password" class="text-sm font-medium text-gray-600">Password <span
                                    class="text-red-500">*</span></label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg data-feather="lock" class="h-5 w-5 text-gray-400"></svg>
                                </span>
                                <input type="password" id="tambah_password" name="password"
                                    class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                    placeholder="Password minimal 6 karakter" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-tambah-karyawan"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Tambah Karyawan
                </button>
            </div>
        </div>

        <!-- MODAL 4: Reset Password -->
        <div id="modal-reset-password"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Reset Password</h2>
                    <div class="flex items-center space-x-2">
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-reset-password">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
                    <div class="mt-1">
                        <p class="text-base font-semibold text-gray-900 employee-name">
                            <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                        <!-- <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                            ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p> -->
                    </div>
                </div>
            </div>

            <div class="px-6 pb-6">
                <form id="form-reset-password" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="karyawan_id" class="employee-id-input employee-id-reset" value="">

                    <div>
                        <label for="reset_new_password" class="text-sm font-medium text-gray-600">Password Baru <span
                                class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="lock" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="password" id="reset_new_password" name="new_password"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Password baru minimal 6 karakter" required>
                        </div>
                    </div>

                    <div class="flex space-x-3 pt-4">
                        <button type="button"
                            class="modal-back-btn flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 5: Konfirmasi Hapus -->
        <div id="modal-hapus-karyawan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-red-600">Hapus Karyawan</h2>
                    <div class="flex items-center space-x-2">
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-hapus-karyawan">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="px-6 pb-6">
                <div class="flex items-center p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
                    <svg data-feather="alert-triangle" class="w-6 h-6 text-red-600 mr-3"></svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">
                            Anda akan menghapus karyawan "<span
                                class="font-bold employee-name"><?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></span>"
                        </p>
                        <!-- <p class="text-xs text-gray-500 font-mono bg-red-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                            ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p> -->
                        <p class="text-sm text-red-600 mt-1">
                            Aksi ini tidak dapat dibatalkan. Semua data karyawan akan dihapus secara permanen.
                        </p>
                    </div>
                </div>

                <form id="form-hapus-karyawan" method="POST">
                    <input type="hidden" name="action" value="delete_employee">
                    <input type="hidden" name="karyawan_id" class="employee-id-input employee-id-hapus" value="">

                    <!-- <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">ID Karyawan</label>
                        <input type="text"
                            class="w-full mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono employee-id-display"
                            value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>" readonly>
                    </div> -->

                    <div class="flex space-x-3">
                        <button type="button"
                            class="modal-back-btn flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                            class="flex-1 bg-red-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 6: Informasi Kinerja -->
        <div id="modal-info-kinerja"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[80vh] hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Informasi Kinerja</h2>
                    <div class="flex items-center space-x-2">
                                                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                            data-modal-id="modal-info-kinerja">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
                    <div class="mt-1">
                        <p class="text-base font-semibold text-gray-900 employee-name">
                            <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                        <!-- <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                            ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p> -->
                    </div>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg data-feather="file-text" class="w-5 h-5 text-blue-600 mr-2"></svg>
                            <span class="text-sm font-medium text-blue-800">Total Transaksi</span>
                        </div>
                        <p class="text-2xl font-bold text-blue-900 mt-2 employee-total-transaksi">0</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg data-feather="calendar" class="w-5 h-5 text-green-600 mr-2"></svg>
                            <span class="text-sm font-medium text-green-800">Bulan Ini</span>
                        </div>
                        <p class="text-2xl font-bold text-green-900 mt-2 employee-transaksi-bulan">0</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-sm font-medium text-gray-600">Bergabung</span>
                        <span class="text-sm text-gray-900 employee-bergabung">-</span>
                    </div>
                    <div class="flex justify-between items-center py-3 border-b border-gray-200">
                        <span class="text-sm font-medium text-gray-600">Status</span>
                        <span class="text-sm text-gray-900 employee-status">Aktif</span>
                    </div>
                    <div class="flex justify-between items-center py-3">
                        <span class="text-sm font-medium text-gray-600">Gaji Pokok</span>
                        <span class="text-sm font-semibold text-gray-900 employee-gaji">Rp 0</span>
                    </div>
                </div>

                <button id="btn-proses-gaji" data-target-modal="modal-proses-gaji"
                    class="modal-navigate w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 flex items-center justify-center">
                    <svg data-feather="credit-card" class="w-5 h-5 mr-2"></svg>
                    Proses Pembayaran Gaji
                </button>
            </div>
        </div>

        <!-- MODAL 7: Proses Gaji -->
        <div id="modal-proses-gaji"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6">
                    <h2 class="text-xl font-bold text-gray-900">Proses Gaji</h2>
                    <div class="flex items-center space-x-2">
                        <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-proses-gaji">
                            <svg data-feather="x" class="w-6 h-6"></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
                    <div class="mt-1">
                        <p class="text-base font-semibold text-gray-900 employee-name">
                            <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                        <!-- <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                            ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p> -->
                    </div>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-proses-gaji" class="space-y-4" method="POST" action="api/query-crud-karyawan.php">
                    <input type="hidden" name="action" value="process_salary">
                    <input type="hidden" name="karyawan_id" class="employee-id-input"
                        value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">
                    <input type="hidden" name="total_gaji" id="input_total_gaji">

                    <div>
                        <label for="input_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                        <div class="relative mt-1">
                            <span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                            <input type="number" step="1000" min="0" id="input_gaji_pokok"
                                class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-100 employee-salary-input"
                                value="<?php echo htmlspecialchars($selectedEmployee['gaji'] ?? '0'); ?>" readonly>
                        </div>
                    </div>

                    <div>
                        <label for="input_bonus" class="text-sm font-medium text-gray-600">Bonus/Tunjangan</label>
                        <div class="relative mt-1">
                            <span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                            <input type="number" step="50000" min="0" max="10000000" id="input_bonus" name="bonus"
                                class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="">
                        </div>
                    </div>

                    <div>
                        <label for="input_potongan" class="text-sm font-medium text-gray-600">Potongan</label>
                        <div class="relative mt-1">
                            <span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                            <input type="number" step="50000" min="0" max="10000000" id="input_potongan" name="potongan"
                                class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="">
                        </div>
                    </div>

                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-semibold text-gray-800">Total Gaji:</span>
                            <span id="total_gaji_display" class="text-xl font-bold text-green-600">Rp 0</span>
                        </div>
                    </div>

                    <div>
                        <label for="input_periode" class="text-sm font-medium text-gray-600">Periode</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="calendar" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="month" id="input_periode" name="periode"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                value="<?php echo date('Y-m'); ?>">
                        </div>
                    </div>

                    <div>
                        <label for="input_keterangan" class="text-sm font-medium text-gray-600">Keterangan</label>
                        <textarea id="input_keterangan" name="keterangan" rows="3"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Catatan tambahan (opsional)"></textarea>
                    </div>
                </form>
            </div>

            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-proses-gaji"
                    class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700">
                    Proses Pembayaran
                </button>
            </div>
        </div>

        <!-- MODAL 8: Success Notification -->
        <div id="modal-sukses"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
            >
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
            </div>
            <div class="px-6 py-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg data-feather="check" class="w-8 h-8 text-green-600"></svg>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Berhasil!</h2>
                <p id="sukses-message" class="text-sm text-gray-600 mb-6">Operasi berhasil dilakukan.</p>
                <button class="btn-close-modal w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700"
                    data-modal-id="modal-sukses">
                    Tutup
                </button>
            </div>
        </div>
    </div>
   
    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-karyawan.js"></script>
    <script>
    // Enhanced search functionality - berdasarkan referensi pelanggan.php
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const clearButton = document.getElementById('clear-search');
        const karyawanCards = document.querySelectorAll('.btn-buka-opsi-karyawan');
        const currentSearch = '<?php echo addslashes($searchTerm); ?>';

        // Real-time search filtering
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                filterKaryawan(searchTerm);

                // Update URL without page reload
                const url = new URL(window.location);
                if (searchTerm) {
                    url.searchParams.set('q', searchTerm);
                } else {
                    url.searchParams.delete('q');
                }
                window.history.replaceState({}, '', url);

                // Toggle clear button
                toggleClearButton(searchTerm);
            });
        }

        // Clear search functionality
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                filterKaryawan('');

                // Update URL
                const url = new URL(window.location);
                url.searchParams.delete('q');
                window.history.replaceState({}, '', url);

                // Hide clear button
                this.style.display = 'none';
                searchInput.focus();
            });
        }

        function filterKaryawan(searchTerm) {
            let visibleCount = 0;

            karyawanCards.forEach(card => {
                const nama = card.dataset.nama?.toLowerCase() || '';
                const telepon = card.dataset.telepon?.toLowerCase() || '';
                const email = card.dataset.email?.toLowerCase() || '';

                // Check search match
                const searchMatch = !searchTerm ||
                    nama.includes(searchTerm) ||
                    telepon.includes(searchTerm) ||
                    email.includes(searchTerm);

                if (searchMatch) {
                    card.style.display = 'flex';
                    visibleCount++;
                    highlightText(card, searchTerm);
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide empty state
            updateEmptyState(visibleCount, searchTerm);
        }

        function highlightText(card, searchTerm) {
            if (!searchTerm) {
                // Reset highlights
                const textElements = card.querySelectorAll('.karyawan-nama, .karyawan-email, .karyawan-phone');
                textElements.forEach(element => {
                    if (element.dataset.originalText) {
                        element.innerHTML = element.dataset.originalText;
                    }
                });
                return;
            }

            const textElements = card.querySelectorAll('.karyawan-nama, .karyawan-email, .karyawan-phone');
            textElements.forEach(element => {
                const originalText = element.dataset.originalText || element.textContent;
                element.dataset.originalText = originalText;

                const regex = new RegExp(`(${searchTerm})`, 'gi');
                element.innerHTML = originalText.replace(regex,
                    '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
            });
        }

        function toggleClearButton(searchTerm) {
            if (clearButton) {
                clearButton.style.display = searchTerm ? 'flex' : 'none';
            }
        }

        function updateEmptyState(visibleCount, searchTerm) {
            const mainContainer = document.querySelector('main');
            let emptyState = document.getElementById('search-empty-state');

            if (visibleCount === 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.id = 'search-empty-state';
                    emptyState.className =
                        'rounded-lg border border-dashed border-gray-300 bg-white p-6 text-center text-sm text-gray-500 mt-4';

                    let message = '';
                    let subMessage = '';

                    if (searchTerm) {
                        message = 'Tidak ada karyawan ditemukan';
                        subMessage = 'Coba gunakan kata kunci yang berbeda';
                    } else {
                        message = 'Belum ada karyawan';
                        subMessage = 'Tambah karyawan baru untuk bisnis Anda';
                    }

                    emptyState.innerHTML = `
                                <svg data-feather="search" class="w-8 h-8 text-gray-400 mx-auto mb-2"></svg>
                                <p class="font-medium">${message}</p>
                                <p>${subMessage}</p>
                            `;
                    mainContainer.insertBefore(emptyState, mainContainer.firstElementChild.nextSibling);
                    if (typeof feather !== 'undefined') {
                        feather.replace();
                    }
                }
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        }

        // Initial setup
        if (currentSearch && searchInput) {
            searchInput.value = currentSearch;
            toggleClearButton(currentSearch);
        }

        // Apply initial filter
        filterKaryawan(currentSearch);
    });
    </script>
</body>

</html>