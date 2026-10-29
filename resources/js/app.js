import './bootstrap';
import Alpine from 'alpinejs';
import QRCode from 'qrcode';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-qr-code]').forEach((canvas) => {
    const token = canvas.dataset.qrToken;
    const printButton = document.querySelector('[data-print-label]');
    const errorMessage = document.querySelector('[data-qr-error]');

    QRCode.toCanvas(canvas, token, {
        width: 240,
        margin: 0,
        errorCorrectionLevel: 'M',
    }).then(() => {
        if (printButton) {
            printButton.disabled = false;
        }
    }).catch((error) => {
        console.error('Gagal membuat QR code:', error);
        if (errorMessage) {
            errorMessage.textContent = 'QR code gagal dibuat. Muat ulang halaman sebelum mencetak label.';
            errorMessage.classList.remove('hidden');
        }
    });
});
