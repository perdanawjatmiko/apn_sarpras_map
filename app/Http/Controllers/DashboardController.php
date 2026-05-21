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

        $installedBySarpras = Sarpras::query()
            ->leftJoin('koperasi_sarpras', 'koperasi_sarpras.sarpras_id', '=', 'sarprases.id')
            ->leftJoin('statuses', 'statuses.id', '=', 'koperasi_sarpras.status_id')
            ->selectRaw("sarprases.id, sarprases.name, SUM(CASE WHEN statuses.name = 'terpasang' THEN 1 ELSE 0 END) as installed_total")
            ->groupBy('sarprases.id', 'sarprases.name')
            ->orderByDesc('installed_total')
            ->orderBy('sarprases.name')
            ->get()
            ->map(fn (Sarpras $sarpras) => [
                'id' => $sarpras->id,
                'name' => $sarpras->name,
                'installed_total' => (int) $sarpras->installed_total,
            ]);

        return Inertia::render('dashboard', [
            'stats' => [
                'koperasis' => $totalKoperasis,
                'installed_sarprases' => (int) ($statusCounts['terpasang'] ?? 0),
                'shipping_sarprases' => (int) ($statusCounts['pengiriman'] ?? 0),
                'completion_percentage' => $completionPercentage,
            ],
            'chart' => $chart,
            'installed_by_sarpras' => $installedBySarpras,
            'total_sarprases' => $totalSarprases,
        ]);
    }
}
