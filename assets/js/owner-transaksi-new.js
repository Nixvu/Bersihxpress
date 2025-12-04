// Transaction page initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing transaction page');
    initializeTransactionPage();
});

function initializeTransactionPage() {
    console.log('Initializing transaction page...');
    initializeModalSystem();
    initializeTransactionButtons();
    initializeFormHandlers();
    console.log('Transaction page initialized successfully');
}

// Modal System Functions
function initializeModalSystem() {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    
    if (!modalContainer || !modalBackdrop) {
        console.warn('Modal container or backdrop not found');
        return;
    }

    // Backdrop click to close
    modalBackdrop.addEventListener('click', function(e) {
        if (e.target === modalBackdrop) {
            closeAllModals();
        }
    });

    // Close modal buttons
    const closeButtons = document.querySelectorAll('.btn-close-modal, .btn-tutup-modal, .btn-batal-modal, [data-action="close-modal"]');
    closeButtons.forEach(button => {
        button.removeAttribute('onclick');
        
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const modalId = this.getAttribute('data-modal-id');
            const closestModal = this.closest('.modal-popup');
            
            if (modalId) {
                closeModal(modalId);
            } else if (closestModal) {
                closeModal(closestModal.id);
            } else {
                closeAllModals();
            }
        });
    });

    // Prevent modal close when clicking inside modal content
    const modalPopups = document.querySelectorAll('.modal-popup');
    modalPopups.forEach(modal => {
        modal.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    console.log('Modal system initialized - close buttons:', closeButtons.length);
}

function showModal(modalId) {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modal = document.getElementById(modalId);
    
    if (!modalContainer || !modal) {
        console.error('Modal not found:', modalId);
        return;
    }

    // Hide all other modals first
    const allModals = document.querySelectorAll('.modal-popup');
    allModals.forEach(m => {
        if (m.id !== modalId) {
            m.classList.add('hidden');
            m.style.display = 'none';
        }
    });

    // Show modal container and specific modal
    modalContainer.classList.remove('hidden');
    modalContainer.style.display = 'flex';
    modal.classList.remove('hidden');
    modal.style.display = 'block';
    
    // Animate backdrop
    if (modalBackdrop) {
        setTimeout(() => {
            modalBackdrop.classList.remove('opacity-0');
            modalBackdrop.classList.add('opacity-100');
        }, 10);
    }

    // Animate modal slide up
    setTimeout(() => {
        modal.style.transform = 'translateY(0)';
    }, 10);
    
    console.log('Showing modal:', modalId);
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    // Animate modal slide down
    modal.style.transform = 'translateY(100%)';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.style.display = 'none';
        
        // Check if any other modal is still open
        const openModals = document.querySelectorAll('.modal-popup:not(.hidden)');
        if (openModals.length === 0) {
            closeAllModals();
        }
    }, 300);
    
    console.log('Closing modal:', modalId);
}

function closeAllModals() {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    
    if (!modalContainer) return;

    // Animate backdrop fade
    if (modalBackdrop) {
        modalBackdrop.classList.remove('opacity-100');
        modalBackdrop.classList.add('opacity-0');
    }
    
    // Hide all modals
    const allModals = document.querySelectorAll('.modal-popup');
    allModals.forEach(modal => {
        modal.style.transform = 'translateY(100%)';
        modal.classList.add('hidden');
        modal.style.display = 'none';
    });

    setTimeout(() => {
        modalContainer.classList.add('hidden');
        modalContainer.style.display = 'none';
    }, 300);
    
    console.log('All modals closed');
}

