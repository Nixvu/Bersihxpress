<?php
// ...existing code...
// Modal Edit Profil Usaha
// require_once __DIR__ . '/middleware/auth_owner.php';

$ownerData = $_SESSION['owner_data'] ?? [];
?>
<div id="modal-profil-usaha" action="form-profil-usaha" class="modal-popup fixed bottom-0 left-0 right-0 bg-white rounded-t-[24px] shadow-2xl z-50 flex flex-col h-[66vh]">
            <div class="flex-shrink-0">
                <div class="w-full py-3"><div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto"></div></div>
                <div class="flex justify-between items-center px-6 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Profil Usaha</h2>
                    <button class="btn-close-modal p-1 text-gray-500 hover:text-gray-800">
                        <svg data-feather="x" class="w-6 h-6"></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex-grow overflow-y-auto p-6 no-scrollbar">
                <form id="form-edit-usaha" class="space-y-4" method="POST" action="form-profil-usaha.php">
                    
                    <div class="bg-white border border-gray-200 rounded-lg p-5 space-y-4">
                        <div>
                            <label for="nama_bisnis" class="text-sm font-medium text-gray-600">Nama Usaha Laundry</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="home" class="h-5 w-5 text-gray-400"></svg></span>
                                <input type="text" id="nama_bisnis" name="nama_bisnis" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Nama Usaha Laundry"
                                value="<?php echo htmlspecialchars($ownerData['nama_bisnis'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div>
                            <label for="no_telepon" class="text-sm font-medium text-gray-600">No Handphone</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="phone" class="h-5 w-5 text-gray-400"></svg></span>
                                <input type="tel" id="no_telepon" name="no_telepon" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="No Handphone"
                                value="<?php echo htmlspecialchars($ownerData['no_telepon'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <!-- <div>
                            <label for="jam_operasional" class="text-sm font-medium text-gray-600">Jam Operasional</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><svg data-feather="clock" class="h-5 w-5 text-gray-400"></svg></span>
                                <input type="text" id="jam_operasional" name="jam_operasional" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Contoh 10:00 - 18:00"
                                value="<?php echo htmlspecialchars($ownerData['jam_operasional'] ?? ''); ?>">
                            </div>
                        </div> -->
                        
                        <div>
                            <label for="alamat" class="text-sm font-medium text-gray-600">Alamat Usaha Laundry</label>
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-0 top-3 pl-3 flex items-start pointer-events-none"><svg data-feather="map-pin" class="h-5 w-5 text-gray-400"></svg></span>
                                <textarea id="alamat" name="alamat" rows="3" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg" placeholder="Contoh : Jln Re Martadinata" required
                                ><?php echo htmlspecialchars($ownerData['alamat'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="flex-shrink-0 p-6 bg-white border-t border-gray-200">
                <button type="submit" form="form-edit-usaha" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700">
                    Simpan Perubahan
                </button>
            </div>
        </div>