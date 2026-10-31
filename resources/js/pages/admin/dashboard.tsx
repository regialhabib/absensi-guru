import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import admin from '@/routes/admin';
import { index as leaves } from '@/routes/admin/leaves';

type Props = {
    today: string;
    summary: {
        total: number;
        hadir: number;
        terlambat: number;
        izin: number;
        belum_absen: number;
    };
    pendingLeaves: number;
};

export default function AdminDashboard({
    today,
    summary,
    pendingLeaves,
}: Props) {
    const cards = [
        { label: 'Hadir', value: summary.hadir },
        { label: 'Terlambat', value: summary.terlambat },
        { label: 'Izin / Sakit / Cuti', value: summary.izin },
        { label: 'Belum Absen', value: summary.belum_absen },
    ];

    const dateLabel = new Date(`${today}T00:00:00`).toLocaleDateString(
        'id-ID',
        {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        },
    );

    return (
        <>
            <Head title="Dasbor Admin" />
            <div className="space-y-4 p-4">
                <Heading
                    title="Dasbor"
                    description={`${dateLabel} - ${summary.total} guru aktif`}
                />

                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    {card.label}
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-semibold">
                                    {card.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Pengajuan izin menunggu</CardTitle>
                    </CardHeader>
                    <CardContent className="flex items-center justify-between gap-2">
                        <p className="text-3xl font-semibold">
                            {pendingLeaves}
                        </p>
                        <Button variant="outline" asChild>
                            <Link
                                href={leaves({ query: { status: 'pending' } })}
                            >
                                Tinjau
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Dasbor', href: admin.dashboard() }],
};
