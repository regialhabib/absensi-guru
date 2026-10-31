import { lazy, Suspense, useEffect, useState } from 'react';
import { Skeleton } from '@/components/ui/skeleton';

export type LocationMapProps = {
    latitude: number | null;
    longitude: number | null;
    radius: number;
    onChange: (latitude: number, longitude: number) => void;
};

const LocationMapInner = lazy(
    () => import('@/components/admin/location-map-inner'),
);

/**
 * Leaflet touches `window` on import, so the map only loads in the browser.
 */
export default function LocationMap(props: LocationMapProps) {
    const [mounted, setMounted] = useState(false);

    useEffect(() => {
        setMounted(true);
    }, []);

    const fallback = <Skeleton className="h-80 w-full rounded-lg" />;

    if (!mounted) {
        return fallback;
    }

    return (
        <Suspense fallback={fallback}>
            <LocationMapInner {...props} />
        </Suspense>
    );
}
