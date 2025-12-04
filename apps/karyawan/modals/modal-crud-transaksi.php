<?php 
$selectedTransaction = $_SESSION['selected_transaksi'] ?? [];

// Load customer and service data for transaction forms
$bisnis_id = $_SESSION['karyawan_data']['bisnis_id'] ?? '';
$karyawanData = $_SESSION['karyawan_data'] ?? [];

$daftar_pelanggan = [];
$daftar_layanan = [];
if ($bisnis_id) {
    require_once __DIR__ . '/../../../config/database.php';
    $stmt = $conn->prepare("SELECT pelanggan_id, nama, no_telepon FROM pelanggan WHERE bisnis_id = ? ORDER BY nama ASC");
    $stmt->execute([$bisnis_id]);
    $daftar_pelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil layanan dari bisnis
    $stmtLayanan = $conn->prepare("SELECT layanan_id, nama_layanan, harga, satuan FROM layanan WHERE kategori_id IN (SELECT kategori_id FROM kategori_layanan WHERE bisnis_id = ?) ORDER BY nama_layanan ASC");
    $stmtLayanan->execute([$bisnis_id]);
    $daftar_layanan = $stmtLayanan->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!-- Modal Rincian Transaksi -->
<div id="modal-rincian-transaksi" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[95vh]">
    <div class="flex-shrink-0 bg-white rounded-t-[24px]">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Rincian Transaksi</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-rincian-transaksi">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>

        <div class="px-6 pb-4 border-b border-gray-200">
            <p class="text-lg font-bold text-gray-900 transaksi-pelanggan-nama"><?php echo htmlspecialchars($selectedTransaction['pelanggan'] ?? 'Guest'); ?></p>
            <p class="text-sm text-gray-500 -mt-1 transaksi-no-nota">ID Nota #<?php echo htmlspecialchars($selectedTransaction['no_nota'] ?? '0000'); ?></p>

            <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                <div class="bg-gray-100 p-3 rounded-lg">
                    <span class="text-xs text-gray-500">Total Tagihan</span>
                    <p class="text-xl font-bold text-blue-600 transaksi-total-display"><?php echo htmlspecialchars($selectedTransaction['total_harga'] ?? 'Rp 0'); ?></p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg">
                    <span class="text-xs text-gray-500">Status Bayar</span>
                    <p class="text-xl font-bold text-gray-900 transaksi-status-bayar-display"><?php echo htmlspecialchars($selectedTransaction['status_bayar'] ?? 'Belum Lunas'); ?></p>
                </div>
                <div class="bg-gray-100 p-3 rounded-lg">
                    <span class="text-xs text-gray-500">Status Pesanan</span>
                    <p class="text-xl font-bold text-gray-900 transaksi-status-display"><?php echo htmlspecialchars($selectedTransaction['status'] ?? 'Pending'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="flex-grow overflow-y-auto divide-y divide-gray-100 no-scrollbar">
        <section class="py-5 px-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Rincian Waktu</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal Masuk</span>
                    <span class="font-medium text-gray-800 transaksi-tanggal-masuk">22 Okt 2025, 19:00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal Selesai</span>
                    <span class="font-medium text-gray-800 transaksi-tanggal-selesai">23 Okt 2025, 19:00</span>
                </div>
            </div>
        </section>

        <section class="py-5 px-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">Aksi Lainnya</h3>
            <div class="space-y-3">
                <button class="btn-cetak-nota w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="printer" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <p class="font-semibold text-gray-800">Cetak Nota</p>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <button class="btn-kirim-wa w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 rounded-lg mr-3">
                            <svg data-feather="message-circle" class="w-5 h-5 text-green-700"></svg>
                        </div>
                        <p class="font-semibold text-gray-800">Kirim Pesan (WhatsApp)</p>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <button id="btn-opsi-lanjutan" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3">
                            <svg data-feather="settings" class="w-5 h-5 text-gray-700"></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">Ubah Status</p>
                            <p class="text-sm text-gray-500">Ubah status bayar atau pesanan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
            </div>
        </section>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button id="btn-aksi-utama" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 shadow-sm">
            Tandai Selesai
        </button>
    </div>
</div>

<!-- Modal Update Status -->
<div id="modal-opsi-lanjutan" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Opsi Lanjutan</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-opsi-lanjutan">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="px-6 space-y-4 overflow-y-auto no-scrollbar flex-grow">
        <form id="form-opsi-lanjutan" method="POST" action="transaksi.php" class="space-y-4">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="transaksi_id" id="update_transaksi_id" class="transaksi-id-input">

            <div>
                <label for="opsi_pembayaran" class="text-sm font-medium text-gray-600">Ubah Status Pembayaran</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <select id="opsi_pembayaran" name="status_bayar"
                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Tidak Diubah --</option>
                        <option value="belum_lunas">Belum Lunas</option>
                        <option value="lunas">Lunas</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="opsi_status" class="text-sm font-medium text-gray-600">Ubah Status Pesanan</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="refresh-cw" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <select id="opsi_status" name="new_status"
                        class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Tidak Diubah --</option>
                        <option value="pending">Pending</option>
                        <option value="proses">Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="diambil">Diambil</option>
                        <option value="batal">Batal</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200 flex space-x-3">
        <button class="btn-close-modal w-1/2 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50" data-modal-id="modal-opsi-lanjutan">
            Batal
        </button>
        <button type="submit" form="form-opsi-lanjutan" class="w-1/2 bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Simpan Perubahan
        </button>
    </div>
</div>

<!-- Modal Buat Transaksi -->
<div id="modal-buat-transaksi" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Buat Transaksi</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-buat-transaksi">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="px-6 pb-6 space-y-3 overflow-y-auto no-scrollbar">
        <button id="btn-transaksi-manual" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="box" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Transaksi Manual</p>
                    <p class="text-sm text-gray-500">Manual input</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>
        <button id="btn-transaksi-template" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="grid" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Transaksi Template</p>
                    <p class="text-sm text-gray-500">Pilih dari paket layanan jadi</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>
        <button id="btn-pengeluaran" class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="minus-circle" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Pengeluaran</p>
                    <p class="text-sm text-gray-500">Pengeluaran Kas Dll</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-100">
        <button class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50" data-modal-id="modal-buat-transaksi">
            Batal
        </button>
    </div>
</div>

<!-- Modal Form Transaksi Manual -->
<div id="modal-form-transaksi" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Buat Transaksi Manual</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-form-transaksi">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-buat-transaksi" action="api/query-buat-transaksi.php" method="POST" class="space-y-6">
            <!-- Hidden fields for database -->
            <input type="hidden" name="bisnis_id" id="bisnis_id" readonly class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100 mb-2" value="<?php echo htmlspecialchars($karyawanData['bisnis_id'] ?? ''); ?>" placeholder="Bisnis ID (readonly)">
            <input type="hidden" name="karyawan_id" id="karyawan_id">
            <input type="hidden" name="pelanggan_id" id="pelanggan_id">

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">1. Informasi Pelanggan</h3>
                <div class="space-y-4">
                    <div>
                        <label for="cari_pelanggan_manual" class="text-sm font-medium text-gray-600">
                            Cari Pelanggan (Nama / No HP)
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <select id="cari_pelanggan_manual" name="pelanggan_id" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                                <option value="">-- Pilih pelanggan yang pernah datang --</option>
                                <?php foreach ($daftar_pelanggan as $plg): ?>
                                    <option value="<?php echo htmlspecialchars($plg['pelanggan_id']); ?>">
                                        <?php echo htmlspecialchars($plg['nama']); ?> (<?php echo htmlspecialchars($plg['no_telepon']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="text-sm text-left text-gray-500 -my-2">atau masukkan data pelanggan baru ...</p>
                    <div>
                        <label for="nama_pelanggan_manual" class="text-sm font-medium text-gray-600">
                            Nama Pelanggan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="nama_pelanggan_manual" name="nama_pelanggan" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Nama Pelanggan">
                        </div>
                    </div>
                    <div>
                        <label for="no_handphone_manual" class="text-sm font-medium text-gray-600">
                            No Handphone (WA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="no_handphone_manual" name="no_handphone" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="No Handphone Pelanggan">
                        </div>
                    </div>
                    <div>
                        <label for="alamat_manual" class="text-sm font-medium text-gray-600">Alamat (Opsional)</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                <svg data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <textarea id="alamat_manual" name="alamat" rows="2"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Alamat untuk data / pengantaran"></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">2. Informasi Pesanan</h3>
                <div class="space-y-4">
                    <div>
                        <label for="tgl_selesai_manual" class="text-sm font-medium text-gray-600">
                            Estimasi Selesai <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" id="tgl_selesai_manual" name="tanggal_selesai" required
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label for="status_awal_manual" class="text-sm font-medium text-gray-600">
                            Status Awal <span class="text-red-500">*</span>
                        </label>
                        <select id="status_awal_manual" name="status" required
                            class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Status --</option>
                            <option value="pending" selected>Pending (Antrian)</option>
                            <option value="proses">Langsung Diproses</option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">3. Rincian Layanan</h3>
                <div class="border-b pb-3 mb-3 layanan-item">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-sm font-medium text-gray-600">
                            Layanan 1 <span class="text-red-500">*</span>
                        </label>
                        <button type="button" class="text-red-500 hover:text-red-700 btn-remove-layanan" style="display: none;">
                            <svg data-feather="trash-2" class="w-4 h-4"></svg>
                        </button>
                    </div>
                    <select name="layanan_id[]" required
                        class="w-full py-3 px-3 border border-gray-300 rounded-lg bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500 layanan-select">
                        <option value="">-- Pilih Layanan (dari Kelola Layanan) --</option>
                        <?php foreach ($daftar_layanan as $layanan): ?>
                            <option value="<?php echo htmlspecialchars($layanan['layanan_id']); ?>">
                                <?php echo htmlspecialchars($layanan['nama_layanan']); ?> (Rp <?php echo number_format($layanan['harga'],0,',','.'); ?>/<?php echo htmlspecialchars($layanan['satuan']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="quantity[]" placeholder="Qty (Kg / Pcs)" required min="0.1" step="0.1"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg quantity-input">
                        <input type="text" placeholder="Harga Total"
                            class="w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100 harga-total"
                            readonly>
                        <input type="hidden" name="harga_satuan[]" class="harga-satuan-hidden">
                    </div>
                </div>
                <button type="button" id="btn-tambah-layanan"
                    class="w-full border-2 border-dashed border-blue-500 text-blue-500 font-semibold py-3 px-4 rounded-lg hover:bg-blue-50">
                    + Tambah Layanan Lain
                </button>
                <div>
                    <div class="border-t pt-4"></div>
                    <label for="catatan_manual" class="text-sm font-medium text-gray-600">Catatan (Opsional)</label>
                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                            <svg data-feather="clipboard" class="h-5 w-5 text-gray-400"></svg>
                        </span>
                        <textarea id="catatan_manual" name="catatan" rows="2"
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Misal: Alergi parfum, minta lipat rapi..."></textarea>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">4. Rincian Pembayaran</h3>
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Subtotal</label>
                        <input type="text" id="subtotal-display"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg bg-gray-100"
                            value="Rp 0" readonly>
                        <input type="hidden" name="subtotal" id="subtotal-value">
                    </div>
                    <div>
                        <label for="diskon" class="text-sm font-medium text-gray-600">Diskon (Rp)</label>
                        <input type="number" id="diskon" name="diskon" min="0"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Contoh: 1000" value="0">
                    </div>
                    <div>
                        <label for="biaya_antar" class="text-sm font-medium text-gray-600">Biaya Antar (Rp)</label>
                        <input type="number" id="biaya_antar" name="biaya_antar" min="0"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg" value="0">
                    </div>
                    <div class="border-t pt-4">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">Total Akhir</span>
                            <span class="text-2xl font-bold text-blue-600" id="total-akhir-display">Rp 0</span>
                        </div>
                        <input type="hidden" name="total_harga" id="total-harga-value" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="metode_bayar_manual" class="text-sm font-medium text-gray-600">
                                Metode Bayar <span class="text-red-500">*</span>
                            </label>
                            <select id="metode_bayar_manual" name="metode_bayar" required
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Tunai">Tunai</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label for="dibayar_manual" class="text-sm font-medium text-gray-600">
                                Jumlah Dibayar <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="dibayar_manual" name="dibayar" required min="0" step="0.01"
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg"
                                placeholder="Jumlah yang dibayar">
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>

    <script>
    // --- ENHANCED DYNAMIC TRANSAKSI FORM LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        // --- Pelanggan Dropdown Logic General ---
        const pelangganData = {};
        <?php foreach ($daftar_pelanggan as $plg): ?>
        pelangganData['<?php echo $plg['pelanggan_id']; ?>'] = {
            nama: '<?php echo addslashes($plg['nama']); ?>',
            no_telepon: '<?php echo addslashes($plg['no_telepon']); ?>'
        };
        <?php endforeach; ?>

        // Manual form
        const selectManual = document.getElementById('cari_pelanggan_manual');
        const namaManual = document.getElementById('nama_pelanggan_manual');
        const nohpManual = document.getElementById('no_handphone_manual');
        if (selectManual && namaManual && nohpManual) {
            selectManual.addEventListener('change', function() {
                const selectedId = this.value;
                if (selectedId && pelangganData[selectedId]) {
                    namaManual.value = pelangganData[selectedId].nama;
                    namaManual.readOnly = true;
                    nohpManual.value = pelangganData[selectedId].no_telepon;
                    nohpManual.readOnly = true;
                } else {
                    namaManual.value = '';
                    namaManual.readOnly = false;
                    nohpManual.value = '';
                    nohpManual.readOnly = false;
                }
            });
        }

        function formatRupiah(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        // Mapping layanan_id ke harga dari PHP
        const layananHargaMap = {};
        <?php foreach ($daftar_layanan as $layanan): ?>
        layananHargaMap['<?php echo $layanan['layanan_id']; ?>'] = <?php echo floatval($layanan['harga']); ?>;
        <?php endforeach; ?>

        const form = document.getElementById('form-buat-transaksi');
        const layananContainer = form.querySelector('section:nth-of-type(3)');
        const btnTambahLayanan = document.getElementById('btn-tambah-layanan');
        const subtotalDisplay = document.getElementById('subtotal-display');
        const subtotalValue = document.getElementById('subtotal-value');
        const diskonInput = document.getElementById('diskon');
        const biayaAntarInput = document.getElementById('biaya_antar');
        const totalAkhirDisplay = document.getElementById('total-akhir-display');
        const totalHargaValue = document.getElementById('total-harga-value');

        function getLayananBlocks() {
            return Array.from(layananContainer.querySelectorAll('.layanan-item'));
        }

        function calculateSubtotal() {
            let subtotal = 0;
            getLayananBlocks().forEach(block => {
                const select = block.querySelector('.layanan-select');
                const qtyInput = block.querySelector('.quantity-input');
                const layananId = select.value;
                const price = layananHargaMap[layananId] || 0;
                const qty = parseFloat(qtyInput.value) || 0;
                subtotal += price * qty;
            });
            return subtotal;
        }

        function updateLayananPrices() {
            getLayananBlocks().forEach(block => {
                const select = block.querySelector('.layanan-select');
                const qtyInput = block.querySelector('.quantity-input');
                const hargaTotalInput = block.querySelector('.harga-total');
                const hargaSatuanInput = block.querySelector('.harga-satuan-hidden');
                const layananId = select.value;
                const price = layananHargaMap[layananId] || 0;
                hargaSatuanInput.value = price;
                const qty = parseFloat(qtyInput.value) || 0;
                const total = price * qty;
                if (qty > 0) {
                    hargaTotalInput.value = formatRupiah(total);
                } else {
                    hargaTotalInput.value = price > 0 ? formatRupiah(price) : 'Rp 0';
                }
            });
        }

        function updateTotals() {
            updateLayananPrices();
            const subtotal = calculateSubtotal();
            subtotalDisplay.value = formatRupiah(subtotal);
            subtotalValue.value = subtotal;
            const diskon = parseFloat(diskonInput.value) || 0;
            const biayaAntar = parseFloat(biayaAntarInput.value) || 0;
            let total = subtotal - diskon + biayaAntar;
            if (total < 0) total = 0;
            totalAkhirDisplay.textContent = formatRupiah(total);
            totalHargaValue.value = total;
        }

        function attachLayananEvents(block) {
            const select = block.querySelector('.layanan-select');
            const qtyInput = block.querySelector('.quantity-input');
            const hargaSatuanInput = block.querySelector('.harga-satuan-hidden');
            select.addEventListener('change', function() {
                updateTotals();
                hargaSatuanInput.value = layananHargaMap[select.value] || 0;
            });
            qtyInput.addEventListener('input', updateTotals);
            const btnRemove = block.querySelector('.btn-remove-layanan');
            if (btnRemove) {
                btnRemove.addEventListener('click', function() {
                    const layananBlocks = getLayananBlocks();
                    if (layananBlocks.length > 1) {
                        block.remove();
                        updateTotals();
                        updateRemoveButtons();
                    }
                });
            }
        }

        function updateRemoveButtons() {
            const blocks = getLayananBlocks();
            blocks.forEach((block, index) => {
                const btnRemove = block.querySelector('.btn-remove-layanan');
                if (btnRemove) {
                    btnRemove.style.display = blocks.length > 1 ? 'block' : 'none';
                }
                const label = block.querySelector('label');
                label.innerHTML = `Layanan ${index + 1} <span class="text-red-500">*</span>`;
            });
        }

        getLayananBlocks().forEach(attachLayananEvents);

        btnTambahLayanan.addEventListener('click', function(e) {
            e.preventDefault();
            const firstBlock = getLayananBlocks()[0];
            const newBlock = firstBlock.cloneNode(true);
            
            newBlock.querySelector('.layanan-select').selectedIndex = 0;
            newBlock.querySelector('.quantity-input').value = '';
            newBlock.querySelector('.harga-total').value = 'Rp 0';
            newBlock.querySelector('.harga-satuan-hidden').value = '';
            attachLayananEvents(newBlock);
            layananContainer.insertBefore(newBlock, btnTambahLayanan);
            updateRemoveButtons();
            updateTotals();
        });

        diskonInput.addEventListener('input', updateTotals);
        biayaAntarInput.addEventListener('input', updateTotals);

        form.addEventListener('submit', function(e) {
            const layananBlocks = getLayananBlocks();
            let hasValidLayanan = false;
            
            layananBlocks.forEach(block => {
                const select = block.querySelector('.layanan-select');
                const qty = block.querySelector('.quantity-input');
                if (select.value && qty.value && parseFloat(qty.value) > 0) {
                    hasValidLayanan = true;
                }
            });
            
            if (!hasValidLayanan) {
                e.preventDefault();
                alert('Minimal harus ada satu layanan yang dipilih dengan quantity yang valid!');
                return;
            }
            
            const totalHarga = parseFloat(totalHargaValue.value) || 0;
            const dibayar = parseFloat(document.getElementById('dibayar_manual').value) || 0;
            
            if (dibayar > totalHarga) {
                const confirm = window.confirm(`Jumlah bayar (${formatRupiah(dibayar)}) lebih besar dari total (${formatRupiah(totalHarga)}). Lanjutkan?`);
                if (!confirm) {
                    e.preventDefault();
                    return;
                }
            }
        });

        updateRemoveButtons();
        updateTotals();

        const now = new Date();
        now.setDate(now.getDate() + 3);
        const defaultDateTime = now.toISOString().slice(0, 16);
        document.getElementById('tgl_selesai_manual').value = defaultDateTime;
    });
    </script>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-buat-transaksi"
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Buat Transaksi
        </button>
    </div>
</div>

<!-- Modal Pengeluaran -->
<div id="modal-pengeluaran" class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Catat Pengeluaran</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-pengeluaran">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-pengeluaran" method="POST" action="transaksi.php" class="space-y-4">
            <input type="hidden" name="action" value="create_expense">

            <div>
                <label for="nama_pengeluaran" class="text-sm font-medium text-gray-600">Nama Pengeluaran <span class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="edit" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text" id="nama_pengeluaran" name="nama_pengeluaran"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        placeholder="Contoh : Beli Deterjen" required>
                </div>
            </div>

            <div>
                <label for="nominal_pengeluaran" class="text-sm font-medium text-gray-600">Nominal Pengeluaran <span class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="number" id="nominal_pengeluaran" name="nominal_pengeluaran" min="0" step="1000"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        placeholder="Contoh : 50000" required>
                </div>
            </div>

            <div>
                <label for="kategori_pengeluaran" class="text-sm font-medium text-gray-600">Kategori</label>
                <select id="kategori_pengeluaran" name="kategori_pengeluaran"
                    class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="bahan-baku">Bahan Baku (Deterjen, Parfum)</option>
                    <option value="operasional">Operasional (Listrik, Air)</option>
                    <option value="gaji">Gaji Karyawan</option>
                    <option value="lain-lain">Lain-Lain</option>
                </select>
            </div>

            <div>
                <label for="tanggal_pengeluaran" class="text-sm font-medium text-gray-600">Tanggal Pengeluaran</label>
                <input type="date" id="tanggal_pengeluaran" name="tanggal_pengeluaran"
                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div>
                <label for="metode_bayar_pengeluaran" class="text-sm font-medium text-gray-600">Metode Pembayaran</label>
                <select id="metode_bayar_pengeluaran" name="metode_bayar_pengeluaran"
                    class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="tunai">Tunai (Kas)</option>
                    <option value="transfer">Transfer Bank</option>
                </select>
            </div>

            <div>
                <label for="keterangan_pengeluaran" class="text-sm font-medium text-gray-600">Keterangan (Opsional)</label>
                <textarea id="keterangan_pengeluaran" name="keterangan_pengeluaran" rows="3"
                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                    placeholder="Contoh: Deterjen bubuk 10kg"></textarea>
            </div>
        </form>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-pengeluaran"
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Simpan Pengeluaran
        </button>
    </div>
</div>

<!-- MODAL TRANSAKSI TEMPLATE -->
<div id="modal-transaksi-template"
    class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Buat Transaksi Cepat (POS)</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-transaksi-template">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-buat-template" action="api/query-buat-transaksi.php" method="POST" class="space-y-6">
            <!-- Hidden fields for database -->
            <input type="hidden" name="bisnis_id" id="bisnis_id_template" readonly value="<?php echo htmlspecialchars($karyawanData['bisnis_id'] ?? ''); ?>">
            <input type="hidden" name="karyawan_id" id="karyawan_id_template">
            <input type="hidden" name="pelanggan_id" id="pelanggan_id_template">
            <input type="hidden" name="form_type" value="template">

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">1. Informasi Pelanggan</h3>
                <div class="space-y-4">
                    <div>
                        <label for="cari_pelanggan_template" class="text-sm font-medium text-gray-600">
                            Cari Pelanggan (Nama / No HP)
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="search" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <select id="cari_pelanggan_template" name="pelanggan_id" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                                <option value="">-- Pilih pelanggan yang pernah datang --</option>
                                <?php foreach ($daftar_pelanggan as $plg): ?>
                                    <option value="<?php echo htmlspecialchars($plg['pelanggan_id']); ?>">
                                        <?php echo htmlspecialchars($plg['nama']); ?> (<?php echo htmlspecialchars($plg['no_telepon']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 -mt-2 mb-3">atau masukkan data pelanggan baru ...</p>
                    <div>
                        <label for="nama_pelanggan_template" class="text-sm font-medium text-gray-600">
                            Nama Pelanggan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="nama_pelanggan_template" name="nama_pelanggan" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Nama Pelanggan">
                        </div>
                    </div>
                    <div>
                        <label for="no_handphone_template" class="text-sm font-medium text-gray-600">
                            No Handphone (WA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="no_handphone_template" name="no_handphone" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="No Handphone Pelanggan">
                        </div>
                    </div>
                    <div>
                        <label for="alamat_template" class="text-sm font-medium text-gray-600">Alamat (Opsional)</label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                                <svg data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <textarea id="alamat_template" name="alamat" rows="2"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Alamat untuk data / pengantaran"></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">2. Informasi Pesanan</h3>
                <div class="space-y-4">
                    <div>
                        <label for="tgl_selesai_template" class="text-sm font-medium text-gray-600">
                            Estimasi Selesai <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" id="tgl_selesai_template" name="tanggal_selesai" required
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg">
                    </div>

                    <div>
                        <label for="status_awal_template" class="text-sm font-medium text-gray-600">
                            Status Awal <span class="text-red-500">*</span>
                        </label>
                        <select id="status_awal_template" name="status" required
                            class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Status --</option>
                            <option value="pending" selected>Pending (Antrian)</option>
                            <option value="proses">Langsung Diproses</option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">3. Pilih Layanan (Menu Cepat)</h3>
                <div class="space-y-2 mb-4" id="keranjang-layanan">
                    <!-- Selected services will appear here -->
                </div>

                <div class="grid grid-cols-2 gap-3" id="daftar-layanan-dinamis">
                    <?php foreach ($daftar_layanan as $layanan): ?>
                    <button type="button"
                        class="text-left bg-white border border-gray-300 rounded-lg p-3 hover:border-blue-500 hover:bg-blue-50 service-btn"
                        data-id="<?php echo htmlspecialchars($layanan['layanan_id']); ?>"
                        data-nama="<?php echo htmlspecialchars($layanan['nama_layanan']); ?>"
                        data-harga="<?php echo htmlspecialchars($layanan['harga']); ?>"
                        data-unit="<?php echo htmlspecialchars($layanan['satuan']); ?>">
                        <p class="font-semibold"><?php echo htmlspecialchars($layanan['nama_layanan']); ?></p>
                        <p class="text-sm text-gray-600">Rp <?php echo number_format($layanan['harga'],0,',','.'); ?> / <?php echo htmlspecialchars($layanan['satuan']); ?></p>
                    </button>
                    <?php endforeach; ?>
                </div>

                <div>
                    <div class="border-t pt-4"></div>
                    <label for="catatan_template" class="text-sm font-medium text-gray-600">Catatan (Opsional)</label>
                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none">
                            <svg data-feather="clipboard" class="h-5 w-5 text-gray-400"></svg>
                        </span>
                        <textarea id="catatan_template" name="catatan" rows="2"
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Misal: Alergi parfum, minta lipat rapi..."></textarea>
                    </div>
                </div>
            </section>

            <section>
                <h3 class="text-lg font-semibold text-gray-800 mb-3 pb-2 border-b">4. Rincian Pembayaran</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">Total Akhir</span>
                            <span class="text-2xl font-bold text-blue-600" id="template-total-akhir">Rp 0</span>
                        </div>
                        <input type="hidden" name="total_harga" id="template-total-value" required>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="metode_bayar_template" class="text-sm font-medium text-gray-600">
                                Metode Bayar <span class="text-red-500">*</span>
                            </label>
                            <select id="metode_bayar_template" name="metode_bayar" required
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- Pilih Metode --</option>
                                <option value="Tunai">Tunai</option>
                                <option value="QRIS">QRIS</option>
                                <option value="Transfer">Transfer</option>
                            </select>
                        </div>
                        <div>
                            <label for="dibayar_template" class="text-sm font-medium text-gray-600">
                                Jumlah Dibayar <span class="text-red-500">*</span>
                            </label>
                            <input type="number" id="dibayar_template" name="dibayar" required min="0" step="0.01"
                                class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg"
                                placeholder="Jumlah yang dibayar">
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>

    <script>
    // --- TEMPLATE FORM LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        // Pelanggan dropdown for template
        const selectTemplate = document.getElementById('cari_pelanggan_template');
        const namaTemplate = document.getElementById('nama_pelanggan_template');
        const nohpTemplate = document.getElementById('no_handphone_template');
        
        const pelangganData = {};
        <?php foreach ($daftar_pelanggan as $plg): ?>
        pelangganData['<?php echo $plg['pelanggan_id']; ?>'] = {
            nama: '<?php echo addslashes($plg['nama']); ?>',
            no_telepon: '<?php echo addslashes($plg['no_telepon']); ?>'
        };
        <?php endforeach; ?>

        if (selectTemplate && namaTemplate && nohpTemplate) {
            selectTemplate.addEventListener('change', function() {
                const selectedId = this.value;
                if (selectedId && pelangganData[selectedId]) {
                    namaTemplate.value = pelangganData[selectedId].nama;
                    namaTemplate.readOnly = true;
                    nohpTemplate.value = pelangganData[selectedId].no_telepon;
                    nohpTemplate.readOnly = true;
                } else {
                    namaTemplate.value = '';
                    namaTemplate.readOnly = false;
                    nohpTemplate.value = '';
                    nohpTemplate.readOnly = false;
                }
            });
        }

        const templateForm = document.getElementById('form-buat-template');
        const keranjangLayanan = document.getElementById('keranjang-layanan');
        const totalDisplay = document.getElementById('template-total-akhir');
        const totalValue = document.getElementById('template-total-value');
        const serviceButtons = document.querySelectorAll('.service-btn');
        
        let selectedServices = [];

        function formatRupiah(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        function updateTotal() {
            const total = selectedServices.reduce((sum, service) => {
                return sum + (service.harga * service.quantity);
            }, 0);
            
            totalDisplay.textContent = formatRupiah(total);
            totalValue.value = total;
        }

        function renderKeranjang() {
            if (selectedServices.length === 0) {
                keranjangLayanan.innerHTML = '<p class="text-gray-500 text-sm">Belum ada layanan dipilih</p>';
            } else {
                keranjangLayanan.innerHTML = selectedServices.map((service, index) => `
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-semibold">${service.nama}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <input type="number" min="0.1" step="0.1" value="${service.quantity}" 
                                           class="w-20 px-2 py-1 border rounded text-sm quantity-template-input" 
                                           data-index="${index}">
                                    <span class="text-sm">${service.unit} × ${formatRupiah(service.harga)}</span>
                                </div>
                                <p class="text-sm text-blue-600 font-semibold">Total: ${formatRupiah(service.harga * service.quantity)}</p>
                                <input type="hidden" name="layanan_id[]" value="${service.id}">
                                <input type="hidden" name="quantity[]" value="${service.quantity}">
                            </div>
                            <button type="button" class="text-red-500 hover:text-red-700 remove-service-btn" data-index="${index}">
                                <svg data-feather="x" class="w-4 h-4"></svg>
                            </button>
                        </div>
                    </div>
                `).join('');
                
                if (typeof feather !== 'undefined') {
                    feather.replace();
                }
                
                document.querySelectorAll('.quantity-template-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const index = this.dataset.index;
                        const newQuantity = parseFloat(this.value) || 0;
                        selectedServices[index].quantity = newQuantity;
                        
                        const hiddenInput = this.parentElement.parentElement.querySelector('input[name="quantity[]"]');
                        hiddenInput.value = newQuantity;
                        
                        renderKeranjang();
                        updateTotal();
                    });
                });
                
                document.querySelectorAll('.remove-service-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const index = parseInt(this.dataset.index);
                        selectedServices.splice(index, 1);
                        renderKeranjang();
                        updateTotal();
                    });
                });
            }
        }

        serviceButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nama = this.dataset.nama;
                const harga = parseInt(this.dataset.harga);
                const unit = this.dataset.unit;
                
                const existingIndex = selectedServices.findIndex(s => s.id === id);
                if (existingIndex !== -1) {
                    selectedServices[existingIndex].quantity += 1;
                } else {
                    selectedServices.push({
                        id: id,
                        nama: nama,
                        harga: harga,
                        unit: unit,
                        quantity: 1
                    });
                }
                renderKeranjang();
                updateTotal();
            });
        });

        templateForm.addEventListener('submit', function(e) {
            if (selectedServices.length === 0) {
                e.preventDefault();
                alert('Minimal harus memilih satu layanan!');
                return;
            }
            
            const totalHarga = parseFloat(totalValue.value) || 0;
            const dibayar = parseFloat(document.getElementById('dibayar_template').value) || 0;
            
            if (dibayar > totalHarga) {
                const confirm = window.confirm(`Jumlah bayar (${formatRupiah(dibayar)}) lebih besar dari total (${formatRupiah(totalHarga)}). Lanjutkan?`);
                if (!confirm) {
                    e.preventDefault();
                    return;
                }
            }
        });

        const now = new Date();
        now.setDate(now.getDate() + 3);
        const defaultDateTime = now.toISOString().slice(0, 16);
        document.getElementById('tgl_selesai_template').value = defaultDateTime;

        renderKeranjang();
        updateTotal();
    });
    </script>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-buat-template"
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Buat Transaksi
        </button>
    </div>
</div>