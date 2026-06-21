<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'nik',
        'employee_id',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'alamat',
        'no_hp',
        'status_pernikahan',
        'agama',
        'kewarganegaraan',
        'status_karyawan',
        'tanggal_masuk',
        'tanggal_keluar',
        'lokasi_kerja',
        'atasan_id',
        'foto',
        'barcode_signature',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Atasan langsung user ini.
     * Usage: $profile->atasan  atau  $user->profile->atasan
     *        Shortcut: $user->atasan  (via accessor di User)
     */
    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_id');
    }

    /**
     * Semua bawahan langsung atasan ini.
     * Usage: $atasanProfile->subordinates
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(
            EmployeeProfile::class,
            'atasan_id',
            'user_id'
        );
    }
}
