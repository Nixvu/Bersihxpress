<?php
require_once __DIR__ . '/../middleware/auth_owner.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['layanan_flash_error'] = 'Metode request tidak valid.';
    redirect('../layanan.php');
}

$bisnisId = $_SESSION['owner_data']['bisnis_id'] ?? null;

if (!$bisnisId) {
    $_SESSION['layanan_flash_error'] = 'Data bisnis tidak ditemukan. Silakan masuk ulang.';
    redirect('../layanan.php');
}

// Check if layanan table has bisnis_id column
$hasLayananBisnisColumn = false;
try {
    $conn->query('SELECT bisnis_id FROM layanan LIMIT 1');
    $hasLayananBisnisColumn = true;
} catch (PDOException $ignored) {
    $hasLayananBisnisColumn = false;
}

$action = $_POST['action'] ?? '';
$redirectUrl = '../layanan.php';

try {
    switch ($action) {
        case 'create_service':
            $namaLayanan = sanitize($_POST['nama_layanan'] ?? '');
            $kategoriId = $_POST['kategori_id'] ?? '';
            $hargaInput = $_POST['harga'] ?? null;
            $satuan = sanitize($_POST['satuan'] ?? '');
            $estimasiInput = $_POST['estimasi_waktu'] ?? '';
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($namaLayanan === '' || $kategoriId === '' || $satuan === '' || $hargaInput === null) {
                throw new InvalidArgumentException('Lengkapi semua field wajib.');
            }

            $harga = filter_var($hargaInput, FILTER_VALIDATE_FLOAT);
            if ($harga === false || $harga < 0) {
                throw new InvalidArgumentException('Harga layanan tidak valid.');
            }

            $stmt = $conn->prepare('SELECT kategori_id FROM kategori_layanan WHERE kategori_id = ? AND bisnis_id = ?');
            $stmt->execute([$kategoriId, $bisnisId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Kategori layanan tidak ditemukan.');
            }

            $estimasi = null;
            if ($estimasiInput !== '') {
                $estimasiValue = filter_var($estimasiInput, FILTER_VALIDATE_INT);
                if ($estimasiValue === false || $estimasiValue < 0) {
                    throw new InvalidArgumentException('Estimasi waktu harus berupa angka jam.');
                }
                $estimasi = $estimasiValue;
            }

            $layananId = generateUUID();

            if ($hasLayananBisnisColumn) {
                $stmt = $conn->prepare('
                    INSERT INTO layanan (
                        layanan_id, bisnis_id, kategori_id, nama_layanan,
                        harga, satuan, estimasi_waktu, deskripsi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $layananId,
                    $bisnisId,
                    $kategoriId,
                    $namaLayanan,
                    $harga,
                    $satuan,
                    $estimasi,
                    $deskripsi !== '' ? $deskripsi : null,
                ]);
            } else {
                $stmt = $conn->prepare('
                    INSERT INTO layanan (
                        layanan_id, kategori_id, nama_layanan,
                        harga, satuan, estimasi_waktu, deskripsi
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $layananId,
                    $kategoriId,
                    $namaLayanan,
                    $harga,
                    $satuan,
                    $estimasi,
                    $deskripsi !== '' ? $deskripsi : null,
                ]);
            }

            $_SESSION['layanan_flash_success'] = 'Layanan baru berhasil disimpan.';
            break;

        case 'update_service':
            $layananId = $_POST['layanan_id'] ?? '';
            $namaLayanan = sanitize($_POST['nama_layanan'] ?? '');
            $kategoriId = $_POST['kategori_id'] ?? '';
            $hargaInput = $_POST['harga'] ?? null;
            $satuan = sanitize($_POST['satuan'] ?? '');
            $estimasiInput = $_POST['estimasi_waktu'] ?? '';
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($layananId === '' || $namaLayanan === '' || $kategoriId === '' || $satuan === '' || $hargaInput === null) {
                throw new InvalidArgumentException('Lengkapi semua field wajib.');
            }

            $harga = filter_var($hargaInput, FILTER_VALIDATE_FLOAT);
            if ($harga === false || $harga < 0) {
                throw new InvalidArgumentException('Harga layanan tidak valid.');
            }

            $stmt = $conn->prepare('SELECT kategori_id FROM kategori_layanan WHERE kategori_id = ? AND bisnis_id = ?');
            $stmt->execute([$kategoriId, $bisnisId]);
            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Kategori layanan tidak ditemukan.');
            }

            if ($hasLayananBisnisColumn) {
                $stmt = $conn->prepare('SELECT layanan_id FROM layanan WHERE layanan_id = ? AND bisnis_id = ?');
                $stmt->execute([$layananId, $bisnisId]);
            } else {
                $stmt = $conn->prepare('
                    SELECT l.layanan_id
                    FROM layanan l
                    INNER JOIN kategori_layanan k ON k.kategori_id = l.kategori_id
                    WHERE l.layanan_id = ? AND k.bisnis_id = ?
                ');
                $stmt->execute([$layananId, $bisnisId]);
            }

            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Layanan tidak ditemukan.');
            }

            $estimasi = null;
            if ($estimasiInput !== '') {
                $estimasiValue = filter_var($estimasiInput, FILTER_VALIDATE_INT);
                if ($estimasiValue === false || $estimasiValue < 0) {
                    throw new InvalidArgumentException('Estimasi waktu harus berupa angka jam.');
                }
                $estimasi = $estimasiValue;
            }

            $query = '
                UPDATE layanan
                SET kategori_id = ?,
                    nama_layanan = ?,
                    harga = ?,
                    satuan = ?,
                    estimasi_waktu = ?,
                    deskripsi = ?
            ';

            if ($hasLayananBisnisColumn) {
                $query .= ' WHERE layanan_id = ? AND bisnis_id = ?';
                $params = [
                    $kategoriId,
                    $namaLayanan,
                    $harga,
                    $satuan,
                    $estimasi,
                    $deskripsi !== '' ? $deskripsi : null,
                    $layananId,
                    $bisnisId,
                ];
            } else {
                $query .= ' WHERE layanan_id = ?';
                $params = [
                    $kategoriId,
                    $namaLayanan,
                    $harga,
                    $satuan,
                    $estimasi,
                    $deskripsi !== '' ? $deskripsi : null,
                    $layananId,
                ];
            }

            $stmt = $conn->prepare($query);
            $stmt->execute($params);

            $_SESSION['layanan_flash_success'] = 'Layanan berhasil diperbarui.';
            break;

        case 'delete_service':
            $layananId = $_POST['layanan_id'] ?? '';
            if ($layananId === '') {
                throw new InvalidArgumentException('ID layanan tidak valid.');
            }

            if ($hasLayananBisnisColumn) {
                $stmt = $conn->prepare('SELECT layanan_id FROM layanan WHERE layanan_id = ? AND bisnis_id = ?');
                $stmt->execute([$layananId, $bisnisId]);
            } else {
                $stmt = $conn->prepare('
                    SELECT l.layanan_id
                    FROM layanan l
                    INNER JOIN kategori_layanan k ON k.kategori_id = l.kategori_id
                    WHERE l.layanan_id = ? AND k.bisnis_id = ?
                ');
                $stmt->execute([$layananId, $bisnisId]);
            }

            if (!$stmt->fetch()) {
                throw new InvalidArgumentException('Layanan tidak ditemukan.');
            }

            $deleteQuery = $hasLayananBisnisColumn
                ? 'DELETE FROM layanan WHERE layanan_id = ? AND bisnis_id = ?'
                : 'DELETE FROM layanan WHERE layanan_id = ?';

            $params = $hasLayananBisnisColumn ? [$layananId, $bisnisId] : [$layananId];

            $stmt = $conn->prepare($deleteQuery);
            $stmt->execute($params);

            $_SESSION['layanan_flash_success'] = 'Layanan berhasil dihapus.';
            break;

        default:
            throw new InvalidArgumentException('Aksi tidak valid.');
    }
} catch (InvalidArgumentException $e) {
    $_SESSION['layanan_flash_error'] = $e->getMessage();
} catch (PDOException $e) {
    logError('Query layanan gagal', [
        'error' => $e->getMessage(),
        'action' => $action,
        'bisnis_id' => $bisnisId,
    ]);
    $_SESSION['layanan_flash_error'] = 'Terjadi kesalahan saat memproses layanan.';
}

redirect($redirectUrl);
