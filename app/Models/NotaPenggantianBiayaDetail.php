<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaPenggantianBiayaDetail extends Model
{
    protected $fillable = ['nota_penggantian_biaya_id', 'keterangan', 'jumlah'];

    protected $casts = ['jumlah' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saved(fn (self $detail) => $detail->notaPenggantianBiaya?->recalculateTotal());
        static::deleted(fn (self $detail) => $detail->notaPenggantianBiaya?->recalculateTotal());
    }

    public function notaPenggantianBiaya(): BelongsTo
    {
        return $this->belongsTo(NotaPenggantianBiaya::class);
    }
}
