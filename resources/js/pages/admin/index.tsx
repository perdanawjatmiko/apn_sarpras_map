import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Database,
    Download,
    FileUp,
    MapPinned,
    Package,
    Plus,
    Search,
    Tags,
    Trash2,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { Pagination } from '@/types';

type Row = Record<string, any>;
type Option = {
    id: number | string;
    name: string;
    phone?: string;
    parent_id?: number | string | null;
};
type Tab =
    | 'koperasis'
    | 'sarprases'
    | 'koperasiSarprases'
    | 'pengaduanCategories'
    | 'pengaduans'
    | 'users';
type AdminOptions = {
    koperasis: Option[];
    sarprases: Option[];
    statuses: Option[];
    cities: Option[];
    districts: Option[];
    villages: Option[];
    users: Option[];
    provinces?: Option[];
    categories?: Option[];
    sub_categories?: Option[];
    priorities?: Option[];
    complaint_statuses?: Option[];
};

const tabs: { key: Tab; label: string; icon: typeof MapPinned }[] = [
    { key: 'koperasis', label: 'Koperasi', icon: MapPinned },
    { key: 'sarprases', label: 'Sarpras', icon: Package },
    { key: 'koperasiSarprases', label: 'Sarpras Koperasi', icon: Database },
    { key: 'pengaduanCategories', label: 'Kategori Pengaduan', icon: Tags },
    { key: 'pengaduans', label: 'Pengaduan', icon: Tags },
    { key: 'users', label: 'User', icon: Users },
];

function text(value: unknown) {
    if (typeof value === 'boolean') {
        return value ? 'Ya' : 'Tidak';
    }

    if (value && typeof value === 'object' && 'name' in value) {
        return String((value as { name?: string }).name ?? '-');
    }

    return value === null || value === undefined || value === ''
        ? '-'
        : String(value);
}

function ResourceForm({
    tab,
    row,
    options,
    onDone,
}: {
    tab: Tab;
    row?: Row | null;
    options: AdminOptions;
    onDone: () => void;
}) {
    if (tab === 'sarprases') {
        return <SarprasForm row={row} onDone={onDone} />;
    }

    if (tab === 'koperasiSarprases') {
        return (
            <KoperasiSarprasForm row={row} options={options} onDone={onDone} />
        );
    }

    if (tab === 'users') {
        return <UserForm row={row} onDone={onDone} />;
    }

    if (tab === 'pengaduanCategories') {
        return (
            <PengaduanCategoryForm
                row={row}
                options={options}
                onDone={onDone}
            />
        );
    }

    if (tab === 'pengaduans') {
        return <PengaduanForm row={row} options={options} onDone={onDone} />;
    }

    return <KoperasiForm row={row} options={options} onDone={onDone} />;
}

