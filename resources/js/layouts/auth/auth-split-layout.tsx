import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Camera, Check, FileBarChart, MapPin } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import AppearanceToggle from '@/components/appearance-toggle';
import GeofenceRings from '@/components/geofence-rings';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const HIGHLIGHTS = [
    { icon: Camera, text: 'Swafoto langsung sebagai bukti kehadiran' },
    { icon: MapPin, text: 'Lokasi diperiksa saat absen masuk dan pulang' },
    { icon: FileBarChart, text: 'Rekap kehadiran bulanan tersusun otomatis' },
];

const BRAND_COLORS =
    'bg-primary text-primary-foreground dark:[--color-primary-foreground:#ffffff] dark:[--color-primary:#1e7b3a]';

function Brand({ name }: { name: string }) {
    return (
        <Link href={home()} className="flex items-center gap-3 font-semibold">
            <AppLogoIcon alt="" className="size-11 shrink-0 bg-white p-0.5" />
            <span className="text-sm leading-tight sm:text-base">{name}</span>
        </Link>
    );
}

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="grid min-h-svh bg-background text-foreground lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <aside
                className={`relative hidden flex-col justify-between overflow-hidden p-12 lg:flex ${BRAND_COLORS}`}
            >
                <GeofenceRings className="-right-64 -bottom-64 size-[42rem]" />

                <div className="relative">
                    <Brand name={name} />
                </div>

                <div className="relative">
                    <p lang="ar" dir="rtl" className="w-fit text-2xl">
                        رياض الأمين
                    </p>
                    <p className="mt-3 max-w-md text-4xl font-bold tracking-tight text-balance">
                        Absensi guru yang rapi dan mudah diperiksa.
                    </p>
                    <ul className="mt-8 grid gap-4">
                        {HIGHLIGHTS.map((highlight) => (
                            <li
                                key={highlight.text}
                                className="flex items-center gap-3"
                            >
                                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary-foreground/15">
                                    <highlight.icon className="size-4" />
                                </span>
                                {highlight.text}
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative flex items-center gap-2 text-sm text-primary-foreground/85">
                    <Check className="size-4" />
                    Akun guru dibuat oleh admin pesantren.
                </p>
            </aside>

            <main className="flex min-w-0 flex-col">
                <header className={`p-4 lg:hidden ${BRAND_COLORS}`}>
                    <Brand name={name} />
                </header>

                <div className="flex flex-1 items-center justify-center p-6 md:p-10">
                    <div className="w-full max-w-sm">
                        <div className="mb-8 flex items-center justify-between gap-4">
                            <Link
                                href={home()}
                                className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
                            >
                                <ArrowLeft className="size-4" />
                                Kembali ke beranda
                            </Link>
                            <AppearanceToggle />
                        </div>

                        <div className="mb-8">
                            <h1 className="text-2xl font-bold tracking-tight">
                                {title}
                            </h1>
                            <p className="mt-2 text-sm text-pretty text-muted-foreground">
                                {description}
                            </p>
                        </div>

                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
