<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Status extends Model
{
    public const NAMES = ['terpasang', 'tiba', 'pengiriman', 'transit', 'tanpa_status'];

    public function koperasiSarprases(): HasMany
    {
        return $this->hasMany(KoperasiSarpras::class);
    }
}
