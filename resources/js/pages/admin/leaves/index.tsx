import { Form, Head, router } from '@inertiajs/react';
import { Paperclip } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import LeaveRequestController from '@/actions/App/Http/Controllers/Admin/LeaveRequestController';
import Pagination from '@/components/admin/pagination';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { index } from '@/routes/admin/leaves';
import { attachment } from '@/routes/leaves';
import { LEAVE_STATUS_LABELS, LEAVE_TYPE_LABELS } from '@/types/absensi';
import type { LeaveRequest, LeaveStatus, Paginated } from '@/types';

type Props = {
    leaveRequests: Paginated<LeaveRequest>;
    filters: { status: LeaveStatus | null };
};

type Review = { leave: LeaveRequest; action: 'approve' | 'reject' };

function formatDate(value: string): string {
    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function statusVariant(
    status: LeaveStatus,
): 'default' | 'secondary' | 'destructive' {
    if (status === 'approved') {
        return 'default';
    }

    return status === 'rejected' ? 'destructive' : 'secondary';
}

export default function LeavesIndex({ leaveRequests, filters }: Props) {
    const [review, setReview] = useState<Review | null>(null);

    function filterByStatus(value: string) {
        router.get(index.url(), value === 'all' ? {} : { status: value }, {
            preserveState: true,
            replace: true,
        });
    }

    const formProps = review
        ? review.action === 'approve'
            ? LeaveRequestController.approve.form(review.leave.id)
            : LeaveRequestController.reject.form(review.leave.id)
        : null;

    return (
        <>
            <Head title="Izin" />
            <div className="space-y-4 p-4">
                <Heading
                    title="Pengajuan Izin"
                    description="Tinjau pengajuan izin, sakit, dan cuti guru"
                />

                <div className="grid max-w-56 gap-2">
                    <Label>Status</Label>
                    <Select
                        value={filters.status ?? 'all'}
                        onValueChange={filterByStatus}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Semua status</SelectItem>
                            {(
                                Object.keys(
                                    LEAVE_STATUS_LABELS,
                                ) as LeaveStatus[]
                            ).map((status) => (
                                <SelectItem key={status} value={status}>
                                    {LEAVE_STATUS_LABELS[status]}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Guru</th>
                                <th className="px-3 py-2">Jenis</th>
                                <th className="px-3 py-2">Tanggal</th>
                                <th className="px-3 py-2">Alasan</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaveRequests.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-6 text-center text-muted-foreground"
                                    >
                                        Tidak ada pengajuan.
                                    </td>
                                </tr>
                            )}
                            {leaveRequests.data.map((leave) => (
                                <tr
                                    key={leave.id}
                                    className="border-t align-top"
                                >
                                    <td className="px-3 py-2 font-medium">
                                        {leave.user?.name}
                                    </td>
                                    <td className="px-3 py-2">
                                        {LEAVE_TYPE_LABELS[leave.type]}
                                    </td>
                                    <td className="px-3 py-2">
                                        {formatDate(leave.start_date)}
                                        {leave.end_date !== leave.start_date &&
                                            ` - ${formatDate(leave.end_date)}`}
                                    </td>
                                    <td className="max-w-xs px-3 py-2">
                                        <p>{leave.reason}</p>
                                        {leave.attachment && (
                                            <a
                                                href={attachment(leave.id).url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="mt-1 inline-flex items-center gap-1 text-xs underline"
                                            >
                                                <Paperclip className="size-3" />{' '}
                                                Lampiran
                                            </a>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <Badge
                                            variant={statusVariant(
                                                leave.status,
                                            )}
                                        >
                                            {LEAVE_STATUS_LABELS[leave.status]}
                                        </Badge>
                                        {leave.review_note && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {leave.review_note}
                                            </p>
                                        )}
                                        {leave.reviewer && (
                                            <p className="text-xs text-muted-foreground">
                                                oleh {leave.reviewer.name}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {leave.status === 'pending' && (
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        setReview({
                                                            leave,
                                                            action: 'approve',
                                                        })
                                                    }
                                                >
                                                    Setujui
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() =>
                                                        setReview({
                                                            leave,
                                                            action: 'reject',
                                                        })
                                                    }
                                                >
                                                    Tolak
                                                </Button>
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={leaveRequests} />
            </div>

            <Dialog
                open={review !== null}
                onOpenChange={(open) => !open && setReview(null)}
            >
                <DialogContent>
                    <DialogTitle>
                        {review?.action === 'approve' ? 'Setujui' : 'Tolak'}{' '}
                        pengajuan
                    </DialogTitle>
                    <DialogDescription>
                        Pengajuan {review?.leave.user?.name}. Catatan bersifat
                        opsional.
                    </DialogDescription>
                    {formProps && (
                        <Form
                            {...formProps}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setReview(null)}
                            onError={(errors) => {
                                if (errors.status) {
                                    toast.error(errors.status);
                                    setReview(null);
                                }
                            }}
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="note">Catatan</Label>
                                        <textarea
                                            id="note"
                                            name="note"
                                            rows={3}
                                            maxLength={500}
                                            className="rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs"
                                        />
                                        <InputError message={errors.note} />
                                    </div>
                                    <DialogFooter className="gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => setReview(null)}
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            disabled={processing}
                                            variant={
                                                review?.action === 'reject'
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                        >
                                            {review?.action === 'approve'
                                                ? 'Setujui'
                                                : 'Tolak'}
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

LeavesIndex.layout = {
    breadcrumbs: [{ title: 'Izin', href: index() }],
};
