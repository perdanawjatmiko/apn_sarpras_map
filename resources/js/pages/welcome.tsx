import { Head, Link } from '@inertiajs/react';
import { LogIn, Settings } from 'lucide-react';
import { IndonesiaMap } from '@/components/map/indonesia-map';
import { Button } from '@/components/ui/button';
import type { SarprasMarker } from '@/types';

export default function Welcome({
    markers,
    filters,
    stats,
}: {
    markers: SarprasMarker[];
    filters: { provinces: string[]; cities: string[]; sarprases: string[] };
    stats: { koperasis: number; sarprases: number };
}) {
    return (
        <>
            <Head title="Peta Sarpras Koperasi" />
            <div className="absolute top-4 right-4 z-20 flex gap-2">
                <div className="hidden rounded-md border border-white/10 bg-zinc-950/80 px-3 py-2 text-xs text-white shadow-lg backdrop-blur md:block">
                    {stats.koperasis} koperasi | {stats.sarprases} sarpras
                </div>
                <Button asChild variant="secondary">
                    <Link href="/login">
                        <LogIn /> Login
                    </Link>
                </Button>
                <Button asChild>
                    <Link href="/admin">
                        <Settings /> Admin
                    </Link>
                </Button>
            </div>
            <IndonesiaMap
                markers={markers}
                provinces={filters.provinces}
                cities={filters.cities}
            />
        </>
    );
}
