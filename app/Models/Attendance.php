<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'clock_in', 'clock_in_lat', 'clock_in_lng',
        'clock_in_photo', 'clock_in_address',
        'clock_out', 'clock_out_lat', 'clock_out_lng',
        'clock_out_photo', 'clock_out_address',
        'status', 'note',
        'clock_in_reason', 'clock_out_reason',
        'clock_in_location_reason', 'clock_out_location_reason',
        'is_outside_radius',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'date' => 'date',
        'is_outside_radius' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasClockedIn(): bool
    {
        return ! is_null($this->clock_in);
    }

    public function hasClockedOut(): bool
    {
        return ! is_null($this->clock_out);
    }
}
