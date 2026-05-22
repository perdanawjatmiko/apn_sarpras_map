<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengaduanCategory;
use App\Services\AdminPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PengaduanCategoryController extends Controller
{
    public function __construct(private readonly AdminPageService $admins) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('admin/index', [
            'resource' => 'pengaduanCategories',
            'title' => 'Kategori Pengaduan',
            'search' => $search,
            'stats' => $this->admins->dashboard(),
            'records' => PengaduanCategory::query()
                ->with('parent:id,name')
                ->when($search, fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%"))
                ->latest('id')
                ->paginate(10)
                ->withQueryString(),
            'options' => [
                'koperasis' => [],
                'sarprases' => [],
                'statuses' => [],
                'cities' => [],
                'districts' => [],
                'villages' => [],
                'users' => [],
                'categories' => PengaduanCategory::query()
                    ->whereNull('parent_id')
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'sub_categories' => [],
                'priorities' => [],
                'complaint_statuses' => [],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PengaduanCategory::create($this->validated($request));

        return back()->with('success', 'Kategori pengaduan dibuat.');
    }

    public function update(Request $request, PengaduanCategory $pengaduanCategory): RedirectResponse
    {
        $pengaduanCategory->update($this->validated($request, $pengaduanCategory));

        return back()->with('success', 'Kategori pengaduan diperbarui.');
    }

    public function destroy(PengaduanCategory $pengaduanCategory): RedirectResponse
    {
        $pengaduanCategory->delete();

        return back()->with('success', 'Kategori pengaduan dihapus.');
    }

    /**
     * @return array{parent_id: int|null, name: string, slug: string|null, description: string|null, is_active: bool}
     */
    private function validated(Request $request, ?PengaduanCategory $category = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:pengaduan_categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('pengaduan_categories')->ignore($category)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pengaduan_categories')->ignore($category)],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        return [
            ...$data,
            'parent_id' => $data['parent_id'] ?? null,
            'slug' => filled($data['slug'] ?? null) ? Str::slug($data['slug']) : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
