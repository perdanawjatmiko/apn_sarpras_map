import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { SlidersHorizontal, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import type { SarprasMarker } from '@/types/sarpras-map';

type RegionOption = { id: number; name: string };
type MarkerColor = 'blue' | 'emerald' | 'amber' | 'red';
type FilterState = {
    query: string;
    retailReady: boolean;
    provinceId: string;
    cityId: string;
    districtId: string;
    villageId: string;
};

const STATUS_ORDER = ['terpasang', 'tiba', 'pengiriman', 'transit', 'tanpa_status'];
const TOTAL_SARPRAS = 16;

function escapeHtml(value?: string | number | null) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function statusBadgeClass(status?: string | null) {
    const normalized = String(status ?? '').toLowerCase();

    if (normalized === 'terpasang') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (normalized === 'tiba') {
        return 'border-sky-200 bg-sky-50 text-sky-700';
    }

    if (normalized === 'pengiriman') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (normalized === 'transit') {
        return 'border-violet-200 bg-violet-50 text-violet-700';
    }

    return 'border-zinc-200 bg-zinc-50 text-zinc-600';
}

function statusSortValue(status?: string | null) {
    const index = STATUS_ORDER.indexOf(String(status ?? 'tanpa_status').toLowerCase());

    return index === -1 ? STATUS_ORDER.length : index;
}

function markerColorClass(marker: SarprasMarker, useRetailReadiness: boolean) {
    const color = markerColor(marker, useRetailReadiness);

    return {
        blue: 'bg-blue-600/60 border-blue-700 shadow-blue-950/40',
        emerald: 'bg-emerald-500/60 border-emerald-600 shadow-emerald-950/40',
        amber: 'bg-yellow-400/60 border-yellow-500 shadow-yellow-950/40',
        red: 'bg-red-500/60 border-red-600 shadow-red-950/40',
    }[color];
}

function markerColor(marker: SarprasMarker, useRetailReadiness = false): MarkerColor {
    if (useRetailReadiness) {
        const mandatoryTotal = Number(marker.mandatory_sarpras_count ?? 0);
        const installedMandatory = Number(marker.installed_mandatory_sarpras_count ?? 0);
        const arrivedMandatory = Number(marker.arrived_mandatory_sarpras_count ?? installedMandatory);
        const percentage = mandatoryTotal > 0 ? (arrivedMandatory / mandatoryTotal) * 100 : 0;

        if (mandatoryTotal > 0 && installedMandatory >= mandatoryTotal) {
            return 'blue';
        }

        if (percentage > 70) {
            return 'emerald';
        }

        if (percentage > 35) {
            return 'amber';
        }

        return 'red';
    }

    const counts = marker.status_counts ?? {};
    const installed = Number(counts.terpasang ?? 0);
    const arrived = installed + Number(counts.tiba ?? 0);
    const percentage = (arrived / TOTAL_SARPRAS) * 100;

    if (installed >= TOTAL_SARPRAS) {
        return 'blue';
    }

    if (percentage > 70) {
        return 'emerald';
    }

    if (percentage > 35) {
        return 'amber';
    }

    return 'red';
}

function popupContent(marker: SarprasMarker) {
    const sarprasItems = Array.isArray(marker.sarprases)
        ? marker.sarprases
        : Object.values(marker.sarprases ?? {});
    const sortedSarprases = [...sarprasItems].sort((first, second) => {
        return statusSortValue(first.status) - statusSortValue(second.status);
    });
    const countedSarprases = sortedSarprases.filter((item) => item.status !== 'tanpa_status').length;
    const counts = marker.status_counts ?? {};
    const statusCards = [
        { label: 'Terpasang', value: counts.terpasang ?? 0, className: 'bg-emerald-50 text-emerald-700' },
        { label: 'Tiba', value: counts.tiba ?? 0, className: 'bg-sky-50 text-sky-700' },
        { label: 'Pengiriman', value: counts.pengiriman ?? 0, className: 'bg-amber-50 text-amber-700' },
        { label: 'Transit', value: counts.transit ?? 0, className: 'bg-violet-50 text-violet-700' },
    ].map((item) => `
        <div class="rounded-md ${item.className} p-2 text-center">
            <div class="text-lg font-semibold leading-none">${item.value}</div>
            <div class="mt-1 text-[10px] uppercase leading-tight">${item.label}</div>
        </div>
    `).join('');
    const sarprases = sortedSarprases.length
        ? sortedSarprases
            .map((item) => `
                <tr class="border-b border-zinc-100 last:border-0">
                    <td class="max-w-0 py-1.5 pl-2 pr-3 align-top text-zinc-700">
                        <span class="block whitespace-normal break-words leading-snug">${escapeHtml(item.name)}</span>
                    </td>
                    <td class="w-28 py-1.5 pl-2 pr-2 align-top text-right">
                        <span class="inline-flex max-w-full items-center justify-center rounded-full border px-2 py-0.5 text-[11px] font-medium capitalize leading-tight ${statusBadgeClass(item.status)}">
                            <span class="truncate">${escapeHtml(item.status ?? '-')}</span>
                        </span>
                    </td>
                </tr>
            `)
            .join('')
        : '<div class="rounded bg-zinc-50 px-2 py-2 text-xs text-zinc-500">Belum ada sarpras tercatat</div>';

    return `
        <div class="w-80 h-fit text-zinc-950">
            <h2 class="text-base font-semibold capitalize">Koperasi Desa ${escapeHtml(marker.name)}</h2>
            <p class="mt-1 text-xs text-zinc-500">${escapeHtml([marker.village, marker.district, marker.city, marker.province].filter(Boolean).join(', '))}</p>
            <h5 class="col-span-4 font-semibold text-zinc-600 text-center my-2 text-base">Detail Sarpras</h5><h5 class="col-span-2 font-semibold text-right"></h5>
            <p>Total sarpras per-KDKMP: <span class="font-semibold text-emerald-600">${countedSarprases}</span> / <span class="font-semibold text-red-600">${sarprasItems.length - 1}</span></p>
            <small>* setiap kdkmp hanya perlu salah satu dari item ini : Internet / Starlink</small>
            <div class="mt-3 grid grid-cols-4 gap-2 text-xs">
                ${statusCards}
            </div>
            <div class="mt-3 max-h-64 overflow-y-auto overflow-x-hidden rounded-md border border-zinc-100 text-xs">
                ${sortedSarprases.length ? `
                    <table class="w-full table-fixed border-collapse">
                        <thead class="sticky top-0 bg-zinc-50 text-left text-[11px] uppercase text-zinc-500">
                            <tr>
                                <th class="px-2 py-1.5 font-medium">Sarpras</th>
                                <th class="w-28 px-2 py-1.5 text-right font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>${sarprases}</tbody>
                    </table>
                ` : sarprases}
            </div>
        </div>
    `;
}

async function fetchRegion(path: string, query: Record<string, string>) {
    const params = new URLSearchParams(query);
    const response = await fetch(`/api/regions/${path}?${params.toString()}`, {
        headers: { Accept: 'application/json' },
    });

    if (!response.ok) {
        return [];
    }

    return normalizeRegionOptions(await response.json());
}

function normalizeRegionOptions(value: unknown): RegionOption[] {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && typeof value === 'object') {
        if ('data' in value) {
            return normalizeRegionOptions((value as { data: unknown }).data);
        }

        return Object.values(value).filter((item): item is RegionOption => {
            return Boolean(item)
                && typeof item === 'object'
                && 'id' in item
                && 'name' in item;
        });
    }

    return [];
}

