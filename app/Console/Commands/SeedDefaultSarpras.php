<?php

namespace App\Console\Commands;

use App\Models\Sarpras;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDefaultSarpras extends Command
{
    protected $signature = 'sarpras:seed-default';

    protected $description = 'Insert default sarpras records used by the koperasi datasheet.';

    public function handle(): int
    {
        $items = [
            'Gerai Rak',
            'Motor Bak Roda 3',
            'Truk',
            'AC',
            'APAR',
            'CCTV',
            'Internet',
            'Software Kasir',
            'Kasir',
            'Mebel',
            'Pickup 4x4',
            'Printer',
            'Keranjang Belanja',
            'Seragam KDKMP',
            'Pallet',
            'Brankas',
        ];

        foreach ($items as $item) {
            Sarpras::updateOrCreate(
                ['slug' => Str::slug($item)],
                ['name' => $item]
            );
        }

        $this->info(count($items).' sarpras default tersimpan.');

        return self::SUCCESS;
    }
}