function PengaduanCategoryForm({
    row,
    options,
    onDone,
}: {
    row?: Row | null;
    options: AdminOptions;
    onDone: () => void;
}) {
    const form = useForm({
        parent_id: row?.parent_id ?? row?.parent?.id ?? '',
        name: row?.name ?? '',
        slug: row?.slug ?? '',
        description: row?.description ?? '',
        is_active: row?.is_active !== false && row?.is_active !== 0,
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const url = isEdit
            ? `/admin/pengaduan-categories/${row?.id}`
            : '/admin/pengaduan-categories';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <SelectField
                label="Parent kategori"
                value={form.data.parent_id}
                options={(options.categories ?? []).filter(
                    (category) => category.id !== row?.id,
                )}
                onChange={(value) => form.setData('parent_id', value)}
                error={form.errors.parent_id}
            />
            <Field
                label="Nama kategori"
                value={form.data.name}
                onChange={(value) => form.setData('name', value)}
                error={form.errors.name}
            />
            <Field
                label="Slug"
                value={form.data.slug}
                onChange={(value) => form.setData('slug', value)}
                error={form.errors.slug}
            />
            <Field
                label="Deskripsi"
                value={form.data.description}
                onChange={(value) => form.setData('description', value)}
                error={form.errors.description}
            />
            <label className="flex items-center gap-2 text-sm font-medium">
                <Checkbox
                    checked={Boolean(form.data.is_active)}
                    onCheckedChange={(checked) =>
                        form.setData('is_active', checked === true)
                    }
                />
                Aktif
            </label>
            {form.errors.is_active && (
                <span className="text-xs text-red-600">
                    {form.errors.is_active}
                </span>
            )}
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function PengaduanForm({
    row,
    options,
    onDone,
}: {
    row?: Row | null;
    options: AdminOptions;
    onDone: () => void;
}) {
    const form = useForm({
        ticket_id: row?.ticket_id ?? '',
        ticket_date: row?.ticket_date ?? '',
        reporter_user_id: row?.reporter_user_id ?? row?.reporter?.id ?? '',
        reporter_name: row?.reporter_name ?? '',
        reporter_phone: row?.reporter_phone ?? '',
        koperasi_id: row?.koperasi_id ?? row?.koperasi?.id ?? '',
        province_id: row?.province_id ?? row?.province?.id ?? '',
        city_id: row?.city_id ?? row?.city?.id ?? '',
        category_id: row?.category_id ?? row?.category?.id ?? '',
        sub_category_id: row?.sub_category_id ?? row?.sub_category?.id ?? '',
        priority: row?.priority ?? 'normal',
        title: row?.title ?? '',
        detail: row?.detail ?? '',
        pic_helpdesk_id: row?.pic_helpdesk_id ?? row?.pic_helpdesk?.id ?? '',
        status: row?.status ?? 'baru',
        assigned_at: row?.assigned_at ?? '',
        completed_at: row?.completed_at ?? '',
        sla_days: row?.sla_days ?? '',
        progress_percentage: row?.progress_percentage ?? 0,
        resolution: row?.resolution ?? '',
        attachment_link: row?.attachment_link ?? '',
        last_update_at: row?.last_update_at ?? '',
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        const url = isEdit
            ? `/admin/pengaduans/${row?.id}`
            : '/admin/pengaduans';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <div className="grid gap-3 md:grid-cols-2">
                <Field
                    label="Ticket ID"
                    value={form.data.ticket_id}
                    onChange={(value) => form.setData('ticket_id', value)}
                    error={form.errors.ticket_id}
                />
                <Field
                    label="Tanggal tiket"
                    type="datetime-local"
                    value={form.data.ticket_date}
                    onChange={(value) => form.setData('ticket_date', value)}
                    error={form.errors.ticket_date}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                <SelectField
                    label="User pelapor"
                    value={form.data.reporter_user_id}
                    options={options.users}
                    onChange={(value) =>
                        form.setData('reporter_user_id', value)
                    }
                    error={form.errors.reporter_user_id}
                />
                <Field
                    label="Nama pelapor"
                    value={form.data.reporter_name}
                    onChange={(value) => form.setData('reporter_name', value)}
                    error={form.errors.reporter_name}
                />
                <Field
                    label="No HP"
                    value={form.data.reporter_phone}
                    onChange={(value) => form.setData('reporter_phone', value)}
                    error={form.errors.reporter_phone}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                <SelectField
                    label="Koperasi"
                    value={form.data.koperasi_id}
                    options={options.koperasis}
                    onChange={(value) => form.setData('koperasi_id', value)}
                    error={form.errors.koperasi_id}
                />
                <SelectField
                    label="Provinsi"
                    value={form.data.province_id}
                    options={options.provinces ?? []}
                    onChange={(value) => form.setData('province_id', value)}
                    error={form.errors.province_id}
                />
                <SelectField
                    label="Kota/Kabupaten"
                    value={form.data.city_id}
                    options={options.cities}
                    onChange={(value) => form.setData('city_id', value)}
                    error={form.errors.city_id}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-4">
                <SelectField
                    label="Kategori"
                    value={form.data.category_id}
                    options={options.categories ?? []}
                    onChange={(value) => form.setData('category_id', value)}
                    error={form.errors.category_id}
                />
                <SelectField
                    label="Sub kategori"
                    value={form.data.sub_category_id}
                    options={options.sub_categories ?? []}
                    onChange={(value) => form.setData('sub_category_id', value)}
                    error={form.errors.sub_category_id}
                />
                <SelectField
                    label="Prioritas"
                    value={form.data.priority}
                    options={options.priorities ?? []}
                    onChange={(value) => form.setData('priority', value)}
                    error={form.errors.priority}
                />
                <SelectField
                    label="Status"
                    value={form.data.status}
                    options={options.complaint_statuses ?? []}
                    onChange={(value) => form.setData('status', value)}
                    error={form.errors.status}
                />
            </div>
            <Field
                label="Judul kendala"
                value={form.data.title}
                onChange={(value) => form.setData('title', value)}
                error={form.errors.title}
            />
            <Field
                label="Detail kendala"
                value={form.data.detail}
                onChange={(value) => form.setData('detail', value)}
                error={form.errors.detail}
            />
            <div className="grid gap-3 md:grid-cols-4">
                <SelectField
                    label="PIC Helpdesk"
                    value={form.data.pic_helpdesk_id}
                    options={options.users}
                    onChange={(value) => form.setData('pic_helpdesk_id', value)}
                    error={form.errors.pic_helpdesk_id}
                />
                <Field
                    label="Tanggal assign"
                    type="datetime-local"
                    value={form.data.assigned_at}
                    onChange={(value) => form.setData('assigned_at', value)}
                    error={form.errors.assigned_at}
                />
                <Field
                    label="Tanggal selesai"
                    type="datetime-local"
                    value={form.data.completed_at}
                    onChange={(value) => form.setData('completed_at', value)}
                    error={form.errors.completed_at}
                />
                <Field
                    label="SLA (Hari)"
                    type="number"
                    value={form.data.sla_days}
                    onChange={(value) => form.setData('sla_days', value)}
                    error={form.errors.sla_days}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <Field
                    label="Progress %"
                    type="number"
                    value={form.data.progress_percentage}
                    onChange={(value) =>
                        form.setData('progress_percentage', value)
                    }
                    error={form.errors.progress_percentage}
                />
                <Field
                    label="Attachment Link"
                    type="url"
                    value={form.data.attachment_link}
                    onChange={(value) => form.setData('attachment_link', value)}
                    error={form.errors.attachment_link}
                />
            </div>
            <Field
                label="Solusi / Tindak Lanjut"
                value={form.data.resolution}
                onChange={(value) => form.setData('resolution', value)}
                error={form.errors.resolution}
            />
            <Field
                label="Last Update"
                type="datetime-local"
                value={form.data.last_update_at}
                onChange={(value) => form.setData('last_update_at', value)}
                error={form.errors.last_update_at}
            />
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function SarprasForm({
    row,
    onDone,
}: {
    row?: Row | null;
    onDone: () => void;
}) {
    const form = useForm({
        name: row?.name ?? '',
        slug: row?.slug ?? '',
        description: row?.description ?? '',
        is_mandatory:
            row?.is_mandatory === true ||
            row?.is_mandatory === 1 ||
            row?.is_mandatory === '1',
        mandatory_group: row?.mandatory_group ?? '',
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            is_mandatory: Boolean(data.is_mandatory),
            mandatory_group: data.mandatory_group || null,
        }));

        const url = isEdit ? `/admin/sarprases/${row?.id}` : '/admin/sarprases';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <Field
                label="Nama sarpras"
                value={form.data.name}
                onChange={(value) => form.setData('name', value)}
                error={form.errors.name}
            />
            <Field
                label="Slug"
                value={form.data.slug}
                onChange={(value) => form.setData('slug', value)}
                error={form.errors.slug}
            />
            <Field
                label="Deskripsi"
                value={form.data.description}
                onChange={(value) => form.setData('description', value)}
                error={form.errors.description}
            />
            <Field
                label="Grup alternatif mandatory"
                value={form.data.mandatory_group}
                onChange={(value) => form.setData('mandatory_group', value)}
                error={form.errors.mandatory_group}
            />
            <label className="flex items-center gap-2 text-sm font-medium">
                <Checkbox
                    checked={Boolean(form.data.is_mandatory)}
                    onCheckedChange={(checked) =>
                        form.setData('is_mandatory', checked === true)
                    }
                />
                Wajib untuk operasional retail
            </label>
            {form.errors.is_mandatory && (
                <span className="text-xs text-red-600">
                    {form.errors.is_mandatory}
                </span>
            )}
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function UserForm({ row, onDone }: { row?: Row | null; onDone: () => void }) {
    const form = useForm({
        name: row?.name ?? '',
        phone: row?.phone ?? '',
        email: row?.email ?? '',
        password: '',
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit ? `/admin/users/${row?.id}` : '/admin/users';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <Field
                label="Nama"
                value={form.data.name}
                onChange={(value) => form.setData('name', value)}
                error={form.errors.name}
            />
            <Field
                label="Nomor handphone"
                type="tel"
                value={form.data.phone}
                onChange={(value) => form.setData('phone', value)}
                error={form.errors.phone}
            />
            <Field
                label="Email"
                type="email"
                value={form.data.email}
                onChange={(value) => form.setData('email', value)}
                error={form.errors.email}
            />
            <Field
                label={isEdit ? 'Password baru' : 'Password'}
                type="password"
                value={form.data.password}
                onChange={(value) => form.setData('password', value)}
                error={form.errors.password}
            />
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function KoperasiSarprasForm({
    row,
    options,
    onDone,
}: {
    row?: Row | null;
    options: AdminOptions;
    onDone: () => void;
}) {
    const form = useForm({
        koperasi_id: row?.koperasi_id ?? row?.koperasi?.id ?? '',
        sarpras_id: row?.sarpras_id ?? row?.sarpras?.id ?? '',
        status_id: row?.status_id ?? row?.status?.id ?? '',
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit
            ? `/admin/koperasi-sarprases/${row?.id}`
            : '/admin/koperasi-sarprases';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <SelectField
                label="Koperasi"
                value={form.data.koperasi_id}
                options={options.koperasis}
                onChange={(value) => form.setData('koperasi_id', value)}
                error={form.errors.koperasi_id}
            />
            <SelectField
                label="Sarpras"
                value={form.data.sarpras_id}
                options={options.sarprases}
                onChange={(value) => form.setData('sarpras_id', value)}
                error={form.errors.sarpras_id}
            />
            <SelectField
                label="Status"
                value={form.data.status_id}
                options={options.statuses}
                onChange={(value) => form.setData('status_id', value)}
                error={form.errors.status_id}
            />
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function KoperasiForm({
    row,
    options,
    onDone,
}: {
    row?: Row | null;
    options: AdminOptions;
    onDone: () => void;
}) {
    const form = useForm({
        ai_id: row?.ai_id ?? '',
        name: row?.name ?? '',
        village_id: row?.village_id ?? '',
        district_id: row?.district_id ?? '',
        city_id: row?.city_id ?? '',
        province_id: row?.province_id ?? '',
        user_id: row?.user_id ?? row?.user?.id ?? '',
        latitude: row?.latitude ?? '',
        longitude: row?.longitude ?? '',
        delivery_percentage: row?.delivery_percentage ?? '',
        installed_percentage: row?.installed_percentage ?? '',
        core_percentage: row?.core_percentage ?? '',
    });
    const isEdit = Boolean(row?.id);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const url = isEdit ? `/admin/koperasis/${row?.id}` : '/admin/koperasis';
        const method = isEdit ? form.put : form.post;

        method(url, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: onDone,
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-3">
            <Field
                label="AI ID"
                value={form.data.ai_id}
                onChange={(value) => form.setData('ai_id', value)}
                error={form.errors.ai_id}
            />
            <Field
                label="Nama koperasi"
                value={form.data.name}
                onChange={(value) => form.setData('name', value)}
                error={form.errors.name}
            />
            <div className="grid gap-3 md:grid-cols-3">
                <SelectField
                    label="Desa"
                    value={form.data.village_id}
                    options={options.villages}
                    onChange={(value) => form.setData('village_id', value)}
                    error={form.errors.village_id}
                />
                <SelectField
                    label="Kecamatan"
                    value={form.data.district_id}
                    options={options.districts}
                    onChange={(value) => form.setData('district_id', value)}
                    error={form.errors.district_id}
                />
                <SelectField
                    label="Kota"
                    value={form.data.city_id}
                    options={options.cities}
                    onChange={(value) => form.setData('city_id', value)}
                    error={form.errors.city_id}
                />
            </div>
            <SelectField
                label="PIC koperasi"
                value={form.data.user_id}
                options={options.users}
                onChange={(value) => form.setData('user_id', value)}
                error={form.errors.user_id}
            />
            <div className="grid gap-3 md:grid-cols-2">
                <Field
                    label="Latitude"
                    value={form.data.latitude}
                    onChange={(value) => form.setData('latitude', value)}
                    error={form.errors.latitude}
                />
                <Field
                    label="Longitude"
                    value={form.data.longitude}
                    onChange={(value) => form.setData('longitude', value)}
                    error={form.errors.longitude}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-3">
                <Field
                    label="Pengiriman"
                    value={form.data.delivery_percentage}
                    onChange={(value) =>
                        form.setData('delivery_percentage', value)
                    }
                    error={form.errors.delivery_percentage}
                />
                <Field
                    label="Terpasang"
                    value={form.data.installed_percentage}
                    onChange={(value) =>
                        form.setData('installed_percentage', value)
                    }
                    error={form.errors.installed_percentage}
                />
                <Field
                    label="Inti"
                    value={form.data.core_percentage}
                    onChange={(value) => form.setData('core_percentage', value)}
                    error={form.errors.core_percentage}
                />
            </div>
            <Button disabled={form.processing} className="mt-2">
                {isEdit ? 'Simpan perubahan' : 'Tambah data'}
            </Button>
        </form>
    );
}

function Field({
    label,
    value,
    onChange,
    error,
    type = 'text',
}: {
    label: string;
    value: any;
    onChange: (value: any) => void;
    error?: string;
    type?: string;
}) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium">{label}</span>
            <Input
                type={type}
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
            />
            {error && <span className="text-xs text-red-600">{error}</span>}
        </label>
    );
}

function SelectField({
    label,
    value,
    options,
    onChange,
    error,
}: {
    label: string;
    value: any;
    options: Option[];
    onChange: (value: any) => void;
    error?: string;
}) {
    return (
        <label className="grid gap-1 text-sm">
            <span className="font-medium">{label}</span>
            <select
                value={value ?? ''}
                onChange={(event) => onChange(event.target.value)}
                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
            >
                <option value="">Pilih</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {option.phone
                            ? `${option.name} (${option.phone})`
                            : option.name}
                    </option>
                ))}
            </select>
            {error && <span className="text-xs text-red-600">{error}</span>}
        </label>
    );
}

function DetailRow({ label, value }: { label: string; value: unknown }) {
    return (
        <div className="grid gap-1">
            <div className="text-xs font-medium text-muted-foreground uppercase">
                {label}
            </div>
            <div className="text-sm break-words">{text(value)}</div>
        </div>
    );
}

function PengaduanDetailDialog({
    row,
    open,
    onOpenChange,
}: {
    row: Row | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    if (!row) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Detail Pengaduan {row.ticket_id}</DialogTitle>
                    <DialogDescription>
                        Informasi lengkap tiket pengaduan.
                    </DialogDescription>
                </DialogHeader>
                <div className="grid max-h-[70vh] gap-4 overflow-y-auto pr-2">
                    <div className="grid gap-4 rounded-md border p-4 md:grid-cols-3">
                        <DetailRow label="Ticket ID" value={row.ticket_id} />
                        <DetailRow
                            label="Tanggal Tiket"
                            value={row.ticket_date}
                        />
                        <DetailRow label="Status" value={row.status} />
                        <DetailRow
                            label="Nama Pelapor"
                            value={row.reporter_name}
                        />
                        <DetailRow label="No HP" value={row.reporter_phone} />
                        <DetailRow label="Nama Koperasi" value={row.koperasi} />
                        <DetailRow label="Provinsi" value={row.province} />
                        <DetailRow label="Kota/Kabupaten" value={row.city} />
                        <DetailRow label="Prioritas" value={row.priority} />
                        <DetailRow label="Kategori" value={row.category} />
                        <DetailRow
                            label="Sub Kategori"
                            value={row.sub_category}
                        />
                        <DetailRow
                            label="PIC Helpdesk"
                            value={row.pic_helpdesk}
                        />
                    </div>
                    <div className="grid gap-4 rounded-md border p-4">
                        <DetailRow label="Judul Kendala" value={row.title} />
                        <DetailRow label="Detail Kendala" value={row.detail} />
                        <DetailRow
                            label="Solusi / Tindak Lanjut"
                            value={row.resolution}
                        />
                    </div>
                    <div className="grid gap-4 rounded-md border p-4 md:grid-cols-4">
                        <DetailRow
                            label="Tanggal Assign"
                            value={row.assigned_at}
                        />
                        <DetailRow
                            label="Tanggal Selesai"
                            value={row.completed_at}
                        />
                        <DetailRow label="SLA (Hari)" value={row.sla_days} />
                        <DetailRow
                            label="Progress %"
                            value={row.progress_percentage}
                        />
                        <DetailRow
                            label="Attachment Link"
                            value={row.attachment_link}
                        />
                        <DetailRow
                            label="Last Update"
                            value={row.last_update_at}
                        />
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function CategoryAssignmentDialog({
    row,
    options,
    open,
    onOpenChange,
}: {
    row: Row | null;
    options: AdminOptions;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const form = useForm({
        reporter_name: row?.reporter_name ?? '',
        reporter_phone: row?.reporter_phone ?? '',
        priority: row?.priority ?? 'normal',
        title: row?.title ?? '',
        detail: row?.detail ?? '',
        status: row?.status ?? 'baru',
        progress_percentage: row?.progress_percentage ?? 0,
        category_id: row?.category_id ?? row?.category?.id ?? '',
        sub_category_id: row?.sub_category_id ?? row?.sub_category?.id ?? '',
    });

    if (!row) {
        return null;
    }

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(`/admin/pengaduans/${row.id}`, {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Atur Kategori</DialogTitle>
                    <DialogDescription>
                        Pilih kategori untuk tiket {row.ticket_id}.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    <SelectField
                        label="Kategori"
                        value={form.data.category_id}
                        options={options.categories ?? []}
                        onChange={(value) => form.setData('category_id', value)}
                        error={form.errors.category_id}
                    />
                    <SelectField
                        label="Sub kategori"
                        value={form.data.sub_category_id}
                        options={options.sub_categories ?? []}
                        onChange={(value) =>
                            form.setData('sub_category_id', value)
                        }
                        error={form.errors.sub_category_id}
                    />
                    <Button disabled={form.processing} className="mt-2">
                        Simpan Kategori
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function routeSegment(tab: Tab) {
    if (tab === 'koperasiSarprases') {
        return 'koperasi-sarprases';
    }

    if (tab === 'pengaduanCategories') {
        return 'pengaduan-categories';
    }

    return tab;
}

function ImportButton({ tab }: { tab: Tab }) {
    const [file, setFile] = useState<File | null>(null);
    const [open, setOpen] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!file) {
            return;
        }

        router.post(
            `/admin/${routeSegment(tab)}/import`,
            { file },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            },
        );
    };

    if (
        tab === 'users' ||
        tab === 'pengaduanCategories' ||
        tab === 'pengaduans'
    ) {
        return null;
    }

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <FileUp /> Import
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Import {tabs.find((item) => item.key === tab)?.label}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-4">
                    <Input
                        type="file"
                        accept=".csv,.xlsx"
                        onChange={(event) =>
                            setFile(event.target.files?.[0] ?? null)
                        }
                    />
                    <p className="text-sm text-muted-foreground">
                        Gunakan format CSV atau XLSX. Datasheet contoh di
                        database/data/2026_1061.xlsx sudah didukung.
                    </p>
                    <Button type="submit">Upload</Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function columns(tab: Tab) {
    if (tab === 'users') {
        return ['name', 'phone', 'email', 'created_at'];
    }

    if (tab === 'sarprases') {
        return [
            'name',
            'slug',
            'description',
            'is_mandatory',
            'mandatory_group',
        ];
    }

    if (tab === 'koperasiSarprases') {
        return ['koperasi', 'sarpras', 'status'];
    }

    if (tab === 'pengaduanCategories') {
        return [
            'parent',
            'name',
            'slug',
            'description',
            'is_active',
            'created_at',
        ];
    }

    if (tab === 'pengaduans') {
        return [
            'ticket_id',
            'ticket_date',
            'reporter_name',
            'reporter_phone',
            'koperasi',
            'province',
            'city',
            'category',
            'sub_category',
        ];
    }

    return [
        'ai_id',
        'name',
        'user',
        'village',
        'district',
        'city',
        'latitude',
        'longitude',
    ];
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
    options: AdminOptions;
}) {
    const tab = resource;
    const [editing, setEditing] = useState<Row | null>(null);
    const [detail, setDetail] = useState<Row | null>(null);
    const [categoryAssignment, setCategoryAssignment] = useState<Row | null>(
        null,
    );
    const [open, setOpen] = useState(false);
    const { flash } = usePage().props;

    const submitSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        router.get(
            `/admin/${routeSegment(tab)}`,
            { search: form.get('search') },
            { preserveState: true },
        );
    };

    const remove = (row: Row) => {
        router.delete(`/admin/${routeSegment(tab)}/${row.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={title} />
            <div className="flex flex-col gap-5 p-4 md:p-6">
                {flash.success && (
                    <Alert className="border-emerald-200 bg-emerald-50 text-emerald-900">
                        <AlertTitle>Berhasil</AlertTitle>
                        <AlertDescription className="text-emerald-800">
                            {flash.success}
                        </AlertDescription>
                    </Alert>
                )}
                {flash.error && (
                    <Alert variant="destructive">
                        <AlertTitle>Gagal</AlertTitle>
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}
                <div className="grid gap-3 md:grid-cols-4">
                    {Object.entries(stats).map(([key, value]) => (
                        <div
                            key={key}
                            className="rounded-lg border bg-card p-4 shadow-xs"
                        >
                            <div className="text-2xl font-semibold">
                                {value}
                            </div>
                            <div className="text-sm text-muted-foreground capitalize">
                                {key.replaceAll('_', ' ')}
                            </div>
                        </div>
                    ))}
                </div>
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal">
                            {title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Kelola data {title.toLowerCase()}.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <form
                            onSubmit={submitSearch}
                            className="relative min-w-0 flex-1 md:flex-none"
                        >
                            <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                            <Input
                                name="search"
                                defaultValue={search}
                                placeholder="Cari data"
                                className="w-full pl-9 md:w-64"
                            />
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
                                <Button onClick={() => setEditing(null)}>
                                    <Plus /> Tambah
                                </Button>
                            </DialogTrigger>
                            <DialogContent className="sm:max-w-3xl">
                                <DialogHeader>
                                    <DialogTitle>
                                        {editing ? 'Edit' : 'Tambah'}{' '}
                                        {
                                            tabs.find(
                                                (item) => item.key === tab,
                                            )?.label
                                        }
                                    </DialogTitle>
                                </DialogHeader>
                                <ResourceForm
                                    key={`${tab}-${editing?.id ?? 'new'}`}
                                    tab={tab}
                                    row={editing}
                                    options={options}
                                    onDone={() => setOpen(false)}
                                />
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
                <div className="overflow-hidden rounded-lg border bg-card">
                    <div className="w-full overflow-x-auto">
                        <table className="w-full min-w-[920px] text-left text-sm">
                            <thead className="bg-muted text-xs text-muted-foreground uppercase">
                                <tr>
                                    {columns(tab).map((column) => (
                                        <th
                                            key={column}
                                            className="p-3 whitespace-nowrap"
                                        >
                                            {column.replaceAll('_', ' ')}
                                        </th>
                                    ))}
                                    <th className="sticky right-0 w-56 bg-muted p-3">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {records.data.map((row) => (
                                    <tr key={row.id} className="border-t">
                                        {columns(tab).map((column) => (
                                            <td
                                                key={column}
                                                className="max-w-64 truncate p-3"
                                            >
                                                {text(row[column])}
                                            </td>
                                        ))}
                                        <td className="sticky right-0 bg-card p-3 shadow-[-8px_0_12px_-12px_rgba(15,23,42,0.8)]">
                                            <div className="flex flex-wrap gap-2">
                                                {tab === 'pengaduans' && (
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        onClick={() =>
                                                            setDetail(row)
                                                        }
                                                    >
                                                        Detail
                                                    </Button>
                                                )}
                                                {tab === 'pengaduans' &&
                                                    !row.category_id &&
                                                    !row.category && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                setCategoryAssignment(
                                                                    row,
                                                                )
                                                            }
                                                        >
                                                            Atur Kategori
                                                        </Button>
                                                    )}
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => {
                                                        setEditing(row);
                                                        setOpen(true);
                                                    }}
                                                >
                                                    Edit
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    onClick={() => remove(row)}
                                                >
                                                    <Trash2 />
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
                <PengaduanDetailDialog
                    row={detail}
                    open={Boolean(detail)}
                    onOpenChange={(isOpen) => !isOpen && setDetail(null)}
                />
                <CategoryAssignmentDialog
                    row={categoryAssignment}
                    options={options}
                    open={Boolean(categoryAssignment)}
                    onOpenChange={(isOpen) =>
                        !isOpen && setCategoryAssignment(null)
                    }
                />
                <div className="flex flex-wrap gap-2">
                    {records.links.map((link, index) => (
                        <Button
                            key={`${link.label}-${index}`}
                            asChild={Boolean(link.url)}
                            disabled={!link.url}
                            variant={link.active ? 'default' : 'outline'}
                            size="sm"
                        >
                            {link.url ? (
                                <Link
                                    href={link.url}
                                    preserveScroll
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ) : (
                                <span
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            )}
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
