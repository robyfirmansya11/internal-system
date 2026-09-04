<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerjalananDinasDetail extends Model
{
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
        /**
         * Auto-hitung subtotal setiap kali detail disimpan.
         * Formula: transportasi + (tunjangan × hari) + (hotel × lama) + misc + other
         */
        static::saving(function ($model) {
            $model->subtotal =
                ($model->amount_transportasi ?? 0) +
                (($model->amount_tunjangan ?? 0) * ($model->jumlah_hari ?? 0)) +
                (($model->amount_hotel ?? 0) * ($model->lama_hotel ?? 0)) +
                ($model->misc ?? 0) +
                ($model->amount_other ?? 0);
        });
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
