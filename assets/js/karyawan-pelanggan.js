document.addEventListener('DOMContentLoaded', () => {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const btnDetailPelangganList = document.querySelectorAll('.btn-detail-pelanggan');
    const btnTambahPelanggan = document.getElementById('btn-tambah-pelanggan');
    const modalTambahPelanggan = document.getElementById('modal-tambah-pelanggan');
    const modalDetailPelanggan = document.getElementById('modal-detail-pelanggan');
    const btnsCloseModal = document.querySelectorAll('.btn-close-modal');
    const formTambahPelanggan = document.getElementById('form-tambah-pelanggan');

    // Event listener untuk tombol tambah pelanggan
    if (btnTambahPelanggan && modalTambahPelanggan) {
        btnTambahPelanggan.addEventListener('click', () => {
            if (formTambahPelanggan) {
                formTambahPelanggan.reset();
            }
            openModal(modalTambahPelanggan);
        });
    }

    // Modal helpers
    function openModal(modalElement) {
        if (!modalElement) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        document.body.style.overflow = 'hidden';
        modalElement.classList.remove('hidden');
        requestAnimationFrame(() => modalElement.style.transform = 'translateY(0)');
    }
    function closeModal(modalElement) {
        if (!modalElement) return;
        modalElement.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modalElement.classList.add('hidden');
            const anySlideModalOpen = document.querySelectorAll('.modal-popup:not(.hidden)').length === 0;
            if (anySlideModalOpen) {
                closeBackdrop();
            }
        }, 300);
    }
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

    // --- PASANG EVENT LISTENERS ---
    btnDetailPelangganList.forEach(btn => {
        btn.addEventListener('click', () => {
            // Ambil data dari button
            const nama = btn.dataset.nama || '-';
            const telepon = btn.dataset.telepon || '-';
            const email = btn.dataset.email || '-';
            const alamat = btn.dataset.alamat || '-';
            const catatan = btn.dataset.catatan || '-';
            const totalTransaksi = btn.dataset.totalTransaksi || '0';
            const totalNilai = btn.dataset.totalNilai || '0';
            const created = btn.dataset.created || '-';

            // Isi modal
            const detailNama = document.getElementById('detail-nama');
            const detailTelepon = document.getElementById('detail-telepon');
            const detailEmail = document.getElementById('detail-email');
            const detailAlamat = document.getElementById('detail-alamat');
            const detailCatatan = document.getElementById('detail-catatan');
            const detailTotalTransaksi = document.getElementById('detail-total-transaksi');
            const detailTotalNilai = document.getElementById('detail-total-nilai');
            const detailCreated = document.getElementById('detail-created');

            if (detailNama) detailNama.textContent = nama;
            if (detailTelepon) detailTelepon.textContent = telepon;
            if (detailEmail) detailEmail.textContent = email;
            if (detailAlamat) detailAlamat.textContent = alamat;
            if (detailCatatan) detailCatatan.textContent = catatan;
            if (detailTotalTransaksi) detailTotalTransaksi.textContent = totalTransaksi;
            if (detailTotalNilai) detailTotalNilai.textContent = totalNilai;
            if (detailCreated) detailCreated.textContent = created;

            openModal(modalDetailPelanggan);
        });
    });

    btnsCloseModal.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modalToClose = document.getElementById(modalId);
            closeModal(modalToClose);
        });
    });

    modalBackdrop.addEventListener('click', closeAllModals);



});