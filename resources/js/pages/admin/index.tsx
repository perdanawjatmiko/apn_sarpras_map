import { Head, Link, router, useForm } from '@inertiajs/react';
import { Database, Download, FileUp, MapPinned, Package, Plus, Search, Trash2, Users } from 'lucide-react';
import { FormEvent, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { Pagination } from '@/types';

type Row = Record<string, any>;
type Option = { id: number | string; name: string };
type Tab = 'koperasis' | 'sarprases' | 'koperasiSarprases' | 'users';

const tabs: { key: Tab; label: string; icon: typeof MapPinned }[] = [
    { key: 'koperasis', label: 'Koperasi', icon: MapPinned },
    { key: 'sarprases', label: 'Sarpras', icon: Package },
    { key: 'koperasiSarprases', label: 'Sarpras Koperasi', icon: Database },
    { key: 'users', label: 'User', icon: Users },
];

function text(value: unknown) {
    if (value && typeof value === 'object' && 'name' in value) {
        return String((value as { name?: string }).name ?? '-');
    }

    return value === null || value === undefined || value === '' ? '-' : String(value);
}

function AdminForm({
    tab,
    row,
    options,
    onDone,
}: {
    tab: Tab;
    row?: Row | null;
    options: { koperasis: Option[]; sarprases: Option[]; statuses: Option[]; cities: Option[]; districts: Option[]; villages: Option[] };
    onDone: () => void;
}) {
    const defaults = useMemo(() => {
        if (tab === 'users') {
            return { name: row?.name ?? '', email: row?.email ?? '', password: '' };
        }

        if (tab === 'sarprases') {
            return { name: row?.name ?? '', slug: row?.slug ?? '', description: row?.description ?? '' };
        }

        if (tab === 'koperasiSarprases') {
            return {
                koperasi_id: row?.koperasi_id ?? row?.koperasi?.id ?? '',
                sarpras_id: row?.sarpras_id ?? row?.sarpras?.id ?? '',
                status_id: row?.status_id ?? row?.status?.id ?? '',
            };
        }

        return {
            ai_id: row?.ai_id ?? '',
            name: row?.name ?? '',
            village_id: row?.village_id ?? '',
            district_id: row?.district_id ?? '',
            city_id: row?.city_id ?? '',
            province_id: row?.province_id ?? '',
            latitude: row?.latitude ?? '',
            longitude: row?.longitude ?? '',
            delivery_percentage: row?.delivery_percentage ?? '',
            installed_percentage: row?.installed_percentage ?? '',
            core_percentage: row?.core_percentage ?? '',
        };
    }, [options, row, tab]);
    const form = useForm(defaults);
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit ? `/admin/${routeSegment(tab)}/${row?.id}` : `/admin/${routeSegment(tab)}`;
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            {tab === 'users' && (
                <>
                    <Field label="Nama" value={form.data.name} onChange={(value) => form.setData('name', value)} error={form.errors.name} />
                    <Field label="Email" type="email" value={form.data.email} onChange={(value) => form.setData('email', value)} error={form.errors.email} />
                    <Field label={isEdit ? 'Password baru' : 'Password'} type="password" value={form.data.password} onChange={(value) => form.setData('password', value)} error={form.errors.password} />
                </>
            )}
            {tab === 'sarprases' && (
                <>
                    <Field label="Nama sarpras" value={form.data.name} onChange={(value) => form.setData('name', value)} error={form.errors.name} />
                    <Field label="Slug" value={form.data.slug} onChange={(value) => form.setData('slug', value)} error={form.errors.slug} />
                    <Field label="Deskripsi" value={form.data.description} onChange={(value) => form.setData('description', value)} error={form.errors.description} />
                </>
            )}
            {tab === 'koperasiSarprases' && (
                <>
                    <SelectField label="Koperasi" value={form.data.koperasi_id} options={options.koperasis} onChange={(value) => form.setData('koperasi_id', value)} error={form.errors.koperasi_id} />
                    <SelectField label="Sarpras" value={form.data.sarpras_id} options={options.sarprases} onChange={(value) => form.setData('sarpras_id', value)} error={form.errors.sarpras_id} />
                    <SelectField label="Status" value={form.data.status_id} options={options.statuses} onChange={(value) => form.setData('status_id', value)} error={form.errors.status_id} />
                </>
            )}
            {tab === 'koperasis' && (
                <>
                    <Field label="AI ID" value={form.data.ai_id} onChange={(value) => form.setData('ai_id', value)} error={form.errors.ai_id} />
                    <Field label="Nama koperasi" value={form.data.name} onChange={(value) => form.setData('name', value)} error={form.errors.name} />
                    <div className="grid gap-3 md:grid-cols-3">
                        <SelectField label="Desa" value={form.data.village_id} options={options.villages} onChange={(value) => form.setData('village_id', value)} error={form.errors.village_id} />
                        <SelectField label="Kecamatan" value={form.data.district_id} options={options.districts} onChange={(value) => form.setData('district_id', value)} error={form.errors.district_id} />
                        <SelectField label="Kota" value={form.data.city_id} options={options.cities} onChange={(value) => form.setData('city_id', value)} error={form.errors.city_id} />
                    </div>
                    <div className="grid gap-3 md:grid-cols-2">
                        <Field label="Latitude" value={form.data.latitude} onChange={(value) => form.setData('latitude', value)} error={form.errors.latitude} />
                        <Field label="Longitude" value={form.data.longitude} onChange={(value) => form.setData('longitude', value)} error={form.errors.longitude} />
                    </div>
                    <div className="grid gap-3 md:grid-cols-3">
                        <Field label="Pengiriman" value={form.data.delivery_percentage} onChange={(value) => form.setData('delivery_percentage', value)} error={form.errors.delivery_percentage} />
                        <Field label="Terpasang" value={form.data.installed_percentage} onChange={(value) => form.setData('installed_percentage', value)} error={form.errors.installed_percentage} />
                        <Field label="Inti" value={form.data.core_percentage} onChange={(value) => form.setData('core_percentage', value)} error={form.errors.core_percentage} />
                    </div>
                </>
            )}
            <Button disabled={form.processing} className="mt-2">{isEdit ? 'Simpan perubahan' : 'Tambah data'}</Button>
        </form>
    );
}

