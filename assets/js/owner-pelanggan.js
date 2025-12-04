document.addEventListener('DOMContentLoaded', () => {
    
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const btnsBukaOpsi = document.querySelectorAll('.btn-buka-opsi');
    const btnTambahPelanggan = document.getElementById('btn-tambah-pelanggan');
    const modalOpsiPelanggan = document.getElementById('modal-opsi-pelanggan');
    const modalEditPelanggan = document.getElementById('modal-edit-pelanggan');
    const modalTambahPelanggan = document.getElementById('modal-tambah-pelanggan');
    const modalDetailPelanggan = document.getElementById('modal-detail-pelanggan');
    const modalHapusPelanggan = document.getElementById('modal-hapus-pelanggan');
    const btnEditPelanggan = document.getElementById('btn-edit-pelanggan');
    const btnHapusPelanggan = document.getElementById('btn-hapus-pelanggan');
    const btnDetailPelanggan = document.getElementById('btn-detail-pelanggan');
    const btnsCloseModal = document.querySelectorAll('.btn-close-modal');

    const formTambahPelanggan = document.getElementById('form-tambah-pelanggan');
    const opsiPelangganNama = document.getElementById('opsi-pelanggan-nama');
    const hapusNama = document.getElementById('hapus-pelanggan-nama');
    const hapusId = document.getElementById('hapus_pelanggan_id');
    const editId = document.getElementById('edit_pelanggan_id');
    const editNama = document.getElementById('edit_nama');
    const editTelepon = document.getElementById('edit_telepon');
    const editEmail = document.getElementById('edit_email');
    const editAlamat = document.getElementById('edit_alamat');
    const editCatatan = document.getElementById('edit_catatan');

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

    let currentCustomer = null;

    // --- PASANG EVENT LISTENERS ---
    btnsBukaOpsi.forEach(btn => {
        btn.addEventListener('click', () => {
            currentCustomer = {
                id: btn.dataset.id || '',
                nama: btn.dataset.nama || '',
                telepon: btn.dataset.telepon || '',
                email: btn.dataset.email || '',
                alamat: btn.dataset.alamat || '',
                catatan: btn.dataset.catatan || '',
                totalTransaksi: btn.dataset.totalTransaksi || '',
                totalNilai: btn.dataset.totalNilai || '',
                created: btn.dataset.created || ''
            };
            if (opsiPelangganNama) opsiPelangganNama.textContent = currentCustomer.nama || '-';
            if (hapusNama) hapusNama.textContent = currentCustomer.nama || '-';
            if (hapusId) hapusId.value = currentCustomer.id || '';
            
            openModal(modalOpsiPelanggan);
        });
    });

    btnTambahPelanggan.addEventListener('click', () => {
        if (formTambahPelanggan) {
            formTambahPelanggan.reset();
        }
        openModal(modalTambahPelanggan);
    });

    btnDetailPelanggan.addEventListener('click', () => {
        if (currentCustomer) {
            // Ambil elemen detail di modal
            const detailNama = document.getElementById('detail-nama');
            const detailTelepon = document.getElementById('detail-telepon');
            const detailEmail = document.getElementById('detail-email');
            const detailAlamat = document.getElementById('detail-alamat');
            const detailCatatan = document.getElementById('detail-catatan');
            const detailTotalTransaksi = document.getElementById('detail-total-transaksi');
            const detailTotalNilai = document.getElementById('detail-total-nilai');
            const detailCreated = document.getElementById('detail-created');

            if (detailNama) detailNama.textContent = currentCustomer.nama || '-';
            if (detailTelepon) detailTelepon.textContent = currentCustomer.telepon || '-';
            if (detailEmail) detailEmail.textContent = currentCustomer.email || '-';
            if (detailAlamat) detailAlamat.textContent = currentCustomer.alamat || '-';
            if (detailCatatan) detailCatatan.textContent = currentCustomer.catatan || '-';
            if (detailTotalTransaksi) detailTotalTransaksi.textContent = currentCustomer.totalTransaksi || '0';
            if (detailTotalNilai) detailTotalNilai.textContent = currentCustomer.totalNilai || '0';
            if (detailCreated) detailCreated.textContent = currentCustomer.created || '-';
        }
        closeModal(modalOpsiPelanggan);
        setTimeout(() => {
            openModal(modalDetailPelanggan);
        }, 350);
    });

    btnEditPelanggan.addEventListener('click', () => {
        if (currentCustomer) {
            if (editId) editId.value = currentCustomer.id || '';
            if (editNama) editNama.value = currentCustomer.nama || '';
            if (editTelepon) editTelepon.value = currentCustomer.telepon || '';
            if (editEmail) editEmail.value = currentCustomer.email || '';
            if (editAlamat) editAlamat.value = currentCustomer.alamat || '';
            if (editCatatan) editCatatan.value = currentCustomer.catatan || '';
        }
        closeModal(modalOpsiPelanggan);
        setTimeout(() => {
            openModal(modalEditPelanggan);
        }, 350);
    });

    btnHapusPelanggan.addEventListener('click', () => {
        if (currentCustomer) {
            if (hapusId) hapusId.value = currentCustomer.id || '';
            if (hapusNama) hapusNama.textContent = currentCustomer.nama || '-';
        }
        closeModal(modalOpsiPelanggan);
        setTimeout(() => {
            // Modal hapus pelanggan (centered): fade/scale
            modalHapusPelanggan.classList.remove('hidden');
            modalHapusPelanggan.style.opacity = '0';
            modalHapusPelanggan.style.transform = 'scale(0.95)';
            requestAnimationFrame(() => {
                modalHapusPelanggan.style.transition = 'opacity 0.3s, transform 0.3s';
                modalHapusPelanggan.style.opacity = '1';
                modalHapusPelanggan.style.transform = 'scale(1)';
            });
        }, 350);
    });

    btnsCloseModal.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modalToClose = document.getElementById(modalId);
            if (modalId === 'modal-edit-pelanggan') {
                closeModal(modalToClose);
                setTimeout(() => {
                    openModal(modalOpsiPelanggan);
                }, 350);
            } else if (modalId === 'modal-detail-pelanggan') {
                closeModal(modalToClose);
                setTimeout(() => {
                    openModal(modalOpsiPelanggan);
                }, 350);
            } else {
                closeAllModals();
            }
        });
    });

    const btnsCloseCentered = document.querySelectorAll('.btn-close-centered');
    btnsCloseCentered.forEach(btn => {
        btn.addEventListener('click', () => {
            // Animasi tutup modal hapus pelanggan
            modalHapusPelanggan.style.opacity = '0';
            modalHapusPelanggan.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modalHapusPelanggan.classList.add('hidden');
                openModal(modalOpsiPelanggan);
            }, 300);
        });
    });
    modalBackdrop.addEventListener('click', closeAllModals);

    

});