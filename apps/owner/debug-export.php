<?php
// Debug file untuk test koneksi dan query
session_start();

// Set session untuk test
$_SESSION['owner_data'] = [
    'bisnis_id' => 1,
    'nama_bisnis' => 'Test Laundry'
];

echo "<h2>Debug Export Function</h2>";

try {
    require_once __DIR__ . '/query-laporan.php';
    
    echo "<p>✅ query-laporan.php loaded successfully</p>";
    
    $bisnisId = 1;
    $laporanQuery = new LaporanQuery($bisnisId);
    
    echo "<p>✅ LaporanQuery class created successfully</p>";
    
    // Test basic query
    $data = $laporanQuery->getAllData('bulan_ini', null, null);
    
    echo "<p>✅ getAllData executed successfully</p>";
    echo "<pre>Sample data: " . print_r($data, true) . "</pre>";
    
    // Test manual export creation
    echo "<hr><h3>Testing Manual Export</h3>";
    
    $exportData = ['export_pendapatan'];
    $format = 'csv';
    $filterType = 'bulan_ini';
    
    if ($format === 'csv') {
        $filename = 'debug_test_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = __DIR__ . '/../../exports/' . $filename;
        
        $file = fopen($filepath, 'w');
        
        if ($file) {
            // UTF-8 BOM
            fwrite($file, "\xEF\xBB\xBF");
            
            // Header
            fputcsv($file, ["Debug Test Export"], ';');
            fputcsv($file, ["Tanggal: " . date('Y-m-d H:i:s')], ';');
            fputcsv($file, [""], ';');
            
            // Test data
            fputcsv($file, ["Total Transaksi", $data['pendapatan']['total_transaksi'] ?? 0], ';');
            fputcsv($file, ["Total Pendapatan", "Rp " . number_format($data['pendapatan']['total_pendapatan'] ?? 0, 0, ',', '.')], ';');
            
            fclose($file);
            
            echo "<p>✅ CSV file created: <a href='../../exports/$filename' target='_blank'>$filename</a></p>";
        } else {
            echo "<p>❌ Failed to create CSV file</p>";
        }
    }
    
    echo "<p><strong>Export functionality appears to be working!</strong></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>Stack trace: " . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='test-export-php.php'>← Back to Test Page</a></p>";
echo "<p><a href='laporan.php'>← Back to Laporan</a></p>";
?>