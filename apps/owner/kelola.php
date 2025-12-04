<?php
require_once __DIR__ . '/middleware/auth_owner.php';
require_once __DIR__ . '/components/layout.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';


// Pastikan data owner diambil dari DB jika session kosong atau tidak lengkap
$ownerData = $_SESSION['owner_data'] ?? [];
if (empty($ownerData) && isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../config/database.php';
    $userId = $_SESSION['user_id'];
    $stmtOwner = $conn->prepare('
        SELECT u.*, b.* 
        FROM users u 
        LEFT JOIN bisnis b ON b.owner_id = u.user_id 
        WHERE u.user_id = ? AND u.role = "owner"');
    $stmtOwner->execute([$userId]);
    $ownerData = $stmtOwner->fetch() ?: [];
    $_SESSION['owner_data'] = $ownerData;
}
$bisnisId = $ownerData['bisnis_id'] ?? null;
$bisnisNama = $ownerData['nama_bisnis'] ?? 'BersihXpress';
$alamatBisnis = $ownerData['alamat'] ?? 'Lengkapi profil usaha terlebih dahulu';
$noTeleponBisnis = $ownerData['no_telepon'] ?? '';


$stats = [
    'pelanggan' => 0,
    'layanan' => 0,
    'karyawan' => 0,
];

if ($bisnisId) {
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM pelanggan WHERE bisnis_id = ?');
        $stmt->execute([$bisnisId]);
        $stats['pelanggan'] = (int) ($stmt->fetch()['total'] ?? 0);

        $layananCount = 0;
        $hasLayananBisnisColumn = false;
        try {
            $conn->query('SELECT bisnis_id FROM layanan LIMIT 1');
            $hasLayananBisnisColumn = true;
        } catch (PDOException $ignored) {
            $hasLayananBisnisColumn = false;
        }

        if ($hasLayananBisnisColumn) {
            $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM layanan WHERE bisnis_id = ?');
            $stmt->execute([$bisnisId]);
            $layananCount = (int) ($stmt->fetch()['total'] ?? 0);
        } else {
            $stmt = $conn->prepare('
                SELECT COUNT(*) AS total
                FROM layanan l
                INNER JOIN kategori_layanan k ON k.kategori_id = l.kategori_id
                WHERE k.bisnis_id = ?
            ');
            $stmt->execute([$bisnisId]);
            $layananCount = (int) ($stmt->fetch()['total'] ?? 0);
        }
        $stats['layanan'] = $layananCount;

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM karyawan WHERE bisnis_id = ? AND status = 'aktif'");
        $stmt->execute([$bisnisId]);
        $stats['karyawan'] = (int) ($stmt->fetch()['total'] ?? 0);
    } catch (PDOException $e) {
        logError('Kelola page stats error', [
            'error' => $e->getMessage(),
            'bisnis_id' => $bisnisId,
        ]);
    }
}

$flashSuccess = $_SESSION['owner_flash_success'] ?? null;
$flashError = $_SESSION['owner_flash_error'] ?? null;
unset($_SESSION['owner_flash_success'], $_SESSION['owner_flash_error']);

// Logo bisnis: default placeholder
$namaLengkap = $ownerData['nama_lengkap'] ?? 'B';
$inisial = strtoupper(substr($namaLengkap, 0, 1));
$logoSrc = "https://placehold.co/64x64/3B82F6/FFFFFF?text=$inisial"; // Default to a personalized placeholder

if ($bisnisId) {
    try {
        $stmt = $conn->prepare('SELECT logo FROM bisnis WHERE bisnis_id = ?');
        $stmt->execute([$bisnisId]);
        $bisnisInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($bisnisInfo && !empty($bisnisInfo['logo'])) {
            // If a custom logo exists, override the placeholder
            $logoSrc = '../../' . ltrim($bisnisInfo['logo'], '/');
        }
    } catch (PDOException $e) {
        error_log('Error loading bisnis logo: ' . $e->getMessage());
    }
}

function formatKelolaCount($value)
{
    $number = is_numeric($value) ? (int) $value : 0;
    return number_format($number, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Bisnis - BersihXpress</title>
    
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 flex flex-col h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>
    <div id="main-content" class="flex-grow overflow-y-auto no-scrollbar pb-24">

        <header class="relative z-10 bg-blue-600 rounded-b-[32px] p-6 pb-28 shadow-lg flex-shrink-0">
            <h1 class="text-2xl font-bold text-white">Kelola Bisnis</h1>
            <p class="text-sm opacity-90 text-white"><?php echo htmlspecialchars($bisnisNama); ?></p>
        </header>
        
        <main class="relative z-20 -mt-20 px-6">
            <?php if ($flashSuccess): ?>
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <?php echo htmlspecialchars($flashSuccess); ?>
            </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <?php echo htmlspecialchars($flashError); ?>
            </div>
            <?php endif; ?>
            <section class="bg-white rounded-lg shadow-md p-5">
                <div class="flex items-center space-x-4 border-b pb-4">
                    <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Logo Usaha" class="w-16 h-16 rounded-lg object-cover">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($bisnisNama); ?></h2>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($alamatBisnis); ?></p>
                    </div>
                </div>
                <div class="flex justify-around text-center pt-4">
                    <div>
                        <p class="text-xl font-bold text-gray-900"><?php echo formatKelolaCount($stats['pelanggan']); ?></p>
                        <span class="text-sm text-gray-500">Pelanggan</span>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900"><?php echo formatKelolaCount($stats['layanan']); ?></p>
                        <span class="text-sm text-gray-500">Layanan</span>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-900"><?php echo formatKelolaCount($stats['karyawan']); ?></p>
                        <span class="text-sm text-gray-500">Karyawan</span>
                    </div>
                </div>
            </section>
        </main>

        <nav class="px-6 mt-6">
            <div class="bg-white rounded-lg shadow overflow-hidden divide-y divide-gray-200">
                
                <button id="btn-profil-usaha" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="briefcase" class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Profil Usaha</p>
                            <p class="text-sm text-gray-500">Perbaharui informasi usaha</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                
                <a href="layanan.php" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="tag" class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Kelola Layanan</p>
                            <p class="text-sm text-gray-500">Kelola layanan laundry</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </a>
                
                <a href="pelanggan.php" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="users" class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Kelola Pelanggan</p>
                            <p class="text-sm text-gray-500">Kelola data pelanggan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </a>
                
                <a href="template.php" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="file-text" class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Template Nota</p>
                            <p class="text-sm text-gray-500">Kelola template nota & pesan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </a>
                
                <a href="karyawan.php" class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="user-check" class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Kelola Karyawan</p>
                            <p class="text-sm text-gray-500">Kelola data karyawan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </a>
            </div> 
        </nav>
    </div>

    <nav class="sticky bottom-0 left-0 right-0 bg-white border-t border-gray-200 grid grid-cols-5 gap-2 px-4 py-3 shadow-[0_-2px_5px_rgba(0,0,0,0.05)] flex-shrink-0 z-20">
        <a href="kelola.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="grid" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Kelola</span>
        </a>
        <a href="transaksi.php" class="flex flex-col items-center text-blue-600 bg-blue-100 rounded-lg px-4 py-2">
            <svg data-feather="file-text" class="w-6 h-6"></svg>
            <span class="text-xs mt-1 font-semibold">Transaksi</span>
        </a>
        <a href="dashboard.php" class="flex flex-col items-center text-gray-500 px-4 py-2">
            <svg data-feather="home" class="w-6 h-6"></svg>
            <span class="text-xs mt-1">Beranda</span>
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

    <!-- Popup Modal -->
    <div id="modal-container" class="hidden z-30">
        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>
            <?php include 'modals/modal-edit-usaha.php'; ?>
            
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/owner-kelola.js"></script>
</body>
</html>