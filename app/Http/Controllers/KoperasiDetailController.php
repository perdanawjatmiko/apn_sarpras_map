<?php

namespace App\Http\Controllers;

use App\Models\Koperasi;
use Inertia\Inertia;
use Inertia\Response;

class KoperasiDetailController extends Controller
{
    public function __invoke(Koperasi $koperasi): Response
    {
        $koperasi->load([
            'province:id,name',
            'city:id,name',
            'district:id,name',
            'village:id,name',
            'sarprasAssignments.sarpras:id,name,description',
            'sarprasAssignments.status:id,name',
        ]);

        return Inertia::render('koperasis/show', [
            'koperasi' => [
                'id' => $koperasi->id,
                'name' => $koperasi->name,
                'latitude' => $koperasi->latitude,
                'longitude' => $koperasi->longitude,
                'delivery_percentage' => $koperasi->delivery_percentage,
                'installed_percentage' => $koperasi->installed_percentage,
                'core_percentage' => $koperasi->core_percentage,
                'province' => $koperasi->province?->name,
                'city' => $koperasi->city?->name,
                'district' => $koperasi->district?->name,
                'village' => $koperasi->village?->name,
                'sarprases' => $koperasi->sarprasAssignments->map(fn ($assignment) => [
                    'id' => $assignment->sarpras?->id,
                    'name' => $assignment->sarpras?->name,
                    'description' => $assignment->sarpras?->description,
                    'status' => $assignment->status?->name,
                ]),
            ],
        ]);
    }
}
