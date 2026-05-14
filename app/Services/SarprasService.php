<?php

namespace App\Services;

use App\Models\Sarpras;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SarprasService
{
    public function __construct(private readonly SpreadsheetReader $reader) {}

    public function paginated(?string $search = null): LengthAwarePaginator
    {
        return Sarpras::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Sarpras
    {
        $sarpras = Sarpras::create([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);

        Cache::forget(PublicMapService::CACHE_KEY);

        return $sarpras;
    }

    public function update(Sarpras $sarpras, array $data): Sarpras
    {
        $sarpras->update([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['name']),
        ]);

        Cache::forget(PublicMapService::CACHE_KEY);

        return $sarpras;
    }

    public function import(UploadedFile $file): int
    {
        $rows = $this->reader->rows($file);
        $headers = array_map(fn ($value) => Str::of((string) $value)->squish()->toString(), $rows[0] ?? []);
        $count = 0;

        foreach ($headers as $index => $header) {
            if ($index < 8 || $header === '' || str_starts_with($header, 'Persentase')) {
                continue;
            }

            Sarpras::updateOrCreate(
                ['slug' => Str::slug($header)],
                ['name' => $header]
            );
            $count++;
        }

        foreach (array_slice($rows, 1) as $row) {
            $name = $row[0] ?? null;

            if (blank($name)) {
                continue;
            }

            Sarpras::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $row[1] ?? null]
            );
            $count++;
        }

        Cache::forget(PublicMapService::CACHE_KEY);

        return $count;
    }
}