function RegionSelect({
    value,
    placeholder,
    options,
    disabled,
    onValueChange,
}: {
    value: string;
    placeholder: string;
    options: RegionOption[];
    disabled?: boolean;
    onValueChange: (value: string) => void;
}) {
    const safeOptions = normalizeRegionOptions(options);

    return (
        <Select value={value} onValueChange={onValueChange} disabled={disabled}>
            <SelectTrigger className="w-full border-white/15 bg-white text-zinc-950">
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {safeOptions.map((option) => (
                    <SelectItem key={option.id} value={String(option.id)}>
                        {option.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

function FilterControls({
    filters,
    provinces,
    cities,
    districts,
    villages,
    onChange,
    onReset,
}: {
    filters: FilterState;
    provinces: RegionOption[];
    cities: RegionOption[];
    districts: RegionOption[];
    villages: RegionOption[];
    onChange: (filters: FilterState) => void;
    onReset: () => void;
}) {
    return (
        <div className="grid gap-2 md:grid-cols-[repeat(4,minmax(0,1fr))_minmax(260px,auto)_auto]">
            <RegionSelect
                value={filters.provinceId}
                placeholder="Provinsi"
                options={provinces}
                onValueChange={(provinceId) => onChange({ ...filters, provinceId, cityId: '', districtId: '', villageId: '' })}
            />
            <RegionSelect
                value={filters.cityId}
                placeholder="Kabupaten/Kota"
                options={cities}
                disabled={!filters.provinceId}
                onValueChange={(cityId) => onChange({ ...filters, cityId, districtId: '', villageId: '' })}
            />
            <RegionSelect
                value={filters.districtId}
                placeholder="Kecamatan"
                options={districts}
                disabled={!filters.cityId}
                onValueChange={(districtId) => onChange({ ...filters, districtId, villageId: '' })}
            />
            <RegionSelect
                value={filters.villageId}
                placeholder="Desa"
                options={villages}
                disabled={!filters.districtId}
                onValueChange={(villageId) => onChange({ ...filters, villageId })}
            />
            <label className="flex h-9 items-center justify-center gap-2 rounded-md border border-white/15 bg-white px-3 text-sm font-medium whitespace-nowrap text-zinc-950">
                <Switch
                    checked={filters.retailReady}
                    onCheckedChange={(checked) => onChange({ ...filters, retailReady: checked })}
                />
                Lihat Berdasarkan Kesiapan Retail
            </label>
            <Button variant="secondary" onClick={onReset}>
                <X /> Reset
            </Button>
        </div>
    );
}

function RetailModeNote({ className = '' }: { className?: string }) {
    return (
        <div className={`rounded-md border border-amber-200/40 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-950 shadow-lg ${className}`}>
            Data hanya menghitung sarpras esensial untuk operasional Retail
        </div>
    );
}

export function IndonesiaMap({
    markers,
    provinces,
}: {
    markers: SarprasMarker[];
    provinces: RegionOption[];
}) {
    const mapElement = useRef<HTMLDivElement | null>(null);
    const map = useRef<L.Map | null>(null);
    const markerLayer = useRef<L.LayerGroup | null>(null);
    const hasFitInitialBounds = useRef(false);
    const [filters, setFilters] = useState<FilterState>({ query: '', retailReady: false, provinceId: '', cityId: '', districtId: '', villageId: '' });
    const [cities, setCities] = useState<RegionOption[]>([]);
    const [districts, setDistricts] = useState<RegionOption[]>([]);
    const [villages, setVillages] = useState<RegionOption[]>([]);

    const filtered = useMemo(() => {
        const query = filters.query.trim().toLowerCase();

        return markers.filter((marker) => {
            return (!query || marker.name.toLowerCase().includes(query))
                && (!filters.provinceId || marker.province_id === Number(filters.provinceId))
                && (!filters.cityId || marker.city_id === Number(filters.cityId))
                && (!filters.districtId || marker.district_id === Number(filters.districtId))
                && (!filters.villageId || marker.village_id === Number(filters.villageId));
        });
    }, [markers, filters]);

    const legendCounts = useMemo(() => {
        return filtered.reduce(
            (totals, marker) => {
                totals[markerColor(marker, filters.retailReady)]++;

                return totals;
            },
            { blue: 0, emerald: 0, amber: 0, red: 0 } as Record<MarkerColor, number>,
        );
    }, [filtered, filters.retailReady]);

    useEffect(() => {
        let cancelled = false;

        const request = filters.provinceId
            ? fetchRegion('cities', { province_id: filters.provinceId })
            : Promise.resolve([]);

        request
            .then((options) => {
                if (!cancelled) {
                    setCities(options);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [filters.provinceId]);

    useEffect(() => {
        let cancelled = false;

        const request = filters.cityId
            ? fetchRegion('districts', { city_id: filters.cityId })
            : Promise.resolve([]);

        request
            .then((options) => {
                if (!cancelled) {
                    setDistricts(options);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [filters.cityId]);

    useEffect(() => {
        let cancelled = false;

        const request = filters.districtId
            ? fetchRegion('villages', { district_id: filters.districtId })
            : Promise.resolve([]);

        request
            .then((options) => {
                if (!cancelled) {
                    setVillages(options);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [filters.districtId]);

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

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri, Maxar, Earthstar Geographics, and the GIS User Community',
            maxZoom: 18,
        }).addTo(map.current);

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Boundaries and labels &copy; Esri',
            maxZoom: 18,
            pane: 'overlayPane',
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

        filtered.forEach((marker) => {
            const icon = L.divIcon({
                className: '',
                html: `<span class="block size-3 rounded-full border shadow-lg ${markerColorClass(marker, filters.retailReady)}"></span>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
                popupAnchor: [0, -8],
            });

            L.marker([marker.latitude, marker.longitude], { icon })
                .bindPopup(popupContent(marker), { maxWidth: 360 })
                .addTo(markerLayer.current!);
        });

        if (!hasFitInitialBounds.current && filtered.length > 0) {
            hasFitInitialBounds.current = true;

            if (filtered.length === 1) {
                map.current.setView([filtered[0].latitude, filtered[0].longitude], 12);

                return;
            }

            const bounds = L.latLngBounds(filtered.map((marker) => [marker.latitude, marker.longitude] as [number, number]));
            map.current.fitBounds(bounds, {
                maxZoom: 11,
                paddingTopLeft: [24, 150],
                paddingBottomRight: [300, 40],
            });
        }
    }, [filtered, filters.retailReady]);

    const resetFilters = () => {
        setFilters({ query: '', retailReady: false, provinceId: '', cityId: '', districtId: '', villageId: '' });
    };

    return (
        <div className="relative min-h-screen bg-primary text-white">
            <div className="absolute inset-x-0 top-0 z-20 hidden border-b border-white/10 bg-primary p-4 md:block">
                <div className="flex flex-col gap-3">
                    <div className="min-w-0">
                        <h1 className="text-xl font-semibold tracking-normal">Peta Pengiriman Sarpras Koperasi Desa Kelurahan Merah Putih</h1>
                        <p className="text-sm text-zinc-300">{filtered.length} dari {markers.length} koperasi tampil</p>
                    </div>
                    <FilterControls
                        filters={filters}
                        provinces={provinces}
                        cities={cities}
                        districts={districts}
                        villages={villages}
                        onChange={setFilters}
                        onReset={resetFilters}
                    />
                    {filters.retailReady && <RetailModeNote />}
                </div>
            </div>

            <div className="absolute top-4 left-4 z-20 md:hidden">
                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="secondary">
                            <SlidersHorizontal /> Filter
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="top-auto bottom-0 translate-y-0 rounded-b-none sm:top-[50%] sm:bottom-auto sm:translate-y-[-50%] sm:rounded-lg">
                        <DialogHeader>
                            <DialogTitle>Filter Wilayah</DialogTitle>
                        </DialogHeader>
                        <FilterControls
                            filters={filters}
                            provinces={provinces}
                            cities={cities}
                            districts={districts}
                            villages={villages}
                            onChange={setFilters}
                            onReset={resetFilters}
                        />
                    </DialogContent>
                </Dialog>
            </div>

            {filters.retailReady && (
                <RetailModeNote className="absolute inset-x-4 top-16 z-20 md:hidden" />
            )}

            <div className="absolute right-4 top-4 z-20 rounded-md border border-white/10 bg-zinc-950/80 px-3 py-2 text-xs text-white shadow-lg backdrop-blur">
                {filtered.length} / {markers.length} koperasi
            </div>

            <div className="absolute right-4 bottom-6 z-20 rounded-md border border-white/10 bg-black-950/50 p-3 text-sm text-white shadow-lg backdrop-blur w-fit">
                <div className="mb-2 font-semibold">Keterangan Marker</div>
                {filters.retailReady && (
                    <div className="mb-2 rounded bg-white/10 px-2 py-1 text-xs font-medium">
                        Berdasarkan sarpras mandatory retail
                    </div>
                )}
                <div className="grid gap-2.5 font-semibold">
                    <div className="flex items-center gap-2"><span className="size-3.5 rounded-full bg-blue-600" /> {filters.retailReady ? 'Siap operasional retail' : '100% terpasang'} <span className="ml-auto">({legendCounts.blue})</span></div>
                    <div className="flex items-center gap-2"><span className="size-3.5 rounded-full bg-emerald-500" /> Lebih dari 70% tiba/terpasang <span className="ml-auto">({legendCounts.emerald})</span></div>
                    <div className="flex items-center gap-2"><span className="size-3.5 rounded-full bg-amber-400" /> Lebih dari 35% tiba/terpasang <span className="ml-auto">({legendCounts.amber})</span></div>
                    <div className="flex items-center gap-2"><span className="size-3.5 rounded-full bg-red-500" /> Kurang dari 35% tiba/terpasang <span className="ml-auto">({legendCounts.red})</span></div>
                </div>
            </div>

            <div ref={mapElement} className="absolute inset-0 z-0" />
        </div>
    );
}
