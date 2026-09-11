import * as faceapi from '@vladmandic/face-api';

let modelsReady = false;
let modelsLoading = null;

export async function loadFaceModels(modelUrl) {
    if (modelsReady) {
        return;
    }

    if (! modelsLoading) {
        modelsLoading = Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl),
            faceapi.nets.faceLandmark68Net.loadFromUri(modelUrl),
            faceapi.nets.faceRecognitionNet.loadFromUri(modelUrl),
        ]).then(() => {
            modelsReady = true;
        });
    }

    await modelsLoading;
}

const midpoint = (left, right) => ({
    x: (left.x + right.x) / 2,
    y: (left.y + right.y) / 2,
});

const distance = (left, right) => Math.hypot(left.x - right.x, left.y - right.y);

const eyeAspectRatio = (outer, topOuter, topInner, inner, bottomInner, bottomOuter) => {
    const vertical = distance(topOuter, bottomOuter) + distance(topInner, bottomInner);
    const horizontal = distance(outer, inner) * 2;

    return horizontal === 0 ? 0 : vertical / horizontal;
};

const lookingAtCamera = {
    minScore: 0.42,
    maxYaw: 0.12,
    minPitch: 0.04,
    maxPitch: 0.32,
    minEar: 0.12,
    maxEarDiff: 0.18,
    minSize: 0.12,
};

export const isLookingAtCamera = (face) => {
    if (! face) {
        return false;
    }

    return face.score >= lookingAtCamera.minScore
        && Math.abs(face.yaw) <= lookingAtCamera.maxYaw
        && face.pitch >= lookingAtCamera.minPitch
        && face.pitch <= lookingAtCamera.maxPitch
        && face.leftEar >= lookingAtCamera.minEar
        && face.rightEar >= lookingAtCamera.minEar
        && Math.abs(face.leftEar - face.rightEar) <= lookingAtCamera.maxEarDiff
        && face.size >= lookingAtCamera.minSize;
};

export async function detectFace(input, options = {}) {
    const detection = await faceapi
        .detectSingleFace(input, new faceapi.TinyFaceDetectorOptions({
            inputSize: options.inputSize ?? 416,
            scoreThreshold: options.scoreThreshold ?? 0.5,
        }))
        .withFaceLandmarks()
        .withFaceDescriptor();

    if (! detection?.descriptor) {
        return null;
    }

    const points = detection.landmarks.positions;
    const midEyes = midpoint(midpoint(points[36], points[39]), midpoint(points[42], points[45]));
    const nose = points[30];
    const box = detection.detection.box;
    const frameWidth = input.videoWidth || input.width || box.width;

    const face = {
        descriptor: Array.from(detection.descriptor),
        yaw: (nose.x - midEyes.x) / Math.max(box.width, 1),
        pitch: (nose.y - midEyes.y) / Math.max(box.height, 1),
        score: detection.detection.score,
        leftEar: eyeAspectRatio(points[36], points[37], points[38], points[39], points[40], points[41]),
        rightEar: eyeAspectRatio(points[42], points[43], points[44], points[45], points[46], points[47]),
        size: box.width / Math.max(frameWidth, 1),
    };

    face.lookingAtCamera = isLookingAtCamera(face);

    return face;
}

export async function detectDescriptor(input) {
    const face = await detectFace(input);

    return face?.lookingAtCamera ? face.descriptor : null;
}

export function averageDescriptors(descriptors) {
    if (! descriptors.length) {
        return null;
    }

    const length = descriptors[0].length;
    const totals = new Array(length).fill(0);

    descriptors.forEach((descriptor) => {
        for (let index = 0; index < length; index += 1) {
            totals[index] += Number(descriptor[index] || 0);
        }
    });

    return totals.map((value) => value / descriptors.length);
}
