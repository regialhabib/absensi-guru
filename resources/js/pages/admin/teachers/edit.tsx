import { Head } from '@inertiajs/react';
import TeacherForm from '@/components/admin/teacher-form';
import Heading from '@/components/heading';
import { index } from '@/routes/admin/teachers';

type Props = {
    teacher: {
        id: number;
        name: string;
        email: string;
        nip: string | null;
        phone: string | null;
    };
};

export default function TeachersEdit({ teacher }: Props) {
    return (
        <>
            <Head title="Ubah Guru" />
            <div className="p-4">
                <Heading
                    title="Ubah Guru"
                    description="Perbarui data akun guru. Kata sandi direset dari halaman daftar guru."
                />
                <TeacherForm teacher={teacher} />
            </div>
        </>
    );
}

TeachersEdit.layout = {
    breadcrumbs: [
        { title: 'Guru', href: index() },
        { title: 'Ubah', href: index() },
    ],
};
