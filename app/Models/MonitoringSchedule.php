<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringSchedule extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'delivery_enabled' => 'boolean', 'generation' => 'integer', 'anchor_date' => 'immutable_date', 'next_run_at' => 'immutable_datetime', 'last_run_at' => 'immutable_datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MonitoringProject::class, 'project_id');
    }
}
