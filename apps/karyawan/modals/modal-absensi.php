<!-- Modal Absensi Karyawan -->
<div id="modal-absensi"
    class="modal-popup hidden fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Absensi Karyawan</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-absensi">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>

    <div class="p-6">
        <!-- Status absensi hari ini -->
        <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <div class="text-center">
                <p class="text-gray-600 mb-2">Waktu saat ini:</p>
                <p id="live-clock" class="text-3xl font-bold text-gray-900">00:00:00</p>
                <p id="live-date" class="text-sm text-gray-500">Loading...</p>
            </div>
        </div>

        <!-- Status absensi hari ini -->
        <div id="absensi-status" class="mb-6">
            <div class="bg-white border rounded-lg p-4">
                <h3 class="font-semibold text-gray-800 mb-2">Status Absensi Hari Ini</h3>
                <div id="status-masuk" class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-600">Jam Masuk:</span>
                    <span id="jam-masuk-display" class="text-sm font-medium text-gray-900">Belum absen</span>
                </div>
                <div id="status-pulang" class="flex justify-between items-center py-2 border-t">
                    <span class="text-sm text-gray-600">Jam Pulang:</span>
                    <span id="jam-pulang-display" class="text-sm font-medium text-gray-900">Belum absen</span>
                </div>
            </div>
        </div>

        <!-- Tombol absensi -->
        <div class="space-y-3">
            <button id="btn-absen-masuk" 
                class="w-full bg-green-600 text-white font-bold py-4 px-4 rounded-lg hover:bg-green-700 text-lg flex items-center justify-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                <svg data-feather="log-in" class="w-5 h-5 mr-2"></svg>
                <span>Absen Masuk</span>
            </button>
            
            <button id="btn-absen-pulang" 
                class="w-full bg-red-600 text-white font-bold py-4 px-4 rounded-lg hover:bg-red-700 text-lg flex items-center justify-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                <svg data-feather="log-out" class="w-5 h-5 mr-2"></svg>
                <span>Absen Pulang</span>
            </button>
        </div>

        <!-- Riwayat absensi minggu ini -->
        <div class="mt-6 border-t pt-6">
            <h3 class="font-semibold text-gray-800 mb-3">Riwayat Minggu Ini</h3>
            <div id="riwayat-absensi" class="space-y-2">
                <!-- Will be populated by JavaScript -->
                <div class="text-sm text-gray-500 text-center py-4">Memuat riwayat...</div>
            </div>
        </div>
    </div>
</div>