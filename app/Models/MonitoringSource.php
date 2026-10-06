<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringSource extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['configuration', 'lease_token'];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'warnings' => 'array', 'generation' => 'integer', 'collect_from' => 'immutable_datetime', 'last_collected_at' => 'immutable_datetime', 'next_collect_at' => 'immutable_datetime', 'coverage_start' => 'immutable_datetime', 'coverage_end' => 'immutable_datetime', 'lease_until' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(MonitoringProject::class, 'project_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(MonitoringCollection::class, 'source_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MonitoringMaterial::class, 'source_id');
    }
}
