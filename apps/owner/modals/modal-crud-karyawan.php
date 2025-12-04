<!-- Modal Container -->
<!-- MODAL 1: Opsi Karyawan -->
<div id="modal-opsi-karyawan"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
    style="display: none;">
    <!-- Header -->
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Opsi Karyawan</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-opsi-karyawan">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
        <div class="px-6 pb-4">
            <?php $selectedEmployee = $_SESSION['selected_karyawan'] ?? []; ?>
            <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan terpilih</p>
            <div class="mt-1">
                <p id="opsi-karyawan-nama" class="text-base font-semibold text-gray-900 employee-name">
                    <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                    ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p>
            </div>
        </div>
    </div>

    <!-- Options -->
    <div class="px-6 space-y-3">
        <button id="btn-detail-karyawan" data-target-modal="modal-info-kinerja"
            class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="bar-chart-2" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Informasi Kinerja</p>
                    <p class="text-sm text-gray-500">Lihat kehadiran & transaksi selesai</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>

        <button id="btn-edit-karyawan" data-target-modal="modal-edit-karyawan"
            class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="edit" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Edit Data Karyawan</p>
                    <p class="text-sm text-gray-500">Ubah nama, telepon, atau gaji</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>

        <button data-target-modal="modal-reset-password"
            class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="key" class="w-5 h-5 text-gray-700"></svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Reset Password</p>
                    <p class="text-sm text-gray-500">Buat password baru untuk karyawan</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>

        <button id="btn-hapus-karyawan" data-target-modal="modal-hapus-karyawan"
            class="modal-navigate w-full flex items-center justify-between bg-white border border-gray-200 rounded-lg shadow-sm p-4 text-left hover:bg-gray-50">
            <div class="flex items-center">
                <div class="p-2 bg-gray-100 rounded-lg mr-3">
                    <svg data-feather="trash-2" class="w-5 h-5 text-red-600"></svg>
                </div>
                <div>
                    <p class="font-semibold text-red-600">Hapus Karyawan</p>
                    <p class="text-sm text-gray-500">Hapus akun dan data karyawan</p>
                </div>
            </div>
            <svg data-feather="chevron-right" class="w-5 h-5 text-gray-400"></svg>
        </button>
    </div>

    <!-- Tombol Batal -->
    <div class="flex-shrink-0 p-6 bg-white">
        <button
            class="btn-close-modal w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50"
            data-modal-id="modal-opsi-karyawan">
            Batal
        </button>
    </div>
</div>

<!-- MODAL 2: Form Edit Karyawan -->
<div id="modal-edit-karyawan"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Edit Karyawan</h2>
            <div class="flex items-center space-x-2">
                <button class="modal-back-btn p-1 text-gray-500 hover:text-gray-800">
                    <svg data-feather="arrow-left" class="w-6 h-6"></svg>
                </button>
                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                    data-modal-id="modal-edit-karyawan">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-edit-karyawan" class="space-y-4" method="POST">
            <input type="hidden" name="action" value="update_employee">
            <input type="hidden" id="edit_karyawan_id" name="karyawan_id" class="employee-id-input"
                value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">

            <!-- ID Karyawan (Readonly) -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">ID Karyawan</label>
                <input type="text"
                    class="w-full mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono employee-id-display"
                    value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>" readonly>
            </div>

            <div>
                <label for="edit_nama_lengkap" class="text-sm font-medium text-gray-600">Nama Lengkap <span
                        class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text" id="edit_nama_lengkap" name="nama_lengkap"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-name-input"
                        value="<?php echo htmlspecialchars($selectedEmployee['nama'] ?? ''); ?>" required>
                </div>
            </div>

            <div>
                <label for="edit_no_telepon" class="text-sm font-medium text-gray-600">No Telepon</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="tel" id="edit_no_telepon" name="no_telepon"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-phone-input"
                        value="<?php echo htmlspecialchars($selectedEmployee['telepon'] ?? ''); ?>"
                        placeholder="08xxxx">
                </div>
            </div>

            <div>
                <label for="edit_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="number" step="1000" min="0" id="edit_gaji_pokok" name="gaji_pokok"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg employee-salary-input"
                        value="<?php echo htmlspecialchars($selectedEmployee['gaji'] ?? '0'); ?>" placeholder="0">
                </div>
            </div>

            <div>
                <label for="edit_status" class="text-sm font-medium text-gray-600">Status</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="toggle-left" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <select id="edit_status" name="status"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg">
                        <option value="aktif"
                            <?php echo ($selectedEmployee['status'] ?? 'aktif') === 'aktif' ? 'selected' : ''; ?>>Aktif
                        </option>
                        <option value="tidak_aktif"
                            <?php echo ($selectedEmployee['status'] ?? 'aktif') === 'tidak_aktif' ? 'selected' : ''; ?>>
                            Tidak Aktif</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-edit-karyawan"
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Simpan Perubahan
        </button>
    </div>
