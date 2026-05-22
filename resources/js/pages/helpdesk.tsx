import { Head, useForm } from '@inertiajs/react';
import {
    Building2,
    ClipboardList,
    LogIn,
    Phone,
    Search,
    UserPlus,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { FormEvent } from 'react';
import {
    cities as cityOptions,
    districts as districtOptions,
    koperasis as koperasiOptions,
    login,
    store,
    villages as villageOptions,
} from '@/actions/App/Http/Controllers/HelpdeskController';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Option = {
    id: number;
    name: string;
    user_id?: string | null;
};

type KoperasiOption = Option & {
    user_id: string | null;
};

type HelpdeskForm = {
    province_id: string;
    city_id: string;
    district_id: string;
    village_id: string;
    koperasi_id: string;
    name: string;
    phone: string;
};

type LoginForm = {
    phone: string;
    password: string;
    remember: boolean;
};

type RegionFilters = {
    province_id?: string;
    city_id?: string;
    district_id?: string;
    village_id?: string;
};

const dummyActions = [
    {
        label: 'Buat Pengaduan',
        description: 'Lapor kendala koperasi',
        icon: ClipboardList,
    },
    {
        label: 'Cek Laporan',
        description: 'Lihat status pengaduan',
        icon: Search,
    },
    {
        label: 'Hubungi Petugas',
        description: 'Minta bantuan langsung',
        icon: Phone,
    },
];

const tabs = [
    { key: 'register', label: 'Daftar Akun', icon: UserPlus },
    { key: 'login', label: 'Sudah Punya Akun', icon: LogIn },
] as const;

type ActiveTab = (typeof tabs)[number]['key'];

async function loadOptions<T>(url: string): Promise<T[]> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        return [];
    }

    return response.json();
}

function NativeSelect({
    label,
    value,
    options,
    disabled,
    placeholder = 'Pilih',
    error,
    onChange,
}: {
    label: string;
    value: string;
    options: Option[];
    disabled?: boolean;
    placeholder?: string;
    error?: string;
    onChange: (value: string) => void;
}) {
    const optionLabel = (option: Option) =>
        option.user_id ? `${option.name} (sudah ada akun)` : option.name;

    return (
        <div className="grid gap-2">
            <Label className="text-base">{label}</Label>
            <select
                value={value}
                disabled={disabled}
                onChange={(event) => onChange(event.target.value)}
                className="h-12 w-full rounded-md border border-input bg-background px-3 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <option value="">{placeholder}</option>
                {options.map((option) => (
                    <option key={option.id} value={option.id}>
                        {optionLabel(option)}
                    </option>
                ))}
            </select>
            <InputError message={error} />
        </div>
    );
}

