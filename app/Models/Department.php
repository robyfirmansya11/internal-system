<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Department extends Model
{
    use HasFactory;

protected $fillable = ['nama_department'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_users');
    }

    public function getNameAttribute(): string
{
    return $this->nama_department;
}

public function departments(): BelongsToMany
{
    return $this->belongsToMany(Department::class)
        ->withPivot(['jabatan', 'is_primary'])
        ->withTimestamps();
}

public function getDepartmentAttribute(): ?string
{
    return $this->departments->firstWhere('pivot.is_primary', true)?->nama_department;
}

public function getJabatanAttribute(): ?string
{
    return $this->departments->firstWhere('pivot.is_primary', true)?->pivot->jabatan;
}

}
