import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { Search, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { SarprasMarker } from '@/types/sarpras-map';

function percent(value?: string | null) {
    if (!value) {
        return '-';
    }

    return `${Math.round(Number(value) * 100)}%`;
}

function escapeHtml(value?: string | number | null) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function popupContent(marker: SarprasMarker) {
    const sarprasItems = Array.isArray(marker.sarprases)
        ? marker.sarprases
        : Object.values(marker.sarprases ?? {});
    const sarprases = sarprasItems
        .map((item) => `
            <div class="flex justify-between gap-2 rounded bg-zinc-50 px-2 py-1">
                <span>${escapeHtml(item.name)}</span>
                <span class="font-medium text-zinc-600">${escapeHtml(item.status ?? '-')}</span>
            </div>
        `)
        .join('');

    return `
        <div class="w-72 text-zinc-950">
            <h2 class="text-base font-semibold">${escapeHtml(marker.name)}</h2>
            <p class="mt-1 text-xs text-zinc-500">${escapeHtml([marker.village, marker.district, marker.city, marker.province].filter(Boolean).join(', '))}</p>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                <div class="rounded-md bg-emerald-50 p-2"><div class="font-semibold text-emerald-700">${marker.sarpras_count}</div><div>Sarpras</div></div>
                <div class="rounded-md bg-sky-50 p-2"><div class="font-semibold text-sky-700">${percent(marker.delivery_percentage)}</div><div>Kirim</div></div>
                <div class="rounded-md bg-amber-50 p-2"><div class="font-semibold text-amber-700">${percent(marker.installed_percentage)}</div><div>Pasang</div></div>
            </div>
            <div class="mt-3 max-h-32 space-y-1 overflow-auto text-xs">${sarprases}</div>
            <a href="${escapeHtml(marker.detail_url)}" class="mt-3 flex h-9 items-center justify-center rounded-md bg-zinc-950 px-3 text-sm font-medium text-white">Detail koperasi</a>
        </div>
    `;
}

export function IndonesiaMap({
    markers,
    provinces,
    cities,
}: {
    markers: SarprasMarker[];
    provinces: string[];
    cities: string[];
}) {
    const mapElement = useRef<HTMLDivElement | null>(null);
    const map = useRef<L.Map | null>(null);
    const markerLayer = useRef<L.LayerGroup | null>(null);
    const [query, setQuery] = useState('');
    const [province, setProvince] = useState('');
    const [city, setCity] = useState('');

    const filtered = useMemo(() => {
        const keyword = query.toLowerCase();

        return markers.filter((marker) => {
            const haystack = [marker.name, marker.province, marker.city, marker.district, marker.village]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            return (!keyword || haystack.includes(keyword))
                && (!province || marker.province === province)
                && (!city || marker.city === city);
        });
    }, [markers, query, province, city]);

    useEffect(() => {
        if (!mapElement.current || map.current) {
            return;
        }

        map.current = L.map(mapElement.current, {
            center: [-2.5, 118],
            zoom: 5,
            minZoom: 4,
            maxZoom: 18,
            zoomControl: true,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map.current);

        markerLayer.current = L.layerGroup().addTo(map.current);

        return () => {
            map.current?.remove();
            map.current = null;
            markerLayer.current = null;
        };
    }, []);

    useEffect(() => {
        if (!map.current || !markerLayer.current) {
            return;
        }

        markerLayer.current.clearLayers();

        const icon = L.divIcon({
            className: '',
            html: '<span class="block size-4 rounded-full border-2 border-white bg-emerald-500 shadow-lg shadow-emerald-950/40"></span>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
            popupAnchor: [0, -8],
        });

        filtered.forEach((marker) => {
            L.marker([marker.latitude, marker.longitude], { icon })
                .bindPopup(popupContent(marker), { maxWidth: 340 })
                .addTo(markerLayer.current!);
        });
    }, [filtered]);

    return (
        <div className="flex min-h-screen flex-col bg-zinc-950 text-white">
            <div className="flex flex-col gap-3 border-b border-white/10 bg-zinc-950/95 p-4 md:flex-row md:items-center">
                <div className="min-w-0 flex-1">
                    <h1 className="text-xl font-semibold tracking-normal">Peta Penyebaran Sarpras Koperasi</h1>
                    <p className="text-sm text-zinc-300">{filtered.length} dari {markers.length} koperasi tampil</p>
                </div>
                <div className="grid gap-2 md:grid-cols-[260px_180px_180px_auto]">
                    <div className="relative">
                        <Search className="absolute top-2.5 left-3 size-4 text-zinc-400" />
                        <Input value={query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari koperasi atau wilayah" className="border-white/15 bg-white pl-9 text-zinc-950" />
                    </div>
                    <select value={province} onChange={(event) => setProvince(event.target.value)} className="h-9 rounded-md border border-white/15 bg-white px-3 text-sm text-zinc-950">
                        <option value="">Semua provinsi</option>
                        {provinces.map((item) => <option key={item} value={item}>{item}</option>)}
                    </select>
                    <select value={city} onChange={(event) => setCity(event.target.value)} className="h-9 rounded-md border border-white/15 bg-white px-3 text-sm text-zinc-950">
                        <option value="">Semua kota</option>
                        {cities.map((item) => <option key={item} value={item}>{item}</option>)}
                    </select>
                    <Button variant="secondary" onClick={() => { setQuery(''); setProvince(''); setCity(''); }}>
                        <X /> Reset
                    </Button>
                </div>
            </div>
            <div className="relative min-h-[calc(100vh-86px)] flex-1">
                <div ref={mapElement} className="absolute inset-0 z-0" />
            </div>
        </div>
    );
}
