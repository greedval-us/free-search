<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringMaterial extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'immutable_datetime', 'collected_at' => 'immutable_datetime', 'metrics' => 'array'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(MonitoringSource::class, 'source_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MonitoringProject::class, 'project_id');
    }
}
