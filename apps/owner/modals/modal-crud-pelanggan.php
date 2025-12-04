        <!-- (Komentar) MODAL 1: Opsi Pelanggan (Slide-up, z-50) -->
        <div id="modal-opsi-pelanggan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
            <!-- Header -->
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Opsi Pelanggan</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-opsi-pelanggan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
                <div class="px-6 pb-4">
                    <p class="text-xs uppercase tracking-wide text-gray-400">Pelanggan terpilih</p>
                    <p id="opsi-pelanggan-nama" class="mt-1 text-base font-semibold text-gray-900">-</p>
                </div>
            </div>
            <!-- (Komentar) Opsi (Desain Kartu Terpisah) -->
            <div class="px-6 space-y-3">
                <button id="btn-detail-pelanggan"
                    class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="eye"
                                class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Lihat Detail</p>
                            <p class="text-sm text-gray-500">Melihat informasi pelanggan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <button id="btn-edit-pelanggan"
                    class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="edit"
                                class="w-5 h-5 text-gray-700"></svg></div>
                        <div>
                            <p class="font-semibold text-gray-800">Edit Pelanggan</p>
                            <p class="text-sm text-gray-500">Memperbaharui data pelanggan</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
                <button id="btn-hapus-pelanggan"
                    class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                    <div class="flex items-center">
                        <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="trash-2"
                                class="w-5 h-5 text-red-600"></svg></div>
                        <div>
                            <p class="font-semibold text-red-600">Hapus Pelanggan</p>
                            <p class="text-sm text-gray-500">Menghapus data pelanggan ini</p>
                        </div>
                    </div>
                    <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                </button>
            </div>
            <!-- Tombol Batal -->
            <div class="flex-shrink-0 p-6 bg-white">
                <button
                    class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50"
                    data-modal-id="modal-opsi-pelanggan">
                    Batal
                </button>
            </div>
        </div>

        <!-- (Komentar) MODAL 2: Detail Pelanggan (Slide-up, z-50) -->
        <div id="modal-detail-pelanggan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[80vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Detail Pelanggan</h2>
                    <button type="button" class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-detail-pelanggan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto px-6 pb-6 no-scrollbar">
                <div class="bg-gray-50 rounded-xl p-4 mb-4">
                    <p class="text-xs uppercase tracking-wide text-gray-500 mb-2">Nama Pelanggan</p>
                    <p id="detail-nama" class="text-lg font-semibold text-gray-900">-</p>
                </div>
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Nomor Telepon</dt>
                        <dd id="detail-telepon" class="text-sm text-gray-900 mt-1">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Email</dt>
                        <dd id="detail-email" class="text-sm text-gray-900 mt-1">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Alamat</dt>
                        <dd id="detail-alamat" class="text-sm text-gray-900 mt-1">-</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-gray-500">Catatan</dt>
                        <dd id="detail-catatan" class="text-sm text-gray-900 mt-1">-</dd>
                    </div>
                </dl>
                <div class="mt-6 border-t border-gray-200 pt-4 grid grid-cols-2 gap-4">
                    <div class="bg-blue-50 rounded-lg p-3">
                        <p class="text-xs uppercase tracking-wide text-blue-600">Total Transaksi</p>
                        <p id="detail-total-transaksi" class="text-lg font-semibold text-blue-700 mt-1">0</p>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-3">
                        <p class="text-xs uppercase tracking-wide text-emerald-600">Total Nilai</p>
                        <p id="detail-total-nilai" class="text-lg font-semibold text-emerald-700 mt-1">Rp 0</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3 col-span-2">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Bergabung Sejak</p>
                        <p id="detail-created" class="text-sm font-medium text-gray-800 mt-1">-</p>
                    </div>
                </div>
            </div>
        </div>

        <div id="modal-edit-pelanggan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Edit Pelanggan</h2>
                    <button type="button" class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-edit-pelanggan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-pelanggan" action="api/query-pelanggan.php" class="space-y-4" method="post">
                    <input type="hidden" name="action" value="update_customer">
                    <input type="hidden" name="pelanggan_id" id="edit_pelanggan_id">

                    <div>
                        <label for="edit_nama" class="text-sm font-medium text-gray-600">Nama <span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="edit_nama" name="nama"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Nama pelanggan"
                                required>
                        </div>
                    </div>

                    <div>
                        <label for="edit_telepon" class="text-sm font-medium text-gray-600">Nomor Telepon <span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="edit_telepon" name="no_telepon" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="08xxxx" pattern="[0-9+\s-]{6,20}">
                        </div>
                    </div>

                    <div>
                        <label for="edit_email" class="text-sm font-medium text-gray-600">Email<span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="email" id="edit_email" name="email" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="nama@email.com">
                        </div>
                    </div>

                    <div>
                        <label for="edit_alamat" class="text-sm font-medium text-gray-600">Alamat</label>
                        <textarea id="edit_alamat" name="alamat" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                            placeholder="Detail alamat pelanggan"></textarea>
                    </div>

                    <div>
                        <label for="edit_catatan" class="text-sm font-medium text-gray-600">Catatan</label>
                        <textarea id="edit_catatan" name="catatan" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                            placeholder="Catatan khusus (opsional)"></textarea>
                    </div>
                </form>
            </div>
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-edit-pelanggan"
                    class="btn-simpan w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                    data-modal-id="modal-edit-pelanggan">
                    Simpan Perubahan
                </button>
            </div>
        </div>

        <div id="modal-tambah-pelanggan"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Tambah Pelanggan</h2>
                    <button type="button" class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                        data-modal-id="modal-tambah-pelanggan">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-tambah-pelanggan" class="space-y-4" method="POST" action="api/query-pelanggan.php">
                    <input type="hidden" name="action" value="create_customer">

                    <div>
                        <label for="tambah_nama" class="text-sm font-medium text-gray-600">Nama <span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="text" id="tambah_nama" name="nama"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="Nama pelanggan" required>
                        </div>
                    </div>

                    <div>
                        <label for="tambah_telepon" class="text-sm font-medium text-gray-600">Nomor Telepon <span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="tel" id="tambah_telepon" name="no_telepon"
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="08xxxx" pattern="[0-9+\s-]{6,20}" required>
                        </div>
                    </div>

                    <div>
                        <label for="tambah_email" class="text-sm font-medium text-gray-600">Email <span class="text-red-500">*</span></label>
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg>
                            </span>
                            <input type="email" id="tambah_email" name="email" required
                                class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                                placeholder="nama@email.com">
                        </div>
                    </div>

                    <div>
                        <label for="tambah_alamat" class="text-sm font-medium text-gray-600">Alamat</label>
                        <textarea id="tambah_alamat" name="alamat" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                            placeholder="Detail alamat pelanggan"></textarea>
                    </div>

                    <div>
                        <label for="tambah_catatan" class="text-sm font-medium text-gray-600">Catatan</label>
                        <textarea id="tambah_catatan" name="catatan" rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-3 mt-1"
                            placeholder="Catatan khusus (opsional)"></textarea>
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

        <!-- (Komentar) MODAL 4: Konfirmasi Hapus (Centered, z-60) -->
        <div id="modal-hapus-pelanggan"
            class="modal-centered hidden fixed inset-0 z-50 flex items-center justify-center p-6">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Hapus pelanggan</h2>
                <p class="text-sm text-gray-600 mb-2">Anda yakin ingin menghapus pelanggan berikut?</p>
                <p id="hapus-pelanggan-nama" class="text-sm font-semibold text-gray-900 mb-6">-</p>
                <form id="form-hapus-pelanggan" method="POST" class="grid grid-cols-2 gap-3" action="api/query-pelanggan.php">
                    <input type="hidden" name="action" value="delete_customer">
                    <input type="hidden" name="pelanggan_id" id="hapus_pelanggan_id">
                    <button type="submit"
                        class="btn-hapus-confirm w-full bg-red-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-red-700">
                        Hapus
                    </button>
                    <button type="button"
                        class="btn-close-centered w-full bg-white border border-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg hover:bg-gray-50"
                        data-modal-id="modal-hapus-layanan">
                        Batal
                    </button>
                </form>
            </div>
        </div>