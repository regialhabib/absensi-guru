import { Form, Link } from '@inertiajs/react';
import TeacherController from '@/actions/App/Http/Controllers/Admin/TeacherController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/admin/teachers';

type TeacherFormValues = {
    id: number;
    name: string;
    email: string;
    nip: string | null;
    phone: string | null;
};

export default function TeacherForm({
    teacher,
}: {
    teacher?: TeacherFormValues;
}) {
    const formProps = teacher
        ? TeacherController.update.form(teacher.id)
        : TeacherController.store.form();

    return (
        <Form {...formProps} className="max-w-xl space-y-5">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Nama</Label>
                        <Input
                            id="name"
                            name="name"
                            defaultValue={teacher?.name}
                            required
                            autoComplete="off"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            defaultValue={teacher?.email}
                            required
                            autoComplete="off"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nip">NIP</Label>
                        <Input
                            id="nip"
                            name="nip"
                            defaultValue={teacher?.nip ?? ''}
                            autoComplete="off"
                        />
                        <InputError message={errors.nip} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="phone">Nomor telepon</Label>
                        <Input
                            id="phone"
                            type="tel"
                            name="phone"
                            defaultValue={teacher?.phone ?? ''}
                            autoComplete="off"
                        />
                        <InputError message={errors.phone} />
                    </div>

                    {!teacher && (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Kata sandi awal
                                </Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="new-password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Konfirmasi kata sandi
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    required
                                    autoComplete="new-password"
                                />
                            </div>
                        </>
                    )}

                    <div className="flex gap-2">
                        <Button disabled={processing}>Simpan</Button>
                        <Button variant="outline" asChild>
                            <Link href={index()}>Batal</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
