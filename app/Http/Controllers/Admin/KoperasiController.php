<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\District;
use App\Models\Koperasi;
use App\Models\Village;
use App\Services\AdminPageService;
use App\Services\KoperasiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KoperasiController extends Controller
{
    public function __construct(
        private readonly AdminPageService $admins,
        private readonly KoperasiService $koperasis,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'koperasis',
            'title' => 'Koperasi',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => $this->koperasis->paginated($search),
            'options' => [
                'koperasis' => [],
                'sarprases' => [],
                'statuses' => [],
                'cities' => City::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'districts' => District::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'villages' => Village::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->koperasis->create($this->validated($request));

        return back()->with('success', 'Koperasi dibuat.');
    }

    public function update(Request $request, Koperasi $koperasi): RedirectResponse
    {
        $this->koperasis->update($koperasi, $this->validated($request));

        return back()->with('success', 'Koperasi diperbarui.');
    }

    public function destroy(Koperasi $koperasi): RedirectResponse
    {
        $this->koperasis->delete($koperasi);

        return back()->with('success', 'Koperasi dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:10240'],
        ]);

        $count = $this->koperasis->import($data['file']);

        return back()->with('success', "{$count} koperasi diimport.");
    }

    public function template(): StreamedResponse
    {
        $headers = [
            'ai_id',
            'name',
            'province',
            'city',
            'district',
            'village',
            'latitude',
            'longitude',
            'delivery_percentage',
            'installed_percentage',
            'core_percentage',
        ];

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        }, 'template-koperasi.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'ai_id' => ['nullable', 'string', 'max:255', Rule::unique('koperasis')->ignore($request->route('koperasi'))],
            'name' => ['required', 'string', 'max:255'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'latitude' => ['nullable', 'numeric', 'between:-11,6'],
            'longitude' => ['nullable', 'numeric', 'between:95,142'],
            'delivery_percentage' => ['nullable', 'numeric', 'between:0,1'],
            'installed_percentage' => ['nullable', 'numeric', 'between:0,1'],
            'core_percentage' => ['nullable', 'numeric', 'between:0,1'],
        ]);
    }
}
