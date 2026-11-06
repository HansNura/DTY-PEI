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
        width: 320,
        margin: 4,
        errorCorrectionLevel: 'M',
        color: {
            dark: '#000000',
            light: '#ffffff',
        },
    }).then(() => {
        canvas.style.removeProperty('width');
        canvas.style.removeProperty('height');

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

const receiptForm = document.querySelector('[data-receipt-form]');

if (receiptForm) {
    const scanInput = receiptForm.querySelector('[data-scan-input]');
    const manualInput = receiptForm.querySelector('[data-manual-input]');
    const addManualButton = receiptForm.querySelector('[data-add-manual]');
    const scanList = receiptForm.querySelector('[data-scan-list]');
    const scanMessage = receiptForm.querySelector('[data-scan-message]');
    const scanProgress = receiptForm.querySelector('[data-scan-progress]');
    const scanItems = [...receiptForm.querySelectorAll('[data-scan-item]')];
    const video = receiptForm.querySelector('[data-scan-video]');
    const cameraButton = receiptForm.querySelector('[data-start-camera]');
    const stopButton = receiptForm.querySelector('[data-stop-camera]');
    const canvas = receiptForm.querySelector('[data-signature-pad]');
    const signatureInput = receiptForm.querySelector('[data-signature-input]');
    const context = canvas.getContext('2d');
    let hasSignature = false;
    let isDrawing = false;
    let cameraControls;
    let scanHandled = false;

    const resizeCanvas = () => {
        const ratio = window.devicePixelRatio || 1;
        const bounds = canvas.getBoundingClientRect();
        canvas.width = bounds.width * ratio;
        canvas.height = bounds.height * ratio;
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.lineWidth = 2;
        context.lineCap = 'round';
        context.strokeStyle = '#0f172a';
        hasSignature = false;
    };

    const scannedTokens = () => [...receiptForm.querySelectorAll('[data-scanned-token]')].map((input) => input.value);

    const updateScanProgress = () => {
        const scanned = scanItems.filter((item) => item.dataset.scanned === '1').length;

        if (scanProgress) {
            scanProgress.textContent = `${scanned}/${scanItems.length}`;
        }
    };

    const markScanItem = (item) => {
        item.dataset.scanned = '1';
        item.classList.add('bg-emerald-50/60');

        const status = item.querySelector('[data-scan-status]');
        if (status) {
            status.textContent = 'Sudah';
            status.className = 'shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-medium text-emerald-700';
        }
    };

    const appendChip = (value) => {
        const chip = document.createElement('span');
        chip.className = 'rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700';
        chip.textContent = `${value} · scan ${receiptForm.querySelectorAll('[data-scanned-token]').length}`;
        scanList.append(chip);
    };

    const addScan = (token) => {
        const value = token.trim();

        if (!value) {
            return;
        }

        if (scannedTokens().includes(value)) {
            scanMessage.textContent = 'Kode ini sudah ditambahkan.';
            return;
        }

        const match = scanItems.find((item) => item.dataset.scanSerial === value
            || item.dataset.scanToken === value
            || item.dataset.scanHu === value);

        if (!match) {
            scanMessage.textContent = 'Kode tidak ada dalam daftar kardus/bobbin Surat Jalan ini.';
            return;
        }

        if (match.dataset.scanned === '1') {
            scanMessage.textContent = 'Kardus/bobbin ini sudah discan.';
            return;
        }

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'scanned_tokens[]';
        input.value = value;
        input.dataset.scannedToken = '';
        receiptForm.append(input);

        markScanItem(match);
        appendChip(value);
        updateScanProgress();
        scanMessage.textContent = `${scannedTokens().length} dari ${scanItems.length} bobbin discan.`;
        scanInput.value = '';
        if (manualInput) manualInput.value = '';
        scanInput.focus();
    };

    scannedTokens().forEach((value) => {
        const match = scanItems.find((item) => item.dataset.scanSerial === value || item.dataset.scanToken === value);

        if (match) {
            markScanItem(match);
            appendChip(value);
        }
    });
    updateScanProgress();

    receiptForm.querySelector('[data-add-scan]').addEventListener('click', () => addScan(scanInput.value));
    scanInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addScan(scanInput.value);
        }
    });

    if (addManualButton && manualInput) {
        addManualButton.addEventListener('click', () => addScan(manualInput.value));
        manualInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addScan(manualInput.value);
            }
        });
    }

    cameraButton.addEventListener('click', async () => {
        try {
            const { BrowserQRCodeReader } = await import('@zxing/browser');
            const reader = new BrowserQRCodeReader();
            scanHandled = false;
            video.classList.remove('hidden');
            cameraButton.classList.add('hidden');
            stopButton.classList.remove('hidden');
            cameraControls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (result && !scanHandled) {
                    scanHandled = true;
                    addScan(result.getText());
                    cameraControls?.stop();
                    video.classList.add('hidden');
                    stopButton.classList.add('hidden');
                    cameraButton.classList.remove('hidden');
                }
            });
            if (scanHandled) {
                cameraControls.stop();
            }
        } catch (error) {
            console.error('Kamera QR tidak dapat dibuka:', error);
            scanMessage.textContent = 'Kamera tidak dapat dibuka. Izinkan akses kamera atau masukkan hasil scan secara manual.';
            video.classList.add('hidden');
            cameraButton.classList.remove('hidden');
            stopButton.classList.add('hidden');
        }
    });

    stopButton.addEventListener('click', () => {
        cameraControls?.stop();
        cameraControls = undefined;
        video.classList.add('hidden');
        stopButton.classList.add('hidden');
        cameraButton.classList.remove('hidden');
    });

    canvas.addEventListener('pointerdown', (event) => {
        isDrawing = true;
        hasSignature = true;
        canvas.setPointerCapture(event.pointerId);
        const bounds = canvas.getBoundingClientRect();
        context.beginPath();
        context.moveTo(event.clientX - bounds.left, event.clientY - bounds.top);
    });

    canvas.addEventListener('pointermove', (event) => {
        if (!isDrawing) {
            return;
        }

        const bounds = canvas.getBoundingClientRect();
        context.lineTo(event.clientX - bounds.left, event.clientY - bounds.top);
        context.stroke();
    });

    canvas.addEventListener('pointerup', () => {
        isDrawing = false;
    });
    canvas.addEventListener('pointercancel', () => {
        isDrawing = false;
    });
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    receiptForm.querySelector('[data-clear-signature]').addEventListener('click', () => {
        context.clearRect(0, 0, canvas.width, canvas.height);
        hasSignature = false;
        signatureInput.value = '';
    });

    receiptForm.addEventListener('submit', (event) => {
        const scanned = scanItems.filter((item) => item.dataset.scanned === '1').length;

        if (scanItems.length > 0 && scanned < scanItems.length) {
            event.preventDefault();
            scanMessage.textContent = `Masih ada ${scanItems.length - scanned} kardus/bobbin yang belum discan.`;
            scanInput.focus();
            return;
        }

        if (!hasSignature) {
            event.preventDefault();
            scanMessage.textContent = 'Tanda tangan penerima wajib diisi.';
            canvas.focus();
            return;
        }

        signatureInput.value = canvas.toDataURL('image/png');
        cameraControls?.stop();
    });
}
