document.addEventListener('DOMContentLoaded', () => {
    // Modal elements
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalRincian = document.getElementById('modal-rincian-transaksi');
    const modalBuat = document.getElementById('modal-buat-transaksi');
    const modalForm = document.getElementById('modal-form-transaksi');
    const templateForm = document.getElementById('modal-transaksi-template')
    const modalPengeluaran = document.getElementById('modal-pengeluaran');
    const modalOpsiLanjutan = document.getElementById('modal-opsi-lanjutan');
    const modalKirimWa = document.getElementById('modal-kirim-wa');

    // Buttons - PERBAIKAN: Sesuaikan dengan HTML
    const transaksiCards = document.querySelectorAll('.transaksi-card'); // UBAH dari .btn-buka-opsi
    const btnManual = document.getElementById('btn-transaksi-manual');
    const btnTemplate = document.getElementById('btn-transaksi-template');
    const btnPengeluaran = document.getElementById('btn-pengeluaran');
    const btnOpsiLanjutan = document.getElementById('btn-opsi-lanjutan');
    const closeButtons = document.querySelectorAll('.btn-close-modal');

    // Form elements
    const formTambah = document.getElementById('form-tambah-transaksi');
    const formOpsiLanjutan = document.getElementById('form-opsi-lanjutan');
    const formPengeluaran = document.getElementById('form-pengeluaran');

    // Rincian elements
    const rincianPelanggan = document.getElementById('rincian-nama-pelanggan');
    const rincianNoNota = document.getElementById('rincian-no-nota');
    const rincianTotalTagihan = document.getElementById('rincian-total-tagihan');
    const rincianStatusBayar = document.getElementById('rincian-status-bayar');
    const rincianStatusPesanan = document.getElementById('rincian-status-pesanan');
    const rincianTanggalMasuk = document.getElementById('rincian-tanggal-masuk');
    const rincianTanggalSelesai = document.getElementById('rincian-tanggal-selesai');

    // Form input elements
    const updateTransaksiId = document.getElementById('update_transaksi_id');

    // Global current transaction data
    let currentTransaction = null;

    // Helper functions
    function formatRupiah(value) {
        // Handle string with currency format
        const cleanValue = typeof value === 'string' ? value.replace(/[^\d]/g, '') : value;
        const number = Number(cleanValue);
        if (Number.isNaN(number) || number === 0) {
            return 'Rp 0';
        }
        return `Rp ${number.toLocaleString('id-ID')}`;
    }

    function formatTeks(value) {
        return value && value.trim() !== '' ? value : '-';
    }

    function formatStatus(status) {
        const statusMap = {
            'pending': 'Antrian',
            'proses': 'Diproses',
            'selesai': 'Selesai',
            'diambil': 'Diambil',
            'batal': 'Dibatalkan'
        };
        return statusMap[status] || status;
    }

    function formatStatusBayar(statusBayar) {
        const statusMap = {
            'lunas': 'Lunas',
            'sebagian': 'Sebagian',
            'belum_lunas': 'Belum Lunas'
        };
        return statusMap[statusBayar] || statusBayar;
    }

    function formatDateTime(dateString) {
        if (!dateString) return 'Belum ditentukan';

        const date = new Date(dateString);
        const options = {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };

        return date.toLocaleDateString('id-ID', options);
    }

    function lockScroll() {
        document.body.style.overflow = 'hidden';
    }

    function unlockScroll() {
        document.body.style.overflow = '';
    }

    // Modal management functions (mengikuti pola pelanggan)
    function openSlideModal(modal) {
        if (!modal) {
            console.error('Modal tidak ditemukan:', modal);
            return;
        }
        console.log('Opening modal:', modal.id);

        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        modal.style.display = 'flex'; // Tambah display flex
        modal.classList.remove('hidden');
        modal.style.transform = 'translateY(100%)';
        lockScroll();

        // Debug info
        console.log('Modal styles after opening:', {
            display: modal.style.display,
            transform: modal.style.transform,
            classes: modal.classList.toString()
        });

        requestAnimationFrame(() => {
            modal.style.transform = 'translateY(0)';
            console.log('Modal animation started, transform:', modal.style.transform);
        });
    }

    function closeSlideModal(modal) {
        if (!modal) return;
        modal.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modal.style.display = 'none'; // Set display none
            modal.classList.add('hidden');
            if (!isAnyModalOpen()) {
                closeBackdrop();
            }
        }, 250);
    }

    function closeBackdrop() {
        modalBackdrop.classList.add('opacity-0');
        setTimeout(() => {
            modalContainer.classList.add('hidden');
            unlockScroll();
        }, 250);
    }

    function isAnyModalOpen() {
        const anyModalOpen = Array.from(document.querySelectorAll('.modal-popup')).some(el => !el.classList.contains('hidden'));
        return anyModalOpen;
    }

    function closeAllModals() {
        document.querySelectorAll('.modal-popup').forEach(closeSlideModal);
    }

    // Data population functions
    function populateRincianModal(data) {
        currentTransaction = data;

        console.log('Populating modal with data:', data); // Debug

        // Update elemen menggunakan class selectors karena tidak ada ID khusus
        const pelangganEl = document.querySelector('.transaksi-pelanggan-nama');
        const notaEl = document.querySelector('.transaksi-no-nota');
        const totalEl = document.querySelector('.transaksi-total-display');
        const statusBayarEl = document.querySelector('.transaksi-status-bayar-display');
        const statusEl = document.querySelector('.transaksi-status-display');
        const tanggalMasukEl = document.querySelector('.transaksi-tanggal-masuk');
        const tanggalSelesaiEl = document.querySelector('.transaksi-tanggal-selesai');

        console.log('Element lookup results:', {
            pelanggan: !!pelangganEl,
            nota: !!notaEl,
            total: !!totalEl,
            statusBayar: !!statusBayarEl,
            status: !!statusEl,
            tanggalMasuk: !!tanggalMasukEl,
            tanggalSelesai: !!tanggalSelesaiEl
        });

        if (pelangganEl) {
            pelangganEl.textContent = formatTeks(data.pelanggan);
            console.log('Set pelanggan:', data.pelanggan);
        }
        if (notaEl) {
            notaEl.textContent = `ID Nota #${data.noNota}`;
            console.log('Set nota:', data.noNota);
        }
        if (totalEl) {
            totalEl.textContent = formatRupiah(data.totalHarga);
            console.log('Set total:', data.totalHarga);
        }
        if (statusBayarEl) {
            statusBayarEl.textContent = formatStatusBayar(data.statusBayar);
            console.log('Set status bayar:', data.statusBayar);
        }
        if (statusEl) {
            statusEl.textContent = formatStatus(data.status);
            console.log('Set status:', data.status);
        }
        if (tanggalMasukEl) {
            tanggalMasukEl.textContent = formatDateTime(data.tanggalMasuk);
            console.log('Set tanggal masuk:', data.tanggalMasuk);
        }
        if (tanggalSelesaiEl) {
            tanggalSelesaiEl.textContent = formatDateTime(data.tanggalSelesai);
            console.log('Set tanggal selesai:', data.tanggalSelesai);
        }

        // Update hidden form inputs
        if (updateTransaksiId) updateTransaksiId.value = data.id || '';

        // Update all transaksi-id-input elements
        document.querySelectorAll('.transaksi-id-input').forEach(el => {
            el.value = data.id || '';
        });
    }

    function resetPengeluaranForm() {
        if (formPengeluaran) {
            formPengeluaran.reset();
        }
    }

    // Event listeners

    // Transaction card clicks - sesuaikan dengan data attributes dari HTML
    transaksiCards.forEach(item => {
        item.addEventListener('click', (e) => {
            // Ignore if clicking on buttons inside card
            if (e.target.closest('button:not(.transaksi-card)')) return;

            e.preventDefault();
            console.log('Transaction card clicked'); // Debug

            const dataset = item.dataset;
            console.log('Dataset:', dataset); // Debug

            // PERBAIKAN: Sesuaikan dengan data attributes dari HTML
            const data = {
                id: dataset.transaksiId, // data-transaksi-id
                noNota: dataset.noNota, // data-no-nota  
                pelanggan: dataset.pelanggan, // data-pelanggan
                totalHarga: dataset.total, // data-total
                status: dataset.status, // data-status
                statusBayar: dataset.statusBayar, // data-status-bayar
                tanggalMasuk: dataset.tanggalMasuk, // data-tanggal-masuk
                tanggalSelesai: dataset.tanggalSelesai, // data-tanggal-selesai
                catatan: dataset.catatan // data-catatan
            };

            console.log('Transaction data:', data); // Debug

            populateRincianModal(data);
            openSlideModal(modalRincian);
        });
    });

    // Transaction type buttons
    btnManual?.addEventListener('click', () => {
        console.log('Manual transaction clicked'); // Debug
        if (modalBuat) {
            closeSlideModal(modalBuat);
            setTimeout(() => {
                resetTambahForm();
                openSlideModal(modalForm);
            }, 100);
        }
    });

    btnTemplate?.addEventListener('click', () => {
        console.log('Manual transaction clicked'); // Debug
        if (modalBuat) {
            closeSlideModal(modalBuat);
            setTimeout(() => {
                resetTambahForm();
                openSlideModal(templateForm);
            }, 100);
        }
    });

    btnPengeluaran?.addEventListener('click', () => {
        console.log('Pengeluaran clicked'); // Debug
        if (modalBuat) {
            closeSlideModal(modalBuat);
            setTimeout(() => {
                resetPengeluaranForm();
                openSlideModal(modalPengeluaran);
            }, 100);
        }
    });

    // Action buttons in detail modal
    btnOpsiLanjutan?.addEventListener('click', () => {
        console.log('Opsi lanjutan clicked'); // Debug
        if (currentTransaction && modalRincian) {
            closeSlideModal(modalRincian);
            setTimeout(() => {
                openSlideModal(modalOpsiLanjutan);
            }, 100);
        }
    });

    // Print and WhatsApp handlers
    document.addEventListener('click', function (e) {
        if (e.target.closest('.btn-cetak-nota')) {
            e.preventDefault();
            console.log('Print nota clicked'); // Debug
            if (currentTransaction && currentTransaction.id) {
                printNota(currentTransaction.id);
            }
        }

        if (e.target.closest('.btn-kirim-wa')) {
            e.preventDefault();
            console.log('WhatsApp clicked'); // Debug
            if (currentTransaction) {
                kirimWhatsApp(currentTransaction);
            }
        }
    });

    // Close modal buttons
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modalId;
            const modal = document.getElementById(modalId);
            console.log('Closing modal:', modalId); // Debug
            closeSlideModal(modal);
        });
    });

    // Close on backdrop click
    modalBackdrop?.addEventListener('click', () => {
        console.log('Backdrop clicked'); // Debug
        closeAllModals();
    });

    // Form submission handlers
    const forms = [formTambah, formOpsiLanjutan, formPengeluaran];
    forms.forEach(form => {
        if (form) {
            form.addEventListener('submit', function (e) {
                console.log(`Form ${this.id} submitted`);

                // Show loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    const originalText = submitBtn.textContent;
                    submitBtn.textContent = 'Memproses...';
                    submitBtn.disabled = true;

                    // Reset will happen on page reload
                }
            });
        }
    });

    // WhatsApp Modal Elements
    const btnKirimWaDinamis = document.getElementById('btn-kirim-wa-dinamis');
    const btnsKirimWa = document.querySelectorAll('.btn-kirim-wa');

    // WhatsApp Modal Handler (same as owner)
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

    function mapStatusToJenis(tx) {
        if (!tx) return 'masuk';
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
                    const opt = selectTemplate.querySelector(`option[value="${defaultJenis}"]`);
                    if (opt) selectTemplate.value = defaultJenis;
                }

                // Tampilkan preview dari window.templatePesan
                let templateText = '';
                if (window.templatePesan && typeof window.templatePesan === 'object') {
                    templateText = window.templatePesan[selectTemplate?.value || defaultJenis] || '';
                }
                if (previewPesan) previewPesan.value = replacePlaceholders(templateText, currentTransaction);

                closeSlideModal(modalRincian);
                openSlideModal(modalKirimWa);
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

    // Utility functions
    function printNota(transaksiId) {
        if (!transaksiId) {
            alert('ID transaksi tidak valid');
            return;
        }

        const printWindow = window.open(`print_nota.php?id=${transaksiId}`, '_blank', 'width=800,height=600');
        if (!printWindow) {
            alert('Pop-up terblokir. Silakan izinkan pop-up untuk mencetak nota.');
        }
    }

    function kirimWhatsApp(transaksiData) {
        const phone = prompt('Masukkan nomor WhatsApp (contoh: 081234567890):');
        if (!phone) return;

        const message = `Halo ${transaksiData.pelanggan},

Informasi Transaksi:
📋 No. Nota: ${transaksiData.noNota}
💰 Total: ${formatRupiah(transaksiData.totalHarga)}
📋 Status: ${formatStatus(transaksiData.status)}

Terima kasih telah menggunakan layanan kami!

*BersihXpress*`;

        const cleanPhone = phone.replace(/\D/g, '');
        const waUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank');
    }

    // Make functions globally accessible (optional)
    window.TransaksiModalManager = {
        openSlideModal,
        closeSlideModal,
        closeAllModals,
        populateRincianModal,
        getCurrentTransaction: () => currentTransaction
    };

    // Initialize feather icons
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    console.log('Owner Transaksi JS initialized');
    console.log('Found transaction cards:', transaksiCards.length); // Debug
    console.log('Modal container:', modalContainer); // Debug
    console.log('Modal rincian:', modalRincian); // Debug
});