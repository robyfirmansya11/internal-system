<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static bool $isLazy = false;

    // canView() dihapus — sekarang semua role (User, Superuser, Admin,
    // Superadmin) bisa mengakses Dashboard. Default Filament sudah
    // mengizinkan semua authenticated user, jadi tidak perlu di-override.

    // getHeaderWidgets() dihapus — dengan begini halaman ini otomatis
    // memakai daftar widget dari AdminPanelProvider->widgets([...]),
    // bukan lagi hardcode ke UserStats::class saja.
}
