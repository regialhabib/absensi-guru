import { MapPin } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import type { GeolocationError } from '@/hooks/use-geolocation';

const errorMessages: Record<
    Exclude<GeolocationError, null>,
    { title: string; description: string }
> = {
    denied: {
        title: 'Izin lokasi ditolak',
        description:
            'Ketuk ikon gembok di sebelah alamat situs, pilih Izin atau Permissions, ubah Lokasi menjadi Izinkan, lalu muat ulang halaman ini. Pastikan GPS di ponsel juga aktif.',
    },
    unavailable: {
        title: 'Lokasi belum tersedia',
        description:
            'Aktifkan GPS ponsel dan keluar ke tempat terbuka, lalu tunggu beberapa saat.',
    },
    insecure: {
        title: 'Koneksi tidak aman',
        description:
            'Kamera dan GPS hanya berfungsi lewat HTTPS. Buka situs ini dengan alamat yang diawali https://.',
    },
};

type Props = {
    error: GeolocationError;
    hasPosition: boolean;
    distance: number | null;
    accuracy: number | null;
    radiusMeters: number;
    maxAccuracyMeters: number;
    isInsideRadius: boolean;
    isAccurateEnough: boolean;
};

export default function LocationStatus({
    error,
    hasPosition,
    distance,
    accuracy,
    radiusMeters,
    maxAccuracyMeters,
    isInsideRadius,
    isAccurateEnough,
}: Props) {
    if (error) {
        const message = errorMessages[error];

        return (
            <Alert variant="destructive">
                <MapPin />
                <AlertTitle>{message.title}</AlertTitle>
                <AlertDescription>{message.description}</AlertDescription>
            </Alert>
        );
    }

    if (!hasPosition || accuracy === null) {
        return (
            <p className="text-sm text-muted-foreground">
                Mencari lokasi Anda...
            </p>
        );
    }

    return (
        <div className="space-y-2 text-sm">
            <div className="flex items-center justify-between gap-2">
                <span>
                    Jarak ke sekolah:{' '}
                    <strong>
                        {distance === null
                            ? 'tidak diketahui'
                            : `${Math.round(distance)} m`}
                    </strong>
                </span>
                <Badge variant={isInsideRadius ? 'default' : 'destructive'}>
                    {isInsideRadius ? 'Dalam area' : 'Di luar area'}
                </Badge>
            </div>
            <div className="flex items-center justify-between gap-2">
                <span>
                    Akurasi GPS: <strong>{Math.round(accuracy)} m</strong>
                </span>
                <Badge variant={isAccurateEnough ? 'default' : 'destructive'}>
                    {isAccurateEnough ? 'Akurasi baik' : 'Akurasi rendah'}
                </Badge>
            </div>
            <p className="text-xs text-muted-foreground">
                Batas area {radiusMeters} m, akurasi maksimal{' '}
                {maxAccuracyMeters} m.
            </p>
        </div>
    );
}