</div>

<!-- MODAL 3: Tambah Karyawan -->
<div id="modal-tambah-karyawan"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Tambah Karyawan Baru</h2>
            <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-tambah-karyawan">
                <svg data-feather="x" class="w-6 h-6"></svg>
            </button>
        </div>
    </div>

    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-tambah-karyawan" class="space-y-4" method="POST">
            <input type="hidden" name="action" value="create_employee">

            <div>
                <label for="tambah_nama_lengkap" class="text-sm font-medium text-gray-600">Nama Lengkap <span
                        class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="user" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="text" id="tambah_nama_lengkap" name="nama_lengkap"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        placeholder="Nama lengkap karyawan" required>
                </div>
            </div>

            <div>
                <label for="tambah_email" class="text-sm font-medium text-gray-600">Email <span
                        class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="mail" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="email" id="tambah_email" name="email"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="email@example.com"
                        required>
                </div>
            </div>

            <div>
                <label for="tambah_no_telepon" class="text-sm font-medium text-gray-600">No Telepon</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="tel" id="tambah_no_telepon" name="no_telepon"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="08xxxx">
                </div>
            </div>

            <div>
                <label for="tambah_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="dollar-sign" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="number" step="1000" min="0" id="tambah_gaji_pokok" name="gaji_pokok"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="0">
                </div>
            </div>

            <div>
                <label for="tambah_tanggal_bergabung" class="text-sm font-medium text-gray-600">Tanggal
                    Bergabung</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="calendar" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="date" id="tambah_tanggal_bergabung" name="tanggal_bergabung"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <div class="border-t pt-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Data Login</h3>

                <div>
                    <label for="tambah_password" class="text-sm font-medium text-gray-600">Password <span
                            class="text-red-500">*</span></label>
                    <div class="relative mt-1">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg data-feather="lock" class="h-5 w-5 text-gray-400"></svg>
                        </span>
                        <input type="password" id="tambah_password" name="password"
                            class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                            placeholder="Password minimal 6 karakter" required>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-tambah-karyawan"
            class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
            Tambah Karyawan
        </button>
    </div>
</div>

<!-- MODAL 4: Reset Password -->
<div id="modal-reset-password"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Reset Password</h2>
            <div class="flex items-center space-x-2">
                <button class="modal-back-btn p-1 text-gray-500 hover:text-gray-800">
                    <svg data-feather="arrow-left" class="w-6 h-6"></svg>
                </button>
                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                    data-modal-id="modal-reset-password">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
        <div class="px-6 pb-4">
            <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
            <div class="mt-1">
                <p class="text-base font-semibold text-gray-900 employee-name">
                    <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                    ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p>
            </div>
        </div>
    </div>

    <div class="px-6 pb-6">
        <form id="form-reset-password" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="karyawan_id" class="employee-id-input"
                value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">
            <input type="hidden" name="karyawan_id" class="employee-id-input"
                value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">ID Karyawan</label>
                <input type="text"
                    class="w-full mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono employee-id-display"
                    value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>" readonly>
            </div>

            <div>
                <label for="reset_new_password" class="text-sm font-medium text-gray-600">Password Baru <span
                        class="text-red-500">*</span></label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="lock" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="password" id="reset_new_password" name="new_password"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        placeholder="Password baru minimal 6 karakter" required>
                </div>
            </div>

            <div class="flex space-x-3 pt-4">
                <button type="button"
                    class="modal-back-btn flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 5: Konfirmasi Hapus -->
