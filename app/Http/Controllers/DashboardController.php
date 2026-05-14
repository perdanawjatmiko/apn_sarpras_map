<?php

namespace App\Http\Controllers;

use App\Models\Koperasi;
use App\Models\KoperasiSarpras;
use App\Models\Sarpras;
use App\Models\Status;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $totalKoperasis = Koperasi::query()->count();
        $totalSarprases = Sarpras::query()->count();
        $statusCounts = KoperasiSarpras::query()
            ->join('statuses', 'statuses.id', '=', 'koperasi_sarpras.status_id')
            ->selectRaw('statuses.name, count(*) as total')
            ->groupBy('statuses.name')
            ->pluck('total', 'name');

        $completeKoperasis = $totalSarprases > 0
            ? KoperasiSarpras::query()
                ->join('statuses', 'statuses.id', '=', 'koperasi_sarpras.status_id')
                ->where('statuses.name', 'terpasang')
                ->select('koperasi_sarpras.koperasi_id')
                ->groupBy('koperasi_sarpras.koperasi_id')
                ->havingRaw('COUNT(DISTINCT koperasi_sarpras.sarpras_id) >= ?', [$totalSarprases])
                ->get()
                ->count()
            : 0;

        $completionPercentage = $totalKoperasis > 0
            ? (int) round(($completeKoperasis / $totalKoperasis) * 100)
            : 0;

        $chart = collect(Status::NAMES)
            ->map(fn (string $status) => [
                'label' => str($status)->replace('_', ' ')->title()->toString(),
                'value' => (int) ($statusCounts[$status] ?? 0),
            ])
            ->values();

        return Inertia::render('dashboard', [
            'stats' => [
                'koperasis' => $totalKoperasis,
                'installed_sarprases' => (int) ($statusCounts['terpasang'] ?? 0),
                'shipping_sarprases' => (int) ($statusCounts['pengiriman'] ?? 0),
                'completion_percentage' => $completionPercentage,
            ],
            'chart' => $chart,
            'total_sarprases' => $totalSarprases,
        ]);
    }
}
