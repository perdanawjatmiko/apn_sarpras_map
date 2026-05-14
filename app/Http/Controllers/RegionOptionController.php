<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\District;
use App\Models\Koperasi;
use App\Models\Province;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionOptionController extends Controller
{
    public function provinces(): JsonResponse
    {
        return response()->json(
            Province::query()
                ->whereIn('id', Koperasi::query()->located()->select('province_id')->whereNotNull('province_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
        ]);

        return response()->json(
            City::query()
                ->where('province_id', $data['province_id'])
                ->whereIn('id', Koperasi::query()
                    ->located()
                    ->select('city_id')
                    ->where('province_id', $data['province_id'])
                    ->whereNotNull('city_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function districts(Request $request): JsonResponse
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        return response()->json(
            District::query()
                ->where('city_id', $data['city_id'])
                ->whereIn('id', Koperasi::query()
                    ->located()
                    ->select('district_id')
                    ->where('city_id', $data['city_id'])
                    ->whereNotNull('district_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }

    public function villages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district_id' => ['required', 'exists:districts,id'],
        ]);

        return response()->json(
            Village::query()
                ->where('district_id', $data['district_id'])
                ->whereIn('id', Koperasi::query()
                    ->located()
                    ->select('village_id')
                    ->where('district_id', $data['district_id'])
                    ->whereNotNull('village_id')
                    ->distinct()
                )
                ->orderBy('name')
                ->get(['id', 'name'])
                ->values()
                ->all()
        );
    }
}
