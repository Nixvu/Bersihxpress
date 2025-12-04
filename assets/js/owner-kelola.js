/**
 * ======================================================
 * SCRIPT KHUSUS: owner/kelola.html
 * Meng-handle:
 * 1. Logika modal "Profil Usaha"
 * ======================================================
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- PILIH ELEMEN MODAL ---
    const modalContainer = document.getElementById('modal-container');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalProfilUsaha = document.getElementById('modal-profil-usaha');
    const btnProfilUsaha = document.getElementById('btn-profil-usaha');
    const closeButtons = document.querySelectorAll('.btn-close-modal');

    if (!modalContainer || !modalBackdrop || !modalProfilUsaha) {
        console.error('Modal profil usaha tidak ter-render lengkap.', {
            modalContainer,
            modalBackdrop,
            modalProfilUsaha,
        });
        return;
    }

    // --- FUNGSI HELPER ---
    function openModal(modalElement) {
        if (!modalElement) return;
        modalContainer.classList.remove('hidden');
        modalBackdrop.classList.remove('opacity-0');
        document.body.style.overflow = 'hidden';
        modalElement.classList.remove('hidden');
        requestAnimationFrame(() => {
            modalElement.style.transform = 'translateY(0)';
        });
    }

    function closeModal(modalElement) {
        if (!modalElement) return;
        modalElement.style.transform = 'translateY(100%)';
        setTimeout(() => {
            modalElement.classList.add('hidden');
            const anyModalOpen = document.querySelectorAll('.modal-popup:not(.hidden)').length > 0;
            if (!anyModalOpen) {
                modalBackdrop.classList.add('opacity-0');
                setTimeout(() => {
                    modalContainer.classList.add('hidden');
                    document.body.style.overflow = 'auto';
                }, 300);
            }
        }, 300);
    }

    // --- EVENT LISTENERS ---
    if (btnProfilUsaha) {
        btnProfilUsaha.addEventListener('click', () => {
            openModal(modalProfilUsaha);
        });
    } else {
        console.error('Tombol btn-profil-usaha tidak ditemukan!');
    }

    closeButtons.forEach(button => {
        if (!button) return;
        button.addEventListener('click', event => {
            const targetId = event.currentTarget?.dataset?.target;
            if (targetId) {
                const targetModal = document.getElementById(targetId);
                closeModal(targetModal || modalProfilUsaha);
                return;
            }
            closeModal(modalProfilUsaha);
        });
    });

    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', () => {
            closeModal(modalProfilUsaha);
        });
    }

    // Initialize navigation active states
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
                
                // Add active class only for exact page match
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

    // Initialize navigation
    NavigationManager.init();
});