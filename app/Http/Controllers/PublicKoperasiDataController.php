<?php

namespace App\Http\Controllers;

use App\Services\PublicKoperasiDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicKoperasiDataController extends Controller
{
    public function __construct(private readonly PublicKoperasiDataService $koperasiData) {}

    public function statistics(Request $request): JsonResponse
    {
        return $this->json($this->koperasiData->statistics($request->has('flush')));
    }

    public function locations(Request $request): JsonResponse
    {
        return $this->json($this->koperasiData->locations($request->has('flush')));
    }

    private function json(array $data): JsonResponse
    {
        return response()
            ->json($data)
            ->withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=3600',
            ]);
    }
}
