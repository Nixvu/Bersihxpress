<!-- Modal Catat Pengeluaran -->
<div id="modal-pengeluaran"
    class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Catat Pengeluaran</h2><button
                        class="btn-close-global p-1 text-gray-500 hover:text-gray-800"><svg data-feather="x"
                            class="w-6 h-6"></svg></button>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-pengeluaran" action="api/query-pengeluaran.php" method="POST" class="space-y-4">
                    <input type="hidden" name="bisnis_id" value="<?php echo htmlspecialchars($ownerData['bisnis_id'] ?? ($_SESSION['owner_data']['bisnis_id'] ?? '')); ?>">
                    <input type="hidden" name="karyawan_id" value="<?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?>">

                    <div><label for="nama_pengeluaran" class="text-sm font-medium text-gray-600">Nama
                            Pengeluaran <span class="text-red-500">*</span></label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    data-feather="edit" class="h-5 w-5 text-gray-400"></svg></span><input type="text"
                                id="nama_pengeluaran" name="nama_pengeluaran" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Contoh : Beli Deterjen"></div>
                    </div>

                    <div><label for="nominal_pengeluaran" class="text-sm font-medium text-gray-600">Nominal
                            Pengeluaran <span class="text-red-500">*</span></label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg></span><input
                                type="number" id="nominal_pengeluaran" name="nominal" min="0" step="0.01" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Contoh : 50000"></div>
                    </div>

                    <div>
                        <label for="kategori_pengeluaran" class="text-sm font-medium text-gray-600">Kategori <span class="text-red-500">*</span></label>
                        <select id="kategori_pengeluaran" name="kategori" required
                            class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="perlengkapan">Perlengkapan (Deterjen, Parfum)</option>
                            <option value="operasional">Operasional (Listrik, Air)</option>
                            <option value="gaji">Gaji Karyawan</option>
                            <option value="lainnya">Lain-Lain</option>
                        </select>
                    </div>

                    <div><label for="tanggal_pengeluaran" class="text-sm font-medium text-gray-600">Tanggal
                            Pengeluaran <span class="text-red-500">*</span></label>
                        <input type="date" id="tanggal_pengeluaran" name="tanggal" required
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg" value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div>
                        <label for="metode_bayar_pengeluaran" class="text-sm font-medium text-gray-600">Metode
                            Pembayaran <span class="text-red-500">*</span></label>
                        <select id="metode_bayar_pengeluaran" name="metode_pembayaran"
                            class="w-full mt-1 py-3 px-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="tunai">Tunai (Kas)</option>
                            <option value="transfer">Transfer Bank</option>
                        </select>
                    </div>

                    <div class="border-b pb-3 mb-3"><label for="keterangan_pengeluaran"
                            class="text-sm font-medium text-gray-600">Keterangan
                            (Opsional)</label>
                        <textarea id="keterangan_pengeluaran" name="keterangan" rows="3"
                            class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Contoh: Deterjen bubuk 10kg"></textarea>
                    </div>

                    <div><label for="dibuat_oleh" class="text-sm font-medium text-gray-600">Dibuat Oleh</label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    data-feather="user" class="h-5 w-5 text-gray-400"></svg></span><input type="text"
                                id="dibuat_oleh"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-100"
                                value="<?php echo htmlspecialchars($ownerData['nama_lengkap'] ?? $_SESSION['user_name'] ?? ''); ?>" readonly></div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-pengeluaran');
            form.addEventListener('submit', function(e) {
            const nama = document.getElementById('nama_pengeluaran').value.trim();
            const nominal = parseFloat(document.getElementById('nominal_pengeluaran').value) || 0;
            const tanggal = document.getElementById('tanggal_pengeluaran').value;
            const kategori = document.getElementById('kategori_pengeluaran').value;
            if (!nama || nominal <= 0 || !tanggal || !kategori) {
                e.preventDefault();
                alert('Nama, nominal (>0), kategori, dan tanggal pengeluaran wajib diisi.');
                return false;
            }
            // allow submit
        });
    });
    </script>
