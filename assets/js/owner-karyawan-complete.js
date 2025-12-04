/**
 * Owner Karyawan Management Script
 * Handles employee CRUD operations, modal interactions, search functionality,
 * and salary calculations following layanan.php patterns
 */

// DOM element references - declared at module level for performance
const modalContainer = document.getElementById('modal-container');
const modalBackdrop = document.getElementById('modal-backdrop');
const searchInput = document.getElementById('search-input');
const clearSearchBtn = document.getElementById('clear-search');

// Employee data store for modal operations
let selectedEmployee = null;

/**
 * Modal Management
 * Provides centralized control for opening/closing modals with proper animations
 */
const ModalManager = {
    isAnimating: false,

    open(modalId, employee = null) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.warn(`Modal ${modalId} not found`);
            this.isAnimating = false;
            return;
        }

        // Store selected employee data
        if (employee) {
            selectedEmployee = employee;
            this.populateModalData(modalId, employee);
        }

        // Show container and animate
        modalContainer.classList.remove('hidden');
        modal.style.display = 'flex';
        
        // Force reflow for smooth animation
        modalContainer.offsetHeight;
        
        requestAnimationFrame(() => {
            modalBackdrop.style.opacity = '1';
            modal.style.transform = 'translateY(0)';
            this.isAnimating = false;
        });

        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    },

    close(modalId) {
        if (this.isAnimating) return;
        
        this.isAnimating = true;
        const modal = document.getElementById(modalId);
        
        if (!modal) {
            console.warn(`Modal ${modalId} not found`);
            this.isAnimating = false;
            return;
        }

        // Animate out
        modalBackdrop.style.opacity = '0';
        modal.style.transform = 'translateY(100%)';

        setTimeout(() => {
            modal.style.display = 'none';
            modalContainer.classList.add('hidden');
            document.body.style.overflow = '';
            selectedEmployee = null;
            this.isAnimating = false;
        }, 200);
    },

    closeAll() {
        const openModals = modalContainer.querySelectorAll('.modal-popup[style*="flex"]');
        openModals.forEach(modal => {
            this.close(modal.id);
        });
    },

    populateModalData(modalId, employee) {
        switch (modalId) {
            case 'modal-opsi-karyawan':
                this.populateEmployeeOptionsModal(employee);
                break;
            case 'modal-edit-karyawan':
                this.populateEditEmployeeModal(employee);
                break;
            case 'modal-info-kinerja':
                this.populatePerformanceModal(employee);
                break;
            case 'modal-proses-gaji':
                this.populateSalaryModal(employee);
                break;
            case 'modal-reset-password':
                this.populateResetPasswordModal(employee);
                break;
            case 'modal-hapus-karyawan':
                this.populateDeleteModal(employee);
                break;
        }
    },

    populateEmployeeOptionsModal(employee) {
        const namaElement = document.getElementById('opsi-karyawan-nama');
        if (namaElement) {
            namaElement.textContent = employee.nama;
        }
    },

    populateEditEmployeeModal(employee) {
        const form = document.getElementById('form-edit-karyawan');
        if (!form) return;

        // Populate form fields
        const fields = {
            'edit_karyawan_id': employee.id,
            'edit_nama_lengkap': employee.nama,
            'edit_no_telepon': employee.telepon || '',
            'edit_gaji_pokok': employee.gaji
        };

        Object.entries(fields).forEach(([fieldId, value]) => {
            const field = document.getElementById(fieldId);
            if (field) field.value = value;
        });
    },

    populatePerformanceModal(employee) {
        document.getElementById('kinerja-nama').textContent = employee.nama;
        document.getElementById('kinerja-total-transaksi').textContent = employee.totalTransaksi || '0';
        document.getElementById('kinerja-transaksi-bulan').textContent = employee.transaksiiBulan || '0';
        document.getElementById('kinerja-bergabung').textContent = employee.bergabung || '-';
        document.getElementById('kinerja-status').textContent = employee.status || 'aktif';
        document.getElementById('kinerja-gaji').textContent = formatCurrency(employee.gaji || 0);
    },

    populateSalaryModal(employee) {
        document.getElementById('salary_karyawan_id').value = employee.id;
        document.getElementById('proses-gaji-nama').textContent = employee.nama;
        document.getElementById('input_gaji_pokok').value = employee.gaji;
        
        // Calculate default values
        this.calculateSalary();
    },

    populateResetPasswordModal(employee) {
        document.getElementById('reset_karyawan_id').value = employee.id;
        document.getElementById('reset-password-nama').textContent = employee.nama;
        
        // Clear password field
        const passwordField = document.getElementById('reset_new_password');
        if (passwordField) passwordField.value = '';
    },

    populateDeleteModal(employee) {
        document.getElementById('delete_karyawan_id').value = employee.id;
        document.getElementById('delete-karyawan-nama').textContent = employee.nama;
    }
};

