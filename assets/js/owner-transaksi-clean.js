/**
 * Owner Transaksi JavaScript
 * Manages transaction functionality for owner panel
 */

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTransactionPage();
});

function initializeTransactionPage() {
    initializeModalSystem();
    initializeTransactionButtons();
    initializeFormHandlers();
}

// Modal System Management
function initializeModalSystem() {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    
    if (!modalContainer || !modalBackdrop) {
        console.warn('Modal container or backdrop not found');
        return;
    }

    // Backdrop click to close - perbaiki supaya tidak conflik dengan card clicks
    modalBackdrop.addEventListener('click', function(e) {
        if (e.target === modalBackdrop) {
            closeAllModals();
        }
    });

    // Close modal buttons - comprehensive selector
    const closeButtons = document.querySelectorAll('.btn-close-modal, .btn-tutup-modal, .btn-batal-modal, [data-action="close-modal"]');
    closeButtons.forEach(button => {
        // Remove any existing onclick first
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
        
        // Check if any other modal is still open
        const openModals = document.querySelectorAll('.modal-popup:not(.hidden)');
        if (openModals.length === 0) {
            closeAllModals();
        }
    }, 300);
}

function closeAllModals() {
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    
    if (!modalContainer) return;

    // Animate backdrop fade
    modalBackdrop.classList.remove('opacity-100');
    
    // Hide all modals
    const allModals = document.querySelectorAll('.modal-popup');
    allModals.forEach(modal => {
        modal.style.transform = 'translateY(100%)';
        modal.classList.add('hidden');
    });

    setTimeout(() => {
        modalContainer.classList.add('hidden');
    }, 300);
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

    // Transaction detail buttons - menggunakan event delegation untuk cards yang sudah difilter
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

    console.log('Transaction buttons initialization complete');
}

    const btnTransaksiTemplate = document.getElementById('btn-transaksi-template');
    if (btnTransaksiTemplate) {
        btnTransaksiTemplate.addEventListener('click', function() {
            // Redirect ke halaman template
            window.location.href = 'template.php';
        });
    }

    const btnPengeluaran = document.getElementById('btn-pengeluaran');
    if (btnPengeluaran) {
        btnPengeluaran.addEventListener('click', function() {
            closeModal('modal-buat-transaksi');
            setTimeout(() => showModal('modal-pengeluaran'), 300);
        });
    }

    // Status update buttons
    const btnBukaOpsi = document.getElementById('btn-buka-opsi');
    if (btnBukaOpsi) {
        btnBukaOpsi.addEventListener('click', function() {
            showModal('modal-opsi-lanjutan');
        });
    }

    // Print nota button
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-cetak-nota')) {
            const transaksiId = document.getElementById('update_transaksi_id').value;
            if (transaksiId) {
                printNota(transaksiId);
            }
        }
    });

    // WhatsApp button
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-kirim-wa')) {
            const transaksiId = document.getElementById('update_transaksi_id').value;
            if (transaksiId) {
                kirimWhatsApp(transaksiId);
            }
        }
    });

    // Action button handler
    const btnAksiUtama = document.getElementById('btn-aksi-utama');
    if (btnAksiUtama) {
        btnAksiUtama.addEventListener('click', function() {
            const transaksiId = document.getElementById('update_transaksi_id').value;
            const currentStatus = this.dataset.currentStatus;
            
            if (transaksiId && currentStatus) {
                updateTransactionStatusQuick(transaksiId, currentStatus);
            }
        });
    }


function showTransactionDetail(data) {
    // Populate transaction detail modal
    document.getElementById('rincian-nama-pelanggan').textContent = data.pelanggan || 'Guest';
    document.getElementById('rincian-no-nota').textContent = `ID Nota #${data.noNota}`;
    document.getElementById('rincian-total-tagihan').textContent = data.total;
    document.getElementById('rincian-status-bayar').textContent = getPaymentStatusDisplay(data.statusBayar);
    document.getElementById('rincian-status-pesanan').textContent = getStatusDisplay(data.status);
    
    if (data.tanggalMasuk) {
        document.getElementById('rincian-tanggal-masuk').textContent = formatDateTime(data.tanggalMasuk);
    }
    
    if (data.tanggalSelesai) {
        document.getElementById('rincian-tanggal-selesai').textContent = formatDateTime(data.tanggalSelesai);
    } else {
        document.getElementById('rincian-tanggal-selesai').textContent = 'Belum ditentukan';
    }

    // Update hidden form fields for status update
    document.getElementById('update_transaksi_id').value = data.id;

    // Update action button based on status
    updateActionButton(data.status);

    showModal('modal-rincian');
}

