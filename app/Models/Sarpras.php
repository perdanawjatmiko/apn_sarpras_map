<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'slug', 'description', 'is_mandatory', 'mandatory_group'])]
class Sarpras extends Model
{
    protected $table = 'sarprases';

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Sarpras $sarpras) {
            if (blank($sarpras->slug)) {
                $sarpras->slug = Str::slug($sarpras->name);
            }
        });
    }

    public function koperasis(): BelongsToMany
    {
        return $this->belongsToMany(Koperasi::class, 'koperasi_sarpras')
            ->withPivot(['status_id'])
            ->withTimestamps();
    }
}
