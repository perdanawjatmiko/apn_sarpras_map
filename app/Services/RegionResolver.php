<?php

namespace App\Services;

use App\Models\City;
use App\Models\District;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Support\Str;

class RegionResolver
{
    /**
     * @return array{province_id: int|null, city_id: int|null, district_id: int|null, village_id: int|null}
     */
    public function resolve(?string $province, ?string $city, ?string $district, ?string $village): array
    {
        $provinceModel = $this->findByName(Province::query(), $province);
        $cityModel = $this->findByName(
            City::query()->when($provinceModel, fn ($query) => $query->where('province_id', $provinceModel->id)),
            $city
        );
        $districtModel = $this->findByName(
            District::query()
                ->when($cityModel, fn ($query) => $query->where('city_id', $cityModel->id))
                ->when(! $cityModel && $provinceModel, fn ($query) => $query->whereIn(
                    'city_id',
                    City::query()->where('province_id', $provinceModel->id)->select('id')
                )),
            $district
        );
        $cityModel ??= $districtModel ? City::find($districtModel->city_id) : null;
        $villageModel = $this->findByName(
            Village::query()->when($districtModel, fn ($query) => $query->where('district_id', $districtModel->id)),
            $village
        );

        return [
            'province_id' => $provinceModel?->id,
            'city_id' => $cityModel?->id,
            'district_id' => $districtModel?->id,
            'village_id' => $villageModel?->id,
        ];
    }

    private function findByName($query, ?string $name): ?object
    {
        if (blank($name)) {
            return null;
        }

        $normalized = Str::of($name)->lower()->replace(['kabupaten ', 'kota ', 'kab. '], '')->squish()->toString();

        $original = Str::of($name)->lower()->squish()->toString();

        return $query
            ->where(fn ($inner) => $inner
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->orWhereRaw('LOWER(name) = ?', [$original]))
            ->first();
    }
}
