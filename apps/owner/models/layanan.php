<?php
require_once __DIR__ . '/../../../config/functions.php';

function createLayanan($conn, $bisnisId, $kategoriId, $namaLayanan, $harga, $satuan, $estimasi, $deskripsi, $hasLayananBisnisColumn) {
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
    return $layananId;
}

function readLayanan($conn, $bisnisId, $hasLayananBisnisColumn, $selectedKategori, $searchTerm) {
    $baseQuery = '
        SELECT l.layanan_id, l.kategori_id, l.nama_layanan, l.harga, l.satuan,
               l.estimasi_waktu, l.deskripsi, k.nama_kategori
        FROM layanan l
        INNER JOIN kategori_layanan k ON k.kategori_id = l.kategori_id
    ';
    $conditions = [];
    $params = [];
    if ($hasLayananBisnisColumn) {
        $conditions[] = 'l.bisnis_id = ?';
        $params[] = $bisnisId;
    } else {
        $conditions[] = 'k.bisnis_id = ?';
        $params[] = $bisnisId;
    }
    if ($selectedKategori !== 'all' && $selectedKategori !== '') {
        $conditions[] = 'l.kategori_id = ?';
        $params[] = $selectedKategori;
    }
    if ($searchTerm !== '') {
        $conditions[] = '(l.nama_layanan LIKE ? OR l.deskripsi LIKE ? OR k.nama_kategori LIKE ? OR l.satuan LIKE ?)';
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    if (!empty($conditions)) {
        $baseQuery .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $baseQuery .= ' ORDER BY l.nama_layanan ASC';
    $stmt = $conn->prepare($baseQuery);
    $stmt->execute($params);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as &$service) {
        $harga = isset($service['harga']) ? (float) $service['harga'] : 0;
        $service['harga_display'] = 'Rp ' . number_format($harga, 0, ',', '.');
        $service['estimasi_display'] = isset($service['estimasi_waktu']) && $service['estimasi_waktu'] !== null
            ? $service['estimasi_waktu'] . ' jam'
            : 'Estimasi belum diatur';
    }
    unset($service);
    return $services;
}

function updateLayanan($conn, $layananId, $bisnisId, $kategoriId, $namaLayanan, $harga, $satuan, $estimasi, $deskripsi, $hasLayananBisnisColumn) {
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
    return $stmt->execute($params);
}

function deleteLayanan($conn, $layananId, $bisnisId, $hasLayananBisnisColumn) {
    $deleteQuery = $hasLayananBisnisColumn
        ? 'DELETE FROM layanan WHERE layanan_id = ? AND bisnis_id = ?'
        : 'DELETE FROM layanan WHERE layanan_id = ?';
    $params = $hasLayananBisnisColumn ? [$layananId, $bisnisId] : [$layananId];
    $stmt = $conn->prepare($deleteQuery);
    return $stmt->execute($params);
}



