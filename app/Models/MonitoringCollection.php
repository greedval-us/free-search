<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringCollection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['warnings' => 'array', 'cursor_history' => 'array', 'start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime', 'lease_until' => 'immutable_datetime', 'next_attempt_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(MonitoringSource::class, 'source_id');
    }
}
