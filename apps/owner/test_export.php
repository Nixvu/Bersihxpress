<?php
// Test file untuk cek koneksi export API
session_start();

// Simulasi session owner untuk testing
$_SESSION['owner_data'] = [
    'bisnis_id' => 1,
    'nama_bisnis' => 'Test Laundry'
];

echo "Testing export API...\n";
echo "Session data: " . json_encode($_SESSION['owner_data']) . "\n";

// Test POST request
$url = 'http://localhost/BersihXpress-main/apps/owner/api/export.php';
$data = json_encode([
    'format' => 'csv',
    'exportData' => ['export_pendapatan'],
    'filterType' => 'bulan_ini',
    'tanggalMulai' => null,
    'tanggalSelesai' => null
]);

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => $data
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "API Response: " . $result . "\n";
?>