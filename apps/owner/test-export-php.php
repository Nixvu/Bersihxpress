<?php
session_start();

// Simulasi data owner untuk testing
$_SESSION['owner_data'] = [
    'bisnis_id' => 1,
    'nama_bisnis' => 'Test Laundry'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Export PHP - BersihXpress</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-8">
        <h1 class="text-2xl font-bold mb-4">Test Export Function (With PHP Session)</h1>
        
        <div class="bg-white p-4 rounded-lg mb-4">
            <h3 class="font-bold">Session Data:</h3>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <!-- Test Button -->
        <button id="btn-test-export" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mr-2">
            Test Export Modal
        </button>
        
        <button id="btn-direct-test" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            Direct API Test
        </button>
        
        <div id="result" class="mt-4 p-4 bg-gray-50 rounded-lg hidden"></div>
        
        <!-- Export Modal -->
        <div id="modal-container" class="hidden fixed inset-0 z-50">
            <div id="modal-backdrop" class="fixed inset-0 bg-black bg-opacity-50"></div>
            <div id="modal-export" class="fixed bottom-0 left-0 right-0 bg-white rounded-t-lg p-6 transform translate-y-full transition-transform">
                <h2 class="text-xl font-bold mb-4">Test Export</h2>
                <form id="form-export">
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="export_pendapatan" checked class="mr-2">
                            <span>Export Pendapatan</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="format" value="pdf" checked class="mr-2">
                                <span>PDF</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="format" value="csv" class="mr-2">
                                <span>CSV</span>
                            </label>
                        </div>
                        <button type="submit" class="w-full bg-green-500 text-white py-2 rounded hover:bg-green-600">
                            Export
                        </button>
                        <button type="button" id="btn-close" class="w-full bg-gray-500 text-white py-2 rounded hover:bg-gray-600">
                            Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        console.log('Test page with PHP session loaded');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM ready');
            
            const btnTestExport = document.getElementById('btn-test-export');
            const btnDirectTest = document.getElementById('btn-direct-test');
            const modalContainer = document.getElementById('modal-container');
            const modalExport = document.getElementById('modal-export');
            const formExport = document.getElementById('form-export');
            const btnClose = document.getElementById('btn-close');
            const resultDiv = document.getElementById('result');
            
            function showResult(text, isError = false) {
                resultDiv.innerHTML = `<pre class="${isError ? 'text-red-600' : 'text-green-600'}">${text}</pre>`;
                resultDiv.classList.remove('hidden');
            }
            
            btnTestExport.addEventListener('click', function() {
                console.log('Test export button clicked');
                modalContainer.classList.remove('hidden');
                setTimeout(() => {
                    modalExport.style.transform = 'translateY(0)';
                }, 10);
            });
            
            btnDirectTest.addEventListener('click', async function() {
                console.log('Direct test button clicked');
                
                try {
                    const response = await fetch('api/export.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            format: 'csv',
                            exportData: ['export_pendapatan'],
                            filterType: 'bulan_ini',
                            tanggalMulai: null,
                            tanggalSelesai: null
                        })
                    });
                    
                    console.log('Response:', response);
                    const text = await response.text();
                    console.log('Response text:', text);
                    
                    showResult(`Status: ${response.status}\nResponse: ${text}`);
                    
                } catch (error) {
                    console.error('Error:', error);
                    showResult(`Error: ${error.message}`, true);
                }
            });
            
            formExport.addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('Form submitted');
                
                const formData = new FormData(formExport);
                const exportData = [];
                if (formData.get('export_pendapatan')) {
                    exportData.push('export_pendapatan');
                }
                
                const data = {
                    format: formData.get('format') || 'pdf',
                    exportData: exportData,
                    filterType: 'bulan_ini',
                    tanggalMulai: null,
                    tanggalSelesai: null
                };
                
                console.log('Sending data:', data);
                
                try {
                    const response = await fetch('api/export.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    
                    console.log('Response status:', response.status);
                    const result = await response.json();
                    console.log('Result:', result);
                    
                    if (result.success) {
                        showResult(`Export berhasil!\nFile: ${result.filename}`);
                        // Download file
                        const link = document.createElement('a');
                        link.href = `../../exports/${result.filename}`;
                        link.download = result.filename;
                        link.click();
                    } else {
                        showResult(`Export gagal: ${result.message}`, true);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showResult(`Error: ${error.message}`, true);
                }
                
                // Close modal
                closeModal();
            });
            
            function closeModal() {
                modalExport.style.transform = 'translateY(100%)';
                setTimeout(() => {
                    modalContainer.classList.add('hidden');
                }, 300);
            }
            
            // Close modal events
            btnClose.addEventListener('click', closeModal);
            document.getElementById('modal-backdrop').addEventListener('click', closeModal);
        });
    </script>
</body>
</html>