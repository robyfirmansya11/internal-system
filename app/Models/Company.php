<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'nama',
        'kode',
        'npwp',
        'alamat',
    ];

    public function employees()
{
    return $this->hasMany(EmployeeProfile::class);
}
}
