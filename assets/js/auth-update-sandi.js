/**
 * ======================================================
 * SCRIPT KHUSUS: otp_daftar.html & otp_sandi.html
 * Meng-handle:
 * 1. Logika input OTP (auto-tab, backspace, paste)
 * ======================================================
 */
document.addEventListener("DOMContentLoaded", () => {
    const inputs = document.querySelectorAll('.otp-input');
    
    inputs.forEach((input, index) => {
        
        input.addEventListener('input', (e) => {
            input.value = input.value.replace(/\D/g, '').substring(0, 1);
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace') {
                if (input.value.length === 0 && index > 0) {
                    inputs[index - 1].focus();
                }
            }
        });

        input.addEventListener('paste', (e) => {
            e.preventDefault(); 
            const pasteData = (e.clipboardData || window.clipboardData).getData('text');
            const digits = pasteData.replace(/\D/g, ''); 

            digits.split('').forEach((digit, i) => {
                const targetIndex = index + i;
                if (targetIndex < inputs.length) {
                    inputs[targetIndex].value = digit;
                    if (targetIndex === inputs.length - 1) {
                        inputs[targetIndex].focus();
                    }
                }
            });
        });
    });

    if (inputs.length > 0) {
        inputs[0].focus();
    }

    const form = document.getElementById('otp-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            let otpCode = '';
            inputs.forEach(input => {
                otpCode += input.value;
            });

            if (otpCode.length === inputs.length) {
                console.log('Kode OTP yang dimasukkan:', otpCode);
                const hidden = form.querySelector('#otp');
                if (hidden) hidden.value = otpCode;
                form.submit();
            } else {
                console.error('Kode OTP tidak lengkap');
            }
        });
    }
});