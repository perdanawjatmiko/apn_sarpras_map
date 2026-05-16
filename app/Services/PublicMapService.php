<?php

namespace App\Services;

use App\Models\Koperasi;
use App\Models\Sarpras;
use App\Models\Status;
use Illuminate\Support\Facades\Cache;

class PublicMapService
{
    public const CACHE_KEY = 'public-map:koperasi-markers:v10';

    private const INSTALLATION_EXEMPT_SLUGS = ['pickup-4x4', 'motor-bak-roda-3', 'truk'];

    /**
     * @return array{markers: array<int, array<string, mixed>>, filters: array<string, mixed>, stats: array<string, int>}
     */
    public function data(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            $mandatoryRules = Sarpras::query()
                ->where('is_mandatory', true)
                ->get(['id', 'mandatory_group'])
                ->groupBy(fn (Sarpras $sarpras) => filled($sarpras->mandatory_group)
                    ? "group:{$sarpras->mandatory_group}"
                    : "item:{$sarpras->id}");
            $mandatoryRuleCount = $mandatoryRules->count();

            $markers = Koperasi::query()
                ->located()
                ->select(['id', 'name', 'province_id', 'city_id', 'district_id', 'village_id', 'latitude', 'longitude', 'delivery_percentage', 'installed_percentage', 'core_percentage'])
                ->with([
                    'province:id,name',
                    'city:id,name',
                    'district:id,name',
                    'village:id,name',
                    'sarprasAssignments.sarpras:id,name,slug,is_mandatory,mandatory_group',
                    'sarprasAssignments.status:id,name',
                ])
                ->withCount('sarprasAssignments')
                ->latest('id')
                ->limit(10000)
                ->get()
                ->map(function (Koperasi $koperasi) use ($mandatoryRuleCount, $mandatoryRules) {
                    $statusOrder = ['terpasang', 'tiba', 'pengiriman', 'transit', 'tanpa_status'];
                    $statusCounts = array_fill_keys(Status::NAMES, 0);

                    foreach ($koperasi->sarprasAssignments as $assignment) {
                        $status = $this->effectiveStatus(
                            $assignment->status?->name,
                            $assignment->sarpras?->slug,
                        );
                        $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                    }

                    $installedMandatorySarprasIds = $koperasi->sarprasAssignments
                        ->filter(fn ($assignment) => $assignment->sarpras?->is_mandatory
                            && $this->effectiveStatus($assignment->status?->name, $assignment->sarpras?->slug) === 'terpasang')
                        ->pluck('sarpras_id')
                        ->unique();
                    $arrivedMandatorySarprasIds = $koperasi->sarprasAssignments
                        ->filter(fn ($assignment) => $assignment->sarpras?->is_mandatory && in_array($assignment->status?->name, ['terpasang', 'tiba'], true))
                        ->pluck('sarpras_id')
                        ->unique();
                    $fulfilledMandatoryRuleCount = $mandatoryRules
                        ->filter(fn ($sarprases) => $sarprases
                            ->pluck('id')
                            ->intersect($installedMandatorySarprasIds)
                            ->isNotEmpty())
                        ->count();
                    $arrivedMandatoryRuleCount = $mandatoryRules
                        ->filter(fn ($sarprases) => $sarprases
                            ->pluck('id')
                            ->intersect($arrivedMandatorySarprasIds)
                            ->isNotEmpty())
                        ->count();

                    return [
                        'id' => $koperasi->id,
                        'name' => $koperasi->name,
                        'province_id' => $koperasi->province_id,
                        'city_id' => $koperasi->city_id,
                        'district_id' => $koperasi->district_id,
                        'village_id' => $koperasi->village_id,
                        'latitude' => (float) $koperasi->latitude,
                        'longitude' => (float) $koperasi->longitude,
                        'province' => $koperasi->province?->name,
                        'city' => $koperasi->city?->name,
                        'district' => $koperasi->district?->name,
                        'village' => $koperasi->village?->name,
                        'sarpras_count' => $koperasi->sarpras_assignments_count,
                        'status_counts' => $statusCounts,
                        'retail_ready' => $mandatoryRuleCount > 0 && $fulfilledMandatoryRuleCount >= $mandatoryRuleCount,
                        'mandatory_sarpras_count' => $mandatoryRuleCount,
                        'installed_mandatory_sarpras_count' => $fulfilledMandatoryRuleCount,
                        'arrived_mandatory_sarpras_count' => $arrivedMandatoryRuleCount,
                        'delivery_percentage' => $koperasi->delivery_percentage,
                        'installed_percentage' => $koperasi->installed_percentage,
                        'core_percentage' => $koperasi->core_percentage,
                        'sarprases' => $koperasi->sarprasAssignments
                            ->sortBy(function ($assignment) use ($statusOrder) {
                                $index = array_search($assignment->status?->name ?? 'tanpa_status', $statusOrder, true);

                                return $index === false ? count($statusOrder) : $index;
                            })
                            ->map(fn ($assignment) => [
                                'name' => $assignment->sarpras?->name,
                                'status' => $assignment->status?->name ?? 'tanpa_status',
                                'effective_status' => $this->effectiveStatus($assignment->status?->name, $assignment->sarpras?->slug),
                                'is_mandatory' => (bool) $assignment->sarpras?->is_mandatory,
                            ])
                            ->values()
                            ->all(),
                    ];
                })
                ->values()
                ->all();

            return [
                'markers' => $markers,
                'filters' => [
                    'provinces' => [
                        ['id' => 13, 'name' => 'Jawa Tengah'],
                        ['id' => 15, 'name' => 'Jawa Timur'],
                    ],
                    'sarprases' => Sarpras::query()->orderBy('name')->pluck('name')->all(),
                ],
                'stats' => [
                    'koperasis' => count($markers),
                    'sarprases' => Sarpras::query()->count(),
                ],
            ];
        });
    }

    private function effectiveStatus(?string $status, ?string $sarprasSlug): string
    {
        $status ??= 'tanpa_status';

        if ($status === 'tiba' && in_array($sarprasSlug, self::INSTALLATION_EXEMPT_SLUGS, true)) {
            return 'terpasang';
        }

        return $status;
    }
}
