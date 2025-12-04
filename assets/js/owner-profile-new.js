/**
 * ======================================================
 * SCRIPT UNTUK: owner/profile.php - Dynamic Profile Dashboard
 * Meng-handle:
 * 1. Load data profil dinamis dari API (optional, fallback to PHP)
 * 2. Chart.js untuk visualisasi data
 * 3. Modal CRUD untuk edit bisnis dan owner
 * 4. Logout functionality
 * ======================================================
 */

let chartData = {};
let charts = {};

document.addEventListener('DOMContentLoaded', () => {
    // Setup basic functionality first
    setupEventListeners();
    NavigationManager.init();
    
    // Try to enhance with dynamic data if possible
    loadDynamicData();
});

// ======= INITIALIZATION =======
function loadDynamicData() {
    // Try to load chart data for visualization
    loadChartData()
    .then(() => {
        initializeCharts();
    })
    .catch(error => {
        console.log('Using empty charts, dynamic loading failed:', error.message);
        initializeEmptyCharts();
    });
}

// ======= API CALLS (OPTIONAL) =======
async function loadChartData() {
    try {
        const response = await fetch('api/query-profile.php?action=get_chart_data');
        if (!response.ok) throw new Error('Network error');
        
        const result = await response.json();
        if (!result.success) throw new Error(result.message);
        
        chartData = result.data;
        return result.data;
    } catch (error) {
        console.log('Chart data not available, using empty charts');
        chartData = {
            pendapatan_harian: [],
            transaksi_bulanan: [],
            layanan_populer: []
        };
        return chartData;
    }
}

// ======= CHART FUNCTIONS =======
function initializeCharts() {
    initializeDailyRevenueChart();
    initializeMonthlyTransactionChart();
    initializePopularServicesChart();
}

function initializeEmptyCharts() {
    chartData = {
        pendapatan_harian: generateEmptyDailyData(),
        transaksi_bulanan: generateEmptyMonthlyData(),
        layanan_populer: []
    };
    initializeCharts();
}

function generateEmptyDailyData() {
    const data = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        data.push({
            tanggal: date.toISOString().split('T')[0],
            pendapatan: 0
        });
    }
    return data;
}

function generateEmptyMonthlyData() {
    const data = [];
    for (let i = 5; i >= 0; i--) {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        const monthStr = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        data.push({
            bulan: monthStr,
            jumlah_transaksi: 0
        });
    }
    return data;
}

function initializeDailyRevenueChart() {
    const ctx = document.getElementById('chartPendapatanHarian');
    if (!ctx) return;
    
    try {
        const data = chartData.pendapatan_harian || generateEmptyDailyData();
        const labels = data.map(item => formatDate(item.tanggal, 'short'));
        const values = data.map(item => parseFloat(item.pendapatan || 0));
        
        charts.dailyRevenue = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: values,
                    borderColor: 'rgb(37, 99, 235)',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value, false);
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating daily revenue chart:', error);
        ctx.parentElement.innerHTML = '<p class="text-center text-gray-500 py-8">Grafik tidak dapat dimuat</p>';
    }
}

function initializeMonthlyTransactionChart() {
    const ctx = document.getElementById('chartTransaksiBulanan');
    if (!ctx) return;
    
    try {
        const data = chartData.transaksi_bulanan || generateEmptyMonthlyData();
        const labels = data.map(item => formatMonth(item.bulan));
        const values = data.map(item => parseInt(item.jumlah_transaksi || 0));
        
        charts.monthlyTransaction = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: values,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating monthly transaction chart:', error);
        ctx.parentElement.innerHTML = '<p class="text-center text-gray-500 py-8">Grafik tidak dapat dimuat</p>';
    }
}

