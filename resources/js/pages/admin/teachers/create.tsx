import { Head } from '@inertiajs/react';
import TeacherForm from '@/components/admin/teacher-form';
import Heading from '@/components/heading';
import { index } from '@/routes/admin/teachers';

export default function TeachersCreate() {
    return (
        <>
            <Head title="Tambah Guru" />
            <div className="p-4">
                <Heading
                    title="Tambah Guru"
                    description="Buat akun guru baru beserta kata sandi awalnya"
                />
                <TeacherForm />
            </div>
        </>
    );
}

TeachersCreate.layout = {
    breadcrumbs: [
        { title: 'Guru', href: index() },
        { title: 'Tambah', href: index() },
    ],
};
