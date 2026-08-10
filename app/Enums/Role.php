<?php

namespace App\Enums;

enum Role: string
{
    case Superuser = 'Superuser';
    case Admin = 'Admin';
    case Superadmin = 'Superadmin';
    case User = 'User';

    public function isAdmin(): bool
    {
        return in_array($this, [
            self::Admin,
            self::Superadmin,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'IT / Super Admin',
            self::Superuser => 'Atasan / Manager',
            self::Admin => 'HRD / Finance',
            self::User => 'Karyawan',
        };
    }

    public function canAccessPanel(): bool
    {
        return in_array($this, [
            self::Superadmin,
            self::Superuser,
            self::Admin,
            self::User,
        ]);
    }

    public function canApprove(): bool
    {
        return in_array($this, [
            self::Superuser,
            self::Admin,
        ]);
    }

    public function isIT(): bool
    {
        return $this === self::Superadmin;
    }

    public function isAtasan(): bool
    {
        return $this === self::Superuser;
    }

    public function isHRDOrFinance(): bool
    {
        return $this === self::Admin;
    }
}
