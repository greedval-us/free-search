<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureUsageDaily extends Model
{
    protected $table = 'feature_usage_daily';

    protected $fillable = [
        'user_id',
        'feature',
        'usage_date',
        'used',
    ];

    protected function casts(): array
    {
        return [
            'usage_date' => 'date',
            'used' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