/**
 * Search Functionality
 * Implements real-time search with URL synchronization
 */
const SearchManager = {
    debounceTimer: null,

    init() {
        if (!searchInput) return;

        // Handle search input with debouncing
        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => {
                this.performSearch(e.target.value.trim());
            }, 300);
        });

        // Handle clear search
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', () => {
                this.clearSearch();
            });
        }

        // Handle enter key
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(this.debounceTimer);
                this.performSearch(searchInput.value.trim());
            }
        });
    },

    performSearch(query) {
        // Update URL without page reload
        const url = new URL(window.location);
        if (query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }
        window.history.replaceState({}, '', url);

        // Reload page to show filtered results
        window.location.reload();
    },

    clearSearch() {
        searchInput.value = '';
        this.performSearch('');
    }
};

/**
 * Salary Calculator
 * Calculates total salary including bonuses and deductions
 */
const SalaryCalculator = {
    calculate() {
        const gajiPokok = parseFloat(document.getElementById('input_gaji_pokok')?.value) || 0;
        const bonus = parseFloat(document.getElementById('input_bonus')?.value) || 0;
        const potongan = parseFloat(document.getElementById('input_potongan')?.value) || 0;
        
        const totalGaji = gajiPokok + bonus - potongan;
        
        // Update display
        const totalElement = document.getElementById('total_gaji_display');
        const hiddenInput = document.getElementById('input_total_gaji');
        
        if (totalElement) {
            totalElement.textContent = formatCurrency(Math.max(0, totalGaji));
        }
        if (hiddenInput) {
            hiddenInput.value = Math.max(0, totalGaji);
        }
        
        return Math.max(0, totalGaji);
    },

    init() {
        // Bind calculation to input changes
        const inputs = ['input_gaji_pokok', 'input_bonus', 'input_potongan'];
        inputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', () => this.calculate());
            }
        });
    }
};

/**
 * Form Validation
 * Provides client-side validation for all forms
 */
const FormValidator = {
    validateAddEmployee(form) {
        const errors = [];
        const namaLengkap = form.querySelector('#tambah_nama_lengkap')?.value?.trim();
        const email = form.querySelector('#tambah_email')?.value?.trim();
        const password = form.querySelector('#tambah_password')?.value;
        const gajiPokok = parseFloat(form.querySelector('#tambah_gaji_pokok')?.value) || 0;

        if (!namaLengkap) errors.push('Nama lengkap wajib diisi');
        if (!email) errors.push('Email wajib diisi');
        if (!this.isValidEmail(email)) errors.push('Format email tidak valid');
        if (!password || password.length < 6) errors.push('Password minimal 6 karakter');
        if (gajiPokok < 0) errors.push('Gaji pokok tidak boleh negatif');

        return errors;
    },

    validateEditEmployee(form) {
        const errors = [];
        const namaLengkap = form.querySelector('#edit_nama_lengkap')?.value?.trim();
        const gajiPokok = parseFloat(form.querySelector('#edit_gaji_pokok')?.value) || 0;

        if (!namaLengkap) errors.push('Nama lengkap wajib diisi');
        if (gajiPokok < 0) errors.push('Gaji pokok tidak boleh negatif');

        return errors;
    },

    validateResetPassword(form) {
        const errors = [];
        const password = form.querySelector('#reset_new_password')?.value;

        if (!password || password.length < 6) {
            errors.push('Password minimal 6 karakter');
        }

        return errors;
    },

    validateSalaryProcessing(form) {
        const errors = [];
        const totalGaji = parseFloat(form.querySelector('#input_total_gaji')?.value) || 0;

        if (totalGaji <= 0) {
            errors.push('Total gaji harus lebih dari 0');
        }

        return errors;
    },

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    showErrors(errors) {
        if (errors.length > 0) {
            alert(errors.join('\n'));
            return false;
        }
        return true;
    }
};