function updateActionButton(status) {
    const btnAksiUtama = document.getElementById('btn-aksi-utama');
    if (!btnAksiUtama) return;

    // Store current status for the button handler
    btnAksiUtama.dataset.currentStatus = status;

    switch (status) {
        case 'pending':
            btnAksiUtama.textContent = 'Mulai Proses';
            btnAksiUtama.className = 'w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 shadow-sm';
            btnAksiUtama.disabled = false;
            break;
        case 'proses':
            btnAksiUtama.textContent = 'Tandai Selesai';
            btnAksiUtama.className = 'w-full bg-green-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-green-700 shadow-sm';
            btnAksiUtama.disabled = false;
            break;
        case 'selesai':
            btnAksiUtama.textContent = 'Tandai Diambil';
            btnAksiUtama.className = 'w-full bg-gray-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-gray-700 shadow-sm';
            btnAksiUtama.disabled = false;
            break;
        case 'diambil':
            btnAksiUtama.textContent = 'Transaksi Selesai';
            btnAksiUtama.className = 'w-full bg-gray-400 text-white font-bold py-3 px-4 rounded-lg cursor-not-allowed';
            btnAksiUtama.disabled = true;
            break;
        default:
            btnAksiUtama.textContent = 'Lihat Detail';
            btnAksiUtama.className = 'w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-blue-700 shadow-sm';
            btnAksiUtama.disabled = false;
    }
}

// Form Handlers
function initializeFormHandlers() {
    // Transaction creation form
    const formBuatTransaksi = document.getElementById('form-buat-transaksi');
    if (formBuatTransaksi) {
        formBuatTransaksi.addEventListener('submit', function(e) {
            if (!validateTransactionForm()) {
                e.preventDefault();
                return false;
            }
            showLoadingState();
        });
    }

    // Expense form
    const formPengeluaran = document.getElementById('form-pengeluaran');
    if (formPengeluaran) {
        formPengeluaran.addEventListener('submit', function(e) {
            if (!validateExpenseForm()) {
                e.preventDefault();
                return false;
            }
            showLoadingState();
        });
    }

    // Status update form
    const formOpsiLanjutan = document.getElementById('form-opsi-lanjutan');
    if (formOpsiLanjutan) {
        formOpsiLanjutan.addEventListener('submit', function(e) {
            showLoadingState();
        });
    }
}

function validateTransactionForm() {
    const pelangganNama = document.getElementById('pelanggan_nama').value.trim();
    const totalHarga = document.getElementById('total_harga').value;
    const pelangganTelepon = document.getElementById('pelanggan_telepon').value.trim();

    if (!pelangganNama) {
        showAlert('Nama pelanggan wajib diisi.', 'error');
        document.getElementById('pelanggan_nama').focus();
        return false;
    }

    if (!totalHarga || parseFloat(totalHarga) <= 0) {
        showAlert('Total harga harus lebih besar dari 0.', 'error');
        document.getElementById('total_harga').focus();
        return false;
    }

    if (pelangganTelepon && !/^[0-9+\s-]{6,20}$/.test(pelangganTelepon)) {
        showAlert('Format nomor telepon tidak valid.', 'error');
        document.getElementById('pelanggan_telepon').focus();
        return false;
    }

    return true;
}

function validateExpenseForm() {
    const namaPengeluaran = document.getElementById('nama_pengeluaran').value.trim();
    const nominalPengeluaran = document.getElementById('nominal_pengeluaran').value;
    const tanggalPengeluaran = document.getElementById('tanggal_pengeluaran').value;

    if (!namaPengeluaran) {
        showAlert('Nama pengeluaran wajib diisi.', 'error');
        document.getElementById('nama_pengeluaran').focus();
        return false;
    }

    if (!nominalPengeluaran || parseFloat(nominalPengeluaran) <= 0) {
        showAlert('Nominal pengeluaran harus lebih besar dari 0.', 'error');
        document.getElementById('nominal_pengeluaran').focus();
        return false;
    }

    if (!tanggalPengeluaran) {
        showAlert('Tanggal pengeluaran wajib diisi.', 'error');
        document.getElementById('tanggal_pengeluaran').focus();
        return false;
    }

    return true;
}

// Utility Functions
function getStatusDisplay(status) {
    const statusMap = {
        'pending': 'Pending',
        'proses': 'Diproses',
        'selesai': 'Selesai',
        'diambil': 'Diambil',
        'batal': 'Batal'
    };
    return statusMap[status] || 'Unknown';
}

