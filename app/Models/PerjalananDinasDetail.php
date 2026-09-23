<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjalananDinasDetail extends Model
{
    protected $table = 'perjalanan_dinas_details';

    protected $fillable = [
        'perjalanan_dinas_id',
        'tanggal_berangkat',
        'waktu_berangkat',
        'tempat_berangkat',
        'tanggal_tujuan',
        'waktu_tujuan',
        'tempat_tujuan',
        'jumlah_hari',
        'amount_transportasi',
        'amount_tunjangan',
        'lama_hotel',
        'amount_hotel',
        'misc',
        'amount_other',
        'subtotal',
    ];

    protected $casts = [
        'tanggal_berangkat' => 'date',
        'tanggal_tujuan' => 'date',
        'amount_transportasi' => 'decimal:2',
        'amount_tunjangan' => 'decimal:2',
        'amount_hotel' => 'decimal:2',
        'amount_other' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->subtotal =
                ((float) ($model->amount_transportasi ?? 0))
                + (
                    (float) ($model->amount_tunjangan ?? 0)
                    * (float) ($model->jumlah_hari ?? 0)
                )
                + (
                    (float) ($model->amount_hotel ?? 0)
                    * (float) ($model->lama_hotel ?? 0)
                )
                + (float) ($model->misc ?? 0)
               + (float) ($model->amount_other ?? 0);
        });

        // Keep the header total accurate even when details are created,
        // edited, or removed outside the Filament repeater.
        static::saved(fn (self $detail) => $detail->perjalananDinas?->recalculateTotal());
        static::deleted(fn (self $detail) => $detail->perjalananDinas?->recalculateTotal());
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /** Header perjalanan dinas */
    public function perjalananDinas(): BelongsTo
    {
        return $this->belongsTo(
            PerjalananDinas::class,
            'perjalanan_dinas_id',
            'id'
        );
    }
}
