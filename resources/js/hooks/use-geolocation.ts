import { useEffect, useState } from 'react';

export type GeolocationError = 'denied' | 'unavailable' | 'insecure' | null;

type Coordinates = {
    latitude: number;
    longitude: number;
    accuracy: number;
};

const EARTH_RADIUS_METERS = 6371000;

/** Great-circle distance in meters. Display only; the server decides. */
export function distanceInMeters(
    fromLatitude: number,
    fromLongitude: number,
    toLatitude: number,
    toLongitude: number,
): number {
    const toRadians = (degrees: number) => (degrees * Math.PI) / 180;
    const deltaLatitude = toRadians(toLatitude - fromLatitude);
    const deltaLongitude = toRadians(toLongitude - fromLongitude);
    const a =
        Math.sin(deltaLatitude / 2) ** 2 +
        Math.cos(toRadians(fromLatitude)) *
            Math.cos(toRadians(toLatitude)) *
            Math.sin(deltaLongitude / 2) ** 2;

    return EARTH_RADIUS_METERS * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

export function useGeolocation() {
    const [coords, setCoords] = useState<Coordinates | null>(null);
    const [error, setError] = useState<GeolocationError>(null);

    useEffect(() => {
        if (!window.isSecureContext) {
            setError('insecure');

            return;
        }

        if (!('geolocation' in navigator)) {
            setError('unavailable');

            return;
        }

        const watchId = navigator.geolocation.watchPosition(
            (position) => {
                setError(null);
                setCoords({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                });
            },
            (positionError) => {
                setError(
                    positionError.code === positionError.PERMISSION_DENIED
                        ? 'denied'
                        : 'unavailable',
                );
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 20000 },
        );

        return () => navigator.geolocation.clearWatch(watchId);
    }, []);

    return { coords, error };
}
