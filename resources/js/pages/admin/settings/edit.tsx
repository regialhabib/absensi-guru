import { Head, useForm } from '@inertiajs/react';
import LocationMap from '@/components/admin/location-map';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, update } from '@/routes/admin/settings';

type Schedule = {
    day_of_week: number;
    is_workday: boolean;
    starts_at: string | null;
    ends_at: string | null;
};

type Props = {
    setting: {
        name: string;
        latitude: number | null;
        longitude: number | null;
        radius_meters: number;
        max_accuracy_meters: number;
        late_tolerance_minutes: number;
        check_in_opens_at: string;
        check_out_closes_at: string;
    };
    schedules: Schedule[];
};

const DAY_NAMES = [
    'Minggu',
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
];

function toNumber(value: string | number | null): number | null {
    if (value === null || value === '') {
        return null;
    }

    const number = Number(value);

    return Number.isFinite(number) ? number : null;
}

export default function SettingsEdit({ setting, schedules }: Props) {
    const form = useForm({
        name: setting.name,
        latitude: setting.latitude === null ? '' : String(setting.latitude),
        longitude: setting.longitude === null ? '' : String(setting.longitude),
        radius_meters: String(setting.radius_meters),
        max_accuracy_meters: String(setting.max_accuracy_meters),
        late_tolerance_minutes: String(setting.late_tolerance_minutes),
        check_in_opens_at: setting.check_in_opens_at,
        check_out_closes_at: setting.check_out_closes_at,
        schedules,
    });

    const errors = form.errors as Record<string, string | undefined>;

    function updateSchedule(index: number, changes: Partial<Schedule>) {
        form.setData(
            'schedules',
            form.data.schedules.map((schedule, i) =>
                i === index ? { ...schedule, ...changes } : schedule,
            ),
        );
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();
        form.put(update.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title="Pengaturan" />
            <form onSubmit={submit} className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Pengaturan"
                    description="Lokasi absensi, toleransi, dan jam kerja"
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Lembaga dan lokasi</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nama lembaga</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(event) =>
                                    form.setData('name', event.target.value)
                                }
                            />
                            <InputError message={errors.name} />
                        </div>

                        <LocationMap
                            latitude={toNumber(form.data.latitude)}
                            longitude={toNumber(form.data.longitude)}
                            radius={toNumber(form.data.radius_meters) ?? 0}
                            onChange={(latitude, longitude) =>
                                form.setData((data) => ({
                                    ...data,
                                    latitude: latitude.toFixed(7),
                                    longitude: longitude.toFixed(7),
                                }))
                            }
                        />
                        <p className="text-sm text-muted-foreground">
                            Klik peta atau geser penanda untuk menentukan titik
                            absensi.
                        </p>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="latitude">Garis lintang</Label>
                                <Input
                                    id="latitude"
                                    inputMode="decimal"
                                    value={form.data.latitude}
                                    onChange={(event) =>
                                        form.setData(
                                            'latitude',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.latitude} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="longitude">Garis bujur</Label>
                                <Input
                                    id="longitude"
                                    inputMode="decimal"
                                    value={form.data.longitude}
                                    onChange={(event) =>
                                        form.setData(
                                            'longitude',
                                            event.target.value,
                                        )
                                    }
                                />
                                <InputError message={errors.longitude} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Aturan absensi</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="radius_meters">
                                Radius (meter)
                            </Label>
                            <Input
                                id="radius_meters"
                                type="number"
                                value={form.data.radius_meters}
                                onChange={(event) =>
                                    form.setData(
                                        'radius_meters',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.radius_meters} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="max_accuracy_meters">
                                Akurasi maksimum (meter)
                            </Label>
                            <Input
                                id="max_accuracy_meters"
                                type="number"
                                value={form.data.max_accuracy_meters}
                                onChange={(event) =>
                                    form.setData(
                                        'max_accuracy_meters',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.max_accuracy_meters} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="late_tolerance_minutes">
                                Toleransi terlambat (menit)
                            </Label>
                            <Input
                                id="late_tolerance_minutes"
                                type="number"
                                value={form.data.late_tolerance_minutes}
                                onChange={(event) =>
                                    form.setData(
                                        'late_tolerance_minutes',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError
                                message={errors.late_tolerance_minutes}
                            />
                        </div>
                        <div />
                        <div className="grid gap-2">
                            <Label htmlFor="check_in_opens_at">
                                Absen masuk dibuka
                            </Label>
                            <Input
                                id="check_in_opens_at"
                                type="time"
                                value={form.data.check_in_opens_at}
                                onChange={(event) =>
                                    form.setData(
                                        'check_in_opens_at',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.check_in_opens_at} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="check_out_closes_at">
                                Absen pulang ditutup
                            </Label>
                            <Input
                                id="check_out_closes_at"
                                type="time"
                                value={form.data.check_out_closes_at}
                                onChange={(event) =>
                                    form.setData(
                                        'check_out_closes_at',
                                        event.target.value,
                                    )
                                }
                            />
                            <InputError message={errors.check_out_closes_at} />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Jam kerja per hari</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {form.data.schedules.map((schedule, index) => (
                            <div
                                key={schedule.day_of_week}
                                className="grid grid-cols-[7rem_1fr] items-start gap-3 sm:grid-cols-[7rem_8rem_1fr_1fr]"
                            >
                                <div className="flex h-9 items-center gap-2">
                                    <Checkbox
                                        id={`workday-${index}`}
                                        checked={schedule.is_workday}
                                        onCheckedChange={(checked) =>
                                            updateSchedule(index, {
                                                is_workday: checked === true,
                                            })
                                        }
                                    />
                                    <Label htmlFor={`workday-${index}`}>
                                        {DAY_NAMES[schedule.day_of_week]}
                                    </Label>
                                </div>
                                <span className="flex h-9 items-center text-sm text-muted-foreground sm:w-32">
                                    {schedule.is_workday
                                        ? 'Hari kerja'
                                        : 'Libur'}
                                </span>
                                <div className="col-span-2 grid grid-cols-2 gap-3 sm:col-span-2">
                                    <div className="grid gap-1">
                                        <Input
                                            type="time"
                                            aria-label={`Jam masuk ${DAY_NAMES[schedule.day_of_week]}`}
                                            disabled={!schedule.is_workday}
                                            value={schedule.starts_at ?? ''}
                                            onChange={(event) =>
                                                updateSchedule(index, {
                                                    starts_at:
                                                        event.target.value,
                                                })
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `schedules.${index}.starts_at`
                                                ]
                                            }
                                        />
                                    </div>
                                    <div className="grid gap-1">
                                        <Input
                                            type="time"
                                            aria-label={`Jam pulang ${DAY_NAMES[schedule.day_of_week]}`}
                                            disabled={!schedule.is_workday}
                                            value={schedule.ends_at ?? ''}
                                            onChange={(event) =>
                                                updateSchedule(index, {
                                                    ends_at: event.target.value,
                                                })
                                            }
                                        />
                                        <InputError
                                            message={
                                                errors[
                                                    `schedules.${index}.ends_at`
                                                ]
                                            }
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </CardContent>
                </Card>

                <Button disabled={form.processing}>Simpan pengaturan</Button>
            </form>
        </>
    );
}

SettingsEdit.layout = {
    breadcrumbs: [{ title: 'Pengaturan', href: edit() }],
};
