import { Head, Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { create, destroy, index, attachment } from '@/routes/leaves';
import { LEAVE_STATUS_LABELS, LEAVE_TYPE_LABELS } from '@/types/absensi';
import type { LeaveRequest, LeaveStatus, Paginated } from '@/types/absensi';

type Props = {
    leaves: Paginated<LeaveRequest>;
};

function formatDate(value: string): string {
    return new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString(
        'id-ID',
        { day: 'numeric', month: 'short', year: 'numeric' },
    );
}

function statusVariant(status: LeaveStatus) {
    if (status === 'rejected') {
        return 'destructive' as const;
    }

    return status === 'approved'
        ? ('default' as const)
        : ('secondary' as const);
}

export default function LeavesIndex({ leaves }: Props) {
    const cancel = (leave: LeaveRequest) => {
        if (!window.confirm('Batalkan pengajuan izin ini?')) {
            return;
        }

        router.delete(destroy(leave.id), { preserveScroll: true });
    };

    return (
        <>
            <Head title="Pengajuan Izin" />

            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <Button asChild size="lg" className="h-12 w-full text-base">
                    <Link href={create()}>Ajukan Izin</Link>
                </Button>

                {leaves.data.length === 0 && (
                    <p className="py-8 text-center text-sm text-muted-foreground">
                        Belum ada pengajuan izin.
                    </p>
                )}

                {leaves.data.map((leave) => (
                    <Card key={leave.id}>
                        <CardContent className="space-y-3">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <div className="font-semibold">
                                        {LEAVE_TYPE_LABELS[leave.type]}
                                    </div>
                                    <div className="text-sm text-muted-foreground">
                                        {formatDate(leave.start_date)}
                                        {leave.end_date.slice(0, 10) !==
                                            leave.start_date.slice(0, 10) &&
                                            ` - ${formatDate(leave.end_date)}`}
                                    </div>
                                </div>
                                <Badge variant={statusVariant(leave.status)}>
                                    {LEAVE_STATUS_LABELS[leave.status]}
                                </Badge>
                            </div>

                            <p className="text-sm whitespace-pre-line">
                                {leave.reason}
                            </p>

                            {leave.review_note && (
                                <p className="rounded-md bg-muted p-2 text-sm">
                                    <span className="font-medium">
                                        Catatan:
                                    </span>{' '}
                                    {leave.review_note}
                                </p>
                            )}

                            <div className="flex flex-wrap gap-2">
                                {leave.attachment && (
                                    <Button asChild variant="outline" size="sm">
                                        <a
                                            href={attachment(leave.id).url}
                                            target="_blank"
                                            rel="noreferrer"
                                        >
                                            Lihat lampiran
                                        </a>
                                    </Button>
                                )}
                                {leave.status === 'pending' && (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => cancel(leave)}
                                    >
                                        Batalkan
                                    </Button>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}

                {leaves.last_page > 1 && (
                    <div className="flex items-center justify-between">
                        <Button
                            asChild={leaves.current_page > 1}
                            variant="outline"
                            disabled={leaves.current_page <= 1}
                        >
                            {leaves.current_page > 1 ? (
                                <Link
                                    href={index({
                                        query: {
                                            page: leaves.current_page - 1,
                                        },
                                    })}
                                >
                                    Sebelumnya
                                </Link>
                            ) : (
                                <span>Sebelumnya</span>
                            )}
                        </Button>
                        <span className="text-sm text-muted-foreground">
                            {leaves.current_page} / {leaves.last_page}
                        </span>
                        <Button
                            asChild={leaves.current_page < leaves.last_page}
                            variant="outline"
                            disabled={leaves.current_page >= leaves.last_page}
                        >
                            {leaves.current_page < leaves.last_page ? (
                                <Link
                                    href={index({
                                        query: {
                                            page: leaves.current_page + 1,
                                        },
                                    })}
                                >
                                    Berikutnya
                                </Link>
                            ) : (
                                <span>Berikutnya</span>
                            )}
                        </Button>
                    </div>
                )}
            </div>
        </>
    );
}

LeavesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pengajuan Izin',
            href: index(),
        },
    ],
};
