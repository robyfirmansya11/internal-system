<?php

namespace App\Models;

use App\Enums\Role;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ⬅️ INI WAJIB
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'jabatan',
        'tanggal_masuk',
    ];

    // protected $with = [
    //     'departments',
    //     'profile',
    //     'finance',
    //     'document',
    // ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => Role::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tanggal_masuk' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    // Jika user bisa banyak department (Superuser)
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'department_users',
            'user_id',
            'department_id'
        );
    }

    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function finance(): HasOne
    {
        return $this->hasOne(EmployeeFinance::class);
    }

    public function document(): HasOne
    {
        return $this->hasOne(EmployeeDocument::class);
    }

    public function lemburs(): HasMany
    {
        return $this->hasMany(Lembur::class);
    }

    public function formCutis(): HasMany
    {
        return $this->hasMany(FormCuti::class);
    }

    public function kasbons(): HasMany
    {
        return $this->hasMany(Kasbon::class);
    }

    public function keterlambatans(): HasMany
    {
        return $this->hasMany(Keterlambatan::class);
    }

    public function perjalananDinas(): HasMany
    {
        return $this->hasMany(PerjalananDinas::class);
    }

    public function suratPerintahBayars(): HasMany
    {
        return $this->hasMany(SuratPerintahBayar::class);
    }

    public function permohonanStempels(): HasMany
    {
        return $this->hasMany(PermohonanStempel::class);
    }

    public function registerSurats(): HasMany
    {
        return $this->hasMany(RegisterSurat::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isSuperadmin(): bool
    {
        return $this->level === Role::Superadmin;
    }

    public function isSuperuser(): bool
    {
        return $this->level === Role::Superuser;
    }

    public function isAdmin(): bool
    {
        return $this->level === Role::Admin;
    }

    public function isUser(): bool
    {
        return $this->level === Role::User;
    }

    public function isHRD(): bool
    {
        return $this->level === Role::Admin
            && $this->jabatan === 'HRD';
    }

    public function isFinanceManager(): bool
    {
        return $this->jabatan === 'Finance Manager';
    }

    /**
     * Cek apakah user ini adalah atasan langsung dari $subordinate.
     */
    public function isAtasanOf(User $subordinate): bool
    {
        return $subordinate->profile?->atasan_id === $this->id;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->level?->canAccessPanel() ?? false;
    }

    public function canApprove(): bool
    {
        return $this->level?->canApprove() ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Shortcut ke atasan langsung via employee_profiles.atasan_id
     * Usage: $user->atasan
     */
    public function getAtasanAttribute(): ?User
    {
        return $this->profile?->atasan;
    }

    /**
     * Nama department utama user.
     * Usage: $user->department
     */
    public function getDepartmentAttribute(): ?string
    {
        return $this->departments
            ->first(fn ($d) => $d->pivot->is_primary)
            ?->nama_department
            ?? $this->departments->first()?->nama_department;
    }

    /**
     * Department utama (object).
     * Usage: $user->primaryDepartment
     */
    public function getPrimaryDepartmentAttribute(): ?Department
    {
        return $this->departments
            ->first(fn ($d) => $d->pivot->is_primary)
            ?? $this->departments->first();
    }

    public function hasDepartment($departmentId): bool
    {
        return $this->departments->pluck('id')->contains($departmentId);
    }
}
