<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ai_id',
    'name',
    'village_id',
    'district_id',
    'city_id',
    'province_id',
    'latitude',
    'longitude',
    'delivery_percentage',
    'installed_percentage',
    'core_percentage',
])]
class Koperasi extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'delivery_percentage' => 'decimal:4',
            'installed_percentage' => 'decimal:4',
            'core_percentage' => 'decimal:4',
        ];
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function sarprases(): BelongsToMany
    {
        return $this->belongsToMany(Sarpras::class, 'koperasi_sarpras')
            ->withPivot(['status_id'])
            ->withTimestamps();
    }

    public function sarprasAssignments(): HasMany
    {
        return $this->hasMany(KoperasiSarpras::class);
    }

    public function scopeLocated(Builder $query): Builder
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }
}
