<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sarpras;
use App\Services\AdminPageService;
use App\Services\PublicMapService;
use App\Services\SarprasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SarprasController extends Controller
{
    public function __construct(
        private readonly AdminPageService $admins,
        private readonly SarprasService $sarprases,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'sarprases',
            'title' => 'Sarpras',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => $this->sarprases->paginated($search),
            'options' => [
                'koperasis' => [],
                'sarprases' => [],
                'statuses' => [],
                'cities' => [],
                'districts' => [],
                'villages' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sarprases')],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('sarprases')],
            'description' => ['nullable', 'string'],
            'is_mandatory' => ['boolean'],
            'mandatory_group' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['mandatory_group'] = $this->mandatoryGroup($data['mandatory_group'] ?? null);

        $this->sarprases->create($data);

        return back()->with('success', 'Sarpras dibuat.');
    }

    public function update(Request $request, Sarpras $sarprase): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_mandatory' => ['boolean'],
            'mandatory_group' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_mandatory'] = $request->boolean('is_mandatory') ? 1 : 0;
        $data['mandatory_group'] = $this->mandatoryGroup($data['mandatory_group'] ?? null);

        $this->sarprases->update($sarprase, $data);

        return back()->with('success', 'Sarpras diperbarui.');
    }

    public function destroy(Sarpras $sarprase): RedirectResponse
    {
        $sarprase->delete();
        cache()->forget(PublicMapService::CACHE_KEY);

        return back()->with('success', 'Sarpras dihapus.');
    }

    public function import(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:10240'],
        ]);

        $count = $this->sarprases->import($data['file']);

        return back()->with('success', "{$count} sarpras diimport.");
    }

    private function mandatoryGroup(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Str::of($value)->lower()->squish()->replace(' ', '_')->toString();
    }
}
