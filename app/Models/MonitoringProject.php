<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringProject extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['filters' => 'array', 'generation' => 'integer', 'collection_enabled' => 'boolean', 'delivery_enabled' => 'boolean', 'attach_files' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(MonitoringSource::class, 'project_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MonitoringSchedule::class, 'project_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(MonitoringReport::class, 'project_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MonitoringMaterial::class, 'project_id');
    }
}
