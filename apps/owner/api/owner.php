<?php
require_once '../../../config/database.php';
require_once '../../../config/functions.php';

// Pastikan request dari Android WebView
enforceAndroidWebView();

// Cek autentikasi
if (!isLoggedIn() || getUserRole() !== 'owner') {
    sendResponse(false, 'Unauthorized access');
    return;
}

// Handle JSON requests untuk AJAX
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'set_selected_employee':
            setSelectedEmployee();
            break;
        case 'get_dashboard_stats':
            getDashboardStats();
            break;
        case 'get_transactions':
            getTransactions();
            break;
        case 'create_transaction':
            createTransaction();
            break;
        case 'update_transaction':
            updateTransaction();
            break;
        case 'get_services':
            getServices();
            break;
        case 'create_service':
            createService();
            break;
        case 'update_service':
            updateService();
            break;
        case 'delete_service':
            deleteService();
            break;
        case 'get_employees':
            getEmployees();
            break;
        case 'create_employee':
            createEmployee();
            break;
        case 'update_employee':
            updateEmployee();
            break;
        case 'delete_employee':
            deleteEmployee();
            break;
        case 'get_customers':
            getCustomers();
            break;
        case 'create_customer':
            createCustomer();
            break;
        case 'update_customer':
            updateCustomer();
            break;
        case 'delete_customer':
            deleteCustomer();
            break;
        case 'get_reports':
            getReports();
            break;
        case 'export_report':
            exportReport();
            break;
        case 'update_profile':
            updateProfilUsaha();
            break;
        default:
            sendResponse(false, 'Invalid action');
    }
}

