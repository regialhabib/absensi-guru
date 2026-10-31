import { Head, router } from '@inertiajs/react';
import { Camera, MapPin, TriangleAlert } from 'lucide-react';
import { useState } from 'react';
import Pagination from '@/components/admin/pagination';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/attendances';
import { photo } from '@/routes/attendance';
import type { Attendance, Decimal, Paginated } from '@/types';

type Props = {
    attendances: Paginated<Attendance>;
    filters: { date: string; user_id: number | null };
    teachers: { id: number; name: string }[];
};

type PhotoView = { attendance: Attendance; type: 'masuk' | 'pulang' };

function formatTime(value: string | null): string {
    return value
        ? new Date(value).toLocaleTimeString('id-ID', {
              hour: '2-digit',
              minute: '2-digit',
          })
        : '-';
}

function formatNumber(value: Decimal | null): string {
    return value === null ? '-' : Number(value).toFixed(0);
}

function mapsUrl(latitude: Decimal, longitude: Decimal): string {
    return `https://www.google.com/maps?q=${latitude},${longitude}`;
}

function FaceWarning({ detected }: { detected: boolean | null }) {
    if (detected !== false) {
        return null;
    }

    return (
        <Badge variant="destructive" className="mt-1">
            <TriangleAlert /> Wajah tidak terdeteksi
        </Badge>
    );
}

export default function AttendancesIndex({
    attendances,
    filters,
    teachers,
}: Props) {
    const [viewing, setViewing] = useState<PhotoView | null>(null);

    function applyFilters(next: Partial<{ date: string; user_id: string }>) {
        const query = {
            date: filters.date,
            user_id: filters.user_id ? String(filters.user_id) : '',
            ...next,
        };

        router.get(
            index.url(),
            Object.fromEntries(
                Object.entries(query).filter(([, value]) => value),
            ),
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Absensi" />
            <div className="space-y-4 p-4">
                <Heading
                    title="Absensi"
                    description="Data absensi masuk dan pulang guru"
                />

                <div className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="date">Tanggal</Label>
                        <Input
                            id="date"
                            type="date"
                            value={filters.date}
                            onChange={(event) =>
                                applyFilters({ date: event.target.value })
                            }
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label>Guru</Label>
                        <Select
                            value={
                                filters.user_id
                                    ? String(filters.user_id)
                                    : 'all'
                            }
                            onValueChange={(value) =>
                                applyFilters({
                                    user_id: value === 'all' ? '' : value,
                                })
                            }
                        >
                            <SelectTrigger className="w-56">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua guru</SelectItem>
                                {teachers.map((teacher) => (
                                    <SelectItem
                                        key={teacher.id}
                                        value={String(teacher.id)}
                                    >
                                        {teacher.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Guru</th>
                                <th className="px-3 py-2">Masuk</th>
                                <th className="px-3 py-2">Pulang</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Jarak / Akurasi</th>
                                <th className="px-3 py-2">Bukti</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attendances.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada data absensi.
                                    </td>
                                </tr>
                            )}
                            {attendances.data.map((attendance) => (
                                <tr
                                    key={attendance.id}
                                    className="border-t align-top"
                                >
                                    <td className="px-3 py-2">
                                        <p className="font-medium">
                                            {attendance.user?.name}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            {attendance.user?.nip ?? '-'}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {formatTime(attendance.check_in_at)}
                                        <div>
                                            <FaceWarning
                                                detected={
                                                    attendance.check_in_face_detected
                                                }
                                            />
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">
                                        {formatTime(attendance.check_out_at)}
                                        <div>
                                            <FaceWarning
                                                detected={
                                                    attendance.check_out_face_detected
                                                }
                                            />
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">
                                        <Badge
                                            variant={
                                                attendance.status ===
                                                'terlambat'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                        >
                                            {attendance.status === 'terlambat'
                                                ? 'Terlambat'
                                                : 'Hadir'}
                                        </Badge>
                                        {attendance.late_minutes > 0 && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Telat {attendance.late_minutes}{' '}
                                                menit
                                            </p>
                                        )}
                                        {attendance.early_leave_minutes > 0 && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                Pulang awal{' '}
                                                {attendance.early_leave_minutes}{' '}
                                                menit
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2 text-xs">
                                        <p>
                                            Masuk:{' '}
                                            {formatNumber(
                                                attendance.check_in_distance,
                                            )}{' '}
                                            m /{' '}
                                            {formatNumber(
                                                attendance.check_in_accuracy,
                                            )}{' '}
                                            m
                                        </p>
                                        {attendance.check_out_at && (
                                            <p>
                                                Pulang:{' '}
                                                {formatNumber(
                                                    attendance.check_out_distance,
                                                )}{' '}
                                                m /{' '}
                                                {formatNumber(
                                                    attendance.check_out_accuracy,
                                                )}{' '}
                                                m
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap gap-1">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    setViewing({
                                                        attendance,
                                                        type: 'masuk',
                                                    })
                                                }
                                            >
                                                <Camera /> Selfie masuk
                                            </Button>
                                            {attendance.check_out_photo && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() =>
                                                        setViewing({
                                                            attendance,
                                                            type: 'pulang',
                                                        })
                                                    }
                                                >
                                                    <Camera /> Selfie pulang
                                                </Button>
                                            )}
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <a
                                                    href={mapsUrl(
                                                        attendance.check_in_latitude,
                                                        attendance.check_in_longitude,
                                                    )}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <MapPin /> Peta
                                                </a>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={attendances} />
            </div>

            <Dialog
                open={viewing !== null}
                onOpenChange={(open) => !open && setViewing(null)}
            >
                <DialogContent>
                    <DialogTitle>
                        Selfie {viewing?.type} -{' '}
                        {viewing?.attendance.user?.name}
                    </DialogTitle>
                    <DialogDescription>
                        Foto diambil langsung saat absensi.
                    </DialogDescription>
                    {viewing && (
                        <img
                            src={
                                photo({
                                    attendance: viewing.attendance.id,
                                    type: viewing.type,
                                }).url
                            }
                            alt={`Selfie ${viewing.type}`}
                            className="max-h-[70vh] w-full rounded-md object-contain"
                        />
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

AttendancesIndex.layout = {
    breadcrumbs: [{ title: 'Absensi', href: index() }],
};
