import { Head, router, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatTime } from '@/lib/datetime';
import { history } from '@/routes/attendance';
import { DAY_STATUS_LABELS } from '@/types/absensi';
import type { DayStatus, MonthlySummary } from '@/types/absensi';

type Day = {
    date: string;
    status: DayStatus;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number;
    early_leave_minutes: number;
};

type Props = {
    year: number;
    month: number;
    days: Day[];
    summary: MonthlySummary;
};

const MONTH_NAMES = Array.from({ length: 12 }, (_, index) =>
    new Date(2000, index, 1).toLocaleDateString('id-ID', { month: 'long' }),
);

const SUMMARY_ITEMS: { key: keyof MonthlySummary; label: string }[] = [
    { key: 'hadir', label: 'Hadir' },
    { key: 'terlambat', label: 'Terlambat' },
    { key: 'izin', label: 'Izin' },
    { key: 'sakit', label: 'Sakit' },
    { key: 'cuti', label: 'Cuti' },
    { key: 'alpa', label: 'Alpa' },
];

const selectClass =
    'h-10 rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none';

function statusVariant(status: DayStatus) {
    if (status === 'alpa') {
        return 'destructive' as const;
    }

    if (status === 'libur' || status === 'belum_absen') {
        return 'outline' as const;
    }

    return 'secondary' as const;
}

export default function AttendanceHistory({
    year,
    month,
    days,
    summary,
}: Props) {
    const { timezone } = usePage().props;
    const currentYear = new Date().getFullYear();
    const years = Array.from(
        new Set([year, currentYear - 1, currentYear, currentYear + 1]),
    ).sort((a, b) => a - b);

    const changePeriod = (nextYear: number, nextMonth: number) => {
        router.get(
            history({ query: { year: nextYear, month: nextMonth } }),
            {},
            { preserveScroll: true, preserveState: true },
        );
    };

    return (
        <>
            <Head title="Riwayat Absensi" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div className="flex gap-2">
                    <select
                        aria-label="Bulan"
                        className={`${selectClass} flex-1`}
                        value={month}
                        onChange={(event) =>
                            changePeriod(year, Number(event.target.value))
                        }
                    >
                        {MONTH_NAMES.map((name, index) => (
                            <option key={name} value={index + 1}>
                                {name}
                            </option>
                        ))}
                    </select>
                    <select
                        aria-label="Tahun"
                        className={selectClass}
                        value={year}
                        onChange={(event) =>
                            changePeriod(Number(event.target.value), month)
                        }
                    >
                        {years.map((optionYear) => (
                            <option key={optionYear} value={optionYear}>
                                {optionYear}
                            </option>
                        ))}
                    </select>
                </div>

                <dl className="grid grid-cols-3 gap-2 text-center sm:grid-cols-6">
                    {SUMMARY_ITEMS.map((item) => (
                        <div key={item.key} className="rounded-lg border p-2">
                            <dd className="text-xl font-semibold">
                                {summary[item.key]}
                            </dd>
                            <dt className="text-xs text-muted-foreground">
                                {item.label}
                            </dt>
                        </div>
                    ))}
                </dl>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {MONTH_NAMES[month - 1]} {year}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <ul className="divide-y">
                            {days.map((day) => {
                                const date = new Date(`${day.date}T00:00:00`);

                                return (
                                    <li
                                        key={day.date}
                                        className="flex items-center gap-3 px-4 py-3"
                                    >
                                        <div className="w-14 shrink-0">
                                            <div className="text-lg leading-none font-semibold">
                                                {date.getDate()}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {date.toLocaleDateString(
                                                    'id-ID',
                                                    { weekday: 'short' },
                                                )}
                                            </div>
                                        </div>
                                        <div className="min-w-0 flex-1 text-sm">
                                            <div>
                                                {formatTime(
                                                    day.check_in_at,
                                                    timezone,
                                                )}{' '}
                                                -{' '}
                                                {formatTime(
                                                    day.check_out_at,
                                                    timezone,
                                                )}
                                            </div>
                                            {(day.late_minutes > 0 ||
                                                day.early_leave_minutes >
                                                    0) && (
                                                <div className="text-xs text-muted-foreground">
                                                    {day.late_minutes > 0 &&
                                                        `Terlambat ${day.late_minutes} menit`}
                                                    {day.late_minutes > 0 &&
                                                        day.early_leave_minutes >
                                                            0 &&
                                                        ' · '}
                                                    {day.early_leave_minutes >
                                                        0 &&
                                                        `Pulang awal ${day.early_leave_minutes} menit`}
                                                </div>
                                            )}
                                        </div>
                                        <Badge
                                            variant={statusVariant(day.status)}
                                        >
                                            {DAY_STATUS_LABELS[day.status]}
                                        </Badge>
                                    </li>
                                );
                            })}
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AttendanceHistory.layout = {
    breadcrumbs: [
        {
            title: 'Riwayat Absensi',
            href: history(),
        },
    ],
};
