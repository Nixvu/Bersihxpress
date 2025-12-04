document.addEventListener('DOMContentLoaded', () => {
    // Handler untuk tombol kirim WA dinamis
    const btnKirimWaDinamis = document.getElementById('btn-kirim-wa-dinamis');
    if (btnKirimWaDinamis) {
        btnKirimWaDinamis.addEventListener('click', function () {
            const inputNomor = modalKirimWa.querySelector('#wa-phone-input');
            const previewPesan = modalKirimWa.querySelector('#wa-message-preview');
            let nomor = inputNomor?.value || '';
            let pesan = previewPesan?.value || '';
            nomor = nomor.replace(/[^\d]/g, '');
            if (nomor.startsWith('0')) nomor = '62' + nomor.slice(1);
            if (!nomor) {
                alert('Nomor WhatsApp tidak valid!');
                return;
            }
            const waUrl = `https://wa.me/${nomor}?text=${encodeURIComponent(pesan)}`;
            window.open(waUrl, '_blank');
        });
    }

    // Modal elements
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalRincian = document.getElementById('modal-rincian-transaksi');
    const modalOpsiLanjutan = document.getElementById('modal-opsi-lanjutan');
    const modalKirimWa = document.getElementById('modal-kirim-wa');

    // Buttons
    const transaksiCards = document.querySelectorAll('.transaksi-card');
    const btnOpsiLanjutan = document.getElementById('btn-opsi-lanjutan');
    const btnsCloseModal = document.querySelectorAll('.btn-close-modal');
    // btnKirimWa bisa lebih dari satu, gunakan querySelectorAll
    const btnsKirimWa = document.querySelectorAll('.btn-kirim-wa');

    // Global current transaction data
    let currentTransaction = null;

    function openModal(modalElement) {
        if (!modalElement) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        document.body.style.overflow = 'hidden';
        modalElement.classList.remove('hidden');
        modalElement.style.display = 'flex';
        requestAnimationFrame(() => modalElement.style.transform = 'translateY(0)');
    }

    function closeModal(modalElement) {
        if (!modalElement) return;
        modalElement.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modalElement.classList.add('hidden');
            modalElement.style.display = 'none';
            const anySlideModalOpen = document.querySelectorAll('.modal-popup:not(.hidden)').length === 0;
            const anyCenteredModalOpen = document.querySelectorAll('.modal-centered:not(.hidden)').length === 0;
            if (anySlideModalOpen && anyCenteredModalOpen) {
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




    // Transaction card clicks - sesuaikan dengan data attributes dari HTML
    transaksiCards.forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.target.closest('button:not(.transaksi-card)')) return;
            e.preventDefault();
            const dataset = item.dataset;
            const data = {
                id: dataset.transaksiId,
                noNota: dataset.noNota,
                pelanggan: dataset.pelanggan,
                totalHarga: dataset.total,
                status: dataset.status,
                statusBayar: dataset.statusBayar,
                telepon: dataset.telepon,
                tanggalMasuk: dataset.tanggalMasuk,
                tanggalSelesai: dataset.tanggalSelesai,
                catatan: dataset.catatan
            };
            populateRincianModal(data);
            openModal(modalRincian);
        });
    });



    // Action buttons in detail modal
    btnOpsiLanjutan?.addEventListener('click', () => {
        console.log('Opsi lanjutan clicked'); // Debug
        if (currentTransaction && modalRincian) {
            closeModal(modalRincian);
            openModal(modalOpsiLanjutan);
        }
    });

    function mapStatusToJenis(tx) {
        if (!tx) return 'masuk';
        // Prioritize pembayaran when payment completed
        if (tx.statusBayar && tx.statusBayar.toLowerCase() === 'lunas') return 'pembayaran';
        const s = (tx.status || '').toLowerCase();
        if (s === 'selesai') return 'selesai';
        if (s === 'proses' || s === 'diproses') return 'proses';
        if (s === 'pending' || s === 'antrian') return 'masuk';
        return 'masuk';
    }

    function replacePlaceholders(template, tx) {
        if (!template) return '';
        let t = template;
        const map = {
            '\\[NAMA_PELANGGAN\\]': tx.pelanggan || '',
            '\\[ID_NOTA\\]': tx.noNota || '',
            '\\[TOTAL_HARGA\\]': tx.totalHarga || '',
            '\\[NAMA_OUTLET\\]': window.bisnisNama || '',
            '\\[ESTIMASI_SELESAI\\]': tx.tanggalSelesai || ''
        };
        Object.keys(map).forEach(k => {
            t = t.replace(new RegExp(k, 'g'), map[k]);
        });
        return t;
    }

    btnsKirimWa.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentTransaction && modalKirimWa) {
                const inputNomor = modalKirimWa.querySelector('#wa-phone-input');
                const selectTemplate = modalKirimWa.querySelector('#wa-template-select');
                const previewPesan = modalKirimWa.querySelector('#wa-message-preview');

                // Isi nomor telepon dari data card (data-telepon)
                if (inputNomor) {
                    inputNomor.value = currentTransaction.telepon || currentTransaction.nohp || currentTransaction.no_telepon || '';
                }

                // Pilih default template berdasarkan status transaksi
                const defaultJenis = mapStatusToJenis(currentTransaction);
                if (selectTemplate) {
                    // set value if exists
                    const opt = selectTemplate.querySelector(`option[value="${defaultJenis}"]`);
                    if (opt) selectTemplate.value = defaultJenis;
                }

                // Tampilkan preview dari window.templatePesan (diinject dari PHP)
                let templateText = '';
                if (window.templatePesan && typeof window.templatePesan === 'object') {
                    templateText = window.templatePesan[selectTemplate?.value || defaultJenis] || '';
                }
                if (previewPesan) previewPesan.value = replacePlaceholders(templateText, currentTransaction);

                // (change listener di-attach sekali di luar handler)

                closeModal(modalRincian);
                openModal(modalKirimWa);
            }
        });
    });

    // Attach a single change listener for template select to update preview using currentTransaction
    (function attachTemplateChange() {
        if (!modalKirimWa) return;
        const selectTemplateGlobal = modalKirimWa.querySelector('#wa-template-select');
        const previewGlobal = modalKirimWa.querySelector('#wa-message-preview');
        if (!selectTemplateGlobal) return;
        selectTemplateGlobal.addEventListener('change', function () {
            const t = (window.templatePesan && window.templatePesan[this.value]) || '';
            if (previewGlobal) previewGlobal.value = replacePlaceholders(t, currentTransaction || {});
        });
    })();

    // Print and WhatsApp handlers
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-cetak-nota')) {
            e.preventDefault();
            if (currentTransaction && currentTransaction.id) {
                printNota(currentTransaction.id);
            }
        }
        // ...ubah status handler bisa ditambahkan di sini...
    });

    // Close modal buttons
    btnsCloseModal.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modalToClose = document.getElementById(modalId);
            if (modalId === 'modal-opsi-lanjutan') {
                closeModal(modalToClose);
                openModal(modalRincian);
            } else if (modalId === 'modal-kirim-wa') {
                // Tutup modal hapus, naikkan kembali modal opsi layanan dengan jeda
                closeModal(modalToClose);
                openModal(modalRincian);
            } else {
                closeAllModals();
            }
        });
    });

    // Close on backdrop click
    modalBackdrop?.addEventListener('click', () => {
        closeAllModals();
    });

    // Data population functions
    // Helper functions
    function formatRupiah(value) {
        const number = Number(typeof value === 'string' ? value.replace(/[^\d]/g, '') : value);
        return Number.isNaN(number) || number === 0 ? 'Rp 0' : `Rp ${number.toLocaleString('id-ID')}`;
    }
    function formatTeks(value) {
        return value && value.trim() !== '' ? value : '-';
    }

    function populateRincianModal(data) {
        currentTransaction = data;
        const pelangganEl = document.querySelector('.transaksi-pelanggan-nama');
        const notaEl = document.querySelector('.transaksi-no-nota');
        const totalEl = document.querySelector('.transaksi-total-display');
        const statusBayarEl = document.querySelector('.transaksi-status-bayar-display');
        const statusEl = document.querySelector('.transaksi-status-display');
        const tanggalMasukEl = document.querySelector('.transaksi-tanggal-masuk');
        const tanggalSelesaiEl = document.querySelector('.transaksi-tanggal-selesai');
        if (pelangganEl) pelangganEl.textContent = formatTeks(data.pelanggan);
        if (notaEl) notaEl.textContent = `ID Nota #${data.noNota}`;
        if (totalEl) totalEl.textContent = formatRupiah(data.totalHarga);
        if (statusBayarEl) statusBayarEl.textContent = data.statusBayar;
        if (statusEl) statusEl.textContent = data.status;
        if (tanggalMasukEl) tanggalMasukEl.textContent = data.tanggalMasuk;
        if (tanggalSelesaiEl) tanggalSelesaiEl.textContent = data.tanggalSelesai;
        document.querySelectorAll('.transaksi-id-input').forEach(el => {
            el.value = data.id || '';
        });
    }

    function printNota(transaksiId) {
        if (!transaksiId) return;
        window.open(`print_nota.php?id=${transaksiId}`, '_blank', 'width=800,height=600');
    }



    // window.TransaksiModalManager = {
    //     openModal,
    //     closeModal,
    //     closeAllModals,
    //     populateRincianModal,
    //     getCurrentTransaction: () => currentTransaction
    // };

    // if (typeof feather !== 'undefined') feather.replace();
});