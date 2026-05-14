import { Head } from '@inertiajs/react';
import { PackageCheck, Send, Store, TrendingUp } from 'lucide-react';
import type { ComponentType } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

type StatProps = {
    title: string;
    value: string | number;
    description: string;
    icon: ComponentType<{ className?: string }>;
};

function StatCard({ title, value, description, icon: Icon }: StatProps) {
    return (
        <Card className="rounded-lg">
            <CardHeader className="flex-row items-start justify-between gap-4">
                <div>
                    <CardDescription>{title}</CardDescription>
                    <CardTitle className="mt-2 text-3xl font-semibold">{value}</CardTitle>
                </div>
                <div className="rounded-md bg-emerald-50 p-2 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                    <Icon className="size-5" />
                </div>
            </CardHeader>
            <CardContent className="text-xs text-muted-foreground">{description}</CardContent>
        </Card>
    );
}

function StatusChart({ data }: { data: { label: string; value: number }[] }) {
    const max = Math.max(...data.map((item) => item.value), 1);

    return (
        <Card className="rounded-lg">
            <CardHeader>
                <CardTitle>Distribusi Status Sarpras</CardTitle>
                <CardDescription>Jumlah sarpras koperasi berdasarkan status terakhir.</CardDescription>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4">
                    {data.map((item) => (
                        <div key={item.label} className="grid gap-2">
                            <div className="flex items-center justify-between text-sm">
                                <span className="font-medium">{item.label}</span>
                                <span className="text-muted-foreground">{item.value}</span>
                            </div>
                            <div className="h-3 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full rounded-full bg-emerald-600"
                                    style={{ width: `${Math.max((item.value / max) * 100, item.value ? 8 : 0)}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({
    stats,
    chart,
    total_sarprases,
}: {
    stats: {
        koperasis: number;
        installed_sarprases: number;
        shipping_sarprases: number;
        completion_percentage: number;
    };
    chart: { label: string; value: number }[];
    total_sarprases: number;
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">Dashboard Admin</h1>
                    <p className="text-sm text-muted-foreground">Ringkasan penyebaran sarpras koperasi.</p>
                </div>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        title="Jumlah Koperasi"
                        value={stats.koperasis}
                        description="Total koperasi yang tercatat di sistem."
                        icon={Store}
                    />
                    <StatCard
                        title="Sarpras Terpasang"
                        value={stats.installed_sarprases}
                        description={`Dari ${total_sarprases} jenis sarpras master.`}
                        icon={PackageCheck}
                    />
                    <StatCard
                        title="Dalam Pengiriman"
                        value={stats.shipping_sarprases}
                        description="Relasi sarpras koperasi berstatus pengiriman."
                        icon={Send}
                    />
                    <StatCard
                        title="Kelengkapan"
                        value={`${stats.completion_percentage}%`}
                        description="Dummy persentase kelengkapan data."
                        icon={TrendingUp}
                    />
                </div>
                <StatusChart data={chart} />
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