function setSelectedEmployee() {
    try {
        $employeeData = $_POST['employee_data'] ?? [];
        
        if (empty($employeeData) || empty($employeeData['id'])) {
            sendResponse(false, 'Data karyawan tidak valid');
            return;
        }

        // Set session dengan data yang diterima dari JavaScript
        $_SESSION['selected_karyawan'] = [
            'id' => $employeeData['id'],
            'nama' => $employeeData['nama'],
            'telepon' => $employeeData['telepon'],
            'email' => $employeeData['email'],
            'gaji' => $employeeData['gaji'],
            'status' => $employeeData['status'],
            'bergabung' => $employeeData['bergabung'],
            'totalTransaksi' => $employeeData['totalTransaksi'],
            'transaksiBulan' => $employeeData['transaksiBulan'],
            'created' => $employeeData['created']
        ];

        sendResponse(true, 'Session updated successfully', $_SESSION['selected_karyawan']);
    } catch (Exception $e) {
        logError('Error setting selected employee', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function updateProfilUsaha(){
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        // sanitize input (gunakan sanitize() dari functions.php)
        $namaBisnis = sanitize($_POST['nama_bisnis'] ?? '');
        $alamatBisnis = sanitize($_POST['alamat_bisnis'] ?? '');
        $teleponBisnis = sanitize($_POST['telepon_bisnis'] ?? '');

        if (empty($namaBisnis) || empty($alamatBisnis)) {
            sendResponse(false, 'Data profil usaha tidak lengkap');
            return;
        }

        $stmt = $conn->prepare("
            UPDATE bisnis 
            SET nama_bisnis = ?, alamat_bisnis = ?, telepon_bisnis = ?
            WHERE bisnis_id = ?
        ");
        $stmt->execute([
            $namaBisnis,
            $alamatBisnis,
            $teleponBisnis,
            $bisnisId
        ]);

        // Update session agar halaman lain langsung mendapat data terbaru
        if (isset($_SESSION['owner_data'])) {
            $_SESSION['owner_data']['nama_bisnis'] = $namaBisnis;
            $_SESSION['owner_data']['alamat'] = $alamatBisnis;
            $_SESSION['owner_data']['telepon'] = $teleponBisnis;
        }

        sendResponse(true, 'Profil usaha berhasil diperbarui');
    } catch (PDOException $e) {
        logError('Error updating business profile', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function getDashboardStats()
{
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];

        // Get today's transactions
        $stmtToday = $conn->prepare("
            SELECT COUNT(*) as total_transaksi,
                   SUM(total_harga) as total_pendapatan
            FROM transaksi
            WHERE bisnis_id = ?
            AND DATE(tanggal_masuk) = CURDATE()
        ");
        $stmtToday->execute([$bisnisId]);
        $today = $stmtToday->fetch();

        // Get monthly income
        $stmtMonthly = $conn->prepare("
            SELECT SUM(total_harga) as pendapatan_bulanan
            FROM transaksi
            WHERE bisnis_id = ?
            AND MONTH(tanggal_masuk) = MONTH(CURDATE())
            AND YEAR(tanggal_masuk) = YEAR(CURDATE())
        ");
        $stmtMonthly->execute([$bisnisId]);
        $monthly = $stmtMonthly->fetch();

        // Get pending orders
        $stmtPending = $conn->prepare("
            SELECT COUNT(*) as orders_pending
            FROM transaksi
            WHERE bisnis_id = ?
            AND status IN ('pending', 'proses')
        ");
        $stmtPending->execute([$bisnisId]);
        $pending = $stmtPending->fetch();

        // Get total customers
        $stmtCustomers = $conn->prepare("
            SELECT COUNT(*) as total_pelanggan
            FROM pelanggan
            WHERE bisnis_id = ?
        ");
        $stmtCustomers->execute([$bisnisId]);
        $customers = $stmtCustomers->fetch();

        $data = [
            'today' => [
                'transactions' => $today['total_transaksi'] ?? 0,
                'income' => $today['total_pendapatan'] ?? 0
            ],
            'monthly_income' => $monthly['pendapatan_bulanan'] ?? 0,
            'pending_orders' => $pending['orders_pending'] ?? 0,
            'total_customers' => $customers['total_pelanggan'] ?? 0
        ];

        sendResponse(true, 'Success', $data);
    } catch (PDOException $e) {
        logError('Error getting dashboard stats', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }



}

function getTransactions()
{
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $status = $_POST['status'] ?? null;
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;

        $query = "
            SELECT t.*, p.nama as nama_pelanggan, k.nama_lengkap as nama_karyawan
            FROM transaksi t
            LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
            LEFT JOIN karyawan kr ON t.karyawan_id = kr.karyawan_id
            LEFT JOIN users k ON kr.user_id = k.user_id
            WHERE t.bisnis_id = ?
        ";
        $params = [$bisnisId];

        if ($status) {
            $query .= " AND t.status = ?";
            $params[] = $status;
        }

        if ($startDate) {
            $query .= " AND DATE(t.tanggal_masuk) >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $query .= " AND DATE(t.tanggal_masuk) <= ?";
            $params[] = $endDate;
        }

        $query .= " ORDER BY t.tanggal_masuk DESC";
        
        // Add LIMIT if specified
        $limit = $_POST['limit'] ?? null;
        if ($limit && is_numeric($limit)) {
            $query .= " LIMIT ?";
            $params[] = (int)$limit;
        }

        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        // Get details for each transaction
        foreach ($transactions as &$transaction) {
            $stmtDetails = $conn->prepare("
                SELECT d.*, l.nama_layanan
                FROM detail_transaksi d
                JOIN layanan l ON d.layanan_id = l.layanan_id
                WHERE d.transaksi_id = ?
            ");
            $stmtDetails->execute([$transaction['transaksi_id']]);
            $transaction['items'] = $stmtDetails->fetchAll();
        }

        sendResponse(true, 'Success', $transactions);
    } catch (PDOException $e) {
        logError('Error getting transactions', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function createTransaction()
{
    global $conn;
    try {
        $conn->beginTransaction();

        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $pelangganId = $_POST['pelanggan_id'] ?? null;
        $karyawanId = $_POST['karyawan_id'] ?? null;
        $items = json_decode($_POST['items'] ?? '[]', true);

        // Validasi input
        if (!$pelangganId || !$karyawanId) {
            sendResponse(false, 'Data pelanggan dan karyawan harus diisi');
            return;
        }

        if (empty($items)) {
            sendResponse(false, 'Item transaksi tidak boleh kosong');
            return;
        }

        // Generate nomor nota
        $today = date('Ymd');
        $stmtNota = $conn->prepare("
            SELECT COUNT(*) as total 
            FROM transaksi 
            WHERE DATE(tanggal_masuk) = CURDATE()
        ");
        $stmtNota->execute();
        $notaCount = $stmtNota->fetch()['total'] + 1;
        $noNota = "TRX-{$today}-" . str_pad($notaCount, 4, '0', STR_PAD_LEFT);

        // Insert transaksi
        $transaksiId = generateUUID();
        $totalHarga = array_sum(array_map(function ($item) {
            return $item['jumlah'] * $item['harga_satuan'];
        }, $items));

        $stmt = $conn->prepare("
            INSERT INTO transaksi (
                transaksi_id, bisnis_id, pelanggan_id, karyawan_id,
                no_nota, tanggal_masuk, total_harga, status
            ) VALUES (?, ?, ?, ?, ?, NOW(), ?, 'pending')
        ");
        $stmt->execute([
            $transaksiId,
            $bisnisId,
            $pelangganId,
            $karyawanId,
            $noNota,
            $totalHarga
        ]);

        // Insert detail transaksi
        foreach ($items as $item) {
            $detailId = generateUUID();
            $subtotal = $item['jumlah'] * $item['harga_satuan'];

            $stmt = $conn->prepare("
                INSERT INTO detail_transaksi (
                    detail_id, transaksi_id, layanan_id,
                    jumlah, harga_satuan, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $detailId,
                $transaksiId,
                $item['layanan_id'],
                $item['jumlah'],
                $item['harga_satuan'],
                $subtotal
            ]);
        }

        $conn->commit();
        sendResponse(true, 'Transaksi berhasil dibuat', ['no_nota' => $noNota]);
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Error creating transaction', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function updateTransaction() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $transaksiId = $_POST['transaksi_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $catatanStatus = $_POST['catatan_status'] ?? '';
        $totalBayar = $_POST['total_bayar'] ?? null;

        if (!$transaksiId || !$status) {
            sendResponse(false, 'ID transaksi dan status harus diisi');
            return;
        }

        // Verifikasi kepemilikan transaksi
        $stmt = $conn->prepare("
            SELECT transaksi_id, status, total_harga 
            FROM transaksi 
            WHERE transaksi_id = ? AND bisnis_id = ?
        ");
        $stmt->execute([$transaksiId, $bisnisId]);
        $transaksi = $stmt->fetch();

        if (!$transaksi) {
            sendResponse(false, 'Transaksi tidak ditemukan');
            return;
        }

        // Validasi status
        $allowedStatus = ['pending', 'proses', 'selesai', 'diambil', 'batal'];
        if (!in_array($status, $allowedStatus)) {
            sendResponse(false, 'Status tidak valid');
            return;
        }

        // Jika status selesai, pastikan total_bayar diisi
        if ($status === 'selesai' && !$totalBayar) {
            sendResponse(false, 'Total pembayaran harus diisi');
            return;
        }

        $stmt = $conn->prepare("
            UPDATE transaksi 
            SET status = ?,
                catatan_status = ?,
                total_bayar = CASE WHEN ? = 'selesai' THEN ? ELSE total_bayar END,
                tanggal_selesai = CASE WHEN ? = 'selesai' THEN NOW() ELSE tanggal_selesai END,
                tanggal_ambil = CASE WHEN ? = 'diambil' THEN NOW() ELSE tanggal_ambil END
            WHERE transaksi_id = ? 
            AND bisnis_id = ?
        ");
        $stmt->execute([
            $status,
            $catatanStatus,
            $status,
            $totalBayar,
            $status,
            $status,
            $transaksiId,
            $bisnisId
        ]);

        sendResponse(true, 'Status transaksi berhasil diperbarui');
    } catch (PDOException $e) {
        logError('Error updating transaction', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function getReports() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $tipe = $_POST['tipe'] ?? 'pendapatan'; // pendapatan, pengeluaran, pelanggan, karyawan
        $periode = $_POST['periode'] ?? 'bulan'; // hari, bulan, tahun
        $startDate = $_POST['start_date'] ?? date('Y-m-01'); // default: awal bulan ini
        $endDate = $_POST['end_date'] ?? date('Y-m-d'); // default: hari ini

        $data = [];
        switch ($tipe) {
            case 'pendapatan':
                $query = "
                    SELECT 
                        DATE(tanggal_masuk) as tanggal,
                        COUNT(*) as jumlah_transaksi,
                        SUM(total_harga) as total_pendapatan,
                        COUNT(DISTINCT pelanggan_id) as jumlah_pelanggan
                    FROM transaksi
                    WHERE bisnis_id = ?
                    AND DATE(tanggal_masuk) BETWEEN ? AND ?
                    AND status != 'batal'
                    GROUP BY DATE(tanggal_masuk)
                    ORDER BY tanggal_masuk ASC
                ";
                break;

            case 'pengeluaran':
                $query = "
                    SELECT 
                        DATE(tanggal) as tanggal,
                        kategori,
                        SUM(jumlah) as total_pengeluaran,
                        COUNT(*) as jumlah_transaksi
                    FROM pengeluaran
                    WHERE bisnis_id = ?
                    AND DATE(tanggal) BETWEEN ? AND ?
                    GROUP BY DATE(tanggal), kategori
                    ORDER BY tanggal ASC
                ";
                break;

            case 'pelanggan':
                $query = "
                    SELECT 
                        p.nama,
                        COUNT(t.transaksi_id) as jumlah_transaksi,
                        SUM(t.total_harga) as total_transaksi,
                        MIN(t.tanggal_masuk) as transaksi_pertama,
                        MAX(t.tanggal_masuk) as transaksi_terakhir
                    FROM pelanggan p
                    LEFT JOIN transaksi t ON p.pelanggan_id = t.pelanggan_id
                    WHERE p.bisnis_id = ?
                    AND (t.transaksi_id IS NULL OR DATE(t.tanggal_masuk) BETWEEN ? AND ?)
                    GROUP BY p.pelanggan_id, p.nama
                    ORDER BY jumlah_transaksi DESC
                ";
                break;

            case 'karyawan':
                $query = "
                    SELECT 
                        u.nama_lengkap,
                        COUNT(t.transaksi_id) as jumlah_transaksi,
                        SUM(t.total_harga) as total_transaksi,
                        k.gaji_pokok,
                        COUNT(DISTINCT DATE(t.tanggal_masuk)) as hari_kerja
                    FROM karyawan k
                    JOIN users u ON k.user_id = u.user_id
                    LEFT JOIN transaksi t ON k.karyawan_id = t.karyawan_id
                    WHERE k.bisnis_id = ?
                    AND (t.transaksi_id IS NULL OR DATE(t.tanggal_masuk) BETWEEN ? AND ?)
                    GROUP BY k.karyawan_id, u.nama_lengkap, k.gaji_pokok
                    ORDER BY jumlah_transaksi DESC
                ";
                break;

            default:
                sendResponse(false, 'Tipe laporan tidak valid');
                return;
        }

        $stmt = $conn->prepare($query);
        $stmt->execute([$bisnisId, $startDate, $endDate]);
        $data = $stmt->fetchAll();

        // Tambahkan summary/total
        $summary = [];
        switch ($tipe) {
            case 'pendapatan':
                $summary = [
                    'total_transaksi' => array_sum(array_column($data, 'jumlah_transaksi')),
                    'total_pendapatan' => array_sum(array_column($data, 'total_pendapatan')),
                    'rata_rata_per_hari' => count($data) ? array_sum(array_column($data, 'total_pendapatan')) / count($data) : 0
                ];
                break;

            case 'pengeluaran':
                $summary = [
                    'total_pengeluaran' => array_sum(array_column($data, 'total_pengeluaran')),
                    'per_kategori' => []
                ];
                foreach ($data as $row) {
                    if (!isset($summary['per_kategori'][$row['kategori']])) {
                        $summary['per_kategori'][$row['kategori']] = 0;
                    }
                    $summary['per_kategori'][$row['kategori']] += $row['total_pengeluaran'];
                }
                break;

            case 'pelanggan':
                $summary = [
                    'total_pelanggan' => count($data),
                    'total_transaksi' => array_sum(array_column($data, 'jumlah_transaksi')),
                    'total_nilai' => array_sum(array_column($data, 'total_transaksi'))
                ];
                break;

            case 'karyawan':
                $summary = [
                    'total_karyawan' => count($data),
                    'total_transaksi' => array_sum(array_column($data, 'jumlah_transaksi')),
                    'total_nilai' => array_sum(array_column($data, 'total_transaksi')),
                    'total_gaji' => array_sum(array_column($data, 'gaji_pokok'))
                ];
                break;
        }

        sendResponse(true, 'Success', [
            'periode' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'data' => $data,
            'summary' => $summary
        ]);
    } catch (PDOException $e) {
        logError('Error getting reports', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function exportReport() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $tipe = $_POST['tipe'] ?? null;
        $format = $_POST['format'] ?? 'csv'; // csv, pdf (tbd)
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;

        if (!$tipe || !$startDate || !$endDate) {
            sendResponse(false, 'Parameter tidak lengkap');
            return;
        }

        if ($format !== 'csv') {
            sendResponse(false, 'Format tidak didukung');
            return;
        }

        // Generate CSV content based on report type
        $csvContent = '';
        switch ($tipe) {
            case 'transaksi':
                $stmt = $conn->prepare("
                    SELECT 
                        t.no_nota,
                        t.tanggal_masuk,
                        t.tanggal_selesai,
                        t.tanggal_ambil,
                        p.nama as pelanggan,
                        u.nama_lengkap as karyawan,
                        t.total_harga,
                        t.total_bayar,
                        t.status,
                        t.catatan_status
                    FROM transaksi t
                    LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
                    LEFT JOIN karyawan k ON t.karyawan_id = k.karyawan_id
                    LEFT JOIN users u ON k.user_id = u.user_id
                    WHERE t.bisnis_id = ?
                    AND DATE(t.tanggal_masuk) BETWEEN ? AND ?
                    ORDER BY t.tanggal_masuk ASC
                ");
                $stmt->execute([$bisnisId, $startDate, $endDate]);
                $data = $stmt->fetchAll();

                // CSV Headers
                $csvContent = "No Nota,Tanggal Masuk,Tanggal Selesai,Tanggal Ambil,Pelanggan,Karyawan,Total Harga,Total Bayar,Status,Catatan\n";

                // Add rows
                foreach ($data as $row) {
                    $csvContent .= implode(',', [
                        $row['no_nota'],
                        $row['tanggal_masuk'],
                        $row['tanggal_selesai'] ?? '',
                        $row['tanggal_ambil'] ?? '',
                        $row['pelanggan'],
                        $row['karyawan'],
                        $row['total_harga'],
                        $row['total_bayar'] ?? '',
                        $row['status'],
                        $row['catatan_status'] ?? ''
                    ]) . "\n";
                }
                break;

            // Add more export types as needed
            
            default:
                sendResponse(false, 'Tipe export tidak valid');
                return;
        }

        // Send CSV response
        sendResponse(true, 'Success', [
            'content' => base64_encode($csvContent),
            'filename' => "report_{$tipe}_{$startDate}_{$endDate}.csv",
            'mime_type' => 'text/csv'
        ]);
    } catch (PDOException $e) {
        logError('Error exporting report', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function getServices() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $tipe = $_POST['tipe'] ?? null;

        $query = "
            SELECT *
            FROM layanan
            WHERE bisnis_id = ?
        ";
        $params = [$bisnisId];

        if ($tipe) {
            $query .= " AND tipe = ?";
            $params[] = $tipe;
        }

        $query .= " ORDER BY nama_layanan ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $services = $stmt->fetchAll();

        sendResponse(true, 'Success', $services);
    } catch (PDOException $e) {
        logError('Error getting services', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function createService() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $namaLayanan = $_POST['nama_layanan'] ?? '';
        $tipe = $_POST['tipe'] ?? '';
        $hargaSatuan = $_POST['harga_satuan'] ?? 0;
        $satuan = $_POST['satuan'] ?? '';
        $estimasi = $_POST['estimasi'] ?? '';

        if (empty($namaLayanan) || empty($tipe) || empty($hargaSatuan)) {
            sendResponse(false, 'Data layanan tidak lengkap');
        }

        $layananId = generateUUID();
        $stmt = $conn->prepare("
            INSERT INTO layanan (
                layanan_id, bisnis_id, nama_layanan, 
                tipe, harga_satuan, satuan, estimasi
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $layananId,
            $bisnisId,
            $namaLayanan,
            $tipe,
            $hargaSatuan,
            $satuan,
            $estimasi
        ]);

        sendResponse(true, 'Layanan berhasil ditambahkan');
    } catch (PDOException $e) {
        logError('Error creating service', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function updateService() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $layananId = $_POST['layanan_id'] ?? '';
        $namaLayanan = $_POST['nama_layanan'] ?? '';
        $tipe = $_POST['tipe'] ?? '';
        $hargaSatuan = $_POST['harga_satuan'] ?? 0;
        $satuan = $_POST['satuan'] ?? '';
        $estimasi = $_POST['estimasi'] ?? '';

        if (empty($layananId) || empty($namaLayanan) || empty($tipe) || empty($hargaSatuan)) {
            sendResponse(false, 'Data layanan tidak lengkap');
        }

        // Verifikasi kepemilikan layanan
        $stmt = $conn->prepare("SELECT bisnis_id FROM layanan WHERE layanan_id = ?");
        $stmt->execute([$layananId]);
        $layanan = $stmt->fetch();

        if (!$layanan || $layanan['bisnis_id'] !== $bisnisId) {
            sendResponse(false, 'Layanan tidak ditemukan');
        }

        $stmt = $conn->prepare("
            UPDATE layanan
            SET nama_layanan = ?,
                tipe = ?,
                harga_satuan = ?,
                satuan = ?,
                estimasi = ?
            WHERE layanan_id = ?
            AND bisnis_id = ?
        ");
        $stmt->execute([
            $namaLayanan,
            $tipe,
            $hargaSatuan,
            $satuan,
            $estimasi,
            $layananId,
            $bisnisId
        ]);

        sendResponse(true, 'Layanan berhasil diperbarui');
    } catch (PDOException $e) {
        logError('Error updating service', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function deleteService() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $layananId = $_POST['layanan_id'] ?? '';

        if (empty($layananId)) {
            sendResponse(false, 'ID layanan tidak valid');
        }

        // Verifikasi kepemilikan layanan
        $stmt = $conn->prepare("SELECT bisnis_id FROM layanan WHERE layanan_id = ?");
        $stmt->execute([$layananId]);
        $layanan = $stmt->fetch();

        if (!$layanan || $layanan['bisnis_id'] !== $bisnisId) {
            sendResponse(false, 'Layanan tidak ditemukan');
        }

        // Cek apakah layanan sudah digunakan dalam transaksi
        $stmt = $conn->prepare("
            SELECT COUNT(*) as used_count 
            FROM detail_transaksi 
            WHERE layanan_id = ?
        ");
        $stmt->execute([$layananId]);
        $usedCount = $stmt->fetch()['used_count'];

        if ($usedCount > 0) {
            sendResponse(false, 'Layanan tidak dapat dihapus karena sudah digunakan dalam transaksi');
        }

        $stmt = $conn->prepare("DELETE FROM layanan WHERE layanan_id = ? AND bisnis_id = ?");
        $stmt->execute([$layananId, $bisnisId]);

        sendResponse(true, 'Layanan berhasil dihapus');
    } catch (PDOException $e) {
        logError('Error deleting service', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function getEmployees() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $status = $_POST['status'] ?? null;

        $query = "
            SELECT k.*, u.nama_lengkap, u.email, u.no_hp
            FROM karyawan k
            JOIN users u ON k.user_id = u.user_id
            WHERE k.bisnis_id = ?
        ";
        $params = [$bisnisId];

        if ($status) {
            $query .= " AND k.status = ?";
            $params[] = $status;
        }

        $query .= " ORDER BY u.nama_lengkap ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $employees = $stmt->fetchAll();

        sendResponse(true, 'Success', $employees);
    } catch (PDOException $e) {
        logError('Error getting employees', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function createEmployee() {
    global $conn;
    try {
        $conn->beginTransaction();

        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $namaLengkap = $_POST['nama_lengkap'] ?? '';
        $email = $_POST['email'] ?? '';
        $noHp = $_POST['no_hp'] ?? '';
        $password = $_POST['password'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $gajiPokok = $_POST['gaji_pokok'] ?? 0;

        if (empty($namaLengkap) || empty($email) || empty($noHp) || empty($password)) {
            sendResponse(false, 'Data karyawan tidak lengkap');
        }

        // Cek email unik
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendResponse(false, 'Email sudah digunakan');
        }

        // Insert user
        $userId = generateUUID();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (
                user_id, nama_lengkap, email, 
                password, no_hp, role
            ) VALUES (?, ?, ?, ?, ?, 'karyawan')
        ");
        $stmt->execute([
            $userId,
            $namaLengkap,
            $email,
            $hashedPassword,
            $noHp
        ]);

        // Insert karyawan
        $karyawanId = generateUUID();
        $stmt = $conn->prepare("
            INSERT INTO karyawan (
                karyawan_id, user_id, bisnis_id,
                alamat, gaji_pokok, status
            ) VALUES (?, ?, ?, ?, ?, 'aktif')
        ");
        $stmt->execute([
            $karyawanId,
            $userId,
            $bisnisId,
            $alamat,
            $gajiPokok
        ]);

        $conn->commit();
        sendResponse(true, 'Karyawan berhasil ditambahkan');
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Error creating employee', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function updateEmployee() {
    global $conn;
    try {
        $conn->beginTransaction();

        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $karyawanId = $_POST['karyawan_id'] ?? '';
        $namaLengkap = $_POST['nama_lengkap'] ?? '';
        $email = $_POST['email'] ?? '';
        $noHp = $_POST['no_hp'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $gajiPokok = $_POST['gaji_pokok'] ?? 0;
        $status = $_POST['status'] ?? '';

        if (empty($karyawanId) || empty($namaLengkap) || empty($email) || empty($noHp)) {
            sendResponse(false, 'Data karyawan tidak lengkap');
        }

        // Verifikasi kepemilikan karyawan
        $stmt = $conn->prepare("
            SELECT k.*, u.email as current_email 
            FROM karyawan k
            JOIN users u ON k.user_id = u.user_id
            WHERE k.karyawan_id = ? AND k.bisnis_id = ?
        ");
        $stmt->execute([$karyawanId, $bisnisId]);
        $karyawan = $stmt->fetch();

        if (!$karyawan) {
            sendResponse(false, 'Karyawan tidak ditemukan');
        }

        // Cek email unik jika email berubah
        if ($email !== $karyawan['current_email']) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $karyawan['user_id']]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Email sudah digunakan');
            }
        }

        // Update users
        $stmt = $conn->prepare("
            UPDATE users 
            SET nama_lengkap = ?,
                email = ?,
                no_hp = ?
            WHERE user_id = ?
        ");
        $stmt->execute([
            $namaLengkap,
            $email,
            $noHp,
            $karyawan['user_id']
        ]);

        // Update karyawan
        $stmt = $conn->prepare("
            UPDATE karyawan
            SET alamat = ?,
                gaji_pokok = ?,
                status = ?
            WHERE karyawan_id = ?
            AND bisnis_id = ?
        ");
        $stmt->execute([
            $alamat,
            $gajiPokok,
            $status,
            $karyawanId,
            $bisnisId
        ]);

        $conn->commit();
        sendResponse(true, 'Data karyawan berhasil diperbarui');
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Error updating employee', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function deleteEmployee() {
    global $conn;
    try {
        $conn->beginTransaction();

        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $karyawanId = $_POST['karyawan_id'] ?? '';

        if (empty($karyawanId)) {
            sendResponse(false, 'ID karyawan tidak valid');
        }

        // Verifikasi kepemilikan karyawan
        $stmt = $conn->prepare("
            SELECT user_id 
            FROM karyawan 
            WHERE karyawan_id = ? AND bisnis_id = ?
        ");
        $stmt->execute([$karyawanId, $bisnisId]);
        $karyawan = $stmt->fetch();

        if (!$karyawan) {
            sendResponse(false, 'Karyawan tidak ditemukan');
        }

        // Cek apakah karyawan masih memiliki transaksi aktif
        $stmt = $conn->prepare("
            SELECT COUNT(*) as active_count 
            FROM transaksi 
            WHERE karyawan_id = ? 
            AND status IN ('pending', 'proses')
        ");
        $stmt->execute([$karyawanId]);
        $activeCount = $stmt->fetch()['active_count'];

        if ($activeCount > 0) {
            sendResponse(false, 'Karyawan masih memiliki transaksi yang aktif');
        }

        // Hapus data karyawan (soft delete dengan update status)
        $stmt = $conn->prepare("
            UPDATE karyawan 
            SET status = 'nonaktif'
            WHERE karyawan_id = ? 
            AND bisnis_id = ?
        ");
        $stmt->execute([$karyawanId, $bisnisId]);

        // Update user status
        $stmt = $conn->prepare("
            UPDATE users 
            SET status = 'nonaktif'
            WHERE user_id = ?
        ");
        $stmt->execute([$karyawan['user_id']]);

        $conn->commit();
        sendResponse(true, 'Karyawan berhasil dinonaktifkan');
    } catch (PDOException $e) {
        $conn->rollBack();
        logError('Error deleting employee', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function getCustomers() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $search = $_POST['search'] ?? '';

        $query = "
            SELECT p.*, 
                   COUNT(t.transaksi_id) as total_transaksi,
                   SUM(t.total_harga) as total_pengeluaran
            FROM pelanggan p
            LEFT JOIN transaksi t ON p.pelanggan_id = t.pelanggan_id
            WHERE p.bisnis_id = ?
        ";
        $params = [$bisnisId];

        if ($search) {
            $query .= " AND (p.nama LIKE ? OR p.no_hp LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " GROUP BY p.pelanggan_id ORDER BY p.nama ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        sendResponse(true, 'Success', $customers);
    } catch (PDOException $e) {
        logError('Error getting customers', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function createCustomer() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $nama = $_POST['nama'] ?? '';
        $noHp = $_POST['no_hp'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        if (empty($nama) || empty($noHp)) {
            sendResponse(false, 'Nama dan nomor HP pelanggan wajib diisi');
        }

        // Cek no_hp unik per bisnis
        $stmt = $conn->prepare("
            SELECT pelanggan_id 
            FROM pelanggan 
            WHERE no_hp = ? AND bisnis_id = ?
        ");
        $stmt->execute([$noHp, $bisnisId]);
        if ($stmt->fetch()) {
            sendResponse(false, 'Nomor HP sudah terdaftar');
        }

        $pelangganId = generateUUID();
        $stmt = $conn->prepare("
            INSERT INTO pelanggan (
                pelanggan_id, bisnis_id, nama,
                no_hp, alamat, catatan
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $pelangganId,
            $bisnisId,
            $nama,
            $noHp,
            $alamat,
            $catatan
        ]);

        sendResponse(true, 'Pelanggan berhasil ditambahkan');
    } catch (PDOException $e) {
        logError('Error creating customer', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function updateCustomer() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $pelangganId = $_POST['pelanggan_id'] ?? '';
        $nama = $_POST['nama'] ?? '';
        $noHp = $_POST['no_hp'] ?? '';
        $alamat = $_POST['alamat'] ?? '';
        $catatan = $_POST['catatan'] ?? '';

        if (empty($pelangganId) || empty($nama) || empty($noHp)) {
            sendResponse(false, 'Data pelanggan tidak lengkap');
        }

        // Verifikasi kepemilikan pelanggan
        $stmt = $conn->prepare("
            SELECT no_hp as current_hp 
            FROM pelanggan 
            WHERE pelanggan_id = ? AND bisnis_id = ?
        ");
        $stmt->execute([$pelangganId, $bisnisId]);
        $pelanggan = $stmt->fetch();

        if (!$pelanggan) {
            sendResponse(false, 'Pelanggan tidak ditemukan');
        }

        // Cek no_hp unik jika berubah
        if ($noHp !== $pelanggan['current_hp']) {
            $stmt = $conn->prepare("
                SELECT pelanggan_id 
                FROM pelanggan 
                WHERE no_hp = ? AND bisnis_id = ? AND pelanggan_id != ?
            ");
            $stmt->execute([$noHp, $bisnisId, $pelangganId]);
            if ($stmt->fetch()) {
                sendResponse(false, 'Nomor HP sudah terdaftar');
            }
        }

        $stmt = $conn->prepare("
            UPDATE pelanggan 
            SET nama = ?,
                no_hp = ?,
                alamat = ?,
                catatan = ?
            WHERE pelanggan_id = ?
            AND bisnis_id = ?
        ");
        $stmt->execute([
            $nama,
            $noHp,
            $alamat,
            $catatan,
            $pelangganId,
            $bisnisId
        ]);

        sendResponse(true, 'Data pelanggan berhasil diperbarui');
    } catch (PDOException $e) {
        logError('Error updating customer', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

function deleteCustomer() {
    global $conn;
    try {
        $bisnisId = $_SESSION['owner_data']['bisnis_id'];
        $pelangganId = $_POST['pelanggan_id'] ?? '';

        if (empty($pelangganId)) {
            sendResponse(false, 'ID pelanggan tidak valid');
        }

        // Verifikasi kepemilikan pelanggan
        $stmt = $conn->prepare("
            SELECT pelanggan_id 
            FROM pelanggan 
            WHERE pelanggan_id = ? AND bisnis_id = ?
        ");
        $stmt->execute([$pelangganId, $bisnisId]);
        if (!$stmt->fetch()) {
            sendResponse(false, 'Pelanggan tidak ditemukan');
        }

        // Cek apakah pelanggan masih memiliki transaksi aktif
        $stmt = $conn->prepare("
            SELECT COUNT(*) as active_count 
            FROM transaksi 
            WHERE pelanggan_id = ? 
            AND status IN ('pending', 'proses')
        ");
        $stmt->execute([$pelangganId]);
        $activeCount = $stmt->fetch()['active_count'];

        if ($activeCount > 0) {
            sendResponse(false, 'Pelanggan masih memiliki transaksi yang aktif');
        }

        $stmt = $conn->prepare("DELETE FROM pelanggan WHERE pelanggan_id = ? AND bisnis_id = ?");
        $stmt->execute([$pelangganId, $bisnisId]);

        sendResponse(true, 'Pelanggan berhasil dihapus');
    } catch (PDOException $e) {
        logError('Error deleting customer', ['error' => $e->getMessage()]);
        sendResponse(false, 'Terjadi kesalahan sistem');
    }
}

