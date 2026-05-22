import { Head, Link, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock3,
    FileText,
    LogOut,
    Plus,
    UserRound,
} from 'lucide-react';
import { logout } from '@/routes';
import { storePengaduan } from '@/actions/App/Http/Controllers/HelpdeskController';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { FormEvent } from 'react';

type Report = {
    id: string;
    title: string;
    detail: string;
    koperasi: string;
    status: string;
    date: string;
    attachment_url?: string | null;
};

type Props = {
    stats: {
        open: number;
        process: number;
        done: number;
    };
    reports: Report[];
    user: {
        name: string;
        phone: string;
    };
};

function statusClass(status: string) {
    if (status === 'Selesai') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800';
    }

    if (status === 'Diproses') {
        return 'border-blue-200 bg-blue-50 text-blue-800';
    }

    return 'border-amber-200 bg-amber-50 text-amber-800';
}

export default function HelpdeskDashboard({ stats, reports, user }: Props) {
    const form = useForm<{
        title: string;
        detail: string;
        attachment: File | null;
    }>({
        title: '',
        detail: '',
        attachment: null,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(storePengaduan.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title="Dashboard Helpdesk" />
            <main className="min-h-screen bg-white text-zinc-950">
                <div className="mx-auto flex w-full max-w-5xl flex-col gap-5 px-4 py-5 sm:px-6 md:py-8">
                    <header className="grid gap-4 rounded-lg border bg-white p-5 shadow-xs md:grid-cols-[1fr_auto] md:items-center md:p-6">
                        <div className="flex items-start gap-3">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                <UserRound className="size-6" />
                            </div>
                            <div className="grid gap-1">
                                <p className="text-sm font-medium text-emerald-700">
                                    Dashboard Helpdesk
                                </p>
                                <h1 className="text-2xl font-semibold tracking-normal md:text-3xl">
                                    Halo, {user.name}
                                </h1>
                                <p className="text-base text-zinc-600">
                                    Pantau laporan koperasi dan tindak lanjut
                                    petugas di sini.
                                </p>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                asChild
                                className="h-11 bg-emerald-600 hover:bg-emerald-700"
                            >
                                <Link href="/helpdesk">
                                    <Plus />
                                    Buat Laporan
                                </Link>
                            </Button>
                            <Button asChild variant="outline" className="h-11">
                                <Link href={logout()} method="post" as="button">
                                    <LogOut />
                                    Keluar
                                </Link>
                            </Button>
                        </div>
                    </header>

                    <section className="grid gap-3 sm:grid-cols-3">
                        <div className="rounded-lg border bg-white p-4 shadow-xs">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-base font-medium">
                                    Laporan Baru
                                </span>
                                <FileText className="size-5 text-amber-600" />
                            </div>
                            <div className="mt-3 text-3xl font-semibold">
                                {stats.open}
                            </div>
                        </div>
                        <div className="rounded-lg border bg-white p-4 shadow-xs">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-base font-medium">
                                    Sedang Diproses
                                </span>
                                <Clock3 className="size-5 text-blue-600" />
                            </div>
                            <div className="mt-3 text-3xl font-semibold">
                                {stats.process}
                            </div>
                        </div>
                        <div className="rounded-lg border bg-white p-4 shadow-xs">
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-base font-medium">
                                    Selesai
                                </span>
                                <CheckCircle2 className="size-5 text-emerald-600" />
                            </div>
                            <div className="mt-3 text-3xl font-semibold">
                                {stats.done}
                            </div>
                        </div>
                    </section>

                    <section className="rounded-lg border bg-white shadow-xs">
                        <div className="border-b p-5">
                            <h2 className="text-xl font-semibold">
                                Buat Pengaduan
                            </h2>
                            <p className="mt-1 text-base text-zinc-600">
                                Isi judul, detail kendala, dan foto jika ada.
                            </p>
                        </div>
                        <form onSubmit={submit} className="grid gap-4 p-5">
                            <div className="grid gap-2">
                                <Label htmlFor="title" className="text-base">
                                    Judul Kendala
                                </Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(event) =>
                                        form.setData(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Contoh: CCTV belum menyala"
                                    className="h-12 text-base"
                                />
                                <InputError message={form.errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="detail" className="text-base">
                                    Detail Kendala
                                </Label>
                                <textarea
                                    id="detail"
                                    value={form.data.detail}
                                    onChange={(event) =>
                                        form.setData(
                                            'detail',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Ceritakan kendala dengan bahasa sederhana."
                                    className="min-h-32 rounded-md border border-input bg-background px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                                />
                                <InputError message={form.errors.detail} />
                            </div>
                            <div className="grid gap-2">
                                <Label
                                    htmlFor="attachment"
                                    className="text-base"
                                >
                                    Foto Kendala
                                </Label>
                                <Input
                                    id="attachment"
                                    type="file"
                                    accept="image/*"
                                    onChange={(event) =>
                                        form.setData(
                                            'attachment',
                                            event.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="h-12 text-base"
                                />
                                <InputError message={form.errors.attachment} />
                            </div>
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="h-12 bg-emerald-600 text-base font-semibold hover:bg-emerald-700"
                            >
                                {form.processing && <Spinner />}
                                Kirim Pengaduan
                            </Button>
                        </form>
                    </section>

                    <section className="rounded-lg border bg-white shadow-xs">
                        <div className="border-b p-5">
                            <h2 className="text-xl font-semibold">
                                Daftar Laporan
                            </h2>
                            <p className="mt-1 text-base text-zinc-600">
                                Data masih contoh sementara sampai fitur laporan
                                aktif.
                            </p>
                        </div>
                        <div className="divide-y">
                            {reports.map((report) => (
                                <article
                                    key={report.id}
                                    className="grid gap-3 p-5 md:grid-cols-[1fr_auto] md:items-center"
                                >
                                    <div className="grid gap-1">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-sm font-medium text-zinc-500">
                                                {report.id}
                                            </span>
                                            <span
                                                className={`rounded-full border px-2.5 py-1 text-sm font-medium ${statusClass(report.status)}`}
                                            >
                                                {report.status}
                                            </span>
                                        </div>
                                        <h3 className="text-lg font-semibold">
                                            {report.title}
                                        </h3>
                                        <p className="text-base text-zinc-600">
                                            {report.koperasi}
                                        </p>
                                        <p className="text-base text-zinc-700">
                                            {report.detail}
                                        </p>
                                        {report.attachment_url && (
                                            <a
                                                href={report.attachment_url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-base font-medium text-emerald-700 underline"
                                            >
                                                Lihat foto
                                            </a>
                                        )}
                                    </div>
                                    <div className="text-base text-zinc-500">
                                        {report.date}
                                    </div>
                                </article>
                            ))}
                        </div>
                    </section>
                </div>
            </main>
        </>
    );
}
