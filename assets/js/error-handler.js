/**
 * Global error handler untuk aplikasi
 */
window.addEventListener('error', (event) => {
    console.error('Error:', event.error);
    showErrorNotification('Terjadi kesalahan sistem. Silakan coba lagi.');
});

/**
 * Error handler untuk network requests
 */
window.addEventListener('unhandledrejection', (event) => {
    console.error('Promise rejection:', event.reason);
    showErrorNotification('Gagal terhubung ke server. Periksa koneksi internet Anda.');
});

/**
 * Fungsi untuk menampilkan notifikasi error
 */
function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    notification.innerHTML = `
        <div class="flex items-center">
            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Hapus notifikasi setelah 5 detik
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

/**
 * Fungsi untuk handle error pada form input
 */
function handleFormError(input, message) {
    // Hapus error yang sudah ada
    clearFormError(input);
    
    // Tambah class error
    input.classList.add('border-red-500');
    
    // Buat pesan error
    const errorMessage = document.createElement('p');
    errorMessage.className = 'text-red-500 text-sm mt-1';
    errorMessage.textContent = message;
    
    // Masukkan pesan error setelah input
    input.parentNode.insertBefore(errorMessage, input.nextSibling);
}

/**
 * Fungsi untuk menghapus error pada form input
 */
function clearFormError(input) {
    input.classList.remove('border-red-500');
    const errorMessage = input.nextElementSibling;
    if (errorMessage && errorMessage.classList.contains('text-red-500')) {
        errorMessage.remove();
    }
}

/**
 * Fungsi untuk validasi form input
 */
function validateFormInput(input) {
    const value = input.value.trim();
    
    if (input.hasAttribute('required') && !value) {
        handleFormError(input, 'Field ini wajib diisi');
        return false;
    }
    
    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            handleFormError(input, 'Format email tidak valid');
            return false;
        }
    }
    
    if (input.id === 'no_telepon' && value) {
        const phoneRegex = /^(\+62|62|0)8[1-9][0-9]{6,9}$/;
        if (!phoneRegex.test(value)) {
            handleFormError(input, 'Format nomor telepon tidak valid');
            return false;
        }
    }
    
    clearFormError(input);
    return true;
}