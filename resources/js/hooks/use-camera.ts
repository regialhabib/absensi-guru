import { useCallback, useEffect, useRef, useState } from 'react';

export type CameraError = 'denied' | 'not-found' | 'insecure' | 'failed' | null;

/** Front camera stream attached to `videoRef`. Only runs while `enabled`. */
export function useCamera(enabled: boolean) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [isReady, setIsReady] = useState(false);
    const [error, setError] = useState<CameraError>(null);

    const stop = useCallback(() => {
        streamRef.current?.getTracks().forEach((track) => track.stop());
        streamRef.current = null;
        setIsReady(false);
    }, []);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        if (!window.isSecureContext || !navigator.mediaDevices) {
            setError('insecure');

            return;
        }

        let cancelled = false;

        navigator.mediaDevices
            .getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(async (stream) => {
                if (cancelled) {
                    stream.getTracks().forEach((track) => track.stop());

                    return;
                }

                streamRef.current = stream;
                setError(null);

                const video = videoRef.current;

                if (video) {
                    video.srcObject = stream;
                    await video.play().catch(() => undefined);
                    setIsReady(true);
                }
            })
            .catch((cameraError: DOMException) => {
                if (cancelled) {
                    return;
                }

                if (
                    cameraError.name === 'NotAllowedError' ||
                    cameraError.name === 'SecurityError'
                ) {
                    setError('denied');
                } else if (
                    cameraError.name === 'NotFoundError' ||
                    cameraError.name === 'OverconstrainedError'
                ) {
                    setError('not-found');
                } else {
                    setError('failed');
                }
            });

        return () => {
            cancelled = true;
            stop();
        };
    }, [enabled, stop]);

    return { videoRef, isReady, error, stop };
}
