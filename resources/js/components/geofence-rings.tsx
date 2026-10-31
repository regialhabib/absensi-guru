import { cn } from '@/lib/utils';

export default function GeofenceRings({ className }: { className?: string }) {
    return (
        <div
            aria-hidden="true"
            className={cn('pointer-events-none absolute', className)}
        >
            {[100, 75, 50].map((size) => (
                <span
                    key={size}
                    style={{
                        width: `${size}%`,
                        height: `${size}%`,
                    }}
                    className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rounded-full border border-primary-foreground/15"
                />
            ))}
        </div>
    );
}
