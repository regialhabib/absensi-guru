import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export const LOGO_SRC = '/images/logo.webp';

/**
 * Institution badge. Falls back to the institution's initials when
 * /images/logo.webp is missing or fails to load.
 */
export default function AppLogoIcon({
    className,
    alt,
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    const { name } = usePage().props;
    const [failed, setFailed] = useState(false);
    const label = alt ?? `Logo ${name}`;

    if (failed) {
        const initials = String(name)
            .split(' ')
            .filter(Boolean)
            .slice(-2)
            .map((word) => word[0])
            .join('')
            .toUpperCase();

        return (
            <span
                role="img"
                aria-label={label}
                className={cn(
                    'inline-flex aspect-square items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground',
                    className,
                )}
            >
                {initials}
            </span>
        );
    }

    return (
        <img
            {...props}
            src={LOGO_SRC}
            alt={label}
            onError={() => setFailed(true)}
            className={cn(
                'aspect-square rounded-full object-contain',
                className,
            )}
        />
    );
}
