<?php
require_once __DIR__ . '/middleware/auth_owner.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Bisnis - BersihXpress</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/webview.css">
    <script src="../../assets/js/webview.js"></script>
    <script src="../../assets/js/tailwind.js"></script>
</head>

<body class="bg-gray-100 flex flex-col min-h-screen">
    <div id="loading-overlay" class="loading-container">
        <img src="../../assets/images/loading.gif" alt="Memuat..." class="loading-indicator">
    </div>

    <div class="flex-grow flex items-center justify-center p-6">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Setup Bisnis Anda</h1>
                <p class="text-gray-600 mt-2">Lengkapi informasi bisnis Anda untuk memulai</p>
            </div>

            <form id="form-setup-bisnis" class="bg-white rounded-lg shadow-lg p-6 space-y-4">
                <div>
                    <label for="nama_bisnis" class="text-sm font-medium text-gray-700 block mb-1">Nama Bisnis</label>
                    <input type="text" id="nama_bisnis" name="nama_bisnis"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label for="alamat" class="text-sm font-medium text-gray-700 block mb-1">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required></textarea>
                </div>

                <div>
                    <label for="no_telp" class="text-sm font-medium text-gray-700 block mb-1">Nomor Telepon</label>
                    <input type="tel" id="no_telp" name="no_telp"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Logo Bisnis (Opsional)</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                        <div class="space-y-1 text-center">
                            <div id="logo-preview" class="hidden mb-3">
                                <img src="" alt="Preview" class="mx-auto h-32 w-32 object-cover rounded-lg">
                            </div>
                            <div id="logo-placeholder">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48" aria-hidden="true">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600">
                                    <label for="logo"
                                        class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                        <span>Upload logo</span>
                                        <input id="logo" name="logo" type="file" class="sr-only" accept="image/*">
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG up to 2MB</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit"
                        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Simpan & Lanjutkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../assets/js/icons.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form-setup-bisnis');
            const logoInput = document.getElementById('logo');
            const logoPreview = document.getElementById('logo-preview');
            const logoPlaceholder = document.getElementById('logo-placeholder');
            const previewImg = logoPreview.querySelector('img');

            // Preview logo
            logoInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        logoPreview.classList.remove('hidden');
                        logoPlaceholder.classList.add('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle form submission
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                showLoading();

                try {
                    const formData = new FormData(form);
                    const response = await fetch('api/bisnis.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    if (result.success) {
                        // Redirect ke dashboard setelah setup berhasil
                        window.location.href = 'dashboard.php';
                    } else {
                        alert(result.message || 'Terjadi kesalahan');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan sistem');
                } finally {
                    hideLoading();
                }
            });
        });
    </script>
</body>

</html>