function getPaymentStatusDisplay(statusBayar) {
    const statusMap = {
        'lunas': 'Lunas',
        'sebagian': 'Sebagian',
        'belum_lunas': 'Belum Lunas'
    };
    return statusMap[statusBayar] || 'Belum Lunas';
}

function formatDateTime(dateTimeString) {
    if (!dateTimeString) return 'Belum ditentukan';
    
    const date = new Date(dateTimeString);
    const options = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleDateString('id-ID', options);
}

function showAlert(message, type = 'info') {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
    
    if (type === 'error') {
        alertDiv.className += ' bg-red-100 border border-red-200 text-red-800';
    } else if (type === 'success') {
        alertDiv.className += ' bg-green-100 border border-green-200 text-green-800';
    } else {
        alertDiv.className += ' bg-blue-100 border border-blue-200 text-blue-800';
    }
    
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <span class="flex-1">${message}</span>
            <button class="ml-2 text-current opacity-50 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                ×
            </button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Animate in
    setTimeout(() => {
        alertDiv.classList.remove('translate-x-full');
    }, 100);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        alertDiv.classList.add('translate-x-full');
        setTimeout(() => {
            if (alertDiv.parentElement) {
                alertDiv.remove();
            }
        }, 300);
    }, 5000);
}

function showLoadingState() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
}

function hideLoadingState() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
}

// Auto-hide loading on page load
window.addEventListener('load', function() {
    hideLoadingState();
});

// Initialize feather icons when modals are shown
function refreshFeatherIcons() {
    if (typeof feather !== 'undefined') {
        feather.replace();
    }
}

// Call icon refresh after modal operations
const originalShowModal = showModal;
showModal = function(modalId) {
    originalShowModal(modalId);
    setTimeout(refreshFeatherIcons, 100);
};

// Phone number formatting
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('0')) {
                value = '62' + value.substring(1);
            } else if (!value.startsWith('62')) {
                value = '62' + value;
            }
            this.value = value;
        });
    });
});

// New functions for additional features
function printNota(transaksiId) {
    if (!transaksiId) {
        showAlert('ID transaksi tidak valid.', 'error');
        return;
    }
    
    // Open print window
    const printUrl = `print_nota.php?id=${transaksiId}`;
    const printWindow = window.open(printUrl, '_blank', 'width=800,height=600,scrollbars=yes');
    
    if (!printWindow) {
        showAlert('Pop-up terblokir. Silakan izinkan pop-up untuk mencetak nota.', 'error');
    } else {
        showAlert('Membuka jendela cetak...', 'info');
    }
}

function kirimWhatsApp(transaksiId) {
    if (!transaksiId) {
        showAlert('ID transaksi tidak valid.', 'error');
        return;
    }
    
    // Get customer phone from current transaction data
    const pelangganNama = document.getElementById('rincian-nama-pelanggan').textContent;
    const noNota = document.getElementById('rincian-no-nota').textContent.replace('ID Nota #', '');
    const totalTagihan = document.getElementById('rincian-total-tagihan').textContent;
    const statusPesanan = document.getElementById('rincian-status-pesanan').textContent;
    
    // Create WhatsApp message
    const message = `Halo ${pelangganNama},\n\nInformasi Transaksi:\n📋 Nota: ${noNota}\n💰 Total: ${totalTagihan}\n📋 Status: ${statusPesanan}\n\nTerima kasih telah menggunakan layanan kami!\n\n*BersihXpress*`;
    
    // Prompt for phone number if not available
    const phoneNumber = prompt('Masukkan nomor WhatsApp pelanggan (contoh: 6281234567890):');
    
    if (phoneNumber) {
        const waUrl = `https://wa.me/${phoneNumber.replace(/\D/g, '')}?text=${encodeURIComponent(message)}`;
        window.open(waUrl, '_blank');
        showAlert('Membuka WhatsApp...', 'success');
    }
}

function updateTransactionStatusQuick(transaksiId, currentStatus) {
    if (!transaksiId || !currentStatus) return;
    
    let newStatus = '';
    let confirmMessage = '';
    
    switch (currentStatus) {
        case 'pending':
            newStatus = 'proses';
            confirmMessage = 'Mulai memproses transaksi ini?';
            break;
        case 'proses':
            newStatus = 'selesai';
            confirmMessage = 'Tandai transaksi sebagai selesai?';
            break;
        case 'selesai':
            newStatus = 'diambil';
            confirmMessage = 'Tandai transaksi sebagai sudah diambil?';
            break;
        default:
            return;
    }
    
    if (confirm(confirmMessage)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'transaksi.php';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_status';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'transaksi_id';
        idInput.value = transaksiId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = newStatus;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        showLoadingState();
        form.submit();
    }
}