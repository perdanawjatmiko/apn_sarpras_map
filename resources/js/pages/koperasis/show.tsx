import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Detail = {
    id: number;
    name: string;
    latitude?: string | null;
    longitude?: string | null;
    province?: string | null;
    city?: string | null;
    district?: string | null;
    village?: string | null;
    delivery_percentage?: string | null;
    installed_percentage?: string | null;
    core_percentage?: string | null;
    sarprases: {
        id: number;
        name: string;
        description?: string | null;
        status?: string | null;
    }[];
};

function percent(value?: string | null) {
    return value ? `${Math.round(Number(value) * 100)}%` : '-';
}

export default function KoperasiShow({ koperasi }: { koperasi: Detail }) {
    return (
        <div className="min-h-screen bg-zinc-50 text-zinc-950">
            <Head title={koperasi.name} />
            <main className="mx-auto flex max-w-6xl flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <Button asChild variant="ghost" className="-ml-3 mb-2">
                            <Link href="/">
                                <ArrowLeft /> Kembali ke peta
                            </Link>
                        </Button>
                        <h1 className="text-3xl font-semibold tracking-normal">
                            {koperasi.name}
                        </h1>
                        <p className="mt-2 flex items-center gap-2 text-sm text-zinc-600">
                            <MapPin className="size-4" />
                            {[koperasi.village, koperasi.district, koperasi.city, koperasi.province]
                                .filter(Boolean)
                                .join(', ') || 'Wilayah belum terhubung'}
                        </p>
                    </div>
                    <div className="grid grid-cols-3 gap-2 text-center">
                        <div className="rounded-lg bg-white p-3 shadow-xs">
                            <div className="text-lg font-semibold text-emerald-700">
                                {percent(koperasi.delivery_percentage)}
                            </div>
                            <div className="text-xs text-zinc-500">Pengiriman</div>
                        </div>
                        <div className="rounded-lg bg-white p-3 shadow-xs">
                            <div className="text-lg font-semibold text-sky-700">
                                {percent(koperasi.installed_percentage)}
                            </div>
                            <div className="text-xs text-zinc-500">Terpasang</div>
                        </div>
                        <div className="rounded-lg bg-white p-3 shadow-xs">
                            <div className="text-lg font-semibold text-amber-700">
                                {percent(koperasi.core_percentage)}
                            </div>
                            <div className="text-xs text-zinc-500">Inti</div>
                        </div>
                    </div>
                </div>
                <section className="grid gap-4 md:grid-cols-[320px_1fr]">
                    <div className="rounded-lg bg-white p-5 shadow-xs">
                        <h2 className="font-semibold">Informasi Lokasi</h2>
                        <dl className="mt-4 space-y-3 text-sm">
                            <div>
                                <dt className="text-zinc-500">Latitude</dt>
                                <dd>{koperasi.latitude ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-zinc-500">Longitude</dt>
                                <dd>{koperasi.longitude ?? '-'}</dd>
                            </div>
                        </dl>
                    </div>
                    <div className="rounded-lg bg-white p-5 shadow-xs">
                        <h2 className="font-semibold">Sarpras Diterima</h2>
                        <div className="mt-4 overflow-hidden rounded-md border">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-zinc-100 text-xs uppercase text-zinc-500">
                                    <tr>
                                        <th className="p-3">Sarpras</th>
                                        <th className="p-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {koperasi.sarprases.map((item) => (
                                        <tr key={item.id} className="border-t">
                                            <td className="p-3 font-medium">{item.name}</td>
                                            <td className="p-3">{item.status ?? '-'}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    );
}
