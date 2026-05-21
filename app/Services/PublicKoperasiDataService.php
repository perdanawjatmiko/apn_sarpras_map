<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PublicKoperasiDataService
{
    private const CACHE_STATISTICS = 'public-data:koperasi-statistics:v1';

    private const CACHE_LOCATIONS = 'public-data:koperasi-locations:v1';

    private const TOTAL_OPERATIONAL_SARPRAS = 16;

    public function __construct(private readonly PublicMapService $maps) {}

    public function statistics(bool $flush = false): array
    {
        if ($flush) {
            $this->flush();
        }

        return Cache::remember(self::CACHE_STATISTICS, now()->addHour(), function () {
            $markers = $this->markers();
            $categoryCounts = [
                'sarpras_100_percent_terpasang' => 0,
                'sarpras_diatas_70_percent_tiba_terpasang' => 0,
                'sarpras_diatas_35_percent_tiba_terpasang' => 0,
                'sarpras_dibawah_sama_dengan_35_percent_tiba_terpasang' => 0,
            ];

            foreach ($markers as $marker) {
                $categoryCounts[$this->categoryKey($marker)]++;
            }

            $retailReady = $markers
                ->filter(fn (array $marker) => (bool) ($marker['retail_ready'] ?? false))
                ->map(fn (array $marker) => $this->koperasiPayload($marker))
                ->values();

            return [
                'generated_at' => now()->toIso8601String(),
                'total_koperasi' => $markers->count(),
                'statistics' => $categoryCounts,
                'siap_operasional_100_percent' => [
                    'count' => $retailReady->count(),
                    'data' => $retailReady->all(),
                ],
            ];
        });
    }

    public function locations(bool $flush = false): array
    {
        if ($flush) {
            $this->flush();
        }

        return Cache::remember(self::CACHE_LOCATIONS, now()->addHour(), function () {
            $data = $this->markers()
                ->filter(fn (array $marker) => $this->isSarprasFullyInstalled($marker) && (bool) ($marker['retail_ready'] ?? false))
                ->map(fn (array $marker) => $this->koperasiPayload($marker))
                ->values();

            return [
                'generated_at' => now()->toIso8601String(),
                'count' => $data->count(),
                'data' => $data->all(),
            ];
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_STATISTICS);
        Cache::forget(self::CACHE_LOCATIONS);
        Cache::forget(PublicMapService::CACHE_KEY);
    }

    private function markers(): Collection
    {
        return collect($this->maps->data()['markers'] ?? []);
    }

    private function categoryKey(array $marker): string
    {
        $counts = $marker['status_counts'] ?? [];
        $installed = (int) ($counts['terpasang'] ?? 0);
        $arrived = $installed + (int) ($counts['tiba'] ?? 0);
        $percentage = ($arrived / self::TOTAL_OPERATIONAL_SARPRAS) * 100;

        if ($this->isSarprasFullyInstalled($marker)) {
            return 'sarpras_100_percent_terpasang';
        }

        if ($percentage > 70) {
            return 'sarpras_diatas_70_percent_tiba_terpasang';
        }

        if ($percentage > 35) {
            return 'sarpras_diatas_35_percent_tiba_terpasang';
        }

        return 'sarpras_dibawah_sama_dengan_35_percent_tiba_terpasang';
    }

    private function isSarprasFullyInstalled(array $marker): bool
    {
        return (int) ($marker['status_counts']['terpasang'] ?? 0) >= self::TOTAL_OPERATIONAL_SARPRAS;
    }

    private function koperasiPayload(array $marker): array
    {
        return [
            'id' => $marker['id'] ?? null,
            'name' => $marker['name'] ?? null,
            'latitude' => $marker['latitude'] ?? null,
            'longitude' => $marker['longitude'] ?? null,
            'province' => $marker['province'] ?? null,
            'city' => $marker['city_id'] ?? null,
            'district' => $marker['district_id'] ?? null,
            'village' => $marker['village_id'] ?? null,
            'retail_ready' => (bool) ($marker['retail_ready'] ?? false),
            'status_counts' => $marker['status_counts'] ?? [],
            'mandatory_sarpras_count' => $marker['mandatory_sarpras_count'] ?? 0,
            'installed_mandatory_sarpras_count' => $marker['installed_mandatory_sarpras_count'] ?? 0,
        ];
    }
}
