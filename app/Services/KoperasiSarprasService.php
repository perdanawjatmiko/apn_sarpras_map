<?php

namespace App\Services;

use App\Models\Koperasi;
use App\Models\KoperasiSarpras;
use App\Models\Sarpras;
use App\Models\Status;
use App\Support\Imports\SpreadsheetReader;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class KoperasiSarprasService
{
    public function __construct(
        private readonly SpreadsheetReader $reader,
        private readonly KoperasiService $koperasis,
    ) {}

    public function paginated(?string $search = null): LengthAwarePaginator
    {
        return KoperasiSarpras::query()
            ->with(['koperasi:id,name', 'sarpras:id,name', 'status:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->whereHas('koperasi', fn ($relation) => $relation->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sarpras', fn ($relation) => $relation->where('name', 'like', "%{$search}%"));
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();
    }

    public function upsert(array $data): KoperasiSarpras
    {
        $record = KoperasiSarpras::updateOrCreate(
            [
                'koperasi_id' => $data['koperasi_id'],
                'sarpras_id' => $data['sarpras_id'],
            ],
            [
                'status_id' => $data['status_id'] ?? null,
            ]
        );

        Cache::forget(PublicMapService::CACHE_KEY);

        return $record;
    }

    public function update(KoperasiSarpras $record, array $data): KoperasiSarpras
    {
        $record->update([
            'koperasi_id' => $data['koperasi_id'],
            'sarpras_id' => $data['sarpras_id'],
            'status_id' => $data['status_id'] ?? null,
        ]);

        Cache::forget(PublicMapService::CACHE_KEY);

        return $record;
    }

    public function delete(KoperasiSarpras $record): void
    {
        $record->delete();
        Cache::forget(PublicMapService::CACHE_KEY);
    }

    public function import(UploadedFile $file): int
    {
        $rows = $this->reader->rows($file);
        $headers = $rows[0] ?? [];
        $normalizedHeaders = array_map(fn ($header) => Str::of((string) $header)->lower()->snake()->toString(), $headers);
        $aiIdColumn = array_search('ai_id', $normalizedHeaders, true);

        if ($aiIdColumn !== false) {
            return $this->importByAiIdTemplate($rows, $headers, $normalizedHeaders, $aiIdColumn);
        }

        $sarprasColumns = [];
        $count = 0;

        foreach ($headers as $index => $header) {
            if ($index < 8 || blank($header) || str_starts_with((string) $header, 'Persentase')) {
                continue;
            }

            $sarprasColumns[$index] = Sarpras::firstOrCreate(
                ['slug' => Str::slug((string) $header)],
                ['name' => $header]
            );
        }

        $this->koperasis->import($file);

        foreach (array_slice($rows, 1) as $row) {
            $koperasiName = $row[0] ?? null;

            if (blank($koperasiName)) {
                continue;
            }

            $koperasi = Koperasi::where('name', $koperasiName)->first();

            if (! $koperasi) {
                continue;
            }

            foreach ($sarprasColumns as $index => $sarpras) {
                $status = $this->resolveStatus($row[$index] ?? null);

                if (! $status) {
                    continue;
                }

                $this->upsert([
                    'koperasi_id' => $koperasi->id,
                    'sarpras_id' => $sarpras->id,
                    'status_id' => $status->id,
                ]);
                $count++;
            }
        }

        Cache::forget(PublicMapService::CACHE_KEY);

        return $count;
    }

    /**
     * @param  array<int, array<int, string|null>>  $rows
     * @param  array<int, string|null>  $headers
     * @param  array<int, string>  $normalizedHeaders
     */
    private function importByAiIdTemplate(array $rows, array $headers, array $normalizedHeaders, int $aiIdColumn): int
    {
        $sarprasColumns = [];
        $count = 0;

        foreach ($headers as $index => $header) {
            if ($index === $aiIdColumn || blank($header) || $this->isIgnoredHeader($normalizedHeaders[$index] ?? '')) {
                continue;
            }

            $sarprasColumns[$index] = Sarpras::firstOrCreate(
                ['slug' => Str::slug((string) $header)],
                ['name' => $header]
            );
        }

        foreach (array_slice($rows, 1) as $row) {
            $aiId = $row[$aiIdColumn] ?? null;

            if (blank($aiId)) {
                continue;
            }

            $koperasi = Koperasi::where('ai_id', $aiId)->first();

            if (! $koperasi) {
                continue;
            }

            foreach ($sarprasColumns as $index => $sarpras) {
                $status = $this->resolveStatus($row[$index] ?? null);

                if (! $status) {
                    continue;
                }

                $this->upsert([
                    'koperasi_id' => $koperasi->id,
                    'sarpras_id' => $sarpras->id,
                    'status_id' => $status->id,
                ]);
                $count++;
            }
        }

        Cache::forget(PublicMapService::CACHE_KEY);

        return $count;
    }

    private function isIgnoredHeader(string $header): bool
    {
        return in_array($header, [
            'nama_induk_koperasi_nik',
            'nama_desa_kel',
            'nama',
            'kecamatan',
            'kab_kota',
            'kabupaten_kota',
            'provinsi',
            'province',
            'city',
            'district',
            'village',
            'latitude',
            'longitude',
            'ket',
        ], true) || str_starts_with($header, 'persentase');
    }

    private function resolveStatus(?string $value): ?Status
    {
        if (blank($value)) {
            return null;
        }

        $normalized = Str::of($value)->lower()->squish()->toString();
        $name = match (true) {
            str_contains($normalized, 'terpasang') => 'terpasang',
            str_contains($normalized, 'tiba') => 'tiba',
            str_contains($normalized, 'pengiriman') => 'pengiriman',
            str_contains($normalized, 'transit') => 'transit',
            default => null,
        };

        return $name ? Status::firstWhere('name', $name) : null;
    }
}