/**
 * Event Handlers
 * Centralized event handling for all interactions
 */
const EventHandlers = {
    init() {
        this.bindEmployeeCardClicks();
        this.bindModalTriggers();
        this.bindModalClosers();
        this.bindFormSubmissions();
        this.bindBackdropClick();
    },

    bindEmployeeCardClicks() {
        document.querySelectorAll('.btn-buka-opsi').forEach(button => {
            button.addEventListener('click', (e) => {
                const employee = this.extractEmployeeData(button);
                ModalManager.open('modal-opsi-karyawan', employee);
            });
        });
    },

    bindModalTriggers() {
        // Add employee button
        const addBtn = document.getElementById('btn-tambah-karyawan');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                ModalManager.open('modal-tambah-karyawan');
            });
        }

        // Modal option buttons
        this.bindModalOption('btn-edit-karyawan', 'modal-edit-karyawan');
        this.bindModalOption('btn-info-kinerja', 'modal-info-kinerja');
        this.bindModalOption('btn-proses-gaji', 'modal-proses-gaji');
        this.bindModalOption('btn-reset-password', 'modal-reset-password');
        this.bindModalOption('btn-hapus-karyawan', 'modal-hapus-karyawan');
    },

    bindModalOption(buttonId, modalId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', () => {
                ModalManager.close('modal-opsi-karyawan');
                setTimeout(() => {
                    ModalManager.open(modalId, selectedEmployee);
                }, 200);
            });
        }
    },

    bindModalClosers() {
        // Close button handlers
        document.querySelectorAll('.btn-close-modal').forEach(button => {
            button.addEventListener('click', (e) => {
                const modalId = button.getAttribute('data-modal-id');
                if (modalId) {
                    ModalManager.close(modalId);
                }
            });
        });
    },

    bindFormSubmissions() {
        // Add employee form
        const addForm = document.getElementById('form-tambah-karyawan');
        if (addForm) {
            addForm.addEventListener('submit', (e) => {
                const errors = FormValidator.validateAddEmployee(addForm);
                if (!FormValidator.showErrors(errors)) {
                    e.preventDefault();
                }
            });
        }

        // Edit employee form
        const editForm = document.getElementById('form-edit-karyawan');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                const errors = FormValidator.validateEditEmployee(editForm);
                if (!FormValidator.showErrors(errors)) {
                    e.preventDefault();
                }
            });
        }

        // Reset password form
        const resetForm = document.getElementById('form-reset-password');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => {
                const errors = FormValidator.validateResetPassword(resetForm);
                if (!FormValidator.showErrors(errors)) {
                    e.preventDefault();
                }
            });
        }

        // Salary processing form
        const salaryForm = document.getElementById('form-proses-gaji');
        if (salaryForm) {
            salaryForm.addEventListener('submit', (e) => {
                const errors = FormValidator.validateSalaryProcessing(salaryForm);
                if (!FormValidator.showErrors(errors)) {
                    e.preventDefault();
                }
            });
        }
    },

    bindBackdropClick() {
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', () => {
                ModalManager.closeAll();
            });
        }
    },

    extractEmployeeData(button) {
        return {
            id: button.getAttribute('data-id'),
            nama: button.getAttribute('data-nama'),
            email: button.getAttribute('data-email'),
            telepon: button.getAttribute('data-telepon'),
            gaji: parseFloat(button.getAttribute('data-gaji')) || 0,
            status: button.getAttribute('data-status'),
            bergabung: button.getAttribute('data-bergabung'),
            totalTransaksi: parseInt(button.getAttribute('data-total-transaksi')) || 0,
            transaksiiBulan: parseInt(button.getAttribute('data-transaksi-bulan')) || 0
        };
    }
};

/**
 * Utility Functions
 */
function formatCurrency(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

/**
 * Initialize all functionality when DOM is ready
 */
document.addEventListener('DOMContentLoaded', () => {
    // Initialize managers
    SearchManager.init();
    SalaryCalculator.init();
    EventHandlers.init();

    // Initialize icons if feather is available
    if (typeof feather !== 'undefined') {
        feather.replace();
    }

    console.log('Owner Karyawan Management initialized successfully');
});

// Global access for debugging
window.ModalManager = ModalManager;
window.SearchManager = SearchManager;
window.SalaryCalculator = SalaryCalculator;