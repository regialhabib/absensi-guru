import { Head, Link, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { show } from '@/routes/attendance';
import { DAY_STATUS_LABELS } from '@/types/absensi';
import type { DayStatus, MonthlySummary } from '@/types/absensi';

type Props = {
    today: {
        date: string;
        status: DayStatus;
        check_in_at: string | null;
        check_out_at: string | null;
    };
    month: { year: number; month: number };
    summary: MonthlySummary;
};

const SUMMARY_ITEMS: { key: keyof MonthlySummary; label: string }[] = [
    { key: 'hadir', label: 'Hadir' },
    { key: 'terlambat', label: 'Terlambat' },
    { key: 'izin', label: 'Izin' },
    { key: 'sakit', label: 'Sakit' },
    { key: 'cuti', label: 'Cuti' },
    { key: 'alpa', label: 'Alpa' },
];

export default function Dashboard({ today, month, summary }: Props) {
    const { timezone } = usePage().props;
    const dateLabel = new Date(`${today.date}T00:00:00`).toLocaleDateString(
        'id-ID',
        { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' },
    );
    const monthLabel = new Date(
        month.year,
        month.month - 1,
        1,
    ).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
    const needsCheckIn = today.check_in_at === null;
    const needsCheckOut = !needsCheckIn && today.check_out_at === null;

    return (
        <>
            <Head title="Dasbor" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between gap-2">
                            <span>{dateLabel}</span>
                            <Badge variant="secondary">
                                {DAY_STATUS_LABELS[today.status]}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <dl className="grid grid-cols-2 gap-4 text-center">
                            <div>
                                <dt className="text-sm text-muted-foreground">
                                    Absen masuk
                                </dt>
                                <dd className="text-2xl font-semibold">
                                    {formatTime(today.check_in_at, timezone)}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm text-muted-foreground">
                                    Absen pulang
                                </dt>
                                <dd className="text-2xl font-semibold">
                                    {formatTime(today.check_out_at, timezone)}
                                </dd>
                            </div>
                        </dl>

                        <Button
                            asChild
                            size="lg"
                            className="h-12 w-full text-base"
                        >
                            <Link href={show()}>
                                {needsCheckIn
                                    ? 'Absen Masuk'
                                    : needsCheckOut
                                      ? 'Absen Pulang'
                                      : 'Lihat Absensi Hari Ini'}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Rekap {monthLabel}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-3 gap-3 text-center">
                            {SUMMARY_ITEMS.map((item) => (
                                <div
                                    key={item.key}
                                    className="rounded-lg border p-3"
                                >
                                    <dd className="text-2xl font-semibold">
                                        {summary[item.key]}
                                    </dd>
                                    <dt className="text-xs text-muted-foreground">
                                        {item.label}
                                    </dt>
                                </div>
                            ))}
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dasbor',
            href: dashboard(),
        },
    ],
};
