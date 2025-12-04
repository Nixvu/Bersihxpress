<div id="modal-container" class="hidden z-30">

        <div id="modal-backdrop" class="modal-backdrop fixed inset-0 bg-black/50 z-40 opacity-0"></div>
        <div id="modal-rincian"
            class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[95vh]">

            <div class="flex-shrink-0 bg-white rounded-t-[24px]">
                <div class="w-full py-3">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
                </div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Rincian Transaksi</h2>
                    <button class="btn-close-rincian p-1 text-gray-500 hover:text-gray-800">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>

                <div class="px-6 pb-4 border-b border-gray-200">
                    <p id="modal-detail-nama" class="text-lg font-bold text-gray-900">-</p>
                    <p id="modal-detail-nota" class="text-sm text-gray-500 -mt-1">ID Nota -</p>

                    <div class="grid grid-cols-3 gap-3 mt-4 text-center">
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Total Tagihan</span>
                            <p id="modal-detail-total" class="text-xl font-bold text-blue-600">Rp 0</p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Status Bayar</span>
                            <p id="modal-detail-status-bayar" class="text-xl font-bold text-gray-900">-</p>
                        </div>
                        <div class="bg-gray-100 p-3 rounded-lg">
                            <span class="text-xs text-gray-500">Status Pesanan</span>
                            <p id="modal-detail-status" class="text-xl font-bold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex-grow overflow-y-auto divide-y divide-gray-100 no-scrollbar">

                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Rincian Layanan</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium text-gray-800">Kiloan Reguler</p>
                                <p class="text-gray-500">5 Kg x Rp 7.000</p>
                            </div>
                            <span class="font-medium text-gray-800">Rp 35.000</span>
                        </div>
                        <div class="flex justify-between">
                            <div>
                                <p class="font-medium text-gray-800">Cuci Karpet</p>
                                <p class="text-gray-500">1 Pcs x Rp 72.000</p>
                            </div>
                            <span class="font-medium text-gray-800">Rp 72.000</span>
                        </div>
                    </div>
                </section>

                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Rincian Pembayaran</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Subtotal Layanan</span><span
                                class="font-medium text-gray-800">Rp 107.000</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Diskon</span><span
                                class="font-medium text-gray-800">- Rp 0</span></div>
                        <div class="flex justify-between border-t border-dashed mt-2 pt-2">
                            <span class="text-base font-bold text-gray-900">Total Akhir</span>
                            <span class="text-base font-bold text-blue-600">Rp 107.000</span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Metode Bayar</span><span
                                class="font-medium text-gray-800">Tunai</span></div>
                    </div>
                </section>

                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Rincian Waktu</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-gray-500">Tanggal Masuk</span><span
                                class="font-medium text-gray-800">22 Okt 2025, 19:00</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">Tanggal Selesai</span><span
                                class="font-medium text-gray-800">23 Okt 2025, 19:00</span></div>
                    </div>
                </section>

                <section class="py-5 px-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-3">Aksi Lainnya</h3>
                    <div class="space-y-3">
                        <button
                            class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="printer"
                                        class="w-5 h-5 text-gray-700"></svg></div>
                                <p class="font-semibold text-gray-800">Cetak Nota</p>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        <button
                            class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg mr-3"><svg data-feather="message-circle"
                                        class="w-5 h-5 text-green-700"></svg></div>
                                <p class="font-semibold text-gray-800">Kirim Pesan (WhatsApp)</p>
                            </div>
                            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
                        </button>
                        <button id="btn-buka-opsi"
                            class="w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
                            <div class="flex items-center">
                                <div class="p-2 bg-gray-100 rounded-lg mr-3"><svg data-feather="settings"
                                        class="w-5 h-5 text-gray-700"></svg></div>
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
                <button id="btn-aksi-utama"
                    class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 shadow-sm">
                    Tandai Selesai
                </button>
            </div>
        </div>
    </div>

    <div id="modal-opsi-lanjutan"
        class="modal-popup-nested hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col">
        <div class="flex-shrink-0">
            <div class="w-full py-3">
                <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
            </div>
            <div class="flex justify-between items-center px-6 pb-4">
                <h2 class="text-xl font-bold text-gray-900">Opsi Lanjutan</h2>
                <button class="btn-close-nested p-1 text-gray-500 hover:text-gray-800"
                    data-modal-id="modal-opsi-lanjutan">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
        <div class="px-6 space-y-4 overflow-y-auto no-scrollbar flex-grow">
            <form id="form-opsi-lanjutan" class="space-y-4">
                <div>
                    <label for="opsi_pembayaran" class="text-sm font-medium text-gray-600">Ubah Status
                        Pembayaran</label>
                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg></span>
                        <select id="opsi_pembayaran"
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option>Belum Lunas</option>
                            <option>Lunas Tunai</option>
                            <option>Lunas QRIS</option>
                            <option>Lunas Transfer</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="opsi_status" class="text-sm font-medium text-gray-600">Ubah Status Pesanan</label>
                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg
                                data-feather="refresh-cw" class="h-5 w-5 text-gray-400"></svg></span>
                        <select id="opsi_status"
                            class="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                            <option>Pending</option>
                            <option>Proses</option>
                            <option>Selesai</option>
                            
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200 flex space-x-3">
            <button
                class="btn-close-nested w-1/2 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50"
                data-modal-id="modal-opsi-lanjutan">
                Batal
            </button>
            <button type="submit" form="form-opsi-lanjutan"
                class="btn-simpan-nested w-1/2 bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700"
                data-modal-id="modal-opsi-lanjutan">
                Simpan Perubahan
            </button>
        </div>
    </div>