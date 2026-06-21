<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    protected $fillable = [
        'user_id',
        'ktp_file',
        'kk_file',
        'cv_file',
        'ijazah_file',
        'kontrak_file',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
