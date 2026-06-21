<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeFinance extends Model
{
    protected $fillable = [
        'user_id',
        'gaji_pokok',
        'tunjangan',
        'bank_name',
        'no_rekening',
        'npwp',
        'bpjs_kesehatan',
        'bpjs_ketenagakerjaan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
