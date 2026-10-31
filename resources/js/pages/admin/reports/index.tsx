import { Head, router } from '@inertiajs/react';
import { FileSpreadsheet, FileText } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { excel, index, pdf } from '@/routes/admin/reports';
import type { MonthlySummary } from '@/types';

type Props = {
    recap: {
        institution: string;
        year: number;
        month: number;
        period: string;
        rows: ({
            id: number;
            name: string;
            nip: string | null;
        } & MonthlySummary)[];
    };
    filters: { year: number; month: number };
};

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const COLUMNS: { key: keyof MonthlySummary; label: string }[] = [
    { key: 'hadir', label: 'Hadir' },
    { key: 'terlambat', label: 'Terlambat' },
    { key: 'izin', label: 'Izin' },
    { key: 'sakit', label: 'Sakit' },
    { key: 'cuti', label: 'Cuti' },
    { key: 'alpa', label: 'Alpa' },
];

export default function ReportsIndex({ recap, filters }: Props) {
    const currentYear = new Date().getFullYear();
    const years = Array.from({ length: 6 }, (_, i) => currentYear - 4 + i);
    const query = { year: filters.year, month: filters.month };

    function change(next: Partial<typeof query>) {
        router.get(
            index.url(),
            { ...query, ...next },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Laporan" />
            <div className="space-y-4 p-4">
                <Heading
                    title="Laporan"
                    description="Rekap absensi bulanan per guru aktif"
                />

                <div className="flex flex-wrap items-end gap-4">
                    <div className="grid gap-2">
                        <Label>Bulan</Label>
                        <Select
                            value={String(filters.month)}
                            onValueChange={(value) =>
                                change({ month: Number(value) })
                            }
                        >
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {MONTHS.map((name, i) => (
                                    <SelectItem
                                        key={name}
                                        value={String(i + 1)}
                                    >
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="grid gap-2">
                        <Label>Tahun</Label>
                        <Select
                            value={String(filters.year)}
                            onValueChange={(value) =>
                                change({ year: Number(value) })
                            }
                        >
                            <SelectTrigger className="w-28">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {years.map((year) => (
                                    <SelectItem key={year} value={String(year)}>
                                        {year}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <a href={excel({ query }).url}>
                                <FileSpreadsheet /> Excel
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={pdf({ query }).url}>
                                <FileText /> PDF
                            </a>
                        </Button>
                    </div>
                </div>

                <p className="text-sm text-muted-foreground">
                    {recap.institution} - {recap.period}
                </p>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Nama</th>
                                <th className="px-3 py-2">NIP</th>
                                {COLUMNS.map((column) => (
                                    <th
                                        key={column.key}
                                        className="px-3 py-2 text-center"
                                    >
                                        {column.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {recap.rows.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={8}
                                        className="px-3 py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada guru aktif.
                                    </td>
                                </tr>
                            )}
                            {recap.rows.map((row) => (
                                <tr key={row.id} className="border-t">
                                    <td className="px-3 py-2 font-medium">
                                        {row.name}
                                    </td>
                                    <td className="px-3 py-2">
                                        {row.nip ?? '-'}
                                    </td>
                                    {COLUMNS.map((column) => (
                                        <td
                                            key={column.key}
                                            className="px-3 py-2 text-center"
                                        >
                                            {row[column.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

ReportsIndex.layout = {
    breadcrumbs: [{ title: 'Laporan', href: index() }],
};
