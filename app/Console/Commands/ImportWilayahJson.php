<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportWilayahJson extends Command
{
    protected $signature = 'wilayah:import-json {--truncate} {--with-locations}';

    protected $description = 'Import wilayah dari JSON ke database lokal';

    public function handle()
    {
        $this->info('Mulai import wilayah...');

        $filePath = $this->option('with-locations')
            ? database_path('data/wilayah_with_lat.json')
            : database_path('data/wilayah.json');

        if (! file_exists($filePath)) {
            $this->error("File JSON tidak ditemukan: {$filePath}");
            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON tidak valid: ' . json_last_error_msg());
            return self::FAILURE;
        }

        foreach (['provinsi', 'kabupaten', 'kecamatan', 'desa'] as $key) {
            if (! isset($data[$key]) || ! is_array($data[$key])) {
                $this->error("Struktur data `{$key}` tidak ditemukan atau tidak valid.");
                return self::FAILURE;
            }
        }

        try {
            DB::transaction(function () use ($data) {
                if ($this->option('truncate')) {
                    DB::statement('TRUNCATE TABLE villages, districts, cities, provinces RESTART IDENTITY CASCADE');
                }

                $now = now();
                $provinsiMap = [];
                $kabupatenMap = [];
                $kecamatanMap = [];

                foreach ($data['provinsi'] as $provinsi) {
                    $provinsiId = DB::table('provinces')->insertGetId([
                        'code' => $provinsi['kode_provinsi'],
                        'name' => $provinsi['nama_provinsi'],
                        'lat' => $provinsi['lat'],
                        'long' => $provinsi['long'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $provinsiMap[$provinsi['kode_provinsi']] = $provinsiId;
                }

                foreach ($data['kabupaten'] as $kabupaten) {
                    $provinsiId = $provinsiMap[$kabupaten['id_provinsi']] ?? null;

                    if (! $provinsiId) {
                        throw new \RuntimeException(
                            "Relasi provinsi tidak ditemukan untuk kabupaten {$kabupaten['kode_lengkap']}"
                        );
                    }

                    $kabupatenId = DB::table('cities')->insertGetId([
                        'code' => $kabupaten['kode_kabupaten'],
                        'full_code' => $kabupaten['kode_lengkap'],
                        'name' => $kabupaten['nama_kabupaten'],
                        'province_id' => $provinsiId,
                        'lat' => $provinsi['lat'],
                        'long' => $provinsi['long'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $kabupatenMap[$kabupaten['kode_lengkap']] = $kabupatenId;
                }

                foreach ($data['kecamatan'] as $kecamatan) {
                    $parentKabupatenCode = $this->extractParentCode($kecamatan['kode_lengkap'], 2);
                    $kabupatenId = $kabupatenMap[$parentKabupatenCode] ?? null;

                    if (! $kabupatenId) {
                        throw new \RuntimeException(
                            "Relasi kabupaten tidak ditemukan untuk kecamatan {$kecamatan['kode_lengkap']}"
                        );
                    }

                    $kecamatanId = DB::table('districts')->insertGetId([
                        'code' => $kecamatan['kode_kecamatan'],
                        'full_code' => $kecamatan['kode_lengkap'],
                        'name' => $kecamatan['nama_kecamatan'],
                        'city_id' => $kabupatenId,
                        'lat' => $provinsi['lat'],
                        'long' => $provinsi['long'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $kecamatanMap[$kecamatan['kode_lengkap']] = $kecamatanId;
                }

                foreach ($data['desa'] as $desa) {
                    $parentKecamatanCode = $this->extractParentCode($desa['kode_lengkap'], 3);
                    $kecamatanId = $kecamatanMap[$parentKecamatanCode] ?? null;

                    if (! $kecamatanId) {
                        throw new \RuntimeException(
                            "Relasi kecamatan tidak ditemukan untuk desa {$desa['kode_lengkap']}"
                        );
                    }

                    DB::table('villages')->insert([
                        'code' => $desa['kode_desa'],
                        'full_code' => $desa['kode_lengkap'],
                        'name' => $desa['nama_desa'],
                        'district_id' => $kecamatanId,
                        'lat' => $provinsi['lat'],
                        'long' => $provinsi['long'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Import wilayah selesai.');

        return self::SUCCESS;
    }

    private function extractParentCode(string $kodeLengkap, int $segments): string
    {
        $parts = explode('.', $kodeLengkap);

        if (count($parts) < $segments) {
            throw new \RuntimeException("Kode lengkap tidak valid: {$kodeLengkap}");
        }

        return implode('.', array_slice($parts, 0, $segments));
    }
}