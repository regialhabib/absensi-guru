import { Head, useForm } from '@inertiajs/react';
import { CheckCircle2, ShieldCheck } from 'lucide-react';
import LocationStatus from '@/components/attendance/location-status';
import SelfieCapture from '@/components/attendance/selfie-capture';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { distanceInMeters, useGeolocation } from '@/hooks/use-geolocation';
import { checkIn, checkOut, show } from '@/routes/attendance';
import { DAY_STATUS_LABELS } from '@/types/absensi';
import type { AttendanceStatus, DayStatus } from '@/types/absensi';

type Props = {
    today: {
        date: string;
        isWorkday: boolean;
        startsAt: string | null;
        endsAt: string | null;
        status: DayStatus;
    };
    attendance: {
        checkInAt: string;
        checkOutAt: string | null;
        status: AttendanceStatus;
        lateMinutes: number;
        earlyLeaveMinutes: number;
    } | null;
    school: {
        name: string;
        latitude: number | null;
        longitude: number | null;
        radiusMeters: number;
        maxAccuracyMeters: number;
    };
    canCheckIn: boolean;
    canCheckOut: boolean;
    blockedReason: string | null;
};

type FormErrors = Partial<
    Record<'location' | 'attendance' | 'photo', string | undefined>
>;

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Jakarta',
    });
}

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00Z`).toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    });
}

export default function AttendanceCheck({
    today,
    attendance,
    school,
    canCheckIn,
    canCheckOut,
    blockedReason,
}: Props) {
    const canAct = canCheckIn || canCheckOut;
    const { coords, error: locationError } = useGeolocation();
    const form = useForm<{
        photo: File | null;
        latitude: number | null;
        longitude: number | null;
        accuracy: number | null;
        face_detected: number;
    }>({
        photo: null,
        latitude: null,
        longitude: null,
        accuracy: null,
        face_detected: 0,
    });
    const errors = form.errors as FormErrors;

    const hasSchoolLocation =
        school.latitude !== null && school.longitude !== null;
    const distance =
        coords && hasSchoolLocation
            ? distanceInMeters(
                  coords.latitude,
                  coords.longitude,
                  school.latitude as number,
                  school.longitude as number,
              )
            : null;
    const isInsideRadius = distance !== null && distance <= school.radiusMeters;
    const isAccurateEnough =
        coords !== null && coords.accuracy <= school.maxAccuracyMeters;
    const canSubmit =
        form.data.photo !== null &&
        coords !== null &&
        isInsideRadius &&
        isAccurateEnough &&
        !form.processing;

    function submit() {
        if (!coords || !canSubmit) {
            return;
        }

        form.transform((data) => ({
            ...data,
            latitude: coords.latitude,
            longitude: coords.longitude,
            accuracy: coords.accuracy,
        }));
        form.post((canCheckIn ? checkIn() : checkOut()).url, {
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Absensi" />
            <div className="mx-auto flex w-full max-w-md flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>{school.name}</CardTitle>
                        <CardDescription>
                            {formatDate(today.date)}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between gap-2 text-sm">
                        <span>
                            {today.isWorkday && today.startsAt && today.endsAt
                                ? `Jadwal ${today.startsAt} - ${today.endsAt}`
                                : 'Tidak ada jadwal hari ini'}
                        </span>
                        <Badge variant="secondary">
                            {DAY_STATUS_LABELS[today.status]}
                        </Badge>
                    </CardContent>
                </Card>

                {attendance && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {!canAct && (
                                    <CheckCircle2 className="size-5 text-green-600" />
                                )}
                                Absensi Hari Ini
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p>
                                Masuk:{' '}
                                <strong>
                                    {formatTime(attendance.checkInAt)}
                                </strong>
                                {attendance.lateMinutes > 0 &&
                                    ` (terlambat ${attendance.lateMinutes} menit)`}
                            </p>
                            <p>
                                Pulang:{' '}
                                <strong>
                                    {attendance.checkOutAt
                                        ? formatTime(attendance.checkOutAt)
                                        : 'Belum absen pulang'}
                                </strong>
                                {attendance.earlyLeaveMinutes > 0 &&
                                    ` (pulang awal ${attendance.earlyLeaveMinutes} menit)`}
                            </p>
                            {attendance.checkOutAt && (
                                <p className="pt-2 text-muted-foreground">
                                    Absensi hari ini sudah selesai. Terima
                                    kasih.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                {blockedReason && (
                    <Alert>
                        <AlertTitle>Absensi belum tersedia</AlertTitle>
                        <AlertDescription>{blockedReason}</AlertDescription>
                    </Alert>
                )}

                {canAct && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {canCheckIn ? 'Absen Masuk' : 'Absen Pulang'}
                            </CardTitle>
                            <CardDescription>
                                Ambil selfie langsung dari kamera depan.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <LocationStatus
                                error={locationError}
                                hasPosition={coords !== null}
                                distance={distance}
                                accuracy={coords?.accuracy ?? null}
                                radiusMeters={school.radiusMeters}
                                maxAccuracyMeters={school.maxAccuracyMeters}
                                isInsideRadius={isInsideRadius}
                                isAccurateEnough={isAccurateEnough}
                            />
                            {!hasSchoolLocation && (
                                <p className="text-sm text-destructive">
                                    Lokasi sekolah belum diatur oleh admin.
                                </p>
                            )}

                            <SelfieCapture
                                photo={form.data.photo}
                                disabled={form.processing}
                                onCapture={(photo, faceDetected) => {
                                    form.clearErrors();
                                    form.setData((data) => ({
                                        ...data,
                                        photo,
                                        face_detected: faceDetected ? 1 : 0,
                                    }));
                                }}
                                onRetake={() => form.setData('photo', null)}
                            />

                            {(errors.location ||
                                errors.attendance ||
                                errors.photo) && (
                                <Alert variant="destructive">
                                    <AlertDescription>
                                        {[
                                            errors.location,
                                            errors.attendance,
                                            errors.photo,
                                        ]
                                            .filter(Boolean)
                                            .map((message) => (
                                                <span
                                                    key={message}
                                                    className="block"
                                                >
                                                    {message}
                                                </span>
                                            ))}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {form.processing && form.progress && (
                                <p className="text-center text-sm text-muted-foreground">
                                    Mengunggah {form.progress.percentage}%
                                </p>
                            )}

                            <Button
                                type="button"
                                className="w-full"
                                size="lg"
                                disabled={!canSubmit}
                                onClick={submit}
                            >
                                {form.processing && <Spinner />}
                                {form.processing
                                    ? 'Mengirim...'
                                    : canCheckIn
                                      ? 'Kirim Absen Masuk'
                                      : 'Kirim Absen Pulang'}
                            </Button>
                            {form.data.photo !== null && !canSubmit && (
                                <p className="text-center text-xs text-muted-foreground">
                                    Tombol aktif setelah Anda berada di dalam
                                    area sekolah dengan akurasi GPS yang cukup.
                                </p>
                            )}

                            <p className="flex gap-2 text-xs text-muted-foreground">
                                <ShieldCheck className="size-4 shrink-0" />
                                Selfie dan lokasi Anda direkam sebagai bukti
                                kehadiran sesuai Undang-Undang Pelindungan Data
                                Pribadi dan hanya dipakai untuk keperluan
                                absensi.
                            </p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

AttendanceCheck.layout = {
    breadcrumbs: [
        {
            title: 'Absensi',
            href: show(),
        },
    ],
};
