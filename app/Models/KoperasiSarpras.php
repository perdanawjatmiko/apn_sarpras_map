<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['koperasi_id', 'sarpras_id', 'status_id'])]
class KoperasiSarpras extends Model
{
    protected $table = 'koperasi_sarpras';

    public function koperasi(): BelongsTo
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function sarpras(): BelongsTo
    {
        return $this->belongsTo(Sarpras::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
