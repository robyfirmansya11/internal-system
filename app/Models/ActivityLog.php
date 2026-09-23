<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'event', 'changes', 'ip_address', 'user_agent'];
    protected $casts = ['changes' => 'array'];
    public function subject(): MorphTo { return $this->morphTo(); }
}
