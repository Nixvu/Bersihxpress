<!-- MODAL TRANSAKSI MANUAL dengan Array Layanan -->
<div id="modal-rincian-transaksi"
    class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Buat Transaksi Manual</h2>
            <button class="btn-close-global p-1 text-gray-500 hover:text-gray-800">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>
    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-buat-transaksi" action="form-buat-transaksi.php" method="POST" class="space-y-6">
            <!-- Hidden fields for database -->
            <input type="hidden" name="bisnis_id" id="bisnis_id" value="<?php echo $user_bisnis_id; ?>" required>
            <input type="hidden" name="karyawan_id" id="karyawan_id" value="<?php echo $user_id; ?>">
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
                            <input type="text" id="cari_pelanggan_manual"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Cari pelanggan yang sudah ada...">
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
                <div id="layanan-container">
                    <!-- Layanan items will be added here dynamically -->
                </div>
                <button type="button" id="btn-tambah-layanan"
                    class="w-full border-2 border-dashed border-blue-500 text-blue-500 font-semibold py-3 px-4 rounded-lg hover:bg-blue-50">
                    + Tambah Layanan
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
    // --- ENHANCED ARRAY-BASED LAYANAN FORM LOGIC ---
    document.addEventListener('DOMContentLoaded', function() {
        let layananIndex = 0;
        const maxLayanan = 10; // Batas maksimal layanan untuk keamanan
        
        // Helper: Format number to Rupiah
        function formatRupiah(num) {
            return 'Rp ' + num.toLocaleString('id-ID');
        }

        // Form elements
        const form = document.getElementById('form-buat-transaksi');
        const layananContainer = document.getElementById('layanan-container');
        const btnTambahLayanan = document.getElementById('btn-tambah-layanan');
        const subtotalDisplay = document.getElementById('subtotal-display');
        const subtotalValue = document.getElementById('subtotal-value');
        const diskonInput = document.getElementById('diskon');
        const biayaAntarInput = document.getElementById('biaya_antar');
        const totalAkhirDisplay = document.getElementById('total-akhir-display');
        const totalHargaValue = document.getElementById('total-harga-value');

        // Template for layanan item
        function createLayananItem(index) {
            return `
                <div class="layanan-item border-b pb-3 mb-3" data-index="${index}">
                    <div class="flex justify-between items-center mb-2">
                        <label class="text-sm font-medium text-gray-600">
                            Layanan ${index + 1} <span class="text-red-500">*</span>
                        </label>
                        <button type="button" class="btn-remove-layanan text-red-500 hover:text-red-700" ${index === 0 ? 'style="display:none"' : ''}>
                            <svg data-feather="trash-2" class="w-4 h-4"></svg>
                        </button>
                    </div>
                    
                    <!-- Service Selection -->
                    <select name="layanan[${index}][service_id]" required 
                        class="layanan-select w-full py-3 px-3 border border-gray-300 rounded-lg bg-white mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Pilih Layanan --</option>
                        <option value="1" data-price="7000" data-unit="kg">Kiloan Reguler (Rp 7.000/kg)</option>
                        <option value="2" data-price="10000" data-unit="kg">Kiloan Ekspres (Rp 10.000/kg)</option>
                        <option value="3" data-price="15000" data-unit="pcs">Satuan - Jas (Rp 15.000/pcs)</option>
                        <option value="4" data-price="25000" data-unit="pcs">Satuan - Bed Cover (Rp 25.000/pcs)</option>
                    </select>
                    
                    <!-- Hidden fields for additional data -->
                    <input type="hidden" name="layanan[${index}][price]" class="layanan-price">
                    <input type="hidden" name="layanan[${index}][unit]" class="layanan-unit">
                    <input type="hidden" name="layanan[${index}][service_name]" class="layanan-name">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="number" name="layanan[${index}][quantity]" placeholder="Qty" required 
                                min="0.1" step="0.1" 
                                class="quantity-input w-full px-3 py-3 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Unit: <span class="unit-display">-</span></p>
                        </div>
                        <div>
                            <input type="text" placeholder="Harga Total"
                                class="harga-total w-full px-3 py-3 border border-gray-300 rounded-lg bg-gray-100"
                                readonly>
                            <input type="hidden" name="layanan[${index}][total_price]" class="total-price-value">
                        </div>
                    </div>
                </div>
            `;
        }

        // Add new layanan item
        function addLayananItem() {
            if (layananIndex >= maxLayanan) {
                alert(`Maksimal ${maxLayanan} layanan yang dapat ditambahkan untuk keamanan sistem.`);
                return;
            }
            
            const itemHtml = createLayananItem(layananIndex);
            layananContainer.insertAdjacentHTML('beforeend', itemHtml);
            
            // Re-initialize feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
            
            // Attach events to new item
            const newItem = layananContainer.querySelector(`[data-index="${layananIndex}"]`);
            attachLayananEvents(newItem);
            
            layananIndex++;
            updateRemoveButtons();
        }

        // Attach events to layanan item
        function attachLayananEvents(item) {
            const index = item.dataset.index;
            const select = item.querySelector('.layanan-select');
            const qtyInput = item.querySelector('.quantity-input');
            const hargaTotalInput = item.querySelector('.harga-total');
            const totalPriceValue = item.querySelector('.total-price-value');
            const priceHidden = item.querySelector('.layanan-price');
            const unitHidden = item.querySelector('.layanan-unit');
            const nameHidden = item.querySelector('.layanan-name');
            const unitDisplay = item.querySelector('.unit-display');
            const btnRemove = item.querySelector('.btn-remove-layanan');

            // Service selection change
            select.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const price = selectedOption.dataset.price || 0;
                const unit = selectedOption.dataset.unit || '';
                const serviceName = selectedOption.textContent || '';
                
                // Update hidden fields
                priceHidden.value = price;
                unitHidden.value = unit;
                nameHidden.value = serviceName;
                unitDisplay.textContent = unit;
                
                // Reset quantity if service changed
                if (qtyInput.value) {
                    updateItemTotal();
                }
            });

            // Quantity change
            qtyInput.addEventListener('input', updateItemTotal);

            // Remove button
            btnRemove.addEventListener('click', function() {
                removeLayananItem(item);
            });

            // Update item total calculation
            function updateItemTotal() {
                const price = parseFloat(priceHidden.value) || 0;
                const qty = parseFloat(qtyInput.value) || 0;
                const total = price * qty;
                
                hargaTotalInput.value = formatRupiah(total);
                totalPriceValue.value = total;
                
                updateGrandTotal();
            }
        }

        // Remove layanan item
        function removeLayananItem(item) {
            const items = layananContainer.querySelectorAll('.layanan-item');
            if (items.length > 1) {
                item.remove();
                updateRemoveButtons();
                updateGrandTotal();
                reindexLayananItems();
            }
        }

        // Update remove button visibility
        function updateRemoveButtons() {
            const items = layananContainer.querySelectorAll('.layanan-item');
            items.forEach((item, index) => {
                const btnRemove = item.querySelector('.btn-remove-layanan');
                const label = item.querySelector('label');
                
                btnRemove.style.display = items.length > 1 ? 'block' : 'none';
                label.innerHTML = `Layanan ${index + 1} <span class="text-red-500">*</span>`;
            });
        }

        // Reindex layanan items after removal
        function reindexLayananItems() {
            const items = layananContainer.querySelectorAll('.layanan-item');
            items.forEach((item, index) => {
                item.dataset.index = index;
                
                // Update all name attributes
                const inputs = item.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.name) {
                        input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
                    }
                });
            });
            layananIndex = items.length;
        }

        // Calculate grand total
        function updateGrandTotal() {
            let subtotal = 0;
            const items = layananContainer.querySelectorAll('.layanan-item');
            
            items.forEach(item => {
                const totalValue = item.querySelector('.total-price-value');
                subtotal += parseFloat(totalValue.value) || 0;
            });
            
            subtotalDisplay.value = formatRupiah(subtotal);
            subtotalValue.value = subtotal;
            
            const diskon = parseFloat(diskonInput.value) || 0;
            const biayaAntar = parseFloat(biayaAntarInput.value) || 0;
            let total = subtotal - diskon + biayaAntar;
            if (total < 0) total = 0;
            
            totalAkhirDisplay.textContent = formatRupiah(total);
            totalHargaValue.value = total;
        }

        // Event listeners
        btnTambahLayanan.addEventListener('click', addLayananItem);
        diskonInput.addEventListener('input', updateGrandTotal);
        biayaAntarInput.addEventListener('input', updateGrandTotal);

        // Form validation
        form.addEventListener('submit', function(e) {
            const items = layananContainer.querySelectorAll('.layanan-item');
            let hasValidLayanan = false;
            
            // Validate each layanan item
            items.forEach(item => {
                const select = item.querySelector('.layanan-select');
                const qty = item.querySelector('.quantity-input');
                
                if (select.value && qty.value && parseFloat(qty.value) > 0) {
                    hasValidLayanan = true;
                }
            });
            
            if (!hasValidLayanan) {
                e.preventDefault();
                alert('Minimal harus ada satu layanan yang dipilih dengan quantity yang valid!');
                return;
            }
            
            // Validate payment
            const totalHarga = parseFloat(totalHargaValue.value) || 0;
            const dibayar = parseFloat(document.getElementById('dibayar_manual').value) || 0;
            
            if (totalHarga === 0) {
                e.preventDefault();
                alert('Total harga tidak boleh 0!');
                return;
            }
            
            if (dibayar > totalHarga) {
                const confirm = window.confirm(`Jumlah bayar (${formatRupiah(dibayar)}) lebih besar dari total (${formatRupiah(totalHarga)}). Lanjutkan?`);
                if (!confirm) {
                    e.preventDefault();
                    return;
                }
            }
        });

        // Initialize with one layanan item
        addLayananItem();

        // Set default datetime (current time + 3 days)
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