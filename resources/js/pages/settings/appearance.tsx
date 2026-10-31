import { Head } from '@inertiajs/react';
import AppearanceTabs from '@/components/appearance-tabs';
import SettingsCard from '@/components/settings-card';
import { edit as editAppearance } from '@/routes/appearance';

export default function Appearance() {
    return (
        <>
            <Head title="Tampilan" />

            <h1 className="sr-only">Pengaturan tampilan</h1>

            <SettingsCard
                title="Tema"
                description="Pilih tampilan terang, gelap, atau ikuti pengaturan perangkat"
            >
                <AppearanceTabs />
            </SettingsCard>
        </>
    );
}

Appearance.layout = {
    breadcrumbs: [
        {
            title: 'Pengaturan akun',
            href: editAppearance(),
        },
    ],
};