// Transaction Button Handlers
function initializeTransactionButtons() {
    console.log('Initializing transaction buttons...');
    
    // Add transaction button
    const btnTambahTransaksi = document.getElementById('btn-tambah-transaksi');
    if (btnTambahTransaksi) {
        btnTambahTransaksi.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Tambah transaksi clicked');
            showModal('modal-buat-transaksi');
        });
        console.log('Btn tambah transaksi attached');
    } else {
        console.warn('btn-tambah-transaksi not found');
    }

    // Transaction type buttons
    const btnTransaksiManual = document.getElementById('btn-transaksi-manual');
    if (btnTransaksiManual) {
        btnTransaksiManual.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Transaksi manual clicked');
            closeModal('modal-buat-transaksi');
            setTimeout(() => showModal('modal-rincian-transaksi'), 300);
        });
        console.log('Btn transaksi manual attached');
    }

    const btnTransaksiBarcode = document.getElementById('btn-transaksi-barcode');
    if (btnTransaksiBarcode) {
        btnTransaksiBarcode.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Transaksi barcode clicked');
            closeModal('modal-buat-transaksi');
            setTimeout(() => showModal('modal-barcode-scanner'), 300);
        });
        console.log('Btn transaksi barcode attached');
    }

    const btnTransaksiTemplate = document.getElementById('btn-transaksi-template');
    if (btnTransaksiTemplate) {
        btnTransaksiTemplate.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'template.php';
        });
    }

    const btnPengeluaran = document.getElementById('btn-pengeluaran');
    if (btnPengeluaran) {
        btnPengeluaran.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal('modal-buat-transaksi');
            setTimeout(() => showModal('modal-pengeluaran'), 300);
        });
    }

    // Transaction card clicks
    document.addEventListener('click', function(e) {
        const transaksiCard = e.target.closest('.transaksi-card');
        if (transaksiCard && !e.target.closest('button')) {
            console.log('Transaksi card clicked');
            const transaksiData = {
                id: transaksiCard.getAttribute('data-transaksi-id'),
                noNota: transaksiCard.getAttribute('data-no-nota'),
                pelanggan: transaksiCard.getAttribute('data-pelanggan'),
                total: transaksiCard.getAttribute('data-total'),
                status: transaksiCard.getAttribute('data-status'),
                statusBayar: transaksiCard.getAttribute('data-status-bayar'),
                tanggalMasuk: transaksiCard.getAttribute('data-tanggal-masuk'),
                tanggalSelesai: transaksiCard.getAttribute('data-tanggal-selesai'),
                catatan: transaksiCard.getAttribute('data-catatan')
            };
            showTransactionDetail(transaksiData);
        }
    });

    // Action buttons
    const btnBukaOpsi = document.getElementById('btn-buka-opsi');
    if (btnBukaOpsi) {
        btnBukaOpsi.addEventListener('click', function(e) {
            e.preventDefault();
            showModal('modal-opsi-lanjutan');
        });
    }

    // Print and WhatsApp handlers
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-cetak-nota')) {
            const transaksiId = document.getElementById('update_transaksi_id')?.value;
            if (transaksiId) {
                printNota(transaksiId);
            }
        }
        
        if (e.target.closest('.btn-kirim-wa')) {
            const transaksiId = document.getElementById('update_transaksi_id')?.value;
            if (transaksiId) {
                kirimWhatsApp(transaksiId);
            }
        }
    });

    console.log('Transaction buttons initialization complete');
}

// Transaction Detail Functions
function showTransactionDetail(data) {
    // Populate transaction detail modal
    const elements = {
        'rincian-nama-pelanggan': data.pelanggan || 'Guest',
        'rincian-no-nota': `ID Nota #${data.noNota}`,
        'rincian-total-tagihan': data.total,
        'rincian-status-bayar': getPaymentStatusDisplay(data.statusBayar),
        'rincian-status-pesanan': getStatusDisplay(data.status)
    };

    Object.entries(elements).forEach(([id, value]) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    });
    
    if (data.tanggalMasuk) {
        const tanggalMasukEl = document.getElementById('rincian-tanggal-masuk');
        if (tanggalMasukEl) tanggalMasukEl.textContent = formatDateTime(data.tanggalMasuk);
    }
    
    if (data.tanggalSelesai) {
        const tanggalSelesaiEl = document.getElementById('rincian-tanggal-selesai');
        if (tanggalSelesaiEl) tanggalSelesaiEl.textContent = formatDateTime(data.tanggalSelesai);
    }

    // Set hidden inputs for forms
    const transaksiIdInputs = document.querySelectorAll('input[name="transaksi_id"], #update_transaksi_id');
    transaksiIdInputs.forEach(input => {
        if (input) input.value = data.id;
    });

    // Show modal
    showModal('modal-rincian-transaksi');
}

// Form Handlers
function initializeFormHandlers() {
    // Form submission handlers
    const formRincian = document.getElementById('form-rincian-transaksi');
    if (formRincian) {
        formRincian.addEventListener('submit', handleRincianSubmit);
    }

    const formUpdateStatus = document.getElementById('form-update-status');
    if (formUpdateStatus) {
        formUpdateStatus.addEventListener('submit', handleStatusUpdate);
    }
}

function handleRincianSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('transaksi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAllModals();
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan sistem');
    });
}

function handleStatusUpdate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch('transaksi.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAllModals();
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan sistem');
    });
}

// Utility Functions
function getStatusDisplay(status) {
    const statusMap = {
        'menunggu': 'Menunggu',
        'diproses': 'Diproses',
        'selesai': 'Selesai',
        'diambil': 'Diambil',
        'dibatalkan': 'Dibatalkan'
    };
    return statusMap[status] || status;
}

function getPaymentStatusDisplay(statusBayar) {
    const statusMap = {
        'belum_bayar': 'Belum Bayar',
        'dp': 'DP',
        'lunas': 'Lunas'
    };
    return statusMap[statusBayar] || statusBayar;
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleString('id-ID');
}

// Print and WhatsApp Functions
function printNota(transaksiId) {
    const printWindow = window.open(`print_nota.php?id=${transaksiId}`, '_blank');
    printWindow.addEventListener('load', function() {
        printWindow.print();
    });
}

function kirimWhatsApp(transaksiId) {
    // Implementation for WhatsApp integration
    const phone = prompt('Masukkan nomor WhatsApp (contoh: 081234567890):');
    if (phone) {
        const message = `Halo, transaksi dengan ID ${transaksiId} sudah siap. Terima kasih!`;
        const whatsappUrl = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
        window.open(whatsappUrl, '_blank');
    }
}