export default function Helpdesk({
    provinces,
    success,
}: {
    provinces: Option[];
    success?: string;
}) {
    const form = useForm<HelpdeskForm>({
        province_id: '',
        city_id: '',
        district_id: '',
        village_id: '',
        koperasi_id: '',
        name: '',
        phone: '',
    });
    const loginForm = useForm<LoginForm>({
        phone: '',
        password: '',
        remember: true,
    });

    const [activeTab, setActiveTab] = useState<ActiveTab>('register');
    const [cities, setCities] = useState<Option[]>([]);
    const [districts, setDistricts] = useState<Option[]>([]);
    const [villages, setVillages] = useState<Option[]>([]);
    const [koperasis, setKoperasis] = useState<KoperasiOption[]>([]);
    const [loadingKoperasis, setLoadingKoperasis] = useState(false);

    const selectedKoperasi = useMemo(
        () =>
            koperasis.find(
                (koperasi) => String(koperasi.id) === form.data.koperasi_id,
            ),
        [form.data.koperasi_id, koperasis],
    );

    const fetchKoperasis = (filters: RegionFilters) => {
        if (!filters.province_id) {
            setKoperasis([]);
            return;
        }

        setLoadingKoperasis(true);
        loadOptions<KoperasiOption>(
            koperasiOptions.url({
                query: {
                    province_id: filters.province_id,
                    city_id: filters.city_id || undefined,
                    district_id: filters.district_id || undefined,
                    village_id: filters.village_id || undefined,
                },
            }),
        )
            .then(setKoperasis)
            .finally(() => setLoadingKoperasis(false));
    };

    const setProvince = (value: string) => {
        form.setData({
            ...form.data,
            province_id: value,
            city_id: '',
            district_id: '',
            village_id: '',
            koperasi_id: '',
        });
        setCities([]);
        setDistricts([]);
        setVillages([]);
        setKoperasis([]);

        if (!value) {
            return;
        }

        loadOptions<Option>(
            cityOptions.url({
                query: { province_id: value },
            }),
        ).then(setCities);
        fetchKoperasis({ province_id: value });
    };

    const setCity = (value: string) => {
        form.setData({
            ...form.data,
            city_id: value,
            district_id: '',
            village_id: '',
            koperasi_id: '',
        });
        setDistricts([]);
        setVillages([]);
        setKoperasis([]);

        if (!value) {
            fetchKoperasis({ province_id: form.data.province_id });
            return;
        }

        loadOptions<Option>(
            districtOptions.url({
                query: { city_id: value },
            }),
        ).then(setDistricts);
        fetchKoperasis({
            province_id: form.data.province_id,
            city_id: value,
        });
    };

    const setDistrict = (value: string) => {
        form.setData({
            ...form.data,
            district_id: value,
            village_id: '',
            koperasi_id: '',
        });
        setVillages([]);
        setKoperasis([]);

        if (!value) {
            fetchKoperasis({
                province_id: form.data.province_id,
                city_id: form.data.city_id,
            });
            return;
        }

        loadOptions<Option>(
            villageOptions.url({
                query: { district_id: value },
            }),
        ).then(setVillages);
        fetchKoperasis({
            province_id: form.data.province_id,
            city_id: form.data.city_id,
            district_id: value,
        });
    };

    const setVillage = (value: string) => {
        form.setData({
            ...form.data,
            village_id: value,
            koperasi_id: '',
        });
        setKoperasis([]);
        fetchKoperasis({
            province_id: form.data.province_id,
            city_id: form.data.city_id,
            district_id: form.data.district_id,
            village_id: value,
        });
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const submitLogin = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        loginForm.post(login.url(), {
            preserveScroll: true,
            onSuccess: () => loginForm.reset('password'),
        });
    };

    return (
        <>
            <Head title="Helpdesk Koperasi" />
            <main className="min-h-screen bg-white text-zinc-950">
                <div className="mx-auto flex w-full max-w-4xl flex-col gap-5 px-4 py-5 sm:px-6 md:py-8">
                    <section className="grid gap-4 rounded-lg border bg-white p-5 shadow-xs md:p-6">
                        <div className="flex items-start gap-3">
                            <div className="flex size-12 shrink-0 items-center justify-center rounded-lg bg-emerald-600 text-white">
                                <UserPlus className="size-6" />
                            </div>
                            <div className="grid gap-1">
                                <p className="text-sm font-medium text-emerald-700">
                                    Layanan Bantuan
                                </p>
                                <h1 className="text-2xl font-semibold tracking-normal md:text-3xl">
                                    Helpdesk Koperasi Desa
                                </h1>
                                <p className="text-base leading-7 text-zinc-600">
                                    Pilih layanan, pilih wilayah koperasi, lalu
                                    isi nama dan nomor handphone. Petugas akan
                                    memakai data ini untuk membantu koperasi.
                                </p>
                            </div>
                        </div>
                    </section>

                    <section className="grid gap-3">
                        <h2 className="text-lg font-semibold">Pilih layanan</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <button
                                type="button"
                                className="flex min-h-28 flex-col items-start justify-between rounded-lg border-2 border-emerald-600 bg-emerald-50 p-4 text-left"
                            >
                                <Building2 className="size-6 text-emerald-700" />
                                <span className="grid gap-1">
                                    <span className="text-base font-semibold">
                                        Daftar PIC
                                    </span>
                                    <span className="text-sm text-zinc-600">
                                        Isi data penanggung jawab koperasi
                                    </span>
                                </span>
                            </button>
                            {dummyActions.map((action) => {
                                const Icon = action.icon;

                                return (
                                    <button
                                        key={action.label}
                                        type="button"
                                        disabled
                                        className="flex min-h-28 flex-col items-start justify-between rounded-lg border bg-zinc-50 p-4 text-left opacity-70"
                                    >
                                        <Icon className="size-6 text-zinc-500" />
                                        <span className="grid gap-1">
                                            <span className="text-base font-semibold">
                                                {action.label}
                                            </span>
                                            <span className="text-sm text-zinc-600">
                                                {action.description}
                                            </span>
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </section>

                    {success && (
                        <Alert className="border-emerald-200 bg-emerald-50 text-emerald-900">
                            <AlertTitle>Berhasil</AlertTitle>
                            <AlertDescription className="text-emerald-800">
                                {success}
                            </AlertDescription>
                        </Alert>
                    )}

                    <section className="grid gap-3 rounded-lg border bg-white p-3 shadow-xs sm:grid-cols-2">
                        {tabs.map((tab) => {
                            const Icon = tab.icon;
                            const selected = activeTab === tab.key;

                            return (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`flex min-h-16 items-center gap-3 rounded-md border px-4 py-3 text-left text-base font-semibold transition ${
                                        selected
                                            ? 'border-emerald-600 bg-emerald-50 text-emerald-900'
                                            : 'border-zinc-200 bg-white text-zinc-700'
                                    }`}
                                >
                                    <Icon className="size-5" />
                                    {tab.label}
                                </button>
                            );
                        })}
                    </section>

                    {activeTab === 'login' && (
                        <Card className="rounded-lg border-zinc-200 shadow-xs">
                            <CardHeader>
                                <CardTitle className="text-xl">
                                    Masuk ke Akun Helpdesk
                                </CardTitle>
                                <CardDescription className="text-base">
                                    Gunakan nomor handphone dan password yang
                                    sudah diberikan.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={submitLogin}
                                    className="grid gap-4"
                                >
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="login-phone"
                                            className="text-base"
                                        >
                                            Email atau nomor handphone
                                        </Label>
                                        <Input
                                            id="login-phone"
                                            type="text"
                                            value={loginForm.data.phone}
                                            onChange={(event) =>
                                                loginForm.setData(
                                                    'phone',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="email@example.com atau 08xxxxxxxxxx"
                                            autoComplete="username"
                                            className="h-12 text-base"
                                        />
                                        <InputError
                                            message={loginForm.errors.phone}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label
                                            htmlFor="login-password"
                                            className="text-base"
                                        >
                                            Password
                                        </Label>
                                        <Input
                                            id="login-password"
                                            type="password"
                                            value={loginForm.data.password}
                                            onChange={(event) =>
                                                loginForm.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder="Masukkan password"
                                            autoComplete="current-password"
                                            className="h-12 text-base"
                                        />
                                        <InputError
                                            message={loginForm.errors.password}
                                        />
                                    </div>
                                    <Button
                                        type="submit"
                                        disabled={loginForm.processing}
                                        className="h-12 w-full bg-emerald-600 text-base font-semibold hover:bg-emerald-700"
                                    >
                                        {loginForm.processing && <Spinner />}
                                        Masuk
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {activeTab === 'register' && (
                        <form onSubmit={submit} className="grid gap-5">
                            <Card className="rounded-lg border-zinc-200 shadow-xs">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-700">
                                            1
                                        </div>
                                        <div>
                                            <CardTitle className="text-xl">
                                                Pilih Wilayah dan Koperasi
                                            </CardTitle>
                                            <CardDescription className="text-base">
                                                Mulai dari provinsi. Setelah itu
                                                pilih koperasi yang sesuai.
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className="grid gap-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <NativeSelect
                                            label="Provinsi"
                                            value={form.data.province_id}
                                            options={provinces}
                                            error={form.errors.province_id}
                                            onChange={setProvince}
                                        />
                                        <NativeSelect
                                            label="Kota/Kabupaten"
                                            value={form.data.city_id}
                                            options={cities}
                                            disabled={!form.data.province_id}
                                            onChange={setCity}
                                        />
                                        <NativeSelect
                                            label="Kecamatan"
                                            value={form.data.district_id}
                                            options={districts}
                                            disabled={!form.data.city_id}
                                            onChange={setDistrict}
                                        />
                                        <NativeSelect
                                            label="Desa"
                                            value={form.data.village_id}
                                            options={villages}
                                            disabled={!form.data.district_id}
                                            onChange={setVillage}
                                        />
                                    </div>
                                    <NativeSelect
                                        label="Nama koperasi"
                                        value={form.data.koperasi_id}
                                        options={koperasis}
                                        disabled={
                                            !form.data.province_id ||
                                            loadingKoperasis
                                        }
                                        placeholder={
                                            loadingKoperasis
                                                ? 'Memuat koperasi...'
                                                : 'Pilih koperasi'
                                        }
                                        error={form.errors.koperasi_id}
                                        onChange={(value) =>
                                            form.setData('koperasi_id', value)
                                        }
                                    />
                                    {selectedKoperasi?.user_id && (
                                        <p className="rounded-md border border-amber-200 bg-amber-50 px-3 py-3 text-base leading-6 text-amber-900">
                                            Catatan: koperasi ini sudah punya
                                            PIC. Jika dilanjutkan, PIC lama akan
                                            diganti.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>

                            <Card className="rounded-lg border-zinc-200 shadow-xs">
                                <CardHeader>
                                    <div className="flex items-center gap-3">
                                        <div className="flex size-9 items-center justify-center rounded-md bg-zinc-100 text-zinc-700">
                                            2
                                        </div>
                                        <div>
                                            <CardTitle className="text-xl">
                                                Isi Data Orang yang Bisa
                                                Dihubungi
                                            </CardTitle>
                                            <CardDescription className="text-base">
                                                Cukup isi nama dan nomor
                                                handphone aktif.
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <div className="grid gap-4">
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="name"
                                                className="text-base"
                                            >
                                                Nama lengkap
                                            </Label>
                                            <Input
                                                id="name"
                                                value={form.data.name}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Nama PIC"
                                                autoComplete="name"
                                                className="h-12 text-base"
                                            />
                                            <InputError
                                                message={form.errors.name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor="phone"
                                                className="text-base"
                                            >
                                                Nomor handphone
                                            </Label>
                                            <Input
                                                id="phone"
                                                type="tel"
                                                value={form.data.phone}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'phone',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="08xxxxxxxxxx"
                                                autoComplete="tel"
                                                className="h-12 text-base"
                                            />
                                            <InputError
                                                message={form.errors.phone}
                                            />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <div className="sticky bottom-0 -mx-4 border-t bg-white/95 p-4 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                    className="h-12 w-full bg-emerald-600 text-base font-semibold hover:bg-emerald-700"
                                >
                                    {form.processing && <Spinner />}
                                    Kirim Data
                                </Button>
                                <p className="mt-3 text-center text-sm text-zinc-500">
                                    Pastikan nomor handphone aktif agar petugas
                                    bisa menghubungi.
                                </p>
                            </div>
                        </form>
                    )}
                </div>
            </main>
        </>
    );
}
