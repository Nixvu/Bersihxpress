/**
 * ======================================================
 * SCRIPT KHUSUS: owner/laporan.php
 * Meng-handle:
 * 1. Logika Tab Utama (Pendapatan, Pengeluaran, etc.)
 * 2. Logika Modal "Ekspor Laporan" 
 * 3. Logika Modal "Filter Waktu" dengan parameter URL
 * 4. Update data dinamis berdasarkan filter
 * ======================================================
 */

console.log('Loading owner-laporan.js');

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded for laporan page');
    // --- LOGIKA UNTUK TAB ---
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanels = document.querySelectorAll('[id^="tab-"]');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetPanelId = button.dataset.target;
            
            // Reset semua tab button
            tabButtons.forEach(btn => {
                btn.classList.remove('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100');
                btn.classList.add('font-medium', 'text-gray-600', 'hover:bg-gray-200');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Aktifkan tab yang diklik
            button.classList.add('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100');
            button.classList.remove('font-medium', 'text-gray-600', 'hover:bg-gray-200');
            button.setAttribute('aria-selected', 'true');
            
            // Sembunyikan semua panel
            tabPanels.forEach(panel => {
                panel.classList.add('hidden');
                panel.setAttribute('aria-hidden', 'true');
            });
            
            // Tampilkan panel yang dipilih
            const targetPanel = document.querySelector(targetPanelId);
            if (targetPanel) {
                targetPanel.classList.remove('hidden');
                targetPanel.setAttribute('aria-hidden', 'false');
            }
        });
    });

    // --- LOGIKA MODAL (UMUM) ---
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');

    function openModal(modalElement) {
        if (!modalElement) return;
        
        modalContainer.classList.remove('hidden');
        modalContainer.setAttribute('aria-hidden', 'false');
        modalBackdrop.classList.remove('opacity-0');
        document.body.style.overflow = 'hidden';
        
        modalElement.classList.remove('hidden');
        modalElement.style.transform = 'translateY(100%)';
        
        requestAnimationFrame(() => {
            modalElement.style.transform = 'translateY(0)';
        });
        
        // Focus management
        const firstFocusable = modalElement.querySelector('input, button, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (firstFocusable) {
            setTimeout(() => firstFocusable.focus(), 200);
        }
    }

    function closeModal(modalElement) {
        if (!modalElement) return;
        
        modalElement.style.transform = 'translateY(100%)';
        
        setTimeout(() => {
            modalElement.classList.add('hidden');
            const anyModalOpen = document.querySelectorAll('.modal-popup:not(.hidden)').length === 0;
            
            if (anyModalOpen) {
                modalBackdrop.classList.add('opacity-0');
                setTimeout(() => {
                    modalContainer.classList.add('hidden');
                    modalContainer.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';
                }, 200);
            }
        }, 250);
    }

    // Close modal buttons
    document.querySelectorAll('.btn-close-modal').forEach(button => {
        button.addEventListener('click', (e) => {
            const modalId = e.currentTarget.dataset.modalId;
            const modalToClose = document.getElementById(modalId);
            closeModal(modalToClose);
        });
    });

    // Close on backdrop click
    modalBackdrop?.addEventListener('click', () => {
        document.querySelectorAll('.modal-popup:not(.hidden)').forEach(closeModal);
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-popup:not(.hidden)').forEach(closeModal);
        }
    });

    // --- LOGIKA MODAL EXPORT ---
    const btnExport = document.getElementById('btn-export');
    const modalExport = document.getElementById('modal-export');
    
    btnExport?.addEventListener('click', () => {
        console.log('Export button clicked');
        openModal(modalExport);
    });

    // Radio button format selection
    document.querySelectorAll('input[name="format"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('label').forEach(label => {
                const radioInput = label.querySelector('input[name="format"]');
                const div = label.querySelector('div');
                if (radioInput && div) {
                    if (radioInput.checked) {
                        div.classList.remove('border-gray-300', 'bg-white');
                        div.classList.add('border-blue-600', 'bg-blue-50');
                        div.querySelector('svg').classList.remove('text-gray-600');
                        div.querySelector('svg').classList.add('text-blue-700');
                        div.querySelector('span').classList.remove('text-gray-600');
                        div.querySelector('span').classList.add('text-blue-700');
                    } else {
                        div.classList.add('border-gray-300', 'bg-white');
                        div.classList.remove('border-blue-600', 'bg-blue-50');
                        div.querySelector('svg').classList.add('text-gray-600');
                        div.querySelector('svg').classList.remove('text-blue-700');
                        div.querySelector('span').classList.add('text-gray-600');
                        div.querySelector('span').classList.remove('text-blue-700');
                    }
                }
            });

                // Button and form action logic
                const btnExportSubmit = document.getElementById('btn-export-submit');
                const formExport = document.getElementById('form-export');
                if (this.value === 'csv') {
                    btnExportSubmit.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    btnExportSubmit.classList.add('bg-green-600', 'hover:bg-green-700');
                    btnExportSubmit.textContent = 'Ekspor CSV';
                    formExport.setAttribute('action', 'export_csv.php');
                } else {
                    btnExportSubmit.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btnExportSubmit.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    btnExportSubmit.textContent = 'Unduh Laporan';
                    formExport.setAttribute('action', 'export_pdf.php');
                }
        });
    });

    // --- LOGIKA MODAL FILTER TANGGAL ---
    const btnFilterTanggal = document.getElementById('btn-filter-tanggal');
    const modalFilterTanggal = document.getElementById('modal-filter-tanggal');

    btnFilterTanggal?.addEventListener('click', () => {
        openModal(modalFilterTanggal);
    });

    // Quick filter buttons
    document.querySelectorAll('.btn-quick-filter').forEach(button => {
        button.addEventListener('click', (e) => {
            const filterText = e.currentTarget.dataset.filterText;
            let filterType = 'bulan_ini';
            
            // Map filter text to filter type
            if (filterText.includes('Hari Ini')) filterType = 'hari_ini';
            else if (filterText.includes('7 Hari')) filterType = '7_hari';
            else if (filterText.includes('Bulan Ini')) filterType = 'bulan_ini';
            else if (filterText.includes('Tahun Ini')) filterType = 'tahun_ini';
            
            // Update URL and reload page
            const url = new URL(window.location);
            url.searchParams.set('filter', filterType);
            url.searchParams.delete('tanggal_mulai');
            url.searchParams.delete('tanggal_selesai');
            
            // Show loading
            showLoading();
            
            window.location.href = url.toString();
        });
    });

    // Custom date filter
    const formFilter = document.getElementById('form-filter');
    formFilter?.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const tanggalMulai = document.getElementById('filter_tanggal_mulai').value;
        const tanggalSelesai = document.getElementById('filter_tanggal_selesai').value;
        
        if (!tanggalMulai || !tanggalSelesai) {
            showToast('Harap pilih tanggal mulai dan selesai', 'warning');
            return;
        }
        
        if (tanggalMulai > tanggalSelesai) {
            showToast('Tanggal mulai tidak boleh lebih besar dari tanggal selesai', 'error');
            return;
        }
        
        // Update URL and reload page
        const url = new URL(window.location);
        url.searchParams.set('filter', 'kustom');
        url.searchParams.set('tanggal_mulai', tanggalMulai);
        url.searchParams.set('tanggal_selesai', tanggalSelesai);
        
        // Show loading
        showLoading();
        
        window.location.href = url.toString();
    });


    // Tidak perlu event submit formExport, biarkan form default action ke test_eksport.php



    // Helper functions
    function showLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    }

    function showSuccessMessage(message) {
        showToast(message, 'success');
    }

    function showToast(message, type = 'info') {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
        
        switch (type) {
            case 'success':
                toast.className += ' bg-green-500 text-white';
                break;
            case 'error':
                toast.className += ' bg-red-500 text-white';
                break;
            case 'warning':
                toast.className += ' bg-yellow-500 text-white';
                break;
            default:
                toast.className += ' bg-blue-500 text-white';
        }

        toast.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium">${message}</span>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
            toast.classList.add('translate-x-0');
        }, 10);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }

    // Initialize page state based on URL parameters
    function initializePageState() {
        const urlParams = new URLSearchParams(window.location.search);
        const currentFilter = urlParams.get('filter') || 'bulan_ini';
        
        // Set active quick filter button
        document.querySelectorAll('.btn-quick-filter').forEach(btn => {
            btn.classList.remove('bg-blue-100', 'text-blue-700', 'font-bold');
            btn.classList.add('bg-gray-100', 'text-gray-700', 'font-medium');
        });
        
        // Find and activate corresponding filter button
        const activeFilterMap = {
            'hari_ini': 'Filter: Hari Ini',
            '7_hari': 'Filter: 7 Hari Terakhir',
            'bulan_ini': 'Filter: Bulan Ini',
            'tahun_ini': 'Filter: Tahun Ini'
        };
        
        if (activeFilterMap[currentFilter]) {
            const activeBtn = document.querySelector(`[data-filter-text="${activeFilterMap[currentFilter]}"]`);
            if (activeBtn) {
                activeBtn.classList.add('bg-blue-100', 'text-blue-700', 'font-bold');
                activeBtn.classList.remove('bg-gray-100', 'text-gray-700', 'font-medium');
            }
        }
        
        // Set custom date values if applicable
        if (currentFilter === 'kustom') {
            const tanggalMulai = urlParams.get('tanggal_mulai');
            const tanggalSelesai = urlParams.get('tanggal_selesai');
            
            if (tanggalMulai) document.getElementById('filter_tanggal_mulai').value = tanggalMulai;
            if (tanggalSelesai) document.getElementById('filter_tanggal_selesai').value = tanggalSelesai;
        }
    }

    // Initialize page state
    initializePageState();
});