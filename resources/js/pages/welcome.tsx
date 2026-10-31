import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Camera,
    Check,
    Clock,
    LockKeyhole,
    MapPin,
    ShieldCheck,
} from 'lucide-react';
import GeofenceRings from '@/components/geofence-rings';
import { Button } from '@/components/ui/button';
import { login } from '@/routes';

const STEPS = [
    {
        icon: MapPin,
        title: 'Buka halaman Absen',
        description:
            'Pastikan Anda berada di area pesantren. Jarak dan akurasi GPS langsung terlihat di layar.',
    },
    {
        icon: Camera,
        title: 'Ambil swafoto',
        description:
            'Foto diambil langsung dari kamera depan, setelah satu wajah terdeteksi di layar.',
    },
    {
        icon: Clock,
        title: 'Kirim absen',
        description:
            'Waktu dicatat oleh server, lalu status hadir atau terlambat muncul otomatis.',
    },
];

const AUDIENCES = [
    {
        title: 'Untuk guru',
        items: [
            'Absen masuk dan pulang dari browser ponsel',
            'Riwayat kehadiran per bulan',
            'Pengajuan izin, sakit, dan cuti beserta lampiran',
        ],
    },
    {
        title: 'Untuk admin',
        items: [
            'Pantau absensi harian beserta foto dan lokasinya',
            'Kelola akun guru dan tinjau pengajuan izin',
            'Rekap bulanan yang bisa diekspor ke Excel dan PDF',
            'Atur titik lokasi, radius, dan jam kerja',
        ],
    },
];

function PhonePreview() {
    return (
        <div
            aria-hidden="true"
            className="relative mx-auto w-full max-w-72 rounded-[2.25rem] border-8 border-foreground/90 bg-background p-4 text-left text-foreground shadow-2xl"
        >
            <p className="text-sm font-semibold">Absen Masuk</p>
            <p className="text-xs text-muted-foreground">
                Ambil swafoto langsung dari kamera depan.
            </p>

            <div className="mt-4 grid gap-2 text-xs">
                <div className="flex items-center justify-between gap-2">
                    <span>Jarak ke sekolah</span>
                    <span className="rounded-full bg-primary px-2 py-0.5 font-medium text-primary-foreground">
                        Dalam area
                    </span>
                </div>
                <div className="flex items-center justify-between gap-2">
                    <span>Akurasi GPS</span>
                    <span className="rounded-full bg-primary px-2 py-0.5 font-medium text-primary-foreground">
                        Akurasi baik
                    </span>
                </div>
            </div>

            <div className="mt-4 flex aspect-square items-center justify-center rounded-xl bg-muted">
                <div className="size-32 rounded-[50%] border-2 border-dashed border-primary" />
            </div>

            <div className="mt-4 rounded-md bg-primary py-2 text-center text-xs font-medium text-primary-foreground">
                Kirim Absen Masuk
            </div>
        </div>
    );
}

