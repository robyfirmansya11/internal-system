<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RegisterSurat extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'company_id',
        'no_surat',
        'tanggal_surat',
        'ditujukan',
        'lampiran_surat',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    // RELATIONS
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }


}
