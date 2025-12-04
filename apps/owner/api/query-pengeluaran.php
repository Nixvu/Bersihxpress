<?php
require_once __DIR__ . '/../../../config/database.php';

// Simple handler to insert pengeluaran
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ../dashboard.php');
        exit;
    }

    $bisnis_id = $_POST['bisnis_id'] ?? null;
    $created_by = $_POST['karyawan_id'] ?? ($_SESSION['user_id'] ?? null);
    $nama = trim($_POST['nama_pengeluaran'] ?? '');
    $nominal = floatval($_POST['nominal'] ?? 0);
    $kategori = $_POST['kategori'] ?? null;
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $metode = $_POST['metode_pembayaran'] ?? null; // not stored in current schema
    $keterangan = trim($_POST['keterangan'] ?? '');

    $missing = [];
    if (empty($bisnis_id)) $missing[] = 'Bisnis';
    if (empty($nama)) $missing[] = 'Nama Pengeluaran';
    if ($nominal <= 0) $missing[] = 'Nominal (harus > 0)';
    if (empty($tanggal)) $missing[] = 'Tanggal';
    if (empty($created_by)) $missing[] = 'Dibuat Oleh';

    if (!empty($missing)) {
        $msg = 'Field berikut wajib diisi: ' . implode(', ', $missing);
        header('Location: ../dashboard.php?error=1&msg=' . urlencode($msg));
        exit;
    }

    // Map category to allowed enum values; fallback to 'lainnya'
    $allowedCategories = ['operasional', 'gaji', 'perlengkapan', 'lainnya'];
    if (!in_array($kategori, $allowedCategories)) {
        $kategori = 'lainnya';
    }

    // Build keterangan: include nama_pengeluaran and optional keterangan field
    $finalKeterangan = $nama;
    if (!empty($keterangan)) {
        $finalKeterangan .= ' - ' . $keterangan;
    }

    // Generate a unique id for pengeluaran
    $pengeluaranId = uniqid();

    $stmt = $conn->prepare("INSERT INTO pengeluaran (pengeluaran_id, bisnis_id, kategori, jumlah, keterangan, tanggal, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$pengeluaranId, $bisnis_id, $kategori, $nominal, $finalKeterangan, $tanggal, $created_by]);

    $successMsg = 'Pengeluaran berhasil disimpan.';
    header('Location: ../dashboard.php?success=1&msg=' . urlencode($successMsg));
    exit;

} catch (Exception $e) {
    // Log full error server-side
    error_log('Error query-pengeluaran: ' . $e->getMessage());
    // Provide limited error detail back to UI for debugging (truncated)
    $detail = substr($e->getMessage(), 0, 300);
    $msg = 'Gagal menyimpan pengeluaran. Detail: ' . $detail;
    header('Location: ../dashboard.php?error=1&msg=' . urlencode($msg));
    exit;
}
