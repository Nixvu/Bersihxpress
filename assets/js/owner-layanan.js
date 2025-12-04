document.addEventListener('DOMContentLoaded', () => {
    
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const btnsBukaOpsi = document.querySelectorAll('.btn-buka-opsi');
    const btnTambahLayanan = document.getElementById('btn-tambah-layanan');
    const modalOpsiLayanan = document.getElementById('modal-opsi-layanan');
    const modalEditLayanan = document.getElementById('modal-edit-layanan');
    const modalTambahLayanan = document.getElementById('modal-tambah-layanan');
    const modalHapusLayanan = document.getElementById('modal-hapus-layanan');
    const btnEditLayanan = document.getElementById('btn-edit-layanan');
    const btnHapusLayanan = document.getElementById('btn-hapus-layanan');
    const btnsCloseModal = document.querySelectorAll('.btn-close-modal');
    
    // Membuka Popup
    function openModal(modalElement) {
        if (!modalElement) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        document.body.style.overflow = 'hidden';
        modalElement.classList.remove('hidden');
        requestAnimationFrame(() => modalElement.style.transform = 'translateY(0)');
    }

    // Menutup Popup
    function closeModal(modalElement) {
        if (!modalElement) return;
        modalElement.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modalElement.classList.add('hidden');
            const anySlideModalOpen = document.querySelectorAll('.modal-popup:not(.hidden)').length === 0;
            const anyCenteredModalOpen = document.querySelectorAll('.modal-centered:not(.hidden)').length === 0;
            if (anySlideModalOpen && anyCenteredModalOpen) {
                closeBackdrop();
            }
        }, 300);
    }

    // Menutup Backdrop
    function closeBackdrop() {
        modalBackdrop.classList.add('opacity-0');
        setTimeout(() => {
            modalContainer.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);
    }

    function closeAllModals() {
        document.querySelectorAll('.modal-popup').forEach(closeModal);
    }

    let currentService = null;

    btnsBukaOpsi.forEach(btn => {
        btn.addEventListener('click', () => {
            currentService = {
                id: btn.dataset.id || '',
                nama: btn.dataset.nama || '',
                kategoriId: btn.dataset.kategori || '',
                kategoriNama: btn.dataset.kategoriNama || '',
                harga: btn.dataset.harga || '',
                satuan: btn.dataset.satuan || '',
                estimasi: btn.dataset.estimasi || '',
                deskripsi: btn.dataset.deskripsi || ''
            };

            const opsiNama = document.getElementById('opsi-layanan-nama');
            if (opsiNama) {
                opsiNama.textContent = currentService.nama || '-';
            }

            const hapusNama = document.getElementById('hapus-layanan-nama');
            const hapusIdInput = document.getElementById('hapus_layanan_id');
            if (hapusNama) {
                hapusNama.textContent = currentService.nama || '-';
            }
            if (hapusIdInput) {
                hapusIdInput.value = currentService.id || '';
            }

            openModal(modalOpsiLayanan);
        });
    });

    btnTambahLayanan.addEventListener('click', () => {
        const formTambah = document.getElementById('form-tambah-layanan');
        if (formTambah) {
            formTambah.reset();
        }
        openModal(modalTambahLayanan);
    });

    btnEditLayanan.addEventListener('click', () => {
        if (currentService) {
            document.getElementById('edit_layanan_id').value = currentService.id || '';
            document.getElementById('edit_nama_layanan').value = currentService.nama || '';
            const kategoriSelect = document.getElementById('edit_kategori_id');
            if (kategoriSelect) {
                kategoriSelect.value = currentService.kategoriId || kategoriSelect.value;
            }
            document.getElementById('edit_harga').value = currentService.harga || '';
            document.getElementById('edit_satuan').value = currentService.satuan || '';
            document.getElementById('edit_estimasi').value = currentService.estimasi || '';
            document.getElementById('edit_deskripsi').value = currentService.deskripsi || '';
        }
        closeModal(modalOpsiLayanan);
        openModal(modalEditLayanan);
    });

    btnHapusLayanan.addEventListener('click', () => {
        const hapusIdInput = document.getElementById('hapus_layanan_id');
        const hapusNama = document.getElementById('hapus-layanan-nama');
        if (currentService) {
            if (hapusIdInput) {
                hapusIdInput.value = currentService.id || '';
            }
            if (hapusNama) {
                hapusNama.textContent = currentService.nama || '-';
            }
        }
        closeModal(modalOpsiLayanan);
        openModal(modalHapusLayanan);
    });

    btnsCloseModal.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modalToClose = document.getElementById(modalId);
            if (modalId === 'modal-edit-layanan') {
                closeModal(modalToClose);
                openModal(modalOpsiLayanan);
            } else if (modalId === 'modal-hapus-layanan') {
                // Tutup modal hapus, naikkan kembali modal opsi layanan dengan jeda
                closeModal(modalHapusLayanan);
                openModal(modalOpsiLayanan);
            } else {
                closeAllModals();
            }
        });
    });

    // Tombol batal pada modal hapus layanan
    const btnsCloseCentered = document.querySelectorAll('.btn-close-centered');
    btnsCloseCentered.forEach(btn => {
        btn.addEventListener('click', () => {
            closeModal(modalHapusLayanan);
            openModal(modalOpsiLayanan);
        });
    });
    modalBackdrop.addEventListener('click', closeAllModals);
});