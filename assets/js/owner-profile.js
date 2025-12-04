/**
 * ======================================================
 * SCRIPT UNTUK: owner/profile.php - Dynamic Profile Dashboard
 * Meng-handle:
 * 1. Load data profil dinamis dari API
 * 2. Chart.js untuk visualisasi data
 * 3. Modal CRUD untuk edit bisnis dan owner
 * 4. Logout functionality
 * ======================================================
 */

let profileData = {};
let statisticsData = {};
let chartData = {};
let charts = {};

document.addEventListener('DOMContentLoaded', () => {
    initializeProfile();
    setupEventListeners();
    NavigationManager.init();
});

// ======= INITIALIZATION =======
function initializeProfile() {
    showLoading(true);
    Promise.all([
        loadProfileData(),
        loadStatistics(),
        loadChartData()
    ])
    .then(() => {
        renderProfileData();
        renderStatistics();
        initializeCharts();
        showContent();
    })
    .catch(error => {
        console.error('Error loading profile:', error);
        showError(error.message || 'Gagal memuat data profil');
    });
}

// ======= API CALLS =======
async function loadProfileData() {
    try {
        const response = await fetch('api/query-profile.php?action=get_profile_data');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        profileData = result.data;
        return result.data;
    } catch (error) {
        throw new Error('Gagal memuat data profil: ' + error.message);
    }
}

async function loadStatistics() {
    try {
        const response = await fetch('api/query-profile.php?action=get_statistics');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        statisticsData = result.data;
        return result.data;
    } catch (error) {
        throw new Error('Gagal memuat statistik: ' + error.message);
    }
}

async function loadChartData() {
    try {
        const response = await fetch('api/query-profile.php?action=get_chart_data');
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.message);
        }
        
        chartData = result.data;
        return result.data;
    } catch (error) {
        throw new Error('Gagal memuat data chart: ' + error.message);
    }
}

// ======= RENDER FUNCTIONS =======
function renderProfileData() {
    const { bisnis, owner } = profileData;
    
    // Business info
    document.getElementById('nama-bisnis').textContent = bisnis.nama_bisnis || 'BersihXpress';
    document.getElementById('alamat-bisnis').textContent = bisnis.alamat || '-';
    document.getElementById('telepon-bisnis').textContent = bisnis.no_telepon || '-';
    document.getElementById('tanggal-bergabung').textContent = formatDate(bisnis.created_at) || '-';
    
    // Owner info
    document.getElementById('nama-pemilik').textContent = owner.nama_lengkap || '-';
    document.getElementById('email-akun').textContent = owner.email || '-';
    
    // Business logo
    const logoElement = document.getElementById('logo-bisnis');
    if (bisnis.logo) {
        logoElement.src = bisnis.logo;
    }
}

function renderStatistics() {
    // Total statistics
    document.getElementById('stat-total-transaksi').textContent = statisticsData.total_transaksi || '0';
    document.getElementById('stat-total-pendapatan').textContent = formatCurrency(statisticsData.total_pendapatan || 0);
    document.getElementById('stat-karyawan').textContent = statisticsData.total_karyawan || '0';
    document.getElementById('stat-pelanggan').textContent = statisticsData.total_pelanggan || '0';
    
    // Monthly statistics
    document.getElementById('stat-transaksi-bulan').textContent = `${statisticsData.transaksi_bulan_ini || 0} bulan ini`;
    document.getElementById('stat-pendapatan-bulan').textContent = `${formatCurrency(statisticsData.pendapatan_bulan_ini || 0)} bulan ini`;
}

function initializeCharts() {
    // Initialize revenue chart
    initializeDailyRevenueChart();
    
    // Initialize monthly transaction chart
    initializeMonthlyTransactionChart();
    
    // Initialize popular services chart
    initializePopularServicesChart();
}

function initializeDailyRevenueChart() {
    const ctx = document.getElementById('chartPendapatanHarian');
    if (!ctx) return;
    
    const data = chartData.pendapatan_harian || [];
    const labels = data.map(item => formatDate(item.tanggal, 'short'));
    const values = data.map(item => parseFloat(item.pendapatan));
    
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
}