function initializePopularServicesChart() {
    const ctx = document.getElementById('chartLayananPopuler');
    if (!ctx) return;
    
    try {
        const data = chartData.layanan_populer || [];
        
        if (data.length === 0) {
            ctx.parentElement.innerHTML = '<p class="text-center text-gray-500 py-8">Belum ada data layanan</p>';
            return;
        }
        
        const labels = data.map(item => item.nama_layanan);
        const values = data.map(item => parseInt(item.jumlah_transaksi || 0));
        
        const colors = [
            'rgba(239, 68, 68, 0.8)',
            'rgba(245, 158, 11, 0.8)', 
            'rgba(34, 197, 94, 0.8)',
            'rgba(59, 130, 246, 0.8)',
            'rgba(139, 92, 246, 0.8)'
        ];
        
        charts.popularServices = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, data.length),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error creating popular services chart:', error);
        ctx.parentElement.innerHTML = '<p class="text-center text-gray-500 py-8">Grafik tidak dapat dimuat</p>';
    }
}

// ======= EVENT LISTENERS =======
function setupEventListeners() {
    // Edit buttons
    const editBusinessBtn = document.getElementById('btn-edit-bisnis');
    const editOwnerBtn = document.getElementById('btn-edit-owner');
    const logoutBtn = document.getElementById('btn-logout');
    
    if (editBusinessBtn) editBusinessBtn.addEventListener('click', openEditBusinessModal);
    if (editOwnerBtn) editOwnerBtn.addEventListener('click', openEditOwnerModal);
    if (logoutBtn) logoutBtn.addEventListener('click', openLogoutModal);
    
    // Form submissions
    const businessForm = document.getElementById('form-edit-bisnis');
    const ownerForm = document.getElementById('form-edit-owner');
    
    if (businessForm) businessForm.addEventListener('submit', handleBusinessUpdate);
    if (ownerForm) ownerForm.addEventListener('submit', handleOwnerUpdate);
    
    // Modal close events
    setupModalCloseEvents();
}

function setupModalCloseEvents() {
    // Close modal when clicking backdrop
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                closeModal(overlay.id);
            }
        });
    });
    
    // Close modal when pressing Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal-overlay:not(.hidden)');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
}

// ======= MODAL FUNCTIONS =======
function openEditBusinessModal() {
    // Get current values from displayed data
    const nama = document.getElementById('nama-bisnis').textContent.trim();
    const alamat = document.getElementById('alamat-bisnis').textContent.trim();
    const telepon = document.getElementById('telepon-bisnis').textContent.trim();
    
    // Fill form with current data
    document.getElementById('edit-nama-bisnis').value = nama !== '-' ? nama : '';
    document.getElementById('edit-alamat-bisnis').value = alamat !== '-' ? alamat : '';
    document.getElementById('edit-telepon-bisnis').value = telepon !== '-' ? telepon : '';
    
    openModal('modal-edit-bisnis');
}

function openEditOwnerModal() {
    // Get current values from displayed data
    const nama = document.getElementById('nama-pemilik').textContent.trim();
    
    // Fill form with current data
    document.getElementById('edit-nama-pemilik').value = nama !== '-' ? nama : '';
    document.getElementById('edit-telepon-pemilik').value = '';
    
    openModal('modal-edit-owner');
}

function openLogoutModal() {
    openModal('modal-logout');
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Focus first input if exists
        const firstInput = modal.querySelector('input, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        
        // Clear form loading state if exists
        const form = modal.querySelector('form');
        if (form) {
            clearFormLoading(form);
        }
    }
}

// ======= FORM HANDLERS =======
async function handleBusinessUpdate(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'update_business');
    
    setFormLoading(form, true);
    
    try {
        const response = await fetch('api/query-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        // Update display with new data
        const nama = formData.get('nama_bisnis');
        const alamat = formData.get('alamat');
        const telepon = formData.get('no_telepon');
        
        document.getElementById('nama-bisnis').textContent = nama;
        document.getElementById('alamat-bisnis').textContent = alamat || '-';
        document.getElementById('telepon-bisnis').textContent = telepon || '-';
        
        closeModal('modal-edit-bisnis');
        showToast('Data bisnis berhasil diperbarui', 'success');
        
    } catch (error) {
        console.error('Error updating business:', error);
        showToast(error.message || 'Gagal memperbarui data bisnis', 'error');
    } finally {
        setFormLoading(form, false);
    }
}

