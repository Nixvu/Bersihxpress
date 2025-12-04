/**
 * ======================================================
 * SCRIPT KHUSUS: karyawan/dashboard.html
 * Meng-handle:
 * 1. Logika modal Aksi Cepat
 * 2. Logika Tab "Tugas Saya"
 * ======================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- PILIH ELEMEN MODAL ---
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');

    // Tombol Aksi Cepat
    const btnOpenTransaksi = document.getElementById('btn-open-transaksi');
    const btnOpenAbsensi = document.getElementById('btn-open-absensi');

    // Modal Utama
    const modalBuatTransaksi = document.getElementById('modal-buat-transaksi');
    const modalAbsensi = document.getElementById('modal-absensi');
    const modalRincianTransaksi = document.getElementById('modal-rincian-transaksi');
    const modalTransaksiTemplate = document.getElementById('modal-transaksi-template');
    const btnTransaksiManual = document.getElementById('btn-transaksi-manual');
    const btnTransaksiTemplate = document.getElementById('btn-transaksi-template');
    const closeButtons = document.querySelectorAll('.btn-close-global');

    // --- FUNGSI-FUNGSI HELPER MODAL (Referensi owner-dashboard.js) ---
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
        setTimeout(() => modalElement.classList.add('hidden'), 300);
    }

    function closeAllModals() {
        const allModals = [
            modalBuatTransaksi, modalRincianTransaksi, modalTransaksiTemplate,
            modalAbsensi
        ];
        allModals.forEach(closeModal);
        modalBackdrop.classList.add('opacity-0');
        setTimeout(() => {
            modalContainer.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);
    }

    // --- PASANG EVENT LISTENERS (MODAL) ---
    if (btnOpenTransaksi) btnOpenTransaksi.addEventListener('click', () => openModal(modalBuatTransaksi));
    if (btnOpenAbsensi) btnOpenAbsensi.addEventListener('click', () => openModal(modalAbsensi));
    if (btnTransaksiManual) btnTransaksiManual.addEventListener('click', () => {
        closeModal(modalBuatTransaksi);
        openModal(modalRincianTransaksi);
    });
    if (btnTransaksiTemplate) btnTransaksiTemplate.addEventListener('click', () => {
        closeModal(modalBuatTransaksi);
        openModal(modalTransaksiTemplate);
    });
    closeButtons.forEach(button => button.addEventListener('click', closeAllModals));
    if (modalBackdrop) modalBackdrop.addEventListener('click', closeAllModals);

    // --- LOGIKA TAB (Tugas Saya) ---
    const tugasTabButtons = document.querySelectorAll('.tugas-tab-button');
    const tugasTabPanels = document.querySelectorAll('.tugas-tab-panel');

    tugasTabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetPanelId = button.dataset.target;
            tugasTabButtons.forEach(btn => btn.classList.remove('aksi-tab-button-active'));
            button.classList.add('aksi-tab-button-active');
            tugasTabPanels.forEach(panel => panel.classList.add('hidden'));
            if (document.querySelector(targetPanelId)) {
                document.querySelector(targetPanelId).classList.remove('hidden');
            }
        });
    });
});