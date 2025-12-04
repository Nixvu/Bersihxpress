/**
 * ======================================================
 * SCRIPT KHUSUS: owner/template.php
 * Meng-handle:
 * 1. Logika Tab (Nota Cetak & Pesan Otomatis)
 * 2. Logika Modal Edit Template Nota
 * 3. Logika Modal Edit Template Pesan (Reusable)
 * 4. Variabel dinamis untuk template pesan
 * 5. Preview real-time nota
 * ======================================================
 */

console.log('Loading owner-template.js');

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded for template page');
    
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

    // --- LOGIKA UNTUK TAB ---
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabPanels = document.querySelectorAll('[id^="tab-"]');

    // Handling tab aktif saat pertama kali load
    function setInitialActiveTab() {
        let activeBtn = document.querySelector('.tab-button.tab-button-active');
        let targetPanelId = activeBtn ? activeBtn.dataset.target : '#tab-nota';
        // Reset semua tab button
        tabButtons.forEach(btn => {
            btn.classList.remove('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100', 'border-blue-600');
            btn.classList.add('font-medium', 'text-gray-600', 'hover:bg-gray-200', 'border-transparent');
            btn.setAttribute('aria-selected', 'false');
        });
        // Aktifkan tab default
        if (activeBtn) {
            activeBtn.classList.add('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100', 'border-blue-600');
            activeBtn.classList.remove('font-medium', 'text-gray-600', 'hover:bg-gray-200', 'border-transparent');
            activeBtn.setAttribute('aria-selected', 'true');
        } else if (tabButtons.length > 0) {
            tabButtons[0].classList.add('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100', 'border-blue-600');
            tabButtons[0].classList.remove('font-medium', 'text-gray-600', 'hover:bg-gray-200', 'border-transparent');
            tabButtons[0].setAttribute('aria-selected', 'true');
            targetPanelId = tabButtons[0].dataset.target;
        }
        // Sembunyikan semua panel
        tabPanels.forEach(panel => {
            panel.classList.add('hidden');
            panel.setAttribute('aria-hidden', 'true');
        });
        // Tampilkan panel aktif
        const targetPanel = document.querySelector(targetPanelId);
        if (targetPanel) {
            targetPanel.classList.remove('hidden');
            targetPanel.setAttribute('aria-hidden', 'false');
        }
    }
    setInitialActiveTab();

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetPanelId = button.dataset.target;
            
            // Reset semua tab button
            tabButtons.forEach(btn => {
                btn.classList.remove('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100', 'border-blue-600');
                btn.classList.add('font-medium', 'text-gray-600', 'hover:bg-gray-200', 'border-transparent');
                btn.setAttribute('aria-selected', 'false');
            });
            
            // Aktifkan tab yang diklik
            button.classList.add('tab-button-active', 'font-semibold', 'text-blue-700', 'bg-blue-100', 'border-blue-600');
            button.classList.remove('font-medium', 'text-gray-600', 'hover:bg-gray-200', 'border-transparent');
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

    // --- LOGIKA MODAL EDIT NOTA ---
    const btnEditNota = document.getElementById('btn-edit-nota');
    const modalEditNota = document.getElementById('modal-edit-nota');
    
    const logoUpload = document.getElementById('logo_upload');
    const logoFormPreview = document.getElementById('logo-form-preview');
    const logoNotaPreview = document.getElementById('logo-preview');
    const headerInput = document.getElementById('nota_header');
    const footerInput = document.getElementById('nota_footer');
    const headerPreview = document.getElementById('header-preview');
    const footerPreview = document.getElementById('footer-preview');

    btnEditNota?.addEventListener('click', () => {
        openModal(modalEditNota);
    });

    function updateNotaPreview() {
        if (headerInput && headerPreview) {
            headerPreview.textContent = headerInput.value + '\n--------------------------------';
        }
        if (footerInput && footerPreview) {
            footerPreview.textContent = footerInput.value;
        }
    }
    
    headerInput?.addEventListener('input', updateNotaPreview);
    footerInput?.addEventListener('input', updateNotaPreview);
    
    logoUpload?.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const imgUrl = e.target.result;
                if (logoFormPreview) logoFormPreview.src = imgUrl; 
                if (logoNotaPreview) logoNotaPreview.src = imgUrl; 
            };
            reader.readAsDataURL(file);
        }
    });

    // --- LOGIKA MODAL EDIT PESAN ---
    const modalEditPesan = document.getElementById('modal-edit-pesan');
    const modalPesanTitle = document.getElementById('modal-pesan-title');
    const pesanTemplateInput = document.getElementById('pesan_template');
    const pesanJenisInput = document.getElementById('pesan_jenis');
    
    const jenisLabels = {
        'masuk': 'Pesanan Diterima',
        'proses': 'Pesanan Diproses',
        'selesai': 'Pesanan Siap Diambil',
        'pembayaran': 'Pembayaran Selesai'
    };
    
    document.querySelectorAll('.btn-edit-pesan').forEach(button => {
        button.addEventListener('click', () => {
            const jenis = button.dataset.jenis;
            const title = button.dataset.title;
            
            if (modalPesanTitle) modalPesanTitle.textContent = `Edit ${title}`;
            if (pesanJenisInput) pesanJenisInput.value = jenis;
            
            // Load current template
            if (window.templateData && window.templateData.pesan && window.templateData.pesan[jenis]) {
                if (pesanTemplateInput) pesanTemplateInput.value = window.templateData.pesan[jenis];
            }
            
            openModal(modalEditPesan);
        });
    });

    // --- LOGIKA VARIABEL DINAMIS ---
    document.querySelectorAll('.btn-variable').forEach(button => {
        button.addEventListener('click', () => {
            const variable = button.dataset.variable;
            
            // Insert variable at cursor position
            if (pesanTemplateInput) {
                const start = pesanTemplateInput.selectionStart;
                const end = pesanTemplateInput.selectionEnd;
                const text = pesanTemplateInput.value;
                
                pesanTemplateInput.value = text.substring(0, start) + variable + text.substring(end);
                pesanTemplateInput.setSelectionRange(start + variable.length, start + variable.length);
                pesanTemplateInput.focus();
            }
            
            // Show toast
            showToast('Variabel ditambahkan!', 'success');
        });
    });

    // --- FORM SUBMISSIONS ---
    const formEditNota = document.getElementById('form-edit-nota');
    const formEditPesan = document.getElementById('form-edit-pesan');

    formEditNota?.addEventListener('submit', (e) => {
        e.preventDefault();
        showLoading();
        setTimeout(() => {
            formEditNota.submit();
        }, 500);
    });

    formEditPesan?.addEventListener('submit', (e) => {
        e.preventDefault();
        showLoading();
        setTimeout(() => {
            formEditPesan.submit();
        }, 500);
    });

    // Helper functions
    function showLoading() {
        const loadingOverlay = document.getElementById('loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
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

        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('translate-x-0');
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.parentElement.removeChild(toast);
                }
            }, 300);
        }, 3000);
    }
});