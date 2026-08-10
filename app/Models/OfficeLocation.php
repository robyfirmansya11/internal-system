<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeLocation extends Model
{
    protected $fillable = [
        'name', 'latitude', 'longitude', 'radius', 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    /**
     * Hitung jarak antara koordinat user dan kantor (dalam meter).
     * Menggunakan formula Haversine.
     */
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000;

        $latDelta = deg2rad($lat - $this->latitude);
        $lngDelta = deg2rad($lng - $this->longitude);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($this->latitude))
            * cos(deg2rad($lat))
            * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Cek apakah user berada dalam radius kantor.
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        return $this->distanceFrom($lat, $lng) <= $this->radius;
    }
}
