document.addEventListener('DOMContentLoaded', () => {
    // Modal elements
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalOpsi = document.getElementById('modal-opsi-karyawan');
    const modalDetail = document.getElementById('modal-detail-karyawan');
    const modalEdit = document.getElementById('modal-edit-karyawan');
    const modalTambah = document.getElementById('modal-tambah-karyawan');
    const modalHapus = document.getElementById('modal-hapus-karyawan');
    const modalReset = document.getElementById('modal-reset-password');
    
    // Buttons
    const btnTambah = document.getElementById('btn-tambah-karyawan');
    const btnBukaOpsi = document.querySelectorAll('.btn-buka-opsi');
    const btnDetail = document.getElementById('btn-detail-karyawan');
    const btnEdit = document.getElementById('btn-edit-karyawan');
    const btnHapus = document.getElementById('btn-hapus-karyawan');
    const btnReset = document.getElementById('btn-reset-password');
    const closeButtons = document.querySelectorAll('.btn-close-modal');
    const closeCenteredButtons = document.querySelectorAll('.btn-close-centered');

    // Form elements
    const formTambah = document.getElementById('form-tambah-karyawan');
    const opsiKaryawanNama = document.getElementById('opsi-karyawan-nama');
    const resetKaryawanNama = document.getElementById('reset-karyawan-nama');
    const hapusKaryawanNama = document.getElementById('hapus-karyawan-nama');
    const hapusId = document.getElementById('hapus_karyawan_id');
    const resetId = document.getElementById('reset_karyawan_id');
    const editId = document.getElementById('edit_karyawan_id');
    const editNama = document.getElementById('edit_nama_lengkap');
    const editTelepon = document.getElementById('edit_no_telepon');
    const editGaji = document.getElementById('edit_gaji_pokok');

    // Global current employee data
    let currentEmployee = null;

    // Helper functions
    function formatRupiah(value) {
        const number = Number(value);
        if (Number.isNaN(number) || number === 0) {
            return 'Rp 0';
        }
        return `Rp ${number.toLocaleString('id-ID')}`;
    }

    function formatTeks(value) {
        return value && value.trim() !== '' ? value : '-';
    }

    function lockScroll() {
        document.body.style.overflow = 'hidden';
    }

    function unlockScroll() {
        document.body.style.overflow = '';
    }

    // Modal management functions
    function openSlideModal(modal) {
        if (!modal) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        modal.style.transform = 'translateY(100%)';
        modal.classList.remove('hidden');
        lockScroll();
        requestAnimationFrame(() => {
            modal.style.transform = 'translateY(0)';
        });
    }

    function closeSlideModal(modal) {
        if (!modal) return;
        modal.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modal.classList.add('hidden');
            if (!isAnyModalOpen()) {
                closeBackdrop();
            }
        }, 250);
    }

    function openCenteredModal(modal) {
        if (!modal) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        modal.classList.remove('hidden');
        lockScroll();
    }

    function closeCenteredModal(modal) {
        if (!modal) return;
        setTimeout(() => {
            modal.classList.add('hidden');
            if (!isAnyModalOpen()) {
                closeBackdrop();
            }
        }, 200);
    }

    function closeBackdrop() {
        modalBackdrop.classList.add('opacity-0');
        setTimeout(() => {
            modalContainer.classList.add('hidden');
            unlockScroll();
        }, 250);
    }

    function isAnyModalOpen() {
        const anySlideOpen = Array.from(document.querySelectorAll('.modal-popup')).some(el => !el.classList.contains('hidden'));
        const anyCenteredOpen = Array.from(document.querySelectorAll('.modal-centered')).some(el => !el.classList.contains('hidden'));
        return anySlideOpen || anyCenteredOpen;
    }

    function closeAllModals() {
        document.querySelectorAll('.modal-popup').forEach(closeSlideModal);
        document.querySelectorAll('.modal-centered').forEach(closeCenteredModal);
    }

    // Data population functions
    function populateOpsiModal(data) {
        currentCustomer = data;
        opsiPelangganNama.textContent = formatTeks(data.nama);
    }

    function populateDetail(data) {
        detailNama.textContent = formatTeks(data.nama);
        detailTelepon.textContent = formatTeks(data.telepon);
        detailEmail.textContent = formatTeks(data.email);
        detailAlamat.textContent = formatTeks(data.alamat);
        detailCatatan.textContent = formatTeks(data.catatan);
        detailTotalTransaksi.textContent = data.totalTransaksi || '0';
        detailTotalNilai.textContent = formatRupiah(data.totalNilai);
        detailCreated.textContent = formatTeks(data.created);
    }

    function populateEditForm(data) {
        editId.value = data.id || '';
        editNama.value = data.nama || '';
        editTelepon.value = data.telepon || '';
        editEmail.value = data.email || '';
        editAlamat.value = data.alamat || '';
        editCatatan.value = data.catatan || '';
    }

    function resetTambahForm() {
        if (formTambah) {
            formTambah.reset();
        }
    }

    function populateHapusForm(data) {
        hapusId.value = data.id || '';
        hapusNama.textContent = data.nama || '-';
    }

    // Event listeners
    
    // Tambah pelanggan
    btnTambah?.addEventListener('click', () => {
        resetTambahForm();
        openSlideModal(modalTambah);
    });

    // Open customer options when clicking customer card
    btnBukaOpsi.forEach(item => {
        item.addEventListener('click', () => {
            const dataset = item.dataset;
            const data = {
                id: dataset.id,
                nama: dataset.nama,
                telepon: dataset.telepon,
                email: dataset.email,
                alamat: dataset.alamat,
                catatan: dataset.catatan,
                totalTransaksi: dataset.totalTransaksi,
                totalNilai: dataset.totalNilai,
                created: dataset.created,
            };

            populateOpsiModal(data);
            openSlideModal(modalOpsi);
        });
    });

    // Customer option buttons
    btnDetail?.addEventListener('click', () => {
        if (currentCustomer) {
            closeSlideModal(modalOpsi);
            setTimeout(() => {
                populateDetail(currentCustomer);
                openSlideModal(modalDetail);
            }, 100);
        }
    });

    btnEdit?.addEventListener('click', () => {
        if (currentCustomer) {
            closeSlideModal(modalOpsi);
            setTimeout(() => {
                populateEditForm(currentCustomer);
                openSlideModal(modalEdit);
            }, 100);
        }
    });

    btnHapus?.addEventListener('click', () => {
        if (currentCustomer) {
            closeSlideModal(modalOpsi);
            setTimeout(() => {
                populateHapusForm(currentCustomer);
                openCenteredModal(modalHapus);
            }, 100);
        }
    });

    // Close modal buttons
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modal = document.getElementById(modalId);
            closeSlideModal(modal);
        });
    });

    closeCenteredButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modal = document.getElementById(modalId);
            closeCenteredModal(modal);
        });
    });

    // Close on backdrop click
    modalBackdrop?.addEventListener('click', () => {
        closeAllModals();
    });
});