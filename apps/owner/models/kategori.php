<?php
require_once __DIR__ . '/../../../config/functions.php';

function createKategori($conn, $bisnisId, $namaKategori) {
    $kategoriId = generateUUID();
    $stmt = $conn->prepare('INSERT INTO kategori_layanan (kategori_id, bisnis_id, nama_kategori) VALUES (?, ?, ?)');
    $stmt->execute([$kategoriId, $bisnisId, $namaKategori]);
    return $kategoriId;
}

function readKategori($conn, $bisnisId) {
    $stmt = $conn->prepare('SELECT kategori_id, nama_kategori FROM kategori_layanan WHERE bisnis_id = ? ORDER BY nama_kategori ASC');
    $stmt->execute([$bisnisId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateKategori($conn, $kategoriId, $namaKategori) {
    $stmt = $conn->prepare('UPDATE kategori_layanan SET nama_kategori = ? WHERE kategori_id = ?');
    return $stmt->execute([$namaKategori, $kategoriId]);
}

function deleteKategori($conn, $kategoriId) {
    $stmt = $conn->prepare('DELETE FROM kategori_layanan WHERE kategori_id = ?');
    return $stmt->execute([$kategoriId]);
}

function ensureDefaultKategori($conn, $bisnisId) {
    $kategori = readKategori($conn, $bisnisId);
    if (!empty($kategori)) {
        return $kategori;
    }
    $defaultId = createKategori($conn, $bisnisId, 'Tanpa Kategori');
    return [[
        'kategori_id' => $defaultId,
        'nama_kategori' => 'Tanpa Kategori',
    ]];
}
