import { Link } from '@inertiajs/react';
import { Palette, ShieldCheck, UserRound } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil',
        href: edit(),
        icon: UserRound,
    },
    {
        title: 'Keamanan',
        href: editSecurity(),
        icon: ShieldCheck,
    },
    {
        title: 'Tampilan',
        href: editAppearance(),
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <div className="max-w-5xl p-4">
            <Heading
                title="Pengaturan Akun"
                description="Kelola profil, keamanan, dan tampilan akun Anda"
            />

            <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:gap-10">
                <nav
                    aria-label="Pengaturan akun"
                    className="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1 lg:w-52 lg:shrink-0"
                >
                    {sidebarNavItems.map((item) => {
                        const isActive = isCurrentOrParentUrl(item.href);

                        return (
                            <Link
                                key={toUrl(item.href)}
                                href={item.href}
                                aria-current={isActive ? 'page' : undefined}
                                className={cn(
                                    'flex items-center justify-center gap-2 rounded-md px-2 py-2 text-sm font-medium transition-colors lg:col-span-3 lg:justify-start lg:px-3',
                                    isActive
                                        ? 'bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                {item.icon && (
                                    <item.icon className="size-4 shrink-0" />
                                )}
                                {item.title}
                            </Link>
                        );
                    })}
                </nav>

                <section className="min-w-0 flex-1 space-y-6">
                    {children}
                </section>
            </div>
        </div>
    );
}