async function handleOwnerUpdate(e) {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'update_owner');
    
    setFormLoading(form, true);
    
    try {
        const response = await fetch('api/query-profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        // Update display with new data
        const nama = formData.get('nama_lengkap');
        document.getElementById('nama-pemilik').textContent = nama;
        
        closeModal('modal-edit-owner');
        showToast('Data profil berhasil diperbarui', 'success');
        
    } catch (error) {
        console.error('Error updating owner:', error);
        showToast(error.message || 'Gagal memperbarui data profil', 'error');
    } finally {
        setFormLoading(form, false);
    }
}

function confirmLogout() {
    window.location.href = '../../logout.php';
}

// ======= UI HELPER FUNCTIONS =======
function setFormLoading(form, loading) {
    const submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;
    
    const btnText = submitButton.querySelector('.btn-text');
    const btnLoading = submitButton.querySelector('.btn-loading');
    
    if (loading) {
        submitButton.disabled = true;
        if (btnText) btnText.classList.add('hidden');
        if (btnLoading) btnLoading.classList.remove('hidden');
    } else {
        submitButton.disabled = false;
        if (btnText) btnText.classList.remove('hidden');
        if (btnLoading) btnLoading.classList.add('hidden');
    }
}

function clearFormLoading(form) {
    setFormLoading(form, false);
}

function showToast(message, type = 'info') {
    // Create toast element if not exists
    let toast = document.getElementById('toast-notification');
    if (!toast) {
        toast = createToastElement();
        document.body.appendChild(toast);
    }
    
    // Set message and style
    toast.textContent = message;
    toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white text-sm z-50 transform transition-all duration-300 translate-x-full`;
    
    if (type === 'success') {
        toast.classList.add('bg-green-600');
    } else if (type === 'error') {
        toast.classList.add('bg-red-600');
    } else {
        toast.classList.add('bg-blue-600');
    }
    
    // Show toast
    setTimeout(() => {
        toast.classList.remove('translate-x-full');
    }, 100);
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.add('translate-x-full');
    }, 3000);
}

function createToastElement() {
    const toast = document.createElement('div');
    toast.id = 'toast-notification';
    return toast;
}

// ======= UTILITY FUNCTIONS =======
function formatCurrency(amount, showPrefix = true) {
    const formatted = new Intl.NumberFormat('id-ID').format(amount || 0);
    return showPrefix ? `Rp ${formatted}` : formatted;
}

function formatDate(dateString, format = 'long') {
    if (!dateString) return '-';
    
    try {
        const date = new Date(dateString);
        
        if (format === 'short') {
            return date.toLocaleDateString('id-ID', { 
                day: '2-digit', 
                month: 'short' 
            });
        }
        
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long', 
            year: 'numeric'
        });
    } catch (error) {
        return '-';
    }
}

function formatMonth(monthString) {
    if (!monthString) return '-';
    
    try {
        const [year, month] = monthString.split('-');
        const date = new Date(year, month - 1);
        
        return date.toLocaleDateString('id-ID', {
            month: 'short',
            year: 'numeric'
        });
    } catch (error) {
        return monthString;
    }
}

// ======= NAVIGATION MANAGER =======
const NavigationManager = {
    init() {
        this.updateActiveStates();
    },

    updateActiveStates() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('nav a');
        
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            
            // Remove active classes
            link.classList.remove('text-blue-600', 'bg-blue-100', 'rounded-lg');
            link.classList.add('text-gray-500');
            
            const span = link.querySelector('span');
            if (span) {
                span.classList.remove('font-semibold');
            }
            
            // Add active class for current page
            if (href === currentPage) {
                link.classList.remove('text-gray-500');
                link.classList.add('text-blue-600', 'bg-blue-100', 'rounded-lg');
                
                if (span) {
                    span.classList.add('font-semibold');
                }
            }
        });
    }
};