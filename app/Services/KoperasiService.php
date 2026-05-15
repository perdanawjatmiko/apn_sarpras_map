<?php

namespace App\Services;

use App\Models\Koperasi;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class KoperasiService
{
    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly RegionResolver $regions,
    ) {}

    public function paginated(?string $search = null): LengthAwarePaginator
    {
        return Koperasi::query()
            ->with(['province:id,name', 'city:id,name', 'district:id,name', 'village:id,name'])
            ->when($search, fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('ai_id', 'like', "%{$search}%"))
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Koperasi
    {
        $koperasi = Koperasi::create($data);
        $this->flushMapCache();

        return $koperasi;
    }

    public function update(Koperasi $koperasi, array $data): Koperasi
    {
        $koperasi->update($data);
        $this->flushMapCache();

        return $koperasi;
    }

    public function delete(Koperasi $koperasi): void
    {
        $koperasi->delete();
        $this->flushMapCache();
    }

    public function import(UploadedFile|string $file): int
    {
        $allRows = $this->reader->rows($file);
        $headers = array_map(fn ($header) => str($header)->lower()->snake()->toString(), $allRows[0] ?? []);
        $rows = array_slice($allRows, 1);
        $usesTemplate = in_array('province', $headers, true) || in_array('province_id', $headers, true);
        $count = 0;

        foreach ($rows as $row) {
            $name = $usesTemplate ? $this->value($headers, $row, 'name') : ($row[0] ?? null);

            if (blank($name)) {
                continue;
            }

            $regionIds = match (true) {
                $usesTemplate && filled($this->value($headers, $row, 'province_id')) => [
                    'province_id' => $this->integer($this->value($headers, $row, 'province_id')),
                    'city_id' => $this->integer($this->value($headers, $row, 'city_id')),
                    'district_id' => $this->integer($this->value($headers, $row, 'district_id')),
                    'village_id' => $this->integer($this->value($headers, $row, 'village_id')),
                ],
                $usesTemplate => $this->regions->resolve(
                    $this->value($headers, $row, 'province') ?? $this->value($headers, $row, 'provinsi'),
                    $this->value($headers, $row, 'city') ?? $this->value($headers, $row, 'kabupaten_kota') ?? $this->value($headers, $row, 'kab_kota'),
                    $this->value($headers, $row, 'district') ?? $this->value($headers, $row, 'kecamatan'),
                    $this->value($headers, $row, 'village') ?? $this->value($headers, $row, 'desa'),
                ),
                default => $this->regions->resolve(
                    $row[4] ?? null,
                    $row[3] ?? null,
                    $row[2] ?? null,
                    $row[1] ?? null,
                ),
            };

            $aiId = $usesTemplate ? $this->value($headers, $row, 'ai_id') : null;
            $attributes = [
                'name' => $name,
                ...$regionIds,
                'latitude' => $this->decimal($usesTemplate ? $this->value($headers, $row, 'latitude') : ($row[5] ?? null)),
                'longitude' => $this->decimal($usesTemplate ? $this->value($headers, $row, 'longitude') : ($row[6] ?? null)),
                'delivery_percentage' => $this->decimal($usesTemplate ? $this->value($headers, $row, 'delivery_percentage') : ($row[24] ?? null)),
                'installed_percentage' => $this->decimal($usesTemplate ? $this->value($headers, $row, 'installed_percentage') : ($row[25] ?? null)),
                'core_percentage' => $this->decimal($usesTemplate ? $this->value($headers, $row, 'core_percentage') : ($row[26] ?? null)),
            ];

            if (filled($aiId)) {
                $attributes['ai_id'] = $aiId;
            }

            $koperasi = Koperasi::updateOrCreate($this->lookupAttributes($aiId, $name), $attributes);

            if ($koperasi->wasRecentlyCreated || $koperasi->wasChanged()) {
                $count++;
            }
        }

        $this->flushMapCache();

        return $count;
    }

    private function decimal(?string $value): ?float
    {
        if (blank($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($value));
        $isPercentage = str_contains($normalized, '%');
        $normalized = str_replace('%', '', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        $number = (float) $normalized;

        return $isPercentage ? $number / 100 : $number;
    }

    private function integer(?string $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    /**
     * @return array<string, string>
     */
    private function lookupAttributes(?string $aiId, string $name): array
    {
        return filled($aiId) ? ['ai_id' => $aiId] : ['name' => $name];
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     */
    private function value(array $headers, array $row, string $key): ?string
    {
        $index = array_search($key, $headers, true);

        return $index === false ? null : ($row[$index] ?? null);
    }

    private function flushMapCache(): void
    {
        Cache::forget(PublicMapService::CACHE_KEY);
    }
}
