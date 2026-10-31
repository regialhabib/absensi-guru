import type { RefObject } from 'react';
import { useEffect, useState } from 'react';

export type FaceDetectionStatus = 'loading' | 'ready' | 'failed';

const DETECTION_INTERVAL_MS = 200;

/**
 * Counts faces in the live video at about 5 fps. MediaPipe is imported
 * dynamically so it never enters the main bundle or the SSR render.
 */
export function useFaceDetection(
    videoRef: RefObject<HTMLVideoElement | null>,
    isVideoReady: boolean,
) {
    const [status, setStatus] = useState<FaceDetectionStatus>('loading');
    const [faceCount, setFaceCount] = useState(0);

    useEffect(() => {
        if (!isVideoReady) {
            return;
        }

        let cancelled = false;
        let timer: number | undefined;
        let detector: { close: () => void } | null = null;

        async function start() {
            try {
                const { FaceDetector, FilesetResolver } =
                    await import('@mediapipe/tasks-vision');
                const fileset =
                    await FilesetResolver.forVisionTasks('/mediapipe/wasm');
                const faceDetector = await FaceDetector.createFromOptions(
                    fileset,
                    {
                        baseOptions: {
                            modelAssetPath:
                                '/mediapipe/blaze_face_short_range.tflite',
                        },
                        runningMode: 'VIDEO',
                    },
                );

                if (cancelled) {
                    faceDetector.close();

                    return;
                }

                detector = faceDetector;
                setStatus('ready');

                const detect = () => {
                    const video = videoRef.current;

                    if (video && video.readyState >= 2) {
                        const result = faceDetector.detectForVideo(
                            video,
                            performance.now(),
                        );
                        setFaceCount(result.detections.length);
                    }

                    timer = window.setTimeout(detect, DETECTION_INTERVAL_MS);
                };

                detect();
            } catch {
                if (!cancelled) {
                    setStatus('failed');
                }
            }
        }

        void start();

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
            detector?.close();
        };
    }, [isVideoReady, videoRef]);

    return { status, faceCount };
}
