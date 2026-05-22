<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'ticket_id',
    'ticket_date',
    'reporter_user_id',
    'reporter_name',
    'reporter_phone',
    'koperasi_id',
    'province_id',
    'city_id',
    'category_id',
    'sub_category_id',
    'priority',
    'title',
    'detail',
    'pic_helpdesk_id',
    'status',
    'assigned_at',
    'completed_at',
    'sla_days',
    'progress_percentage',
    'resolution',
    'attachment_path',
    'attachment_link',
    'last_update_at',
])]
class Pengaduan extends Model
{
    use SoftDeletes;

    public const PRIORITIES = ['rendah', 'normal', 'tinggi', 'urgent'];

    public const STATUSES = ['baru', 'ditugaskan', 'diproses', 'selesai', 'ditutup'];

    protected function casts(): array
    {
        return [
            'ticket_date' => 'datetime',
            'assigned_at' => 'datetime',
            'completed_at' => 'datetime',
            'last_update_at' => 'datetime',
            'progress_percentage' => 'integer',
            'sla_days' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Pengaduan $pengaduan) {
            $pengaduan->ticket_id ??= self::nextTicketId();
            $pengaduan->ticket_date ??= now();
            $pengaduan->last_update_at ??= now();
        });

        static::saving(function (Pengaduan $pengaduan) {
            $pengaduan->last_update_at = now();
        });
    }

    public static function nextTicketId(): string
    {
        $next = (self::query()->max('id') ?? 0) + 1;

        return 'TKT-'.now()->format('Ymd').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function koperasi(): BelongsTo
    {
        return $this->belongsTo(Koperasi::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PengaduanCategory::class, 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(PengaduanCategory::class, 'sub_category_id');
    }

    public function picHelpdesk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_helpdesk_id');
    }

    public function attachmentUrl(): ?string
    {
        if (filled($this->attachment_link)) {
            return $this->attachment_link;
        }

        return filled($this->attachment_path) ? Storage::disk('public')->url($this->attachment_path) : null;
    }
}