<div id="modal-hapus-karyawan"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-red-600">Hapus Karyawan</h2>
            <div class="flex items-center space-x-2">
                <button class="modal-back-btn p-1 text-gray-500 hover:text-gray-800">
                    <svg data-feather="arrow-left" class="w-6 h-6"></svg>
                </button>
                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                    data-modal-id="modal-hapus-karyawan">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="px-6 pb-6">
        <div class="flex items-center p-4 mb-4 bg-red-50 border border-red-200 rounded-lg">
            <svg data-feather="alert-triangle" class="w-6 h-6 text-red-600 mr-3"></svg>
            <div>
                <p class="text-sm font-medium text-red-800">
                    Anda akan menghapus karyawan "<span
                        class="font-bold employee-name"><?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></span>"
                </p>
                <p class="text-xs text-gray-500 font-mono bg-red-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                    ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p>
                <p class="text-sm text-red-600 mt-1">
                    Aksi ini tidak dapat dibatalkan. Semua data karyawan akan dihapus secara permanen.
                </p>
            </div>
        </div>

        <form id="form-hapus-karyawan" method="POST">
            <input type="hidden" name="action" value="delete_employee">
            <input type="hidden" name="karyawan_id" class="employee-id-input"
                value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-4">
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wide">ID Karyawan</label>
                <input type="text"
                    class="w-full mt-1 px-3 py-2 bg-gray-100 border border-gray-300 rounded text-sm font-mono employee-id-display"
                    value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>" readonly>
            </div>

            <div class="flex space-x-3">
                <button type="button"
                    class="modal-back-btn flex-1 bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit"
                    class="flex-1 bg-red-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-red-700">
                    Ya, Hapus
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 6: Informasi Kinerja -->
<div id="modal-info-kinerja"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[80vh] hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Informasi Kinerja</h2>
            <div class="flex items-center space-x-2">
                <button class="modal-back-btn p-1 text-gray-500 hover:text-gray-800">
                    <svg data-feather="arrow-left" class="w-6 h-6"></svg>
                </button>
                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800"
                    data-modal-id="modal-info-kinerja">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
        <div class="px-6 pb-4">
            <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
            <div class="mt-1">
                <p class="text-base font-semibold text-gray-900 employee-name">
                    <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                    ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p>
            </div>
        </div>
    </div>

    <div class="flex-grow overflow-y-auto p-6 no-scrollbar space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg data-feather="file-text" class="w-5 h-5 text-blue-600 mr-2"></svg>
                    <span class="text-sm font-medium text-blue-800">Total Transaksi</span>
                </div>
                <p class="text-2xl font-bold text-blue-900 mt-2 employee-total-transaksi">0</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg data-feather="calendar" class="w-5 h-5 text-green-600 mr-2"></svg>
                    <span class="text-sm font-medium text-green-800">Bulan Ini</span>
                </div>
                <p class="text-2xl font-bold text-green-900 mt-2 employee-transaksi-bulan">0</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <span class="text-sm font-medium text-gray-600">Bergabung</span>
                <span class="text-sm text-gray-900 employee-bergabung">-</span>
            </div>
            <div class="flex justify-between items-center py-3 border-b border-gray-200">
                <span class="text-sm font-medium text-gray-600">Status</span>
                <span class="text-sm text-gray-900 employee-status">Aktif</span>
            </div>
            <div class="flex justify-between items-center py-3">
                <span class="text-sm font-medium text-gray-600">Gaji Pokok</span>
                <span class="text-sm font-semibold text-gray-900 employee-gaji">Rp 0</span>
            </div>
        </div>

        <button data-target-modal="modal-proses-gaji"
            class="modal-navigate w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 flex items-center justify-center">
            <svg data-feather="credit-card" class="w-5 h-5 mr-2"></svg>
            Proses Pembayaran Gaji
        </button>
    </div>
