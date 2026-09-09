import { Html5Qrcode } from 'html5-qrcode';

const root = document.getElementById('scanner-app');

if (root) {
    const scanUrl = root.dataset.scanUrl;
    const csrf = root.dataset.csrf;
    const cameraSelect = document.getElementById('camera-select');
    const startButton = document.getElementById('start-scan');
    const stopButton = document.getElementById('stop-scan');
    const statusEl = document.getElementById('scan-status');
    const resultName = document.getElementById('result-name');
    const resultMeta = document.getElementById('result-meta');
    const resultMessage = document.getElementById('result-message');
    const resultTime = document.getElementById('result-time');
    const resultPhoto = document.getElementById('result-photo');
    const resultFallback = document.getElementById('result-photo-fallback');
    const recentList = document.getElementById('recent-list');

    let scanner = null;
    let running = false;
    let inFlight = false;
    let lastScanAt = 0;
    const cooldownMs = 1500;

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
        }
    };

    const paintResult = (payload, fallbackMessage) => {
        const student = payload?.student;
        const message = payload?.message || fallbackMessage;
        const code = payload?.code || 'error';

        resultName.textContent = student?.name || (code === 'invalid' ? 'Unknown QR' : 'Scan result');
        resultMeta.textContent = student
            ? [student.student_number, student.level, student.section].filter(Boolean).join(' · ')
            : 'Present a student QR to the camera.';
        resultMessage.textContent = message;
        resultTime.textContent = payload?.time_in ? `Time-in: ${payload.time_in}` : '';

        resultMessage.classList.remove('text-emerald-700', 'text-amber-700', 'text-rose-700', 'text-slate-600');
        resultMessage.classList.add(
            code === 'recorded' ? 'text-emerald-700' : code === 'duplicate' ? 'text-amber-700' : 'text-rose-700',
        );

        if (student?.photo_url) {
            resultPhoto.src = student.photo_url;
            resultPhoto.alt = student.name || 'Student photo';
            resultPhoto.classList.remove('hidden');
            resultFallback.classList.add('hidden');
        } else {
            resultPhoto.removeAttribute('src');
            resultPhoto.classList.add('hidden');
            resultFallback.classList.remove('hidden');
        }
    };

    const prependRecent = (payload) => {
        if (! recentList || ! payload?.student || payload.code !== 'recorded') {
            return;
        }

        const empty = recentList.querySelector('li.text-center');
        empty?.remove();

        const item = document.createElement('li');
        item.className = 'px-5 py-3';
        item.innerHTML = `<p class="text-sm font-medium text-slate-900"></p><p class="text-xs text-slate-500"></p>`;
        item.children[0].textContent = payload.student.name;
        item.children[1].textContent = `${payload.student.student_number} · ${payload.time_in ?? ''}`;
        recentList.prepend(item);

        while (recentList.children.length > 12) {
            recentList.lastElementChild?.remove();
        }
    };

    const submitToken = async (token) => {
        const now = Date.now();

        if (inFlight || now - lastScanAt < cooldownMs) {
            return;
        }

        inFlight = true;
        lastScanAt = now;
        setStatus('Confirming attendance…');

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ token }),
            });

            let payload = {};

            try {
                payload = await response.json();
            } catch {
                payload = {};
            }

            if (! response.ok && ! payload.message) {
                paintResult(null, 'Unable to confirm attendance. Please try again.');
                setStatus('Ready to scan.');
                return;
            }

            paintResult(payload, payload.message || 'Unable to confirm attendance. Please try again.');
            prependRecent(payload);
            setStatus('Ready to scan.');
        } catch {
            paintResult(null, 'Unable to confirm attendance. Please try again.');
            setStatus('Ready to scan.');
        } finally {
            inFlight = false;
        }
    };

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    const selectedCameraId = () => cameraSelect?.value || '';

    const stopScanner = async () => {
        if (! scanner) {
            running = false;
            setStatus('Camera is stopped.');
            return;
        }

        try {
            if (running) {
                await scanner.stop();
            }
            await scanner.clear();
        } catch {
            // Camera may already be gone.
        }

        running = false;
        scanner = null;
        setStatus('Camera is stopped.');
    };

    const insecureOriginMessage = () => {
        const host = window.location.hostname;

        return `Browsers block the webcam on http://${host}. Open http://127.0.0.1:8000/scanner after running php artisan serve, or use HTTPS.`;
    };

    const cameraBlockedReason = () => {
        if (! window.isSecureContext) {
            return insecureOriginMessage();
        }

        if (! navigator.mediaDevices?.getUserMedia) {
            return 'This browser cannot access a webcam. Use Chrome or Edge on this computer.';
        }

        return null;
    };

    const cameraConfig = { fps: 10 };

    const onDecoded = (decodedText) => {
        submitToken(decodedText);
    };

    const describeStartError = (error) => {
        const name = error?.name || '';
        const message = String(error?.message || error).toLowerCase();

        if (name === 'NotAllowedError' || message.includes('permission')) {
            return 'Camera permission was denied. Allow camera access for this site, then try again.';
        }

        if (name === 'NotFoundError' || message.includes('requested device not found')) {
            return 'That camera was not found. Unplug it, plug it back in, then press start scanning.';
        }

        if (name === 'NotReadableError' || message.includes('could not start video source') || message.includes('in use')) {
            return 'The plugged-in camera is busy or still releasing. Close Zoom, Teams, or the Windows Camera app, click Stop, wait a second, then start the USB webcam again.';
        }

        if (! window.isSecureContext) {
            return insecureOriginMessage();
        }

        return 'Unable to start that camera. Click Stop, select the plugged-in webcam, then start scanning.';
    };

    const startScanner = async () => {
        if (running) {
            return;
        }

        const blocked = cameraBlockedReason();

        if (blocked) {
            setStatus(blocked);
            return;
        }

        if (! selectedCameraId()) {
            await loadCameras();
        }

        const cameraId = selectedCameraId();

        if (! cameraId) {
            setStatus('Select a camera first.');
            return;
        }

        await stopScanner();
        await wait(400);

        scanner = new Html5Qrcode('reader', { verbose: false });
        const source = { deviceId: { exact: cameraId } };

        try {
            await scanner.start(source, cameraConfig, onDecoded, () => {});
            running = true;
            setStatus('Scanning. Hold a QR steady in the frame.');
        } catch (firstError) {
            await wait(700);

            try {
                scanner = new Html5Qrcode('reader', { verbose: false });
                await scanner.start(source, cameraConfig, onDecoded, () => {});
                running = true;
                setStatus('Scanning. Hold a QR steady in the frame.');
            } catch (error) {
                await stopScanner();
                setStatus(describeStartError(error));
            }
        }
    };

    const loadCameras = async () => {
        const blocked = cameraBlockedReason();

        if (blocked) {
            setStatus(blocked);
            return;
        }

        const preferredId = selectedCameraId();

        try {
            const cameras = await Html5Qrcode.getCameras();

            if (! cameras.length) {
                setStatus('No camera was found. Connect a webcam, then press start scanning.');
                return;
            }

            cameraSelect.innerHTML = '';
            cameras.forEach((camera) => {
                const option = document.createElement('option');
                option.value = camera.id;
                option.textContent = camera.label || 'Camera';
                cameraSelect.append(option);
            });

            if (preferredId && cameras.some((camera) => camera.id === preferredId)) {
                cameraSelect.value = preferredId;
            }

            setStatus('Camera ready. Press start scanning.');
        } catch (error) {
            const name = error?.name || '';

            if (name === 'NotAllowedError') {
                setStatus('Camera permission was denied. Allow camera access for this site, then press start scanning.');
            } else if (! window.isSecureContext) {
                setStatus(insecureOriginMessage());
            } else {
                setStatus('Unable to list cameras. Allow camera access, then press start scanning.');
            }
        }
    };

    startButton?.addEventListener('click', () => {
        startScanner();
    });

    stopButton?.addEventListener('click', () => {
        stopScanner();
    });

    cameraSelect?.addEventListener('change', async () => {
        if (running) {
            await stopScanner();
            await startScanner();
        }
    });

    window.addEventListener('pagehide', () => {
        stopScanner();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopScanner();
        }
    });

    loadCameras();
}
