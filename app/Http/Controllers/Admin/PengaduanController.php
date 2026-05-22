<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Koperasi;
use App\Models\Pengaduan;
use App\Models\PengaduanCategory;
use App\Models\Province;
use App\Models\User;
use App\Services\AdminPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PengaduanController extends Controller
{
    public function __construct(private readonly AdminPageService $admins) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'pengaduans',
            'title' => 'Pengaduan',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => Pengaduan::query()
                ->with([
                    'reporter:id,name,phone',
                    'koperasi:id,name',
                    'province:id,name',
                    'city:id,name',
                    'category:id,name',
                    'subCategory:id,name',
                    'picHelpdesk:id,name,phone',
                ])
                ->when($search, fn ($query) => $query
                    ->where('ticket_id', 'like', "%{$search}%")
                    ->orWhere('reporter_name', 'like', "%{$search}%")
                    ->orWhere('reporter_phone', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
            'options' => [
                'koperasis' => Koperasi::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'sarprases' => [],
                'statuses' => [],
                'cities' => City::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'districts' => [],
                'villages' => [],
                'users' => User::query()->orderBy('name')->limit(1000)->get(['id', 'name', 'phone']),
                'provinces' => Province::query()->orderBy('name')->limit(1000)->get(['id', 'name']),
                'categories' => PengaduanCategory::query()->whereNull('parent_id')->orderBy('name')->get(['id', 'name']),
                'sub_categories' => PengaduanCategory::query()->whereNotNull('parent_id')->orderBy('name')->get(['id', 'name', 'parent_id']),
                'priorities' => $this->options(Pengaduan::PRIORITIES),
                'complaint_statuses' => $this->options(Pengaduan::STATUSES),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Pengaduan::create($this->validated($request));

        return back()->with('success', 'Pengaduan dibuat.');
    }

    public function update(Request $request, Pengaduan $pengaduan): RedirectResponse
    {
        $pengaduan->update($this->validated($request));

        return back()->with('success', 'Pengaduan diperbarui.');
    }

    public function destroy(Pengaduan $pengaduan): RedirectResponse
    {
        $pengaduan->delete();

        return back()->with('success', 'Pengaduan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'ticket_id' => ['nullable', 'string', 'max:255'],
            'ticket_date' => ['nullable', 'date'],
            'reporter_user_id' => ['nullable', 'exists:users,id'],
            'reporter_name' => ['required', 'string', 'max:255'],
            'reporter_phone' => ['required', 'string', 'max:30'],
            'koperasi_id' => ['nullable', 'exists:koperasis,id'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'category_id' => ['nullable', 'exists:pengaduan_categories,id'],
            'sub_category_id' => ['nullable', 'exists:pengaduan_categories,id'],
            'priority' => ['sometimes', 'required', Rule::in(Pengaduan::PRIORITIES)],
            'title' => ['required', 'string', 'max:255'],
            'detail' => ['required', 'string'],
            'pic_helpdesk_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', 'required', Rule::in(Pengaduan::STATUSES)],
            'assigned_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'sla_days' => ['nullable', 'integer', 'min:0'],
            'progress_percentage' => ['sometimes', 'required', 'integer', 'between:0,100'],
            'resolution' => ['nullable', 'string'],
            'attachment_link' => ['nullable', 'url', 'max:2048'],
            'last_update_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * @param  array<int, string>  $items
     * @return array<int, array{id: string, name: string}>
     */
    private function options(array $items): array
    {
        return collect($items)
            ->map(fn (string $item) => [
                'id' => $item,
                'name' => str($item)->replace('_', ' ')->title()->toString(),
            ])
            ->values()
            ->all();
    }
}
