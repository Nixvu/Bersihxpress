<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';

function pelanggan_create($bisnisId, $data) {
	global $conn;
	$nama = sanitize($data['nama'] ?? '');
	$noTelepon = trim($data['no_telepon'] ?? '');
	$email = trim($data['email'] ?? '');
	$alamat = trim($data['alamat'] ?? '');
	$catatan = trim($data['catatan'] ?? '');

	if ($nama === '') {
		return ['success' => false, 'message' => 'Nama pelanggan wajib diisi.'];
	}
	if ($noTelepon !== '' && !preg_match('/^[0-9+\s-]+$/', $noTelepon)) {
		return ['success' => false, 'message' => 'Format nomor telepon tidak valid.'];
	}
	if ($email !== '' && !validateEmail($email)) {
		return ['success' => false, 'message' => 'Format email tidak valid.'];
	}
	if ($noTelepon !== '') {
		$stmt = $conn->prepare('SELECT pelanggan_id FROM pelanggan WHERE bisnis_id = ? AND no_telepon = ?');
		$stmt->execute([$bisnisId, $noTelepon]);
		if ($stmt->fetch()) {
			return ['success' => false, 'message' => 'Nomor telepon sudah terdaftar.'];
		}
	}
	$pelangganId = generateUUID();
	$stmt = $conn->prepare('INSERT INTO pelanggan (pelanggan_id, bisnis_id, nama, no_telepon, email, alamat, catatan) VALUES (?, ?, ?, ?, ?, ?, ?)');
	$stmt->execute([
		$pelangganId,
		$bisnisId,
		$nama,
		$noTelepon !== '' ? $noTelepon : null,
		$email !== '' ? $email : null,
		$alamat !== '' ? $alamat : null,
		$catatan !== '' ? $catatan : null,
	]);
	return ['success' => true, 'message' => 'Pelanggan baru berhasil ditambahkan.'];
}

function pelanggan_update($bisnisId, $data) {
	global $conn;
	$pelangganId = $data['pelanggan_id'] ?? '';
	$nama = sanitize($data['nama'] ?? '');
	$noTelepon = trim($data['no_telepon'] ?? '');
	$email = trim($data['email'] ?? '');
	$alamat = trim($data['alamat'] ?? '');
	$catatan = trim($data['catatan'] ?? '');
	if ($pelangganId === '') {
		return ['success' => false, 'message' => 'ID pelanggan tidak valid.'];
	}
	if ($nama === '') {
		return ['success' => false, 'message' => 'Nama pelanggan wajib diisi.'];
	}
	if ($noTelepon !== '' && !preg_match('/^[0-9+\s-]+$/', $noTelepon)) {
		return ['success' => false, 'message' => 'Format nomor telepon tidak valid.'];
	}
	if ($email !== '' && !validateEmail($email)) {
		return ['success' => false, 'message' => 'Format email tidak valid.'];
	}
	$stmt = $conn->prepare('SELECT pelanggan_id, no_telepon FROM pelanggan WHERE pelanggan_id = ? AND bisnis_id = ?');
	$stmt->execute([$pelangganId, $bisnisId]);
	$existing = $stmt->fetch();
	if (!$existing) {
		return ['success' => false, 'message' => 'Data pelanggan tidak ditemukan.'];
	}
	if ($noTelepon !== '' && $noTelepon !== ($existing['no_telepon'] ?? '')) {
		$stmt = $conn->prepare('SELECT pelanggan_id FROM pelanggan WHERE bisnis_id = ? AND no_telepon = ? AND pelanggan_id != ?');
		$stmt->execute([$bisnisId, $noTelepon, $pelangganId]);
		if ($stmt->fetch()) {
			return ['success' => false, 'message' => 'Nomor telepon sudah terdaftar pada pelanggan lain.'];
		}
	}
	$stmt = $conn->prepare('UPDATE pelanggan SET nama = ?, no_telepon = ?, email = ?, alamat = ?, catatan = ? WHERE pelanggan_id = ? AND bisnis_id = ?');
	$stmt->execute([
		$nama,
		$noTelepon !== '' ? $noTelepon : null,
		$email !== '' ? $email : null,
		$alamat !== '' ? $alamat : null,
		$catatan !== '' ? $catatan : null,
		$pelangganId,
		$bisnisId,
	]);
	return ['success' => true, 'message' => 'Data pelanggan berhasil diperbarui.'];
}

