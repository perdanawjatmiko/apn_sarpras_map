<?php

namespace App\Console\Commands;

use App\Services\KoperasiSarprasService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImportKoperasiSarprasSpreadsheet extends Command
{
    private const DEFAULT_URL = 'https://docs.google.com/spreadsheets/d/1FQRvzqAaCjlKXKRyXL2-_yL1gEta6cBr/gviz/tq?tqx=out:csv&sheet=template_seed_sarpras_koperasi';

    protected $signature = 'sarpras-koperasi:import-spreadsheet
        {--url= : URL CSV spreadsheet yang akan diimport}
        {--path= : Path file CSV/XLSX lokal untuk testing manual}';

    protected $description = 'Import data sarpras koperasi dari spreadsheet CSV Google Sheets atau file lokal.';

    public function handle(KoperasiSarprasService $imports): int
    {
        $path = $this->option('path');
        $temporaryPath = null;

        if (! $path) {
            $url = $this->option('url') ?: self::DEFAULT_URL;
            $temporaryPath = tempnam(sys_get_temp_dir(), 'sarpras-koperasi-').'.csv';

            $response = Http::timeout(60)
                ->retry(3, 1000)
                ->get($url)
                ->throw();

            file_put_contents($temporaryPath, $response->body());
            $path = $temporaryPath;
        }

        $changed = $imports->import($path);
        $message = "Berhasil import data sarpras koperasi {$changed} data berubah";

        Log::info($message, [
            'source' => $this->option('path') ? 'local' : 'url',
            'path' => $this->option('path'),
            'url' => $this->option('url') ?: self::DEFAULT_URL,
        ]);

        $this->info($message);

        if ($temporaryPath && file_exists($temporaryPath)) {
            unlink($temporaryPath);
        }

        return self::SUCCESS;
    }
}
