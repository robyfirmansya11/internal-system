<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class KuotaCuti extends Model
{
    protected $table = 'kuota_cuti';

    protected $fillable = [
        'user_id',
        'tahun',
        'kuota_tahunan',
        'cuti_terpakai',
        'sisa_cuti',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // kode expire otomatis setiap 31 Januari di tahun berikutnya.

    public static function autoExpire(): void
    {
        $today = Carbon::today();

        $kuotas = static::where('sisa_cuti', '>', 0)->get();

        foreach ($kuotas as $kuota) {

            $expireDate = Carbon::create(
                $kuota->tahun + 1,
                1,
                31
            )->endOfDay();

            if ($today->greaterThan($expireDate)) {
                $kuota->update([
                    'sisa_cuti' => 0,
                ]);
            }
        }
    }

    // Tambahkan safety check saat mengurangi sisa cuti, pastikan tidak menjadi negatif.
    protected static function booted()
    {
        static::saving(function ($model) {

            $model->cuti_terpakai = max(
                0,
                $model->cuti_terpakai
            );

            $model->sisa_cuti = max(
                0,
                $model->kuota_tahunan - $model->cuti_terpakai
            );

        });
    }
}
