import { Html5Qrcode } from 'html5-qrcode';

const root = document.getElementById('scanner-app');

if (root) {
    const scanUrl = root.dataset.scanUrl;
    const faceUrl = root.dataset.faceUrl;
    const facesUrl = root.dataset.facesUrl;
    const enrollUrl = root.dataset.enrollUrl;
    const modelsUrl = root.dataset.modelsUrl;
    const csrf = root.dataset.csrf;
    const cameraSelect = document.getElementById('camera-select');
    const cameraPickerButton = document.getElementById('camera-picker-button');
    const cameraPickerLabel = document.getElementById('camera-picker-label');
    const cameraPickerMenu = document.getElementById('camera-picker-menu');
    const startButton = document.getElementById('start-scan');
    const stopButton = document.getElementById('stop-scan');
    const statusEl = document.getElementById('scan-status');
    const resultName = document.getElementById('result-name');
    const resultMeta = document.getElementById('result-meta');
    const resultMessage = document.getElementById('result-message');
    const resultTime = document.getElementById('result-time');
    const resultPhoto = document.getElementById('result-photo');
    const resultFallback = document.getElementById('result-photo-fallback');
    const resultBadge = document.getElementById('result-badge');
    const resultCard = document.getElementById('result-card');
    const resultInitials = document.getElementById('result-initials');
    const resultPhotoIcon = document.getElementById('result-photo-icon');
    const liveLabel = document.getElementById('scan-live-label');
    const recentList = document.getElementById('recent-list');
    const recentCount = document.getElementById('recent-count');
    const faceVideo = document.getElementById('face-video');
    const enrollPanel = document.getElementById('enroll-panel');
    const enrollSearch = document.getElementById('enroll-search');
    const enrollResults = document.getElementById('enroll-results');
    const enrollSelected = document.getElementById('enroll-selected');
    const enrollSelectedName = document.getElementById('enroll-selected-name');
    const enrollSelectedMeta = document.getElementById('enroll-selected-meta');
    const enrollClear = document.getElementById('enroll-clear');
    const recentLimit = 40;

    let scanner = null;
    let faceStream = null;
    let faceLoop = 0;
    let running = false;
    let inFlight = false;
    let lastScanAt = 0;
    let selectedStudent = null;
    let searchTimer = null;
    let faceHelpers = null;
    let lookStreak = 0;
    const cooldownMs = 1500;
    const requiredLookFrames = 4;

    const currentMode = () => root.dataset.mode || 'qr';
    const enrollPoses = [
        { id: 'left', label: 'Look left', arrow: '←', match: (face) => face.yaw < -0.12 },
        { id: 'right', label: 'Look right', arrow: '→', match: (face) => face.yaw > 0.12 },
        { id: 'up', label: 'Look up', arrow: '↑', match: (face) => face.pitch < 0.02 },
        { id: 'down', label: 'Look down', arrow: '↓', match: (face) => face.pitch > 0.18 },
        { id: 'center', label: 'Look at the camera', arrow: '•', match: (face) => face.lookingAtCamera },
    ];
    const samplesPerPose = 2;
    const faceGuide = document.getElementById('face-guide');
    const faceGuideLabel = document.getElementById('face-guide-label');
    const faceGuideArrow = document.getElementById('face-guide-arrow');
    const faceGuideStep = document.getElementById('face-guide-step');

    const showFaceGuide = (pose, poseIndex, poseSamples) => {
        faceGuide?.classList.remove('hidden');
        if (faceGuideLabel) {
            faceGuideLabel.textContent = pose.label;
        }
        if (faceGuideArrow) {
            faceGuideArrow.textContent = pose.arrow;
        }
        if (faceGuideStep) {
            faceGuideStep.textContent = `${poseIndex + 1}/${enrollPoses.length} · ${poseSamples}/${samplesPerPose}`;
        }
    };

    const hideFaceGuide = () => {
        faceGuide?.classList.add('hidden');
    };

    const idleMeta = () => {
        if (currentMode() === 'enroll') {
            return selectedStudent
                ? 'Start the camera to register this student\'s face.'
                : 'Search a student, or start the camera to scan their QR.';
        }

        if (currentMode() === 'face') {
            return 'Look at the camera to check in.';
        }

        return 'Present a student QR to the camera.';
    };

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
        }
    };

    const setScanningState = (isScanning) => {
        root.dataset.scanning = isScanning ? 'true' : 'false';

        if (liveLabel) {
            liveLabel.textContent = isScanning ? 'Live' : 'Idle';
        }

        if (startButton) {
            startButton.disabled = isScanning;
            startButton.classList.toggle('opacity-60', isScanning);
            startButton.classList.toggle('cursor-not-allowed', isScanning);
        }
    };

    const paintResult = (payload, fallbackMessage) => {
        const student = payload?.student;
        const message = payload?.message || fallbackMessage;
        const code = payload?.code || 'error';

        resultName.textContent = student?.name || (code === 'invalid' ? 'Unknown QR' : code === 'unrecognized' ? 'Unknown face' : 'Scan result');
        resultMeta.textContent = student
            ? [student.student_number, student.level, student.section].filter(Boolean).join(' · ')
            : idleMeta();
        resultMessage.textContent = message;
        resultTime.textContent = payload?.time_in ? `Time-in: ${payload.time_in}` : '';

        resultMessage.classList.remove('text-emerald-700', 'text-amber-700', 'text-rose-700', 'text-slate-600');
        resultMessage.classList.add(
            ['recorded', 'enrolled'].includes(code)
                ? 'text-emerald-700'
                : ['duplicate', 'replace'].includes(code)
                    ? 'text-amber-700'
                    : code === 'idle'
                        ? 'text-slate-600'
                        : 'text-rose-700',
        );

        resultCard?.setAttribute('data-result', code);

        if (resultBadge) {
            const badges = {
                recorded: ['Present', 'bg-emerald-50 text-emerald-700'],
                enrolled: ['Face registered', 'bg-emerald-50 text-emerald-700'],
                replace: ['Replace face', 'bg-amber-50 text-amber-800'],
                duplicate: ['Already checked in', 'bg-amber-50 text-amber-800'],
                invalid: ['Invalid QR', 'bg-rose-50 text-rose-700'],
                unrecognized: ['Not recognized', 'bg-rose-50 text-rose-700'],
                inactive: ['Inactive', 'bg-rose-50 text-rose-700'],
            };
            const badge = badges[code];

            resultBadge.className = 'rounded-full px-2.5 py-1 text-[11px] font-semibold';

            if (badge) {
                resultBadge.hidden = false;
                resultBadge.textContent = badge[0];
                resultBadge.classList.add(...badge[1].split(' '));
            } else {
                resultBadge.hidden = true;
                resultBadge.textContent = '';
            }
        }

        const ring = code === 'recorded' || code === 'enrolled' ? 'ring-emerald-100' : code === 'duplicate' ? 'ring-amber-100' : 'ring-slate-100';

        resultPhoto.classList.remove('ring-emerald-100', 'ring-amber-100', 'ring-slate-100', 'ring-rose-100');
        resultFallback.classList.remove('ring-emerald-100', 'ring-amber-100', 'ring-slate-100', 'ring-rose-100');
        resultPhoto.classList.add(ring);
        resultFallback.classList.add(ring);

        if (student?.photo_url) {
            resultPhoto.src = student.photo_url;
            resultPhoto.alt = student.name || 'Student photo';
            resultPhoto.classList.remove('hidden');
            resultFallback.classList.add('hidden');
        } else {
            resultPhoto.removeAttribute('src');
            resultPhoto.classList.add('hidden');
            resultFallback.classList.remove('hidden');

            const initials = student?.initials || initialsFromName(student?.name);

            if (initials && resultInitials) {
                resultInitials.textContent = initials;
                resultInitials.classList.remove('hidden');
                resultPhotoIcon?.classList.add('hidden');
            } else {
                resultInitials?.classList.add('hidden');
                resultPhotoIcon?.classList.remove('hidden');
            }
        }
    };

    const initialsFromName = (name) => {
        return String(name || '')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0))
            .join('')
            .toUpperCase();
    };

    const levelTone = (label) => {
        if (label === 'Kinder') {
            return 'bg-rose-50 text-rose-800';
        }

        if (label === 'Elementary') {
            return 'bg-sky-50 text-sky-800';
        }

        if (label === 'JHS') {
            return 'bg-amber-50 text-amber-800';
        }

        if (label === 'SHS') {
            return 'bg-violet-50 text-violet-800';
        }

        if (label === 'College') {
            return 'bg-emerald-50 text-emerald-800';
        }

        return 'bg-slate-100 text-slate-600';
    };

    const refreshRecentCount = () => {
        if (! recentCount || ! recentList) {
            return;
        }

        recentCount.textContent = String(recentList.querySelectorAll('li:not(.text-center)').length);
    };

    const prependRecent = (payload) => {
        if (! recentList || ! payload?.student || payload.code !== 'recorded') {
            return;
        }

        const empty = recentList.querySelector('li.text-center');
        empty?.remove();

        const item = document.createElement('li');
        item.className = 'flex items-center gap-3 px-4 py-3';
        item.innerHTML = `<div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-900 text-[11px] font-semibold text-white"></div><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium text-slate-900"></p><p class="truncate text-xs text-slate-500"></p></div><div class="flex shrink-0 flex-col items-end gap-1"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold"></span><p class="text-xs font-medium tabular-nums text-slate-400"></p></div>`;
        item.querySelector('div').textContent = payload.student.initials || initialsFromName(payload.student.name) || '—';
        item.querySelectorAll('p')[0].textContent = payload.student.name;
        item.querySelectorAll('p')[1].textContent = payload.student.student_number || '';
        const chip = item.querySelector('span');
        chip.textContent = payload.student.level || '';
        chip.className = `rounded-full px-2 py-0.5 text-[10px] font-semibold ${levelTone(payload.student.level)}`;
        item.querySelectorAll('p')[2].textContent = payload.time_in ?? '';
        recentList.prepend(item);

        while (recentList.children.length > recentLimit) {
            recentList.lastElementChild?.remove();
        }

        refreshRecentCount();
    };

    const postJson = async (url, body) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });

        let payload = {};

        try {
            payload = await response.json();
        } catch {
            payload = {};
        }

        return { response, payload };
    };

    const submitToken = async (token) => {
        const now = Date.now();

        if (inFlight || now - lastScanAt < cooldownMs) {
            return;
        }

        if (currentMode() === 'enroll') {
            inFlight = true;
            lastScanAt = now;
            setStatus('Looking up student…');

            try {
                const response = await fetch(`${facesUrl}?token=${encodeURIComponent(token)}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json();
                const student = payload.students?.[0];

                if (! student) {
                    paintResult({ code: 'invalid', message: 'Invalid QR code' }, 'Invalid QR code');
                    setStatus('Search a student, or scan a valid QR.');
                    return;
                }

                selectStudent(student);
                setStatus('Student selected. Hold still to register the face.');
                await stopScanner();
                await startFaceCamera();
            } catch {
                paintResult(null, 'Unable to look up that QR. Please try again.');
                setStatus('Ready to enroll.');
            } finally {
                inFlight = false;
            }

            return;
        }

        inFlight = true;
        lastScanAt = now;
        setStatus('Confirming attendance…');

        try {
            const { response, payload } = await postJson(scanUrl, { token });

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

    const submitFaceMatch = async (descriptor) => {
        const now = Date.now();

        if (inFlight || now - lastScanAt < cooldownMs) {
            return;
        }

        inFlight = true;
        lastScanAt = now;
        setStatus('Confirming attendance…');

        try {
            const { response, payload } = await postJson(faceUrl, { descriptor });

            if (! response.ok && ! payload.message) {
                paintResult(null, 'Unable to confirm attendance. Please try again.');
                await stopScanner();
                setStatus('Camera stopped. Press start scanning to try again.');
                return;
            }

            paintResult(payload, payload.message || 'Unable to confirm attendance. Please try again.');
            prependRecent(payload);
            await stopScanner();
            setStatus(
                payload.code === 'recorded'
                    ? 'Attendance recorded. Camera stopped.'
                    : payload.code === 'duplicate'
                        ? 'Already checked in. Camera stopped.'
                        : 'Camera stopped. Press start scanning to try again.',
            );
        } catch {
            paintResult(null, 'Unable to confirm attendance. Please try again.');
            setStatus('Look directly at the camera.');
        } finally {
            inFlight = false;
        }
    };

    const submitFaceEnroll = async (descriptor) => {
        if (inFlight || ! selectedStudent) {
            return;
        }

        inFlight = true;
        setStatus('Saving face…');

        try {
            const { response, payload } = await postJson(enrollUrl, {
                student_id: selectedStudent.id,
                descriptor,
            });

            if (! response.ok && ! payload.message) {
                paintResult(null, 'Unable to register that face. Please try again.');
                setStatus('Hold still to register the face.');
                return;
            }

            paintResult(payload, payload.message || 'Unable to register that face. Please try again.');

            if (payload.code === 'enrolled' && payload.student) {
                renderSelectedStudent(payload.student);
                setStatus('Face registered. You can enroll another student.');
                await stopScanner();
                return;
            }

            setStatus('Hold still to register the face.');
        } catch {
            paintResult(null, 'Unable to register that face. Please try again.');
            setStatus('Hold still to register the face.');
        } finally {
            inFlight = false;
        }
    };

    const ensureFaceHelpers = async () => {
        if (! faceHelpers) {
            faceHelpers = await import('./face-scanner');
        }

        return faceHelpers;
    };

    const wait = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

    const selectedCameraId = () => cameraSelect?.value || '';

    const closeCameraPicker = () => {
        if (! cameraPickerMenu || ! cameraPickerButton) {
            return;
        }

        cameraPickerMenu.hidden = true;
        cameraPickerMenu.classList.add('hidden');
        cameraPickerButton.setAttribute('aria-expanded', 'false');
    };

    const openCameraPicker = () => {
        if (! cameraPickerMenu || ! cameraPickerButton) {
            return;
        }

        cameraPickerMenu.hidden = false;
        cameraPickerMenu.classList.remove('hidden');
        cameraPickerButton.setAttribute('aria-expanded', 'true');
    };

    const syncCameraPicker = () => {
        if (! cameraSelect || ! cameraPickerMenu || ! cameraPickerLabel) {
            return;
        }

        const options = Array.from(cameraSelect.options);
        const current = cameraSelect.value;
        const currentLabel = options.find((option) => option.value === current)?.textContent
            || options[0]?.textContent
            || 'Select a camera';

        cameraPickerLabel.textContent = currentLabel;
        cameraPickerMenu.innerHTML = '';

        if (! options.length) {
            const empty = document.createElement('li');
            empty.className = 'px-3 py-2 text-sm text-slate-400';
            empty.textContent = 'No camera found';
            cameraPickerMenu.append(empty);
            return;
        }

        options.forEach((option) => {
            const item = document.createElement('li');
            const choice = document.createElement('button');
            choice.type = 'button';
            choice.role = 'option';
            choice.setAttribute('aria-selected', option.value === current ? 'true' : 'false');
            choice.dataset.value = option.value;
            choice.className = 'flex w-full px-3 py-2 text-left text-sm text-slate-100 hover:bg-white/10';
            choice.textContent = option.textContent || 'Camera';
            choice.addEventListener('click', () => {
                if (cameraSelect.value !== option.value) {
                    cameraSelect.value = option.value;
                    cameraSelect.dispatchEvent(new Event('change'));
                }

                syncCameraPicker();
                closeCameraPicker();
            });
            item.append(choice);
            cameraPickerMenu.append(item);
        });
    };

    const stopFaceCamera = () => {
        faceLoop += 1;
        hideFaceGuide();
        faceStream?.getTracks().forEach((track) => track.stop());
        faceStream = null;

        if (faceVideo) {
            faceVideo.srcObject = null;
        }
    };

    const stopScanner = async () => {
        stopFaceCamera();
        delete root.dataset.camera;

        if (! scanner) {
            running = false;
            setScanningState(false);
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
        setScanningState(false);
        setStatus('Camera is stopped.');
    };

    const insecureOriginMessage = () => {
        const host = window.location.hostname;

        if (host.endsWith('.test')) {
            return `Browsers block the webcam on http://${host}. Open https://${host}/scanner instead.`;
        }

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

    const startQrCamera = async () => {
        await stopScanner();
        await wait(400);

        scanner = new Html5Qrcode('reader', { verbose: false });
        const source = { deviceId: { exact: selectedCameraId() } };

        try {
            await scanner.start(source, cameraConfig, onDecoded, () => {});
            running = true;
            root.dataset.camera = 'qr';
            setScanningState(true);
            setStatus(currentMode() === 'enroll' ? 'Scan the student QR to select them.' : 'Hold a QR code steady in the frame.');
        } catch (firstError) {
            await wait(700);

            try {
                scanner = new Html5Qrcode('reader', { verbose: false });
                await scanner.start(source, cameraConfig, onDecoded, () => {});
                running = true;
                root.dataset.camera = 'qr';
                setScanningState(true);
                setStatus(currentMode() === 'enroll' ? 'Scan the student QR to select them.' : 'Hold a QR code steady in the frame.');
            } catch (error) {
                await stopScanner();
                setStatus(describeStartError(error));
            }
        }
    };

    const runFaceLoop = async (loopId, samples = [], poseIndex = 0, poseSamples = 0) => {
        if (loopId !== faceLoop || ! running) {
            hideFaceGuide();
            return;
        }

        if (currentMode() === 'enroll' && selectedStudent) {
            showFaceGuide(enrollPoses[poseIndex], poseIndex, poseSamples);
        } else {
            hideFaceGuide();
        }

        if (faceVideo && faceVideo.readyState >= 2 && ! inFlight && faceHelpers) {
            try {
                if (currentMode() === 'face') {
                    const face = await faceHelpers.detectFace(faceVideo, { scoreThreshold: 0.4 });

                    if (face?.lookingAtCamera) {
                        lookStreak += 1;
                        setStatus(`Look at the camera… ${lookStreak}/${requiredLookFrames}`);

                        if (lookStreak >= requiredLookFrames) {
                            lookStreak = 0;
                            await submitFaceMatch(face.descriptor);
                        }
                    } else {
                        lookStreak = 0;
                        setStatus(face ? 'Look directly at the camera.' : 'No face in the frame.');
                    }
                }

                if (currentMode() === 'enroll' && selectedStudent) {
                    const pose = enrollPoses[poseIndex];
                    const face = await faceHelpers.detectFace(faceVideo, {
                        inputSize: pose.id === 'center' ? 416 : 320,
                        scoreThreshold: 0.4,
                    });

                    if (face && pose.match(face)) {
                        samples.push(face.descriptor);
                        poseSamples += 1;
                        setStatus(`${pose.label}… ${poseSamples}/${samplesPerPose}`);
                        showFaceGuide(pose, poseIndex, poseSamples);

                        if (poseSamples >= samplesPerPose) {
                            poseIndex += 1;
                            poseSamples = 0;

                            if (poseIndex >= enrollPoses.length) {
                                hideFaceGuide();
                                const averaged = faceHelpers.averageDescriptors(samples);

                                if (averaged) {
                                    await submitFaceEnroll(averaged);
                                }

                                return;
                            }

                            setStatus(enrollPoses[poseIndex].label);
                        }
                    } else if (! face) {
                        setStatus(`${pose.label}. Keep your face in the frame.`);
                    } else if (pose.id === 'center') {
                        setStatus('Look straight at the camera and hold still.');
                    } else {
                        setStatus(pose.label);
                    }
                }
            } catch {
                // Keep the camera loop alive if a single frame fails.
            }
        }

        window.setTimeout(() => {
            runFaceLoop(loopId, samples, poseIndex, poseSamples);
        }, currentMode() === 'enroll' ? 220 : 320);
    };

    const startFaceCamera = async () => {
        await stopScanner();
        await wait(400);

        const cameraId = selectedCameraId();

        try {
            setStatus('Loading face models…');
            const helpers = await ensureFaceHelpers();
            await helpers.loadFaceModels(modelsUrl);

            faceStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    deviceId: { exact: cameraId },
                    width: { ideal: 1280 },
                    height: { ideal: 720 },
                },
            });

            if (faceVideo) {
                faceVideo.srcObject = faceStream;
                await faceVideo.play();
            }

            running = true;
            lookStreak = 0;
            root.dataset.camera = 'face';
            setScanningState(true);
            setStatus(currentMode() === 'enroll' ? enrollPoses[0].label : 'Look directly at the camera.');
            if (currentMode() === 'enroll') {
                showFaceGuide(enrollPoses[0], 0, 0);
            }
            runFaceLoop(faceLoop);
        } catch (error) {
            await stopScanner();
            setStatus(describeStartError(error));
        }
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

        if (! selectedCameraId()) {
            setStatus('Select a camera first.');
            return;
        }

        if (currentMode() === 'face') {
            await startFaceCamera();
            return;
        }

        if (currentMode() === 'enroll') {
            if (selectedStudent) {
                await startFaceCamera();
                return;
            }

            await startQrCamera();
            return;
        }

        await startQrCamera();
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

            syncCameraPicker();
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

    const enrollPrompt = (student) => ({
        code: student.face_enrolled ? 'replace' : 'idle',
        student,
        message: student.face_enrolled
            ? 'This student already has a face. Capture again to replace it.'
            : 'Follow the on-screen guide to register this face.',
    });

    const renderSelectedStudent = (student) => {
        selectedStudent = student;
        enrollSelected?.classList.remove('hidden');
        enrollResults?.classList.add('hidden');

        if (enrollSelectedName) {
            enrollSelectedName.textContent = student.name;
        }

        if (enrollSelectedMeta) {
            enrollSelectedMeta.textContent = [student.student_number, student.level, student.section, student.face_enrolled ? 'Face enrolled' : 'No face yet']
                .filter(Boolean)
                .join(' · ');
        }

        if (enrollSearch) {
            enrollSearch.value = student.name;
        }
    };

    const lookupStudent = async (studentId) => {
        try {
            const response = await fetch(`${facesUrl}?student_id=${encodeURIComponent(studentId)}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                return null;
            }

            const payload = await response.json();

            return payload.students?.[0] ?? null;
        } catch {
            return null;
        }
    };

    const selectStudent = async (student) => {
        renderSelectedStudent(student);
        paintResult(enrollPrompt(student));

        if (currentMode() === 'enroll' && root.dataset.camera === 'qr') {
            startFaceCamera();
        }

        const fresh = await lookupStudent(student.id);

        if (! fresh || selectedStudent?.id !== fresh.id) {
            return;
        }

        renderSelectedStudent(fresh);
        paintResult(enrollPrompt(fresh));
    };

    const refreshSelectedStudent = async () => {
        if (! selectedStudent) {
            return;
        }

        const fresh = await lookupStudent(selectedStudent.id);

        if (! fresh || selectedStudent.id !== fresh.id) {
            return;
        }

        renderSelectedStudent(fresh);

        if (currentMode() === 'enroll' && ! running) {
            paintResult(enrollPrompt(fresh));
        }
    };

    const clearSelectedStudent = () => {
        selectedStudent = null;
        enrollSelected?.classList.add('hidden');

        if (enrollSearch) {
            enrollSearch.value = '';
        }

        paintResult({ code: 'idle', message: '' }, '');
        resultName.textContent = 'Waiting for a scan';
        resultMeta.textContent = idleMeta();
        resultMessage.textContent = '';
    };

    const renderSearchResults = (students) => {
        if (! enrollResults) {
            return;
        }

        enrollResults.innerHTML = '';

        if (! students.length) {
            enrollResults.classList.remove('hidden');
            const empty = document.createElement('li');
            empty.className = 'px-3 py-2 text-sm text-slate-500';
            empty.textContent = 'No matching students.';
            enrollResults.append(empty);
            return;
        }

        students.forEach((student) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'flex w-full flex-col px-3 py-2 text-left hover:bg-slate-50';
            button.innerHTML = `<span class="text-sm font-medium text-slate-900"></span><span class="text-xs text-slate-500"></span>`;
            button.querySelector('span').textContent = student.name;
            button.querySelectorAll('span')[1].textContent = [student.student_number, student.level, student.section, student.face_enrolled ? 'Enrolled' : '']
                .filter(Boolean)
                .join(' · ');
            button.addEventListener('click', () => {
                selectStudent(student);
            });
            item.append(button);
            enrollResults.append(item);
        });

        enrollResults.classList.remove('hidden');
    };

    const searchStudents = async (query) => {
        const response = await fetch(`${facesUrl}?q=${encodeURIComponent(query)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            return;
        }

        const payload = await response.json();
        renderSearchResults(payload.students || []);
    };

    const setMode = async (mode) => {
        if (currentMode() === mode && ! running) {
            root.dataset.mode = mode;
            return;
        }

        await stopScanner();
        root.dataset.mode = mode;

        document.querySelectorAll('[data-scanner-mode]').forEach((button) => {
            const active = button.dataset.scannerMode === mode;
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
            button.classList.toggle('text-white', active);
            button.classList.toggle('text-slate-300', ! active);
        });

        enrollPanel?.classList.toggle('hidden', mode !== 'enroll');

        if (mode !== 'enroll') {
            enrollResults?.classList.add('hidden');
        }

        resultName.textContent = 'Waiting for a scan';
        resultMeta.textContent = idleMeta();
        resultMessage.textContent = '';
        setStatus(mode === 'enroll'
            ? 'Search a student, or start the camera to scan their QR.'
            : mode === 'face'
                ? 'Camera ready. Press start scanning to recognize faces.'
                : 'Camera ready. Press start scanning.');

        if (mode === 'face' || mode === 'enroll') {
            ensureFaceHelpers()
                .then((helpers) => helpers.loadFaceModels(modelsUrl))
                .catch(() => {});
        }
    };

    startButton?.addEventListener('click', () => {
        startScanner();
    });

    stopButton?.addEventListener('click', () => {
        stopScanner();
    });

    cameraPickerButton?.addEventListener('click', () => {
        if (cameraPickerMenu?.hidden) {
            openCameraPicker();
            return;
        }

        closeCameraPicker();
    });

    document.addEventListener('click', (event) => {
        if (! event.target.closest('.camera-picker')) {
            closeCameraPicker();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCameraPicker();
        }
    });

    cameraSelect?.addEventListener('change', async () => {
        syncCameraPicker();

        if (running) {
            await stopScanner();
            await startScanner();
        }
    });

    document.querySelectorAll('[data-scanner-mode]').forEach((button) => {
        button.addEventListener('click', () => {
            setMode(button.dataset.scannerMode);
        });
    });

    enrollSearch?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        const query = enrollSearch.value.trim();

        if (query.length < 1) {
            enrollResults?.classList.add('hidden');
            return;
        }

        searchTimer = window.setTimeout(() => {
            searchStudents(query);
        }, 280);
    });

    enrollClear?.addEventListener('click', () => {
        clearSelectedStudent();
        enrollSearch?.focus();
    });

    syncCameraPicker();

    window.addEventListener('pagehide', () => {
        stopScanner();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopScanner();
            return;
        }

        refreshSelectedStudent();
    });

    loadCameras();
}
