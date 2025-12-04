<?php
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/middleware/auth_owner.php';

$ownerData = $_SESSION['owner_data'] ?? [];
$bisnisId = $ownerData['bisnis_id'] ?? null;
$transaksiId = $_GET['id'] ?? '';

if (!$bisnisId || !$transaksiId) {
    die('Data tidak valid.');
}

// Get transaction data
try {
    $stmt = $conn->prepare('
        SELECT 
            t.*,
            p.nama as pelanggan_nama,
            p.no_telepon as pelanggan_telepon,
            p.alamat as pelanggan_alamat,
            b.nama_bisnis as bisnis_nama,
            b.alamat as bisnis_alamat,
            b.no_telepon as bisnis_telepon
        FROM transaksi t
        LEFT JOIN pelanggan p ON t.pelanggan_id = p.pelanggan_id
        LEFT JOIN bisnis b ON t.bisnis_id = b.bisnis_id
        WHERE t.transaksi_id = ? AND t.bisnis_id = ?
    ');
    $stmt->execute([$transaksiId, $bisnisId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        die('Transaksi tidak ditemukan.');
    }

    // Get nota template
    $stmtTemplate = $conn->prepare('SELECT header, footer FROM template_nota WHERE bisnis_id = ? LIMIT 1');
    $stmtTemplate->execute([$bisnisId]);
    $notaTemplate = $stmtTemplate->fetch(PDO::FETCH_ASSOC);
    $notaHeader = $notaTemplate['header'] ?? '';
    $notaFooter = $notaTemplate['footer'] ?? '';

    // Get transaction items (fallback to detail_transaksi)
    $items = [];
    try {
        $stmtItems = $conn->prepare('SELECT d.jumlah as qty, l.nama_layanan as nama_item, d.harga_satuan, d.subtotal as total_harga FROM detail_transaksi d LEFT JOIN layanan l ON d.layanan_id = l.layanan_id WHERE d.transaksi_id = ?');
        $stmtItems->execute([$transaksiId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Jika gagal, items tetap kosong
    }

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota #<?php echo htmlspecialchars($transaction['no_nota']); ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .nota {
            width: 58mm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .bisnis-nama {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }
        .total {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="nota">
        <!-- Nota Header from template_nota -->
        <?php if ($notaHeader): ?>
            <div class="header">
                <?php echo $notaHeader; ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <span>No Nota:</span>
            <span><?php echo htmlspecialchars($transaction['no_nota']); ?></span>
        </div>
        <div class="row">
            <span>Tanggal:</span>
            <span><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></span>
        </div>
        <div class="row">
            <span>Pelanggan:</span>
            <span><?php echo htmlspecialchars($transaction['pelanggan_nama'] ?? 'Guest'); ?></span>
        </div>
        <?php if ($transaction['pelanggan_telepon']): ?>
        <div class="row">
            <span>Telepon:</span>
            <span><?php echo htmlspecialchars($transaction['pelanggan_telepon']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($transaction['catatan']): ?>
        <div class="row">
            <span>Catatan:</span>
            <span><?php echo htmlspecialchars($transaction['catatan']); ?></span>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Tabel Item Transaksi -->
        <table style="width:100%; font-size:12px; border-collapse:collapse;">
            <thead>
                <tr style="border-bottom:1px dashed #000;">
                    <th style="text-align:left;">Item</th>
                    <th style="text-align:center;">Qty</th>
                    <th style="text-align:right;">Harga</th>
                    <th style="text-align:right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['nama_item']); ?></td>
                    <td style="text-align:center;"><?php echo $item['qty']; ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                    <td style="text-align:right;">Rp <?php echo number_format($item['total_harga'], 0, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="row total">
            <span>TOTAL:</span>
            <span>Rp <?php echo number_format($transaction['total_harga'], 0, ',', '.'); ?></span>
        </div>
        <div class="row">
            <span>Dibayar:</span>
            <span>Rp <?php echo number_format($transaction['dibayar'], 0, ',', '.'); ?></span>
        </div>
        <?php 
        $sisa = $transaction['total_harga'] - $transaction['dibayar'];
        if ($sisa > 0): 
        ?>
        <div class="row">
            <span>Sisa:</span>
            <span>Rp <?php echo number_format($sisa, 0, ',', '.'); ?></span>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <div class="row">
            <span>Status:</span>
            <span><?php echo ucfirst($transaction['status']); ?></span>
        </div>
        <?php if ($transaction['tanggal_selesai']): ?>
        <div class="row">
            <span>Estimasi:</span>
            <span><?php echo date('d/m/Y H:i', strtotime($transaction['tanggal_selesai'])); ?></span>
        </div>
        <?php endif; ?>

        <!-- Nota Footer from template_nota -->
        <?php if ($notaFooter): ?>
            <div class="footer">
                <?php echo $notaFooter; ?>
            </div>
        <?php else: ?>
            <div class="footer">
                <div>Terima kasih atas kepercayaan Anda</div>
                <div>Barang hilang/rusak bukan tanggung jawab kami</div>
                <div style="margin-top: 10px;">
                    Dicetak: <?php echo date('d/m/Y H:i'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 30px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px;">Cetak Nota</button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; margin-left: 10px;">Tutup</button>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            window.print();
        };
        
        // Close window after printing
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>