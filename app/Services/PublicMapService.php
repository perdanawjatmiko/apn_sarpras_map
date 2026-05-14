<?php

namespace App\Services;

use App\Models\Koperasi;
use App\Models\Sarpras;
use Illuminate\Support\Facades\Cache;

class PublicMapService
{
    public const CACHE_KEY = 'public-map:koperasi-markers:v3';

    /**
     * @return array{markers: array<int, array<string, mixed>>, filters: array<string, mixed>, stats: array<string, int>}
     */
    public function data(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            $markers = Koperasi::query()
                ->located()
                ->select(['id', 'name', 'province_id', 'city_id', 'district_id', 'village_id', 'latitude', 'longitude', 'delivery_percentage', 'installed_percentage', 'core_percentage'])
                ->with([
                    'province:id,name',
                    'city:id,name',
                    'district:id,name',
                    'village:id,name',
                    'sarprasAssignments.sarpras:id,name',
                    'sarprasAssignments.status:id,name',
                ])
                ->withCount('sarprasAssignments')
                ->latest('id')
                ->limit(10000)
                ->get()
                ->map(fn (Koperasi $koperasi) => [
                    'id' => $koperasi->id,
                    'name' => $koperasi->name,
                    'latitude' => (float) $koperasi->latitude,
                    'longitude' => (float) $koperasi->longitude,
                    'province' => $koperasi->province?->name,
                    'city' => $koperasi->city?->name,
                    'district' => $koperasi->district?->name,
                    'village' => $koperasi->village?->name,
                    'sarpras_count' => $koperasi->sarpras_assignments_count,
                    'delivery_percentage' => $koperasi->delivery_percentage,
                    'installed_percentage' => $koperasi->installed_percentage,
                    'core_percentage' => $koperasi->core_percentage,
                    'sarprases' => $koperasi->sarprasAssignments->take(6)->map(fn ($assignment) => [
                        'name' => $assignment->sarpras?->name,
                        'status' => $assignment->status?->name,
                    ])->values()->all(),
                    'detail_url' => route('koperasis.show', $koperasi),
                ])
                ->values()
                ->all();

            return [
                'markers' => $markers,
                'filters' => [
                    'provinces' => collect($markers)->pluck('province')->filter()->unique()->sort()->values()->all(),
                    'cities' => collect($markers)->pluck('city')->filter()->unique()->sort()->values()->all(),
                    'sarprases' => Sarpras::query()->orderBy('name')->pluck('name')->all(),
                ],
                'stats' => [
                    'koperasis' => count($markers),
                    'sarprases' => Sarpras::query()->count(),
                ],
            ];
        });
    }
}
