<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\District;
use App\Models\Koperasi;
use App\Models\Pengaduan;
use App\Models\Province;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HelpdeskController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('helpdesk', [
            'provinces' => Province::query()
                ->whereIn('id', Koperasi::query()
                    ->select('province_id')
                    ->whereNotNull('province_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values(),
            'success' => $request->session()->get('success'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function dashboard(Request $request): Response
    {
        $reports = Pengaduan::query()
            ->where('reporter_user_id', $request->user()->id)
            ->latest('id')
            ->get()
            ->map(fn (Pengaduan $pengaduan) => [
                'id' => $pengaduan->ticket_id,
                'title' => $pengaduan->title,
                'detail' => $pengaduan->detail,
                'koperasi' => $pengaduan->koperasi?->name ?? '-',
                'status' => str($pengaduan->status)->replace('_', ' ')->title()->toString(),
                'date' => $pengaduan->ticket_date?->translatedFormat('d F Y') ?? '-',
                'attachment_url' => $pengaduan->attachmentUrl(),
            ]);

        return Inertia::render('helpdesk/dashboard', [
            'stats' => [
                'open' => Pengaduan::query()->where('reporter_user_id', $request->user()->id)->where('status', 'baru')->count(),
                'process' => Pengaduan::query()->where('reporter_user_id', $request->user()->id)->whereIn('status', ['ditugaskan', 'diproses'])->count(),
                'done' => Pengaduan::query()->where('reporter_user_id', $request->user()->id)->whereIn('status', ['selesai', 'ditutup'])->count(),
            ],
            'reports' => $reports,
            'user' => $request->user()->only(['name', 'phone']),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('phone', $data['phone'])
            ->orWhere('email', $data['phone'])
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Email/nomor handphone atau password salah.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return to_route('helpdesk.dashboard');
    }

    public function storePengaduan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $koperasi = Koperasi::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $attachmentPath = $request->file('attachment')?->store('pengaduan-attachments', 'public');

        Pengaduan::create([
            'reporter_user_id' => $request->user()->id,
            'reporter_name' => $request->user()->name,
            'reporter_phone' => $request->user()->phone,
            'koperasi_id' => $koperasi?->id,
            'province_id' => $koperasi?->province_id,
            'city_id' => $koperasi?->city_id,
            'priority' => 'normal',
            'title' => $data['title'],
            'detail' => $data['detail'],
            'status' => 'baru',
            'progress_percentage' => 0,
            'attachment_path' => $attachmentPath,
        ]);

        return back()->with('success', 'Pengaduan berhasil dikirim.');
    }

    public function koperasis(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'village_id' => ['nullable', 'exists:villages,id'],
        ]);

        return response()->json(
            Koperasi::query()
                ->where('province_id', $data['province_id'])
                ->when($data['city_id'] ?? null, fn ($query, $cityId) => $query->where('city_id', $cityId))
                ->when($data['district_id'] ?? null, fn ($query, $districtId) => $query->where('district_id', $districtId))
                ->when($data['village_id'] ?? null, fn ($query, $villageId) => $query->where('village_id', $villageId))
                ->orderBy('name')
                ->limit(500)
                ->get(['id', 'name', 'user_id'])
                ->values()
                ->all()
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
        ]);

        return response()->json(
            City::query()
                ->where('province_id', $data['province_id'])
                ->whereIn('id', Koperasi::query()
                    ->select('city_id')
                    ->where('province_id', $data['province_id'])
                    ->whereNotNull('city_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function districts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        return response()->json(
            District::query()
                ->where('city_id', $data['city_id'])
                ->whereIn('id', Koperasi::query()
                    ->select('district_id')
                    ->where('city_id', $data['city_id'])
                    ->whereNotNull('district_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function villages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
        ]);

        return response()->json(
            Village::query()
                ->where('district_id', $data['district_id'])
                ->whereIn('id', Koperasi::query()
                    ->select('village_id')
                    ->where('district_id', $data['district_id'])
                    ->whereNotNull('village_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'koperasi_id' => ['required', 'exists:koperasis,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users')],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'password' => config('auth.helpdesk_default_password'),
            ]);

            Koperasi::query()
                ->whereKey($data['koperasi_id'])
                ->update(['user_id' => $user->id]);
        });

        return back()->with('success', 'Akun PIC koperasi berhasil dibuat.');
    }
}