function Field({ label, value, onChange, error, type = 'text' }: { label: string; value: any; onChange: (value: any) => void; error?: string; type?: string }) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium">{label}</span>
            <Input type={type} value={value ?? ''} onChange={(event) => onChange(event.target.value)} />
            {error && <span className="text-xs text-red-600">{error}</span>}
        </label>
    );
}

function SelectField({ label, value, options, onChange, error }: { label: string; value: any; options: Option[]; onChange: (value: any) => void; error?: string }) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium">{label}</span>
            <select value={value ?? ''} onChange={(event) => onChange(event.target.value)} className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs">
                <option value="">Pilih</option>
                {options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}
            </select>
            {error && <span className="text-xs text-red-600">{error}</span>}
        </label>
    );
}

function routeSegment(tab: Tab) {
    return tab === 'koperasiSarprases' ? 'koperasi-sarprases' : tab;
}

function ImportButton({ tab }: { tab: Tab }) {
    const [file, setFile] = useState<File | null>(null);
    const [open, setOpen] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!file) {
            return;
        }

        router.post(`/admin/${routeSegment(tab)}/import`, { file }, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    if (tab === 'users') {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline"><FileUp /> Import</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader><DialogTitle>Import {tabs.find((item) => item.key === tab)?.label}</DialogTitle></DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <Input type="file" accept=".csv,.xlsx" onChange={(event) => setFile(event.target.files?.[0] ?? null)} />
                    <p className="text-sm text-muted-foreground">Gunakan format CSV atau XLSX. Datasheet contoh di database/data/2026_1061.xlsx sudah didukung.</p>
                    <Button type="submit">Upload</Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function columns(tab: Tab) {
    if (tab === 'users') {
        return ['name', 'email', 'created_at'];
    }

    if (tab === 'sarprases') {
        return ['name', 'slug', 'description'];
    }

    if (tab === 'koperasiSarprases') {
        return ['koperasi', 'sarpras', 'status'];
    }

    return ['ai_id', 'name', 'village', 'district', 'city', 'latitude', 'longitude'];
}

export default function AdminIndex({
    resource,
    title,
    search,
    stats,
    records,
    options,
}: {
    resource: Tab;
    title: string;
    search: string;
    stats: Record<string, number>;
    records: Pagination<Row>;
    options: { koperasis: Option[]; sarprases: Option[]; statuses: Option[]; cities: Option[]; districts: Option[]; villages: Option[] };
}) {
    const tab = resource;
    const [editing, setEditing] = useState<Row | null>(null);
    const [open, setOpen] = useState(false);

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get(`/admin/${routeSegment(tab)}`, { search: form.get('search') }, { preserveState: true });
    };

    const remove = (row: Row) => {
        router.delete(`/admin/${routeSegment(tab)}/${row.id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-5 p-4 md:p-6">
                <div className="grid gap-3 md:grid-cols-4">
                    {Object.entries(stats).map(([key, value]) => (
                        <div key={key} className="rounded-lg border bg-card p-4 shadow-xs">
                            <div className="text-2xl font-semibold">{value}</div>
                            <div className="text-sm capitalize text-muted-foreground">{key.replaceAll('_', ' ')}</div>
                        </div>
                    ))}
                </div>
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal">{title}</h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola data {title.toLowerCase()}.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <form onSubmit={submitSearch} className="relative min-w-0 flex-1 md:flex-none">
                            <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                            <Input name="search" defaultValue={search} placeholder="Cari data" className="w-full pl-9 md:w-64" />
                        </form>
                        <ImportButton tab={tab} />
                        {tab === 'koperasis' && (
                            <Button asChild variant="outline">
                                <a href="/admin/koperasis/template">
                                    <Download /> Template
                                </a>
                            </Button>
                        )}
                        {tab === 'koperasiSarprases' && (
                            <>
                                <Button asChild variant="outline">
                                    <a href="/admin/koperasi-sarprases/template.csv">
                                        <Download /> CSV
                                    </a>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href="/admin/koperasi-sarprases/template.xlsx">
                                        <Download /> Excel
                                    </a>
                                </Button>
                            </>
                        )}
                        <Dialog open={open} onOpenChange={setOpen}>
                            <DialogTrigger asChild>
                                <Button onClick={() => setEditing(null)}><Plus /> Tambah</Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-3xl">
                                <DialogHeader><DialogTitle>{editing ? 'Edit' : 'Tambah'} {tabs.find((item) => item.key === tab)?.label}</DialogTitle></DialogHeader>
                                <AdminForm tab={tab} row={editing} options={options} onDone={() => setOpen(false)} />
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
                <div className="overflow-hidden rounded-lg border bg-card">
                    <div className="w-full overflow-x-auto">
                    <table className="min-w-[920px] w-full text-left text-sm">
                        <thead className="bg-muted text-xs uppercase text-muted-foreground">
                            <tr>
                                {columns(tab).map((column) => <th key={column} className="whitespace-nowrap p-3">{column.replaceAll('_', ' ')}</th>)}
                                <th className="sticky right-0 w-40 bg-muted p-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {records.data.map((row) => (
                                <tr key={row.id} className="border-t">
                                    {columns(tab).map((column) => <td key={column} className="max-w-64 truncate p-3">{text(row[column])}</td>)}
                                    <td className="sticky right-0 bg-card p-3 shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.8)]">
                                        <div className="flex gap-2">
                                            <Button size="sm" variant="outline" onClick={() => { setEditing(row); setOpen(true); }}>Edit</Button>
                                            <Button size="sm" variant="destructive" onClick={() => remove(row)}><Trash2 /></Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    </div>
                </div>
                <div className="flex flex-wrap gap-2">
                    {records.links.map((link, index) => (
                        <Button key={`${link.label}-${index}`} asChild={Boolean(link.url)} disabled={!link.url} variant={link.active ? 'default' : 'outline'} size="sm">
                            {link.url ? <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} /> : <span dangerouslySetInnerHTML={{ __html: link.label }} />}
                        </Button>
                    ))}
                </div>
            </div>
        </>
    );
}

AdminIndex.layout = {
    breadcrumbs: [
        {
            title: 'Admin',
            href: '/admin',
        },
    ],
};
