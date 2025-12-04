<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/functions.php';


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