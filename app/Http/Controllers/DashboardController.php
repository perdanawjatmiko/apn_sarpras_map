<?php

namespace App\Http\Controllers;

use App\Models\Koperasi;
use App\Models\KoperasiSarpras;
use App\Models\Sarpras;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $statusCounts = KoperasiSarpras::query()
            ->join('statuses', 'statuses.id', '=', 'koperasi_sarpras.status_id')
            ->selectRaw('statuses.name, count(*) as total')
            ->groupBy('statuses.name')
            ->pluck('total', 'name');

        $chart = collect(['terpasang', 'tiba', 'pengiriman', 'transit'])
            ->map(fn (string $status) => [
                'label' => str($status)->title()->toString(),
                'value' => (int) ($statusCounts[$status] ?? 0),
            ])
            ->values();

        return Inertia::render('dashboard', [
            'stats' => [
                'koperasis' => Koperasi::query()->count(),
                'installed_sarprases' => (int) ($statusCounts['terpasang'] ?? 0),
                'shipping_sarprases' => (int) ($statusCounts['pengiriman'] ?? 0),
                'completion_percentage' => 76,
            ],
            'chart' => $chart,
            'total_sarprases' => Sarpras::query()->count(),
        ]);
    }
}