function pelanggan_delete($bisnisId, $pelangganId) {
	global $conn;
	if ($pelangganId === '') {
		return ['success' => false, 'message' => 'ID pelanggan tidak valid.'];
	}
	$stmt = $conn->prepare('SELECT pelanggan_id FROM pelanggan WHERE pelanggan_id = ? AND bisnis_id = ?');
	$stmt->execute([$pelangganId, $bisnisId]);
	if (!$stmt->fetch()) {
		return ['success' => false, 'message' => 'Data pelanggan tidak ditemukan.'];
	}
	$stmt = $conn->prepare('DELETE FROM pelanggan WHERE pelanggan_id = ? AND bisnis_id = ?');
	$stmt->execute([$pelangganId, $bisnisId]);
	return ['success' => true, 'message' => 'Pelanggan berhasil dihapus.'];
}

function pelanggan_list($bisnisId, $searchTerm, $selectedFilter) {
	global $conn;
	$customers = [];
	$query = 'SELECT p.pelanggan_id, p.nama, p.no_telepon, p.email, p.alamat, p.catatan, p.created_at, COUNT(t.transaksi_id) AS total_transaksi, COALESCE(SUM(t.total_harga), 0) AS total_nilai, MAX(t.created_at) AS transaksi_terakhir FROM pelanggan p LEFT JOIN transaksi t ON t.pelanggan_id = p.pelanggan_id WHERE p.bisnis_id = ?';
	$params = [$bisnisId];
	if ($searchTerm !== '') {
		$query .= ' AND (p.nama LIKE ? OR p.no_telepon LIKE ? OR p.email LIKE ? OR p.alamat LIKE ?)';
		$searchPattern = '%' . $searchTerm . '%';
		$params[] = $searchPattern;
		$params[] = $searchPattern;
		$params[] = $searchPattern;
		$params[] = $searchPattern;
	}
	$query .= ' GROUP BY p.pelanggan_id';
	if ($selectedFilter !== 'semua') {
		if ($selectedFilter === 'terbaru') {
			$query .= ' HAVING COUNT(t.transaksi_id) > 0 ORDER BY MAX(t.created_at) DESC, p.created_at DESC';
		} elseif ($selectedFilter === 'sering') {
			$query .= ' HAVING COUNT(t.transaksi_id) >= 5 ORDER BY COUNT(t.transaksi_id) DESC, COALESCE(SUM(t.total_harga), 0) DESC';
		} elseif ($selectedFilter === 'jarang') {
			$query .= ' HAVING COUNT(t.transaksi_id) BETWEEN 1 AND 4 ORDER BY COUNT(t.transaksi_id) ASC, MAX(t.created_at) ASC';
		}
	} else {
		$query .= ' ORDER BY p.nama ASC';
	}
	$stmt = $conn->prepare($query);
	$stmt->execute($params);
	$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($customers as &$customer) {
		$customer['total_transaksi'] = (int) ($customer['total_transaksi'] ?? 0);
		$customer['total_nilai'] = (float) ($customer['total_nilai'] ?? 0);
		$customer['total_nilai_display'] = 'Rp ' . number_format($customer['total_nilai'], 0, ',', '.');
		$customer['created_display'] = $customer['created_at'] ? date('d M Y', strtotime($customer['created_at'])) : '-';
		$totalTransaksi = $customer['total_transaksi'];
		if ($totalTransaksi >= 5) {
			$customer['kategori_filter'] = 'sering';
		} elseif ($totalTransaksi >= 1) {
			$customer['kategori_filter'] = 'jarang';
		} else {
			$customer['kategori_filter'] = 'baru';
		}
		$isRecentCustomer = false;
		if ($customer['transaksi_terakhir']) {
			$transaksiTerakhir = strtotime($customer['transaksi_terakhir']);
			$tigaPuluhHariLalu = strtotime('-30 days');
			$isRecentCustomer = $transaksiTerakhir > $tigaPuluhHariLalu;
		}
		$customer['is_recent'] = $isRecentCustomer;
	}
	unset($customer);
	return $customers;
}
?>