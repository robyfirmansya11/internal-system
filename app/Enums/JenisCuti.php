<?php

namespace App\Enums;

enum JenisCuti: string
{
    case Tahunan = 'Cuti Tahunan';
    case Sakit = 'Cuti Sakit';
    case Melahirkan = 'Cuti Melahirkan';
    case Keguguran = 'Cuti Keguguran';
    case Khusus = 'Cuti Khusus';
    case Besar = 'Cuti Besar';
    case Haid = 'Cuti Haid';
}
