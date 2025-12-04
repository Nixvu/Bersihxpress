<div id="modal-tambah-pelanggan"
            class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Tambah Pelanggan Baru</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-tambah-pelanggan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-tambah-pelanggan" action="api/query-tambah-pelanggan.php" method="POST" class="space-y-4">
                    <input type="hidden" name="bisnis_id" value="<?php echo htmlspecialchars($ownerData['bisnis_id'] ?? ($_SESSION['owner_data']['bisnis_id'] ?? '')); ?>">
                    <div><label for="tambah_nama" class="text-sm font-medium text-gray-600">Nama Pelanggan <span class="text-red-500">*</span></label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    data-feather="user" class="h-5 w-5 text-gray-400"></svg></span><input type="text"
                                id="tambah_nama" name="nama" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Nama Pelanggan"></div>
                    </div>
                    <div><label for="tambah_no_hp" class="text-sm font-medium text-gray-600">No Handphone <span class="text-red-500">*</span></label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                    data-feather="phone" class="h-5 w-5 text-gray-400"></svg></span><input type="tel"
                                id="tambah_no_hp" name="no_telepon" required class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="No Handphone"></div>
                    </div>
                    <div><label for="tambah_alamat" class="text-sm font-medium text-gray-600">Alamat Pelanggan</label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none"><svg
                                    data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg></span><textarea
                                id="tambah_alamat" name="alamat" rows="3"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Contoh : Jln Re Martadinata"></textarea></div>
                    </div>
                    <div><label for="tambah_catatan" class="text-sm font-medium text-gray-600">Catatan
                            (Opsional)</label>
                        <div class="relative mt-1"><span
                                class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none"><svg
                                    data-feather="clipboard" class="h-5 w-5 text-gray-400"></svg></span><textarea
                                id="tambah_catatan" name="catatan" rows="2"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Misal: Alergi parfum, minta lipat rapi..."></textarea></div>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-tambah-pelanggan"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-tambah-pelanggan">
                    Simpan Pelanggan
                </button>
            </div>
        </div>