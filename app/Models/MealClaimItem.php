<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealClaimItem extends Model
{
    protected $fillable = [
        'meal_claim_id', 'meal_date', 'meal_type', 'merchant', 'amount', 'note', 'receipt_path',
        'ocr_status', 'ocr_confidence', 'ocr_raw_text',
    ];

    protected $casts = [
        'meal_date' => 'date',
        'amount' => 'decimal:2',
        'ocr_confidence' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saved(fn (self $item) => $item->mealClaim?->recalculateTotals());
        static::deleted(fn (self $item) => $item->mealClaim?->recalculateTotals());
    }

    public function mealClaim(): BelongsTo
    {
        return $this->belongsTo(MealClaim::class);
    }
}
