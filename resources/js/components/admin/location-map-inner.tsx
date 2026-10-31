import L from 'leaflet';
import iconRetinaUrl from 'leaflet/dist/images/marker-icon-2x.png';
import iconUrl from 'leaflet/dist/images/marker-icon.png';
import shadowUrl from 'leaflet/dist/images/marker-shadow.png';
import 'leaflet/dist/leaflet.css';
import { useEffect, useMemo } from 'react';
import {
    Circle,
    MapContainer,
    Marker,
    TileLayer,
    useMap,
    useMapEvents,
} from 'react-leaflet';
import type { LocationMapProps } from '@/components/admin/location-map';

L.Icon.Default.mergeOptions({ iconRetinaUrl, iconUrl, shadowUrl });

const DEFAULT_CENTER: [number, number] = [-2.5, 118];

function ClickToSet({ onChange }: Pick<LocationMapProps, 'onChange'>) {
    useMapEvents({
        click(event) {
            onChange(event.latlng.lat, event.latlng.lng);
        },
    });

    return null;
}

function PanTo({ position }: { position: [number, number] }) {
    const map = useMap();

    useEffect(() => {
        map.panTo(position);
    }, [map, position]);

    return null;
}

export default function LocationMapInner({
    latitude,
    longitude,
    radius,
    onChange,
}: LocationMapProps) {
    const hasPosition = latitude !== null && longitude !== null;
    const position = useMemo<[number, number]>(
        () => (hasPosition ? [latitude, longitude] : DEFAULT_CENTER),
        [hasPosition, latitude, longitude],
    );

    return (
        <MapContainer
            center={position}
            zoom={hasPosition ? 17 : 5}
            scrollWheelZoom
            className="z-0 h-80 w-full rounded-lg border"
        >
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            <ClickToSet onChange={onChange} />
            {hasPosition && (
                <>
                    <PanTo position={position} />
                    <Marker
                        position={position}
                        draggable
                        eventHandlers={{
                            dragend(event) {
                                const { lat, lng } = (
                                    event.target as L.Marker
                                ).getLatLng();
                                onChange(lat, lng);
                            },
                        }}
                    />
                    <Circle center={position} radius={radius} />
                </>
            )}
        </MapContainer>
    );
}
