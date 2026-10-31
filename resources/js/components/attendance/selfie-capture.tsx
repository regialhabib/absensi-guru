import { Camera, RefreshCw } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useCamera } from '@/hooks/use-camera';
import type { CameraError } from '@/hooks/use-camera';
import { useFaceDetection } from '@/hooks/use-face-detection';

const CAPTURE_WIDTH = 720;

const cameraErrorMessages: Record<
    Exclude<CameraError, null>,
    { title: string; description: string }
> = {
    denied: {
        title: 'Izin kamera ditolak',
        description:
            'Ketuk ikon gembok di sebelah alamat situs, pilih Izin atau Permissions, ubah Kamera menjadi Izinkan, lalu muat ulang halaman ini.',
    },
    'not-found': {
        title: 'Kamera tidak ditemukan',
        description:
            'Perangkat ini tidak memiliki kamera depan yang dapat dipakai. Coba gunakan perangkat lain.',
    },
    insecure: {
        title: 'Koneksi tidak aman',
        description:
            'Kamera dan GPS hanya berfungsi lewat HTTPS. Buka situs ini dengan alamat yang diawali https://.',
    },
    failed: {
        title: 'Kamera gagal dibuka',
        description:
            'Tutup aplikasi lain yang memakai kamera, lalu muat ulang halaman ini.',
    },
};

type Props = {
    photo: File | null;
    disabled: boolean;
    onCapture: (photo: File, faceDetected: boolean) => void;
    onRetake: () => void;
};

export default function SelfieCapture({
    photo,
    disabled,
    onCapture,
    onRetake,
}: Props) {
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const { videoRef, isReady, error, stop } = useCamera(photo === null);
    const { status, faceCount } = useFaceDetection(videoRef, isReady);

    useEffect(() => {
        if (!photo) {
            setPreviewUrl(null);

            return;
        }

        const url = URL.createObjectURL(photo);
        setPreviewUrl(url);

        return () => URL.revokeObjectURL(url);
    }, [photo]);

    const detectionFailed = status === 'failed';
    const canCapture =
        isReady &&
        !disabled &&
        (detectionFailed || (status === 'ready' && faceCount === 1));

    function capture() {
        const video = videoRef.current;

        if (!video || !video.videoWidth) {
            return;
        }

        const canvas = document.createElement('canvas');
        canvas.width = CAPTURE_WIDTH;
        canvas.height = Math.round(
            (video.videoHeight / video.videoWidth) * CAPTURE_WIDTH,
        );
        canvas
            .getContext('2d')
            ?.drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    return;
                }

                stop();
                onCapture(
                    new File([blob], 'selfie.jpg', { type: 'image/jpeg' }),
                    !detectionFailed && faceCount === 1,
                );
            },
            'image/jpeg',
            0.8,
        );
    }

    function faceMessage(): string | null {
        if (!isReady || detectionFailed) {
            return null;
        }

        if (status === 'loading') {
            return 'Menyiapkan deteksi wajah...';
        }

        if (faceCount === 0) {
            return 'Wajah tidak terdeteksi';
        }

        return faceCount > 1 ? 'Terdeteksi lebih dari satu wajah' : null;
    }

    if (previewUrl) {
        return (
            <div className="space-y-3">
                <img
                    src={previewUrl}
                    alt="Hasil swafoto"
                    className="aspect-[3/4] w-full rounded-lg border bg-muted object-cover"
                />
                <Button
                    type="button"
                    variant="outline"
                    className="w-full"
                    onClick={onRetake}
                    disabled={disabled}
                >
                    <RefreshCw />
                    Ulangi
                </Button>
            </div>
        );
    }

    if (error) {
        const message = cameraErrorMessages[error];

        return (
            <Alert variant="destructive">
                <Camera />
                <AlertTitle>{message.title}</AlertTitle>
                <AlertDescription>{message.description}</AlertDescription>
            </Alert>
        );
    }

    const message = faceMessage();

    return (
        <div className="space-y-3">
            <div className="relative aspect-[3/4] w-full overflow-hidden rounded-lg border bg-muted">
                <video
                    ref={videoRef}
                    className="size-full -scale-x-100 object-cover"
                    playsInline
                    muted
                />
                {!isReady && (
                    <div className="absolute inset-0 flex items-center justify-center">
                        <Spinner />
                    </div>
                )}
            </div>
            {message && (
                <p className="text-center text-sm font-medium text-amber-600 dark:text-amber-400">
                    {message}
                </p>
            )}
            {detectionFailed && (
                <p className="text-center text-xs text-muted-foreground">
                    Deteksi wajah tidak tersedia di perangkat ini. Anda tetap
                    dapat mengambil foto.
                </p>
            )}
            <Button
                type="button"
                className="w-full"
                onClick={capture}
                disabled={!canCapture}
            >
                <Camera />
                Ambil Foto
            </Button>
        </div>
    );
}