function initializeMonthlyTransactionChart() {
    const ctx = document.getElementById('chartTransaksiBulanan');
    if (!ctx) return;
    
    const data = chartData.transaksi_bulanan || [];
    const labels = data.map(item => formatMonth(item.bulan));
    const values = data.map(item => parseInt(item.jumlah_transaksi));
    
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
}

function initializePopularServicesChart() {
    const ctx = document.getElementById('chartLayananPopuler');
    if (!ctx) return;
    
    const data = chartData.layanan_populer || [];
    const labels = data.map(item => item.nama_layanan);
    const values = data.map(item => parseInt(item.jumlah_transaksi));
    
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
                backgroundColor: colors,
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
}

// ======= EVENT LISTENERS =======
function setupEventListeners() {
    // Edit buttons
    document.getElementById('btn-edit-bisnis').addEventListener('click', openEditBusinessModal);
    document.getElementById('btn-edit-owner').addEventListener('click', openEditOwnerModal);
    
    // Logout button
    document.getElementById('btn-logout').addEventListener('click', openLogoutModal);
    
    // Form submissions
    document.getElementById('form-edit-bisnis').addEventListener('submit', handleBusinessUpdate);
    document.getElementById('form-edit-owner').addEventListener('submit', handleOwnerUpdate);
    
    // Retry button
    document.getElementById('btn-retry').addEventListener('click', initializeProfile);
    
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
    const { bisnis } = profileData;
    
    // Fill form with current data
    document.getElementById('edit-nama-bisnis').value = bisnis.nama_bisnis || '';
    document.getElementById('edit-alamat-bisnis').value = bisnis.alamat || '';
    document.getElementById('edit-telepon-bisnis').value = bisnis.no_telepon || '';
    
    openModal('modal-edit-bisnis');
}

function openEditOwnerModal() {
    const { owner } = profileData;
    
    // Fill form with current data
    document.getElementById('edit-nama-pemilik').value = owner.nama_lengkap || '';
    document.getElementById('edit-telepon-pemilik').value = owner.no_telepon || '';
    
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
        
        // Clear form if exists
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
        
        // Update profile data
        await loadProfileData();
        renderProfileData();
        
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
        
        // Update profile data
        await loadProfileData();
        renderProfileData();
        
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
function showLoading(show) {
    const loadingElement = document.getElementById('loading-content');
    const contentElement = document.getElementById('profile-content');
    const errorElement = document.getElementById('error-content');
    
    if (show) {
        loadingElement.classList.remove('hidden');
        contentElement.classList.add('hidden');
        errorElement.classList.add('hidden');
    } else {
        loadingElement.classList.add('hidden');
    }
}

function showContent() {
    const loadingElement = document.getElementById('loading-content');
    const contentElement = document.getElementById('profile-content');
    const errorElement = document.getElementById('error-content');
    
    loadingElement.classList.add('hidden');
    contentElement.classList.remove('hidden');
    errorElement.classList.add('hidden');
}

function showError(message) {
    const loadingElement = document.getElementById('loading-content');
    const contentElement = document.getElementById('profile-content');
    const errorElement = document.getElementById('error-content');
    const errorMessageElement = document.getElementById('error-message');
    
    loadingElement.classList.add('hidden');
    contentElement.classList.add('hidden');
    errorElement.classList.remove('hidden');
    errorMessageElement.textContent = message;
}

function setFormLoading(form, loading) {
    const submitButton = form.querySelector('button[type="submit"]');
    const btnText = submitButton.querySelector('.btn-text');
    const btnLoading = submitButton.querySelector('.btn-loading');
    
    if (loading) {
        submitButton.disabled = true;
        btnText.classList.add('hidden');
        btnLoading.classList.remove('hidden');
    } else {
        submitButton.disabled = false;
        btnText.classList.remove('hidden');
        btnLoading.classList.add('hidden');
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
}

function formatMonth(monthString) {
    if (!monthString) return '-';
    
    const [year, month] = monthString.split('-');
    const date = new Date(year, month - 1);
    
    return date.toLocaleDateString('id-ID', {
        month: 'short',
        year: 'numeric'
    });
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