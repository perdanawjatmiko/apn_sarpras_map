import { Head } from '@inertiajs/react';
import { IndonesiaMap } from '@/components/map/indonesia-map';
import type { SarprasMarker } from '@/types';

type RegionOption = { id: number; name: string };

export default function Welcome({
    markers,
    filters,
    stats,
}: {
    markers: SarprasMarker[];
    filters: { provinces: RegionOption[]; sarprases: string[] };
    stats: { koperasis: number; sarprases: number };
}) {
    return (
        <>
            <Head title="Peta Sarpras Koperasi" />
            <div className="absolute top-4 right-4 z-20 flex gap-2">
                <div className="hidden rounded-md border border-white/10 bg-zinc-950/80 px-3 py-2 text-xs text-white shadow-lg backdrop-blur md:block">
                    {stats.koperasis} koperasi | {stats.sarprases} sarpras
                </div>
            </div>
            <IndonesiaMap
                markers={markers}
                provinces={filters.provinces}
            />
        </>
    );
}
