import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index, store } from '@/routes/leaves';
import type { LeaveType } from '@/types/absensi';

type Props = {
    types: { value: LeaveType; label: string }[];
};

const fieldClass =
    'flex w-full rounded-md border border-input bg-background px-3 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 outline-none';

export default function LeavesCreate({ types }: Props) {
    return (
        <>
            <Head title="Ajukan Izin" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <Form {...store.form()} className="space-y-5">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Jenis</Label>
                                <select
                                    id="type"
                                    name="type"
                                    required
                                    defaultValue={types[0]?.value}
                                    className={`${fieldClass} h-10`}
                                >
                                    {types.map((type) => (
                                        <option
                                            key={type.value}
                                            value={type.value}
                                        >
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="start_date">
                                        Tanggal mulai
                                    </Label>
                                    <Input
                                        id="start_date"
                                        name="start_date"
                                        type="date"
                                        required
                                    />
                                    <InputError message={errors.start_date} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="end_date">
                                        Tanggal selesai
                                    </Label>
                                    <Input
                                        id="end_date"
                                        name="end_date"
                                        type="date"
                                        required
                                    />
                                    <InputError message={errors.end_date} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="reason">Alasan</Label>
                                <textarea
                                    id="reason"
                                    name="reason"
                                    rows={4}
                                    maxLength={1000}
                                    required
                                    className={`${fieldClass} py-2`}
                                />
                                <InputError message={errors.reason} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="attachment">
                                    Lampiran (opsional)
                                </Label>
                                <Input
                                    id="attachment"
                                    name="attachment"
                                    type="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                <p className="text-xs text-muted-foreground">
                                    PDF, JPG, atau PNG, maksimal 5 MB.
                                </p>
                                <InputError message={errors.attachment} />
                            </div>

                            <Button
                                type="submit"
                                size="lg"
                                className="h-12 w-full text-base"
                                disabled={processing}
                            >
                                {processing ? 'Mengirim...' : 'Kirim Pengajuan'}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

LeavesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Pengajuan Izin',
            href: index(),
        },
        {
            title: 'Ajukan Izin',
            href: create(),
        },
    ],
};