export default function Welcome() {
    const { name } = usePage().props;

    return (
        <>
            <Head title="Absensi Guru" />

            <div className="min-h-svh bg-background text-foreground">
                <header className="relative overflow-hidden bg-primary text-primary-foreground dark:[--color-primary-foreground:#ffffff] dark:[--color-primary:#1e7b3a]">
                    <GeofenceRings className="top-1/2 left-1/2 hidden size-[56rem] -translate-y-1/2 lg:block" />

                    <div className="relative mx-auto max-w-6xl px-4 sm:px-6">
                        <nav className="flex items-center justify-between gap-4 py-4">
                            <div className="flex min-w-0 items-center gap-3">
                                <img
                                    src="/images/logo.webp"
                                    alt=""
                                    width={44}
                                    height={46}
                                    className="size-11 shrink-0 rounded-full bg-white object-contain p-0.5"
                                />
                                <span className="text-sm leading-tight font-semibold sm:text-base">
                                    {name}
                                </span>
                            </div>
                            <Button
                                asChild
                                variant="outline"
                                className="shrink-0 border-primary-foreground/40 bg-transparent text-primary-foreground hover:bg-primary-foreground/10 hover:text-primary-foreground dark:border-primary-foreground/40 dark:bg-transparent"
                            >
                                <Link href={login()}>Masuk</Link>
                            </Button>
                        </nav>

                        <div className="grid items-center gap-12 pt-10 pb-16 lg:grid-cols-[1.2fr_1fr] lg:pt-16 lg:pb-24">
                            <div>
                                <p
                                    lang="ar"
                                    dir="rtl"
                                    className="w-fit text-2xl"
                                >
                                    رياض الأمين
                                </p>
                                <h1 className="mt-3 text-4xl font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                                    Absen dari ponsel, cukup swafoto di area
                                    pesantren.
                                </h1>
                                <p className="mt-5 max-w-xl text-base text-pretty text-primary-foreground/85 sm:text-lg">
                                    Guru absen masuk dan pulang langsung dari
                                    browser, tanpa aplikasi yang perlu dipasang.
                                    Waktu, lokasi, dan swafoto tercatat sebagai
                                    bukti kehadiran.
                                </p>
                                <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <Button
                                        asChild
                                        size="lg"
                                        className="bg-background text-foreground hover:bg-background/90"
                                    >
                                        <Link href={login()}>
                                            Masuk ke Akun <ArrowRight />
                                        </Link>
                                    </Button>
                                    <p className="text-sm text-primary-foreground/85">
                                        Akun guru dibuat oleh admin.
                                    </p>
                                </div>
                            </div>

                            <PhonePreview />
                        </div>
                    </div>
                </header>

                <main>
                    <section className="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-3xl">
                            Tiga langkah setiap absen
                        </h2>
                        <ol className="mt-8 grid gap-8 md:grid-cols-3">
                            {STEPS.map((step, index) => (
                                <li key={step.title} className="flex gap-4">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground">
                                        <step.icon className="size-5" />
                                    </span>
                                    <div>
                                        <h3 className="font-semibold">
                                            {index + 1}. {step.title}
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {step.description}
                                        </p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </section>

                    <section className="bg-secondary">
                        <div className="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 md:grid-cols-2 lg:py-20">
                            {AUDIENCES.map((audience) => (
                                <div key={audience.title}>
                                    <h2 className="text-2xl font-bold tracking-tight">
                                        {audience.title}
                                    </h2>
                                    <ul className="mt-5 grid gap-3">
                                        {audience.items.map((item) => (
                                            <li
                                                key={item}
                                                className="flex gap-3 text-sm sm:text-base"
                                            >
                                                <Check className="mt-0.5 size-5 shrink-0 text-primary" />
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="mx-auto grid max-w-6xl gap-8 px-4 py-16 sm:px-6 md:grid-cols-2 lg:py-20">
                        <div className="flex gap-4">
                            <LockKeyhole className="mt-0.5 size-6 shrink-0 text-primary" />
                            <div>
                                <h2 className="font-semibold">
                                    Data kehadiran disimpan privat
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Swafoto dan lokasi direkam sebagai bukti
                                    kehadiran. Foto hanya bisa dibuka oleh guru
                                    yang bersangkutan dan admin.
                                </p>
                            </div>
                        </div>
                        <div className="flex gap-4">
                            <ShieldCheck className="mt-0.5 size-6 shrink-0 text-primary" />
                            <div>
                                <h2 className="font-semibold">
                                    Siapkan izin kamera dan lokasi
                                </h2>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Saat pertama kali absen, browser akan
                                    meminta izin. Pilih izinkan agar swafoto dan
                                    lokasi bisa terbaca.
                                </p>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t">
                    <p className="mx-auto max-w-6xl px-4 py-6 text-sm text-muted-foreground sm:px-6">
                        © {new Date().getFullYear()} {name}
                    </p>
                </footer>
            </div>
        </>
    );
}
