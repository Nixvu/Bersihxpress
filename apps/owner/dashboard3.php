<?php
require_once 'middleware/auth_owner.php';

// Ambil data dari session
$ownerData = $_SESSION['owner_data'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <!-- ====================================================== -->
    <!-- KONTEN UTAMA (DASHBOARD)                                -->
    <!-- ====================================================== -->
    <div id="main-content" class="flex-grow flex flex-col overflow-hidden">
        <!-- Wrapper Sticky untuk Header dan Kartu Statistik -->
        <div class="flex-shrink-0">
            <!-- Header -->
            <header class="bg-blue-600 rounded-b-[32px] p-6 pb-28 shadow-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-white mb-1">Selamat datang,</h1>
                        <?php if ($ownerData): ?>
                            <p class="text-blue-100"><?php echo htmlspecialchars($ownerData['nama_lengkap']); ?></p>
                            <p class="text-blue-100"><?php echo htmlspecialchars($ownerData['nama_bisnis']); ?></p>
                        <?php else: ?>
                            <p class="text-blue-100">Data owner tidak ditemukan. Silakan hubungi admin atau coba login ulang.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Kartu-kartu Statistik -->
            <div class="px-6 -mt-20">
                <div class="grid grid-cols-2 gap-4">
                    <!-- Kartu Transaksi Hari Ini -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-blue-100 p-2 rounded-lg">
                                <svg data-feather="shopping-bag" class="w-5 h-5 text-blue-600"></svg>
                            </div>
                            <span class="text-sm text-gray-600">Transaksi Hari Ini</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900" id="todayTransactions">0</h3>
                        <p class="text-sm text-gray-500" id="todayIncome">Rp0</p>
                    </div>

                    <!-- Kartu Pendapatan Bulan Ini -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-green-100 p-2 rounded-lg">
                                <svg data-feather="dollar-sign" class="w-5 h-5 text-green-600"></svg>
                            </div>
                            <span class="text-sm text-gray-600">Pendapatan Bulan Ini</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900" id="monthlyIncome">Rp0</h3>
                    </div>

                    <!-- Kartu Order Pending -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-yellow-100 p-2 rounded-lg">
                                <svg data-feather="clock" class="w-5 h-5 text-yellow-600"></svg>
                            </div>
                            <span class="text-sm text-gray-600">Order Pending</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900" id="pendingOrders">0</h3>
                        <a href="transaksi.php?status=pending" class="text-sm text-blue-600">Lihat Detail →</a>
                    </div>

                    <!-- Kartu Total Pelanggan -->
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="bg-purple-100 p-2 rounded-lg">
                                <svg data-feather="users" class="w-5 h-5 text-purple-600"></svg>
                            </div>
                            <span class="text-sm text-gray-600">Total Pelanggan</span>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900" id="totalCustomers">0</h3>
                        <a href="pelanggan.php" class="text-sm text-blue-600">Lihat Detail →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian yang bisa di-scroll -->
        <div class="flex-grow overflow-y-auto mt-6 pb-24">
            <!-- Ringkasan Transaksi -->
            <section class="px-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-gray-900">Ringkasan Transaksi</h2>
                    <a href="transaksi.php" class="text-sm text-blue-600">Lihat Semua</a>
                </div>

                <div class="space-y-4" id="recentTransactions">
                    <!-- Transaksi akan diisi melalui JavaScript -->
                </div>
            </section>
        </div>
    </div>

    <!-- ====================================================== -->
    <!-- NAVIGATION BAR                                          -->
    <!-- ====================================================== -->
    <nav class="fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 flex justify-around items-center p-2 z-50">
        <a href="dashboard.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Beranda</span>
        </a>
        <a href="laporan.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="bar-chart-2" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Laporan</span>
        </a>
        <a href="profile.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="user" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Akun</span>
        </a>
    </nav>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        // Helper function for consistent money formatting
        function formatMoney(amount) {
            return 'Rp' + new Intl.NumberFormat('id-ID').format(amount);
        }
        document.addEventListener('DOMContentLoaded', function() {
            // Load dashboard stats
            fetch('api/owner.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_dashboard_stats'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const stats = data.data;
                    
                    // Update statistics
                    document.getElementById('todayTransactions').textContent = stats.today.transactions;
                    document.getElementById('todayIncome').textContent = formatMoney(stats.today.income);
                    document.getElementById('monthlyIncome').textContent = formatMoney(stats.monthly_income);
                    document.getElementById('pendingOrders').textContent = stats.pending_orders;
                    document.getElementById('totalCustomers').textContent = stats.total_customers;
                }
            })
            .catch(error => console.error('Error:', error));

            // Load recent transactions
            fetch('api/owner.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'get_transactions',
                    limit: 5
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('recentTransactions');
                    container.innerHTML = data.data.map(transaction => `
                        <div class="bg-white rounded-xl p-4 shadow-sm">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">${transaction.no_nota}</p>
                                    <p class="text-sm text-gray-600">${transaction.nama_pelanggan || 'Pelanggan Umum'}</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-medium ${
                                    transaction.status === 'selesai' ? 'bg-green-100 text-green-700' :
                                    transaction.status === 'proses' ? 'bg-yellow-100 text-yellow-700' :
                                    'bg-gray-100 text-gray-700'
                                }">${
                                    transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1)
                                }</span>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Total:</p>
                                <p class="font-bold text-gray-900">Rp${new Intl.NumberFormat('id-ID').format(transaction.total_harga)}</p>
                            </div>
                        </div>
                    `).join('');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    </script>
</body>
</html>