</div>

<!-- MODAL 7: Proses Gaji -->
<div id="modal-proses-gaji"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[90vh] hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
        <div class="flex justify-between items-center px-6 pb-4">
            <h2 class="text-xl font-bold text-gray-900">Proses Gaji</h2>
            <div class="flex items-center space-x-2">
                <button class="modal-back-btn p-1 text-gray-500 hover:text-gray-800">
                    <svg data-feather="arrow-left" class="w-6 h-6"></svg>
                </button>
                <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800" data-modal-id="modal-proses-gaji">
                    <svg data-feather="x" class="w-6 h-6"></svg>
                </button>
            </div>
        </div>
        <div class="px-6 pb-4">
            <p class="text-xs uppercase tracking-wide text-gray-400">Karyawan</p>
            <div class="mt-1">
                <p class="text-base font-semibold text-gray-900 employee-name">
                    <?php echo htmlspecialchars($selectedEmployee['nama'] ?? '-'); ?></p>
                <p class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded mt-1 inline-block employee-id">
                    ID: <?php echo htmlspecialchars($selectedEmployee['id'] ?? '-'); ?></p>
            </div>
        </div>
    </div>

    <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
        <form id="form-proses-gaji" class="space-y-4" method="POST" action="api/query-crud-karyawan.php">
            <input type="hidden" name="action" value="process_salary">
            <input type="hidden" name="karyawan_id" class="employee-id-input"
                value="<?php echo htmlspecialchars($selectedEmployee['id'] ?? ''); ?>">
            <input type="hidden" name="total_gaji" id="input_total_gaji">

            <div>
                <label for="input_gaji_pokok" class="text-sm font-medium text-gray-600">Gaji Pokok</label>
                <div class="relative mt-1">
                    <span
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                    <input type="number" step="1000" min="0" id="input_gaji_pokok"
                        class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg bg-gray-100 employee-salary-input"
                        value="<?php echo htmlspecialchars($selectedEmployee['gaji'] ?? '0'); ?>" readonly>
                </div>
            </div>

            <div>
                <label for="input_bonus" class="text-sm font-medium text-gray-600">Bonus/Tunjangan</label>
                <div class="relative mt-1">
                    <span
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                    <input type="number" step="1000" min="0" id="input_bonus" name="bonus"
                        class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="0">
                </div>
            </div>

            <div>
                <label for="input_potongan" class="text-sm font-medium text-gray-600">Potongan</label>
                <div class="relative mt-1">
                    <span
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-500">Rp</span>
                    <input type="number" step="1000" min="0" id="input_potongan" name="potongan"
                        class="w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="0">
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-800">Total Gaji:</span>
                    <span id="total_gaji_display" class="text-xl font-bold text-green-600">Rp 0</span>
                </div>
            </div>

            <div>
                <label for="input_periode" class="text-sm font-medium text-gray-600">Periode</label>
                <div class="relative mt-1">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg data-feather="calendar" class="h-5 w-5 text-gray-400"></svg>
                    </span>
                    <input type="month" id="input_periode" name="periode"
                        class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg"
                        value="<?php echo date('Y-m'); ?>">
                </div>
            </div>

            <div>
                <label for="input_keterangan" class="text-sm font-medium text-gray-600">Keterangan</label>
                <textarea id="input_keterangan" name="keterangan" rows="3"
                    class="w-full mt-1 px-3 py-3 border border-gray-300 rounded-lg"
                    placeholder="Catatan tambahan (opsional)"></textarea>
            </div>
        </form>
    </div>

    <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
        <button type="submit" form="form-proses-gaji"
            class="w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700">
            Proses Pembayaran
        </button>
    </div>
