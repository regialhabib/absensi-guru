import { Form, Head, Link, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Power, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import TeacherController from '@/actions/App/Http/Controllers/Admin/TeacherController';
import Pagination from '@/components/admin/pagination';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/teachers';
import type { Paginated, Teacher } from '@/types';

type Props = {
    teachers: Paginated<Teacher>;
    filters: { search: string };
};

export default function TeachersIndex({ teachers, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [deleting, setDeleting] = useState<Teacher | null>(null);
    const [resetting, setResetting] = useState<Teacher | null>(null);

    function submitSearch(event: React.FormEvent) {
        event.preventDefault();
        router.get(index.url(), search ? { search } : {}, {
            preserveState: true,
            replace: true,
        });
    }

    function toggle(teacher: Teacher) {
        router.patch(
            TeacherController.toggle.url(teacher.id),
            {},
            { preserveScroll: true },
        );
    }

    function confirmDelete() {
        if (!deleting) {
            return;
        }

        router.delete(TeacherController.destroy.url(deleting.id), {
            preserveScroll: true,
            onError: (errors) =>
                toast.error(errors.teacher ?? 'Guru gagal dihapus.'),
            onFinish: () => setDeleting(null),
        });
    }

    return (
        <>
            <Head title="Guru" />
            <div className="space-y-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <Heading
                        title="Guru"
                        description="Kelola akun guru yang melakukan absensi"
                    />
                    <Button asChild>
                        <Link href={create()}>
                            <Plus /> Tambah Guru
                        </Link>
                    </Button>
                </div>

                <form onSubmit={submitSearch} className="flex max-w-md gap-2">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari nama atau NIP"
                        aria-label="Cari nama atau NIP"
                    />
                    <Button type="submit" variant="outline">
                        <Search /> Cari
                    </Button>
                </form>

                <div className="overflow-x-auto rounded-lg border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2">Nama</th>
                                <th className="px-3 py-2">NIP</th>
                                <th className="px-3 py-2">Email</th>
                                <th className="px-3 py-2">Telepon</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {teachers.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada data guru.
                                    </td>
                                </tr>
                            )}
                            {teachers.data.map((teacher) => (
                                <tr key={teacher.id} className="border-t">
                                    <td className="px-3 py-2 font-medium">
                                        {teacher.name}
                                    </td>
                                    <td className="px-3 py-2">
                                        {teacher.nip ?? '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {teacher.email}
                                    </td>
                                    <td className="px-3 py-2">
                                        {teacher.phone ?? '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <Badge
                                            variant={
                                                teacher.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {teacher.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'}
                                        </Badge>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                                title="Ubah"
                                            >
                                                <Link
                                                    href={TeacherController.edit.url(
                                                        teacher.id,
                                                    )}
                                                >
                                                    <Pencil />
                                                    <span className="sr-only">
                                                        Ubah
                                                    </span>
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title={
                                                    teacher.is_active
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'
                                                }
                                                onClick={() => toggle(teacher)}
                                            >
                                                <Power />
                                                <span className="sr-only">
                                                    {teacher.is_active
                                                        ? 'Nonaktifkan'
                                                        : 'Aktifkan'}
                                                </span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Reset kata sandi"
                                                onClick={() =>
                                                    setResetting(teacher)
                                                }
                                            >
                                                <KeyRound />
                                                <span className="sr-only">
                                                    Reset kata sandi
                                                </span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Hapus"
                                                onClick={() =>
                                                    setDeleting(teacher)
                                                }
                                            >
                                                <Trash2 />
                                                <span className="sr-only">
                                                    Hapus
                                                </span>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginator={teachers} />
            </div>

            <Dialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
            >
                <DialogContent>
                    <DialogTitle>Hapus guru?</DialogTitle>
                    <DialogDescription>
                        Akun {deleting?.name} akan dihapus permanen. Guru yang
                        sudah memiliki data absensi tidak dapat dihapus,
                        nonaktifkan akunnya sebagai gantinya.
                    </DialogDescription>
                    <DialogFooter className="gap-2">
                        <Button
                            variant="secondary"
                            onClick={() => setDeleting(null)}
                        >
                            Batal
                        </Button>
                        <Button variant="destructive" onClick={confirmDelete}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={resetting !== null}
                onOpenChange={(open) => !open && setResetting(null)}
            >
                <DialogContent>
                    <DialogTitle>Reset kata sandi</DialogTitle>
                    <DialogDescription>
                        Masukkan kata sandi baru untuk {resetting?.name}.
                    </DialogDescription>
                    {resetting && (
                        <Form
                            {...TeacherController.resetPassword.form(
                                resetting.id,
                            )}
                            options={{ preserveScroll: true }}
                            onSuccess={() => setResetting(null)}
                            resetOnSuccess
                            className="space-y-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reset-password">
                                            Kata sandi baru
                                        </Label>
                                        <PasswordInput
                                            id="reset-password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                        />
                                        <InputError message={errors.password} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="reset-password-confirmation">
                                            Konfirmasi kata sandi
                                        </Label>
                                        <PasswordInput
                                            id="reset-password-confirmation"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                        />
                                    </div>
                                    <DialogFooter className="gap-2">
                                        <Button
                                            type="button"
                                            variant="secondary"
                                            onClick={() => setResetting(null)}
                                        >
                                            Batal
                                        </Button>
                                        <Button disabled={processing}>
                                            Simpan
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

TeachersIndex.layout = {
    breadcrumbs: [{ title: 'Guru', href: index() }],
};