</div>

<!-- MODAL 8: Success Notification -->
<div id="modal-sukses"
    class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-auto hidden"
    style="display: none;">
    <div class="flex-shrink-0">
        <div class="w-full py-3">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div>
        </div>
    </div>
    <div class="px-6 py-8 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg data-feather="check" class="w-8 h-8 text-green-600"></svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Berhasil!</h2>
        <p id="sukses-message" class="text-sm text-gray-600 mb-6">Operasi berhasil dilakukan.</p>
        <button class="btn-close-modal w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700"
            data-modal-id="modal-sukses">
            Tutup
        </button>
    </div>
</div>
<!-- JavaScript untuk Modal Management -->
<script>
// Enhanced Modal Manager with Navigation History and State Management

// ModalManager class: manages modal navigation, state, and data for karyawan modals
class ModalManager {
    // Constructor: initializes modal state and loads initial employee data
    constructor() {
        this.currentModal = null; // Currently open modal ID
        this.modalStack = []; // Stack for navigation history
        this.employeeData = {}; // Data for selected employee
        this.init();
    }

    // Initializes event listeners and loads employee data
    init() {
        this.setupEventListeners();
        this.loadInitialEmployeeData();
    }

    // Sets up click listeners for modal navigation, back, and close actions
    setupEventListeners() {
        // Modal navigation buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.modal-navigate')) {
                e.preventDefault();
                const button = e.target.closest('.modal-navigate');
                const targetModal = button.getAttribute('data-target-modal');
                if (targetModal) {
                    this.navigateToModal(targetModal); // Open target modal
                }
            }

            // Back buttons: go back to previous modal
            if (e.target.closest('.modal-back-btn')) {
                e.preventDefault();
                this.goBack();
            }

            // Close buttons: close all modals
            if (e.target.closest('.btn-close-modal')) {
                e.preventDefault();
                const button = e.target.closest('.btn-close-modal');
                const modalId = button.getAttribute('data-modal-id');
                if (modalId) {
                    this.closeAll();
                }
            }
        });

        // Setup salary calculation for gaji modal
        this.setupSalaryCalculation();

        // Setup form submissions (can be customized)
        this.setupFormSubmissions();
    }

    // Loads initial employee data from PHP session
    loadInitialEmployeeData() {
        // Load employee data from PHP session or server
        const phpEmployeeData = <?php echo json_encode($_SESSION['selected_karyawan'] ?? []); ?>;
        if (phpEmployeeData && Object.keys(phpEmployeeData).length > 0) {
            this.setEmployeeData(phpEmployeeData);
        }
    }

    // Sets employee data and updates all modal displays
    setEmployeeData(data) {
        this.employeeData = {
            id: data.karyawan_id || data.id || '',
            nama: data.nama_lengkap || data.nama || '',
            telepon: data.telepon || data.no_telepon || '',
            gaji: data.gaji_pokok || data.gaji || 0,
            email: data.email || '',
            bergabung: data.tanggal_bergabung || data.bergabung || '',
            status: data.status || 'aktif',
            total_transaksi: data.total_transaksi || 0,
            transaksi_bulan: data.transaksi_bulan || 0
        };
        this.updateAllModalDisplays();
    }

    // Updates all modal fields and displays with current employee data
    updateAllModalDisplays() {
        // Update employee name displays
        document.querySelectorAll('.employee-name').forEach(el => {
            el.textContent = this.employeeData.nama || '-';
        });
        // Update employee ID displays
        document.querySelectorAll('.employee-id').forEach(el => {
            el.textContent = `ID: ${this.employeeData.id || '-'}`;
        });
        // Update form inputs
        document.querySelectorAll('.employee-id-input').forEach(el => {
            el.value = this.employeeData.id || '';
        });
        document.querySelectorAll('.employee-id-display').forEach(el => {
            el.value = this.employeeData.id || '-';
        });
        document.querySelectorAll('.employee-name-input').forEach(el => {
            el.value = this.employeeData.nama || '';
        });
        document.querySelectorAll('.employee-phone-input').forEach(el => {
            el.value = this.employeeData.telepon || '';
        });
        document.querySelectorAll('.employee-salary-input').forEach(el => {
            el.value = this.employeeData.gaji || 0;
        });
        // Update performance info
        document.querySelectorAll('.employee-total-transaksi').forEach(el => {
            el.textContent = this.employeeData.total_transaksi || '0';
        });
        document.querySelectorAll('.employee-transaksi-bulan').forEach(el => {
            el.textContent = this.employeeData.transaksi_bulan || '0';
        });
        document.querySelectorAll('.employee-bergabung').forEach(el => {
            el.textContent = this.employeeData.bergabung || '-';
        });
        document.querySelectorAll('.employee-status').forEach(el => {
            el.textContent = this.employeeData.status ? this.employeeData.status.charAt(0).toUpperCase() +
                this.employeeData.status.slice(1) : 'Aktif';
        });
        document.querySelectorAll('.employee-gaji').forEach(el => {
            const gaji = parseInt(this.employeeData.gaji) || 0;
            el.textContent = `Rp ${gaji.toLocaleString('id-ID')}`;
        });
        // Update status select in edit form
        const statusSelect = document.getElementById('edit_status');
        if (statusSelect && this.employeeData.status) {
            statusSelect.value = this.employeeData.status;
        }
    }

    // Opens a modal by ID, optionally from another modal (for navigation)
    open(modalId, fromModal = null) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        // If another modal is open, push to stack and hide it
        if (this.currentModal && this.currentModal !== modalId) {
            if (fromModal) {
                this.modalStack.push(this.currentModal);
            }
            this.hide(this.currentModal);
            setTimeout(() => {
                this.show(modalId);
                this.currentModal = modalId;
                this.updateAllModalDisplays();
            }, 150);
        } else {
            // Show immediately
            this.show(modalId);
            this.currentModal = modalId;
            this.updateAllModalDisplays();
        }
    }

    // Navigates to another modal, pushing current to stack
    navigateToModal(targetModalId) {
        if (this.currentModal) {
            this.modalStack.push(this.currentModal);
            this.hide(this.currentModal);
            setTimeout(() => {
                this.show(targetModalId);
                this.currentModal = targetModalId;
                this.updateAllModalDisplays();
            }, 150);
        } else {
            this.show(targetModalId);
            this.currentModal = targetModalId;
            this.updateAllModalDisplays();
        }
    }

    // Shows a modal by ID, displays container and backdrop
    show(modalId) {
        const modal = document.getElementById(modalId);
        const container = document.getElementById('modal-container');
        const backdrop = document.getElementById('modal-backdrop');
        if (modal && container && backdrop) {
            container.classList.remove('hidden');
            backdrop.classList.remove('opacity-0');
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            modal.style.transform = 'translateY(100%)';
            document.body.style.overflow = 'hidden';
            requestAnimationFrame(() => {
                modal.style.transform = 'translateY(0)';
                modal.classList.add('show');
            });
        }
    }

    // Hides a modal by ID
    hide(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.transform = 'translateY(100%)';
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }, 300);
        }
    }

    // Goes back to previous modal in stack, or closes all if stack empty
    goBack() {
        if (this.modalStack.length > 0) {
            const currentModalId = this.currentModal;
            const previousModalId = this.modalStack.pop();
            if (currentModalId) {
                this.hide(currentModalId);
                setTimeout(() => {
                    this.show(previousModalId);
                    this.currentModal = previousModalId;
                    this.updateAllModalDisplays();
                }, 150);
            }
        } else {
            this.closeAll();
        }
    }

    // Closes all modals and resets stack
    closeAll() {
        if (this.currentModal) {
            this.hide(this.currentModal);
        }
        this.modalStack.forEach(modalId => {
            this.hide(modalId);
        });
        this.currentModal = null;
        this.modalStack = [];
        setTimeout(() => {
            const container = document.getElementById('modal-container');
            const backdrop = document.getElementById('modal-backdrop');
            if (backdrop) backdrop.classList.add('opacity-0');
            setTimeout(() => {
                if (container) container.classList.add('hidden');
                document.body.style.overflow = '';
            }, 250);
        }, 150);
    }

    // Sets up salary calculation for gaji modal
    setupSalaryCalculation() {
        const gajiPokokInput = document.getElementById('input_gaji_pokok');
        const bonusInput = document.getElementById('input_bonus');
        const potonganInput = document.getElementById('input_potongan');
        const totalDisplay = document.getElementById('total_gaji_display');
        const totalHiddenInput = document.getElementById('input_total_gaji');

        function calculateTotal() {
            const gajiPokok = parseInt(gajiPokokInput?.value) || 0;
            const bonus = parseInt(bonusInput?.value) || 0;
            const potongan = parseInt(potonganInput?.value) || 0;
            const total = gajiPokok + bonus - potongan;
            if (totalDisplay) {
                totalDisplay.textContent = `Rp ${total.toLocaleString('id-ID')}`;
            }
            if (totalHiddenInput) {
                totalHiddenInput.value = total;
            }
        }
        [bonusInput, potonganInput].forEach(input => {
            if (input) {
                input.addEventListener('input', calculateTotal);
            }
        });
        calculateTotal();
    }

    // Sets up form submissions (can be customized for AJAX, etc)
    setupFormSubmissions() {
        const forms = ['form-edit-karyawan', 'form-tambah-karyawan', 'form-reset-password', 'form-hapus-karyawan',
            'form-proses-gaji'
        ];
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                form.addEventListener('submit', (e) => {
                    // You can add validation or custom handling here
                    // For now, let the form submit normally
                    console.log(`Form ${formId} submitted`);
                });
            }
        });
    }

    // Shows success modal with custom message
    showSuccessMessage(message) {
        const messageEl = document.getElementById('sukses-message');
        if (messageEl) {
            messageEl.textContent = message;
        }
        this.open('modal-sukses');
    }

    // Updates employee data from form submission
    updateEmployeeFromForm(formData) {
        if (formData.nama_lengkap) this.employeeData.nama = formData.nama_lengkap;
        if (formData.no_telepon) this.employeeData.telepon = formData.no_telepon;
        if (formData.gaji_pokok) this.employeeData.gaji = formData.gaji_pokok;
        this.updateAllModalDisplays();
    }
}

// Initialize Modal Manager
const modalManager = new ModalManager();

// Make it globally accessible for external scripts
window.modalManager = modalManager;
window.ModalManager = {
    open: (modalId, fromModal) => modalManager.open(modalId, fromModal),
    close: (modalId) => modalManager.closeAll(),
    goBack: () => modalManager.goBack(),
    setEmployeeData: (data) => modalManager.setEmployeeData(data),
    showSuccessMessage: (message) => modalManager.showSuccessMessage(message),
    closeAll: () => modalManager.closeAll()
};

// Initialize feather icons after DOM load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
});
</script>

<style>
/* Simple modal styling following pelanggan.php pattern */
.modal-popup {
    display: none;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}

.modal-popup.show {
    display: flex;
    transform: translateY(0);
}

.modal-backdrop {
    transition: opacity 0.3s ease;
}

/* Scrolling */
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Additional modal styles */
.modal-popup {
    backdrop-filter: blur(4px);
}

/* Ensure proper z-index layering */
#modal-container {
    z-index: 9999;
}

.modal-backdrop {
    z-index: 10000;
}

.modal-popup {
    z-index: 10001;
}
